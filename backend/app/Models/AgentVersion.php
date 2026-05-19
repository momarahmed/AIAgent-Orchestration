<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id', 'version', 'role', 'system_instructions',
        'model_config', 'allowed_mcp_servers', 'allowed_tools',
        'memory_scope', 'metadata', 'created_by',
    ];

    protected $casts = [
        'model_config' => 'array',
        'allowed_mcp_servers' => 'array',
        'allowed_tools' => 'array',
        'metadata' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
