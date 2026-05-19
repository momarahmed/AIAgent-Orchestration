<?php

namespace App\Services;

use App\Models\AbacPolicy;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Full RBAC/ABAC engine (Phase 3).
 *
 * Layered evaluation:
 *  1. Check user's tenant-level role permissions
 *  2. Check user's project-level role override (if any)
 *  3. Evaluate ABAC policies for attribute-based conditions
 *  4. Deny-overrides: any explicit deny wins
 */
class RbacService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * @param array $context Extra attributes (ip, env, resource_owner, etc.)
     */
    public function can(User $user, string $permission, int $tenantId, ?int $projectId = null, array $context = []): bool
    {
        if (empty($permission)) {
            return true;
        }

        // Platform Owner / Admin bypass
        $tenantRole = $user->roleForTenant($tenantId);
        if ($tenantRole && in_array($tenantRole->name, ['admin', 'platform_owner'], true)) {
            return true;
        }

        // Check role-based permissions
        if ($this->checkRolePermission($user, $permission, $tenantId, $projectId)) {
            // Even if role allows, ABAC deny can override
            if ($this->checkAbacDeny($user, $permission, $tenantId, $projectId, $context)) {
                return false;
            }
            return true;
        }

        // Check ABAC allow policies
        if ($this->checkAbacAllow($user, $permission, $tenantId, $projectId, $context)) {
            return true;
        }

        return false;
    }

    public function userPermissions(User $user, int $tenantId, ?int $projectId = null): array
    {
        $perms = [];

        $tenantRole = $user->roleForTenant($tenantId);
        if ($tenantRole) {
            $perms = array_merge($perms, $tenantRole->permissions ?? []);
            $perms = array_merge($perms, $tenantRole->permissions()->pluck('name')->toArray());
        }

        if ($projectId) {
            $projectRole = $user->roleForProject($projectId);
            if ($projectRole) {
                $perms = array_merge($perms, $projectRole->permissions ?? []);
                $perms = array_merge($perms, $projectRole->permissions()->pluck('name')->toArray());
            }
        }

        return array_unique($perms);
    }

    public function assignRoleToTenant(User $user, int $tenantId, int $roleId): void
    {
        $user->tenants()->syncWithoutDetaching([$tenantId => ['role_id' => $roleId]]);
        $this->clearCache($user->id, $tenantId);
    }

    public function assignRoleToProject(User $user, int $projectId, int $roleId): void
    {
        $user->projects()->syncWithoutDetaching([$projectId => ['role_id' => $roleId]]);
    }

    protected function checkRolePermission(User $user, string $permission, int $tenantId, ?int $projectId): bool
    {
        $cacheKey = "rbac:{$user->id}:{$tenantId}:{$projectId}:{$permission}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $permission, $tenantId, $projectId) {
            // Check project-level role first (more specific)
            if ($projectId) {
                $projectRole = $user->roleForProject($projectId);
                if ($projectRole?->hasPermission($permission)) {
                    return true;
                }
            }

            // Fall back to tenant-level role
            $tenantRole = $user->roleForTenant($tenantId);
            return $tenantRole?->hasPermission($permission) ?? false;
        });
    }

    protected function checkAbacAllow(User $user, string $permission, int $tenantId, ?int $projectId, array $context): bool
    {
        return $this->evaluateAbacPolicies($user, $permission, $tenantId, $projectId, $context, 'allow');
    }

    protected function checkAbacDeny(User $user, string $permission, int $tenantId, ?int $projectId, array $context): bool
    {
        return $this->evaluateAbacPolicies($user, $permission, $tenantId, $projectId, $context, 'deny');
    }

    protected function evaluateAbacPolicies(User $user, string $permission, int $tenantId, ?int $projectId, array $context, string $effect): bool
    {
        [$resourceType, $action] = $this->parsePermission($permission);

        $policies = AbacPolicy::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('effect', $effect)
            ->where(function ($q) use ($resourceType) {
                $q->where('resource_type', $resourceType)->orWhere('resource_type', '*');
            })
            ->where(function ($q) use ($action) {
                $q->where('action', $action)->orWhere('action', '*');
            })
            ->orderBy('priority')
            ->get();

        foreach ($policies as $policy) {
            if ($this->evaluateConditions($policy->conditions, $user, $tenantId, $projectId, $context)) {
                return true;
            }
        }

        return false;
    }

    protected function evaluateConditions(array $conditions, User $user, int $tenantId, ?int $projectId, array $context): bool
    {
        foreach ($conditions as $condition) {
            $field    = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? 'eq';
            $value    = $condition['value'] ?? null;

            $actual = $this->resolveField($field, $user, $tenantId, $projectId, $context);

            $match = match ($operator) {
                'eq'       => $actual == $value,
                'neq'      => $actual != $value,
                'in'       => is_array($value) && in_array($actual, $value, true),
                'not_in'   => is_array($value) && ! in_array($actual, $value, true),
                'contains' => is_string($actual) && str_contains($actual, (string) $value),
                'gt'       => $actual > $value,
                'lt'       => $actual < $value,
                'gte'      => $actual >= $value,
                'lte'      => $actual <= $value,
                default    => false,
            };

            if (! $match) {
                return false;
            }
        }

        return true;
    }

    protected function resolveField(string $field, User $user, int $tenantId, ?int $projectId, array $context): mixed
    {
        return match ($field) {
            'user.id'       => $user->id,
            'user.email'    => $user->email,
            'user.role'     => $user->roleForTenant($tenantId)?->name,
            'tenant.id'     => $tenantId,
            'project.id'    => $projectId,
            'context.ip'    => $context['ip'] ?? null,
            'context.method'=> $context['method'] ?? null,
            default         => $context[$field] ?? null,
        };
    }

    protected function parsePermission(string $permission): array
    {
        $parts = explode('.', $permission, 2);
        return [
            $parts[0] ?? '*',
            $parts[1] ?? '*',
        ];
    }

    protected function clearCache(int $userId, int $tenantId): void
    {
        // Clear all cached permission checks for this user+tenant
        Cache::forget("rbac:{$userId}:{$tenantId}:*");
    }
}
