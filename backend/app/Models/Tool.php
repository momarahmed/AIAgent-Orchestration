<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tool extends Model
{
    use HasFactory;

    protected $fillable = [
        'mcp_server_id', 'name', 'description',
        'input_schema', 'output_schema', 'risk_level', 'is_enabled',
    ];

    protected $casts = [
        'input_schema' => 'array',
        'output_schema' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(McpServer::class, 'mcp_server_id');
    }
}
