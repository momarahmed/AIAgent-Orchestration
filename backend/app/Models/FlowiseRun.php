<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowiseRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'flowise_agent_id', 'tenant_id', 'user_id',
        'status', 'input', 'output', 'tool_calls', 'conversation',
        'prompt_tokens', 'completion_tokens', 'total_tokens', 'duration_ms',
        'error_message', 'session_id', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'input'        => 'array',
        'output'       => 'array',
        'tool_calls'   => 'array',
        'conversation' => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(FlowiseAgent::class, 'flowise_agent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
