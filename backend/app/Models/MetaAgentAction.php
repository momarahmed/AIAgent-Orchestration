<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAgentAction extends Model
{
    protected $fillable = [
        'meta_agent_run_id', 'sequence', 'action', 'subject_type', 'subject_id',
        'input', 'output', 'status', 'approval_required', 'approval_id',
        'reasoning', 'started_at', 'completed_at',
    ];
    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'approval_required' => 'bool',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function run(): BelongsTo { return $this->belongsTo(MetaAgentRun::class, 'meta_agent_run_id'); }
    public function approval(): BelongsTo { return $this->belongsTo(Approval::class); }
}
