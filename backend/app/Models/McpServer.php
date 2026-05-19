<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class McpServer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'project_id', 'name', 'slug', 'description',
        'transport', 'runtime', 'endpoint', 'auth_method',
        'secret_refs', 'status', 'health', 'last_health_check_at',
        'current_version_id',
    ];

    protected $casts = [
        'secret_refs' => 'array',
        'last_health_check_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tools(): HasMany
    {
        return $this->hasMany(Tool::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(McpServerVersion::class);
    }
}
