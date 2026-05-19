<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RunReplay extends Model
{
    protected $fillable = [
        'source_run_id', 'replay_run_id', 'replayed_by',
        'pinned_versions', 'status', 'diff', 'reason',
    ];
    protected $casts = [
        'pinned_versions' => 'array',
        'diff' => 'array',
    ];

    public function source(): BelongsTo { return $this->belongsTo(WorkflowRun::class, 'source_run_id'); }
    public function replay(): BelongsTo { return $this->belongsTo(WorkflowRun::class, 'replay_run_id'); }
}
