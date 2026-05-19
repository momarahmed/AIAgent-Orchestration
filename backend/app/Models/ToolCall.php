<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_run_id', 'tool_id', 'mcp_server_id', 'tool_name',
        'input', 'output', 'status', 'error', 'duration_ms',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];
}
