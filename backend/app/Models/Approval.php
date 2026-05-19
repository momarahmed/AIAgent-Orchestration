<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = [
        'tenant_id', 'project_id', 'subject_type', 'subject_id',
        'reason', 'risk_level', 'status', 'payload', 'approver_pool',
        'expires_at', 'requested_by', 'decided_by', 'decision_comment', 'decided_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'approver_pool' => 'array',
        'expires_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public function isOpen(): bool
    {
        return $this->status === 'pending' && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
