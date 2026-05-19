<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodegenJob extends Model
{
    protected $fillable = [
        'tenant_id', 'project_id', 'kind', 'status',
        'prompt', 'inputs', 'outputs', 'repo_branch', 'pr_url',
        'triggered_by', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'inputs' => 'array',
        'outputs' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
