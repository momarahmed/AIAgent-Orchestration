<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')->withPivot('role_id')->withTimestamps();
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user')->withPivot('role_id')->withTimestamps();
    }

    public function roleForTenant(int $tenantId): ?Role
    {
        $pivot = $this->tenants()->where('tenants.id', $tenantId)->first();
        if (! $pivot?->pivot?->role_id) {
            return null;
        }
        return Role::find($pivot->pivot->role_id);
    }

    public function roleForProject(int $projectId): ?Role
    {
        $pivot = $this->projects()->where('projects.id', $projectId)->first();
        if (! $pivot?->pivot?->role_id) {
            return null;
        }
        return Role::find($pivot->pivot->role_id);
    }

    public function hasPermissionInTenant(string $permission, int $tenantId): bool
    {
        $role = $this->roleForTenant($tenantId);
        return $role?->hasPermission($permission) ?? false;
    }

    /**
     * Convenience accessor used by Phase 5 controllers / SDKs.
     * Resolves the user's active tenant id from the `X-Tenant-Id` header
     * (when present and the user belongs to that tenant) or falls back to
     * the first attached tenant. Returns null only for users with no tenant.
     */
    public function getTenantIdAttribute(): ?int
    {
        $headerId = request()?->header('X-Tenant-Id');
        if ($headerId) {
            $ids = $this->tenants()->pluck('tenants.id')->toArray();
            if (in_array((int) $headerId, $ids, true)) {
                return (int) $headerId;
            }
        }
        return $this->tenants()->value('tenants.id');
    }
}
