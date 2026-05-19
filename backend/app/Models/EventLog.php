<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    protected $table = 'event_log';
    protected $fillable = [
        'topic', 'event_type', 'tenant_id', 'event_id', 'trace_id',
        'payload', 'headers', 'partition_offset', 'status',
        'emitted_at', 'consumed_at',
    ];
    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'emitted_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
