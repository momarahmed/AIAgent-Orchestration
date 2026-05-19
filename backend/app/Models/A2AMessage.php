<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class A2AMessage extends Model
{
    protected $table = 'a2a_messages';
    protected $fillable = [
        'message_id', 'conversation_id', 'tenant_id', 'workflow_run_id',
        'from_agent', 'to_agent', 'from_scope', 'to_scope',
        'message_type', 'priority', 'direction', 'status',
        'payload', 'headers', 'error', 'latency_ms',
    ];
    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
    ];
}
