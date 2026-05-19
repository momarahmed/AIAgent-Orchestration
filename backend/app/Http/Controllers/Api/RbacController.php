<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AbacPolicy;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RbacService;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RbacController extends Controller
{
    public function __construct(protected RbacService $rbac) {}

    // ─── Roles ───────────────────────────────────────────────────────

    public function roles(): JsonResponse
    {
        return response()->json(Role::with('permissions')->orderBy('level')->get());
    }

    public function createRole(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|unique:roles,name',
            'label'       => 'required|string',
            'level'       => 'integer|min:0|max:100',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(array_merge($data, ['is_system' => false]));

        if (! empty($data['permissions'])) {
            $permIds = Permission::whereIn('name', $data['permissions'])->pluck('id');
            $role->permissions()->sync($permIds);
        }

        Audit::record('create', 'role_created', 'role', $role->id, [
            'name' => $role->name,
        ]);

        return response()->json($role->load('permissions'), 201);
    }

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['error' => 'System roles cannot be modified'], 403);
        }

        $data = $request->validate([
            'label'       => 'string',
            'level'       => 'integer|min:0|max:100',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
        ]);

        $role->update($data);

        if (isset($data['permissions'])) {
            $permIds = Permission::whereIn('name', $data['permissions'])->pluck('id');
            $role->permissions()->sync($permIds);
        }

        return response()->json($role->load('permissions'));
    }

    // ─── Permissions ─────────────────────────────────────────────────

    public function permissions(): JsonResponse
    {
        return response()->json(Permission::orderBy('group')->orderBy('name')->get());
    }

    // ─── User role assignments ───────────────────────────────────────

    public function assignTenantRole(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'   => 'required|exists:users,id',
            'tenant_id' => 'required|exists:tenants,id',
            'role_id'   => 'required|exists:roles,id',
        ]);

        $user = \App\Models\User::findOrFail($data['user_id']);
        $this->rbac->assignRoleToTenant($user, $data['tenant_id'], $data['role_id']);

        Audit::record('update', 'tenant_role_assigned', 'user', $user->id, $data);

        return response()->json(['message' => 'Role assigned']);
    }

    public function assignProjectRole(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'role_id'    => 'required|exists:roles,id',
        ]);

        $user = \App\Models\User::findOrFail($data['user_id']);
        $this->rbac->assignRoleToProject($user, $data['project_id'], $data['role_id']);

        Audit::record('update', 'project_role_assigned', 'user', $user->id, $data);

        return response()->json(['message' => 'Project role assigned']);
    }

    public function userPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = (int) $request->query('tenant_id', $user->tenants()->first()?->id ?? 0);
        $projectId = $request->query('project_id') ? (int) $request->query('project_id') : null;

        return response()->json([
            'permissions' => $this->rbac->userPermissions($user, $tenantId, $projectId),
            'tenant_role' => $user->roleForTenant($tenantId)?->name,
            'project_role'=> $projectId ? $user->roleForProject($projectId)?->name : null,
        ]);
    }

    // ─── ABAC Policies ───────────────────────────────────────────────

    public function abacPolicies(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');
        $query = AbacPolicy::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        return response()->json($query->orderBy('priority')->paginate(50));
    }

    public function createAbacPolicy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'     => 'required|exists:tenants,id',
            'name'          => 'required|string',
            'description'   => 'nullable|string',
            'resource_type' => 'required|string',
            'action'        => 'required|string',
            'conditions'    => 'required|array',
            'effect'        => 'in:allow,deny',
            'priority'      => 'integer|min:0|max:9999',
        ]);

        $policy = AbacPolicy::create(array_merge($data, [
            'created_by' => $request->user()->id,
        ]));

        Audit::record('create', 'abac_policy_created', 'abac_policy', $policy->id, [
            'resource_type' => $data['resource_type'], 'effect' => $data['effect'] ?? 'allow',
        ]);

        return response()->json($policy, 201);
    }

    public function updateAbacPolicy(Request $request, AbacPolicy $abacPolicy): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'string',
            'description'   => 'nullable|string',
            'conditions'    => 'array',
            'effect'        => 'in:allow,deny',
            'priority'      => 'integer',
            'is_active'     => 'boolean',
        ]);

        $abacPolicy->update($data);
        return response()->json($abacPolicy);
    }

    public function deleteAbacPolicy(AbacPolicy $abacPolicy): JsonResponse
    {
        $abacPolicy->delete();
        return response()->json(null, 204);
    }
}
