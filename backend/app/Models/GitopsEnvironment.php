<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GitopsEnvironment extends Model
{
    protected $table = 'gitops_environments';

    protected $fillable = [
        'name', 'engine', 'repo_url', 'branch', 'path', 'cluster', 'namespace',
        'environment_class', 'auto_sync', 'drift_state', 'last_sync_at',
        'last_commit_sha',
    ];

    protected $casts = [
        'auto_sync'    => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function syncs(): HasMany
    {
        return $this->hasMany(GitopsSync::class, 'environment_id');
    }
}
