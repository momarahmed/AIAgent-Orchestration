<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McpServerVersion extends Model
{
    use HasFactory;

    protected $fillable = ['mcp_server_id', 'version', 'config', 'created_by'];

    protected $casts = ['config' => 'array'];

    public function server(): BelongsTo
    {
        return $this->belongsTo(McpServer::class, 'mcp_server_id');
    }
}
