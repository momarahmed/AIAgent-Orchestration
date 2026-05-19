<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id', 'workflow_version_id', 'status', 'environment', 'attempt',
        'input', 'output', 'error',
        'started_at', 'completed_at', 'paused_at', 'resumed_at', 'checkpoint',
        'triggered_by',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'checkpoint' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
    ];

    public function workflowVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TaskRun::class);
    }
}
