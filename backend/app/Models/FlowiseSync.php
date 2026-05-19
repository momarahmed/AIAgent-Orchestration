<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowiseSync extends Model
{
    use HasFactory;

    protected $fillable = [
        'flowise_agent_id', 'tenant_id',
        'direction', 'sync_status', 'flowise_chatflow_id',
        'payload', 'sync_error', 'last_synced_at',
    ];

    protected $casts = [
        'payload'        => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(FlowiseAgent::class, 'flowise_agent_id');
    }
}
