<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'project_id', 'name', 'slug', 'description',
        'status', 'risk_level', 'max_risk_level_without_approval',
        'environment_config', 'current_version_id',
    ];

    protected $casts = [
        'environment_config' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AgentVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(AgentVersion::class, 'current_version_id');
    }
}
