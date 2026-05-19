<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'label', 'level', 'is_system', 'description', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
        'is_system'   => 'boolean',
        'level'       => 'integer',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        $perms = $this->permissions ?? [];
        if (in_array('*', $perms, true)) {
            return true;
        }
        if (in_array($permission, $perms, true)) {
            return true;
        }
        return $this->permissions()->where('name', $permission)->exists();
    }
}
