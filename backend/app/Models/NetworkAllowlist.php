<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkAllowlist extends Model
{
    protected $fillable = [
        'tenant_id', 'mcp_server_id', 'environment', 'direction',
        'host', 'port', 'protocol', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'port'      => 'integer',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function mcpServer(): BelongsTo
    {
        return $this->belongsTo(McpServer::class);
    }
}
