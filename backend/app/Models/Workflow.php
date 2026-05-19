<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'project_id', 'name', 'slug', 'description',
        'status', 'trigger_type', 'current_version_id',
        'risk_level', 'schedule_config', 'retry_policy',
    ];

    protected $casts = [
        'schedule_config' => 'array',
        'retry_policy' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'current_version_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }
}
