<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GitopsSync extends Model
{
    protected $table = 'gitops_syncs';

    protected $fillable = [
        'environment_id', 'commit_sha', 'triggered_by', 'status',
        'drift_resources', 'result', 'user_id',
    ];

    protected $casts = [
        'drift_resources' => 'array',
        'result'          => 'array',
    ];

    public function environment(): BelongsTo
    {
        return $this->belongsTo(GitopsEnvironment::class, 'environment_id');
    }
}
