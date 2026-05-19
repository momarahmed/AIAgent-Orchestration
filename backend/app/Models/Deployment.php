<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deployment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'project_id', 'asset_type', 'asset_id', 'asset_version_id',
        'environment', 'status', 'pipeline', 'config', 'secret_refs', 'notes',
        'created_by', 'approved_by', 'approved_at', 'deployed_at',
    ];

    protected $casts = [
        'pipeline' => 'array',
        'config' => 'array',
        'secret_refs' => 'array',
        'approved_at' => 'datetime',
        'deployed_at' => 'datetime',
    ];

    public function approvals()
    {
        return $this->morphMany(Approval::class, 'subject');
    }
}
