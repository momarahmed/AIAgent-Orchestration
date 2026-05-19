<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityScan extends Model
{
    protected $fillable = [
        'tenant_id', 'deployment_id', 'scan_type', 'target_type', 'target_ref',
        'status', 'severity_summary', 'findings', 'critical_count', 'high_count',
        'medium_count', 'low_count', 'blocks_promotion', 'triggered_by',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'findings'         => 'array',
        'critical_count'   => 'integer',
        'high_count'       => 'integer',
        'medium_count'     => 'integer',
        'low_count'        => 'integer',
        'blocks_promotion' => 'boolean',
        'started_at'       => 'datetime',
        'completed_at'     => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function hasBlockingFindings(): bool
    {
        return $this->critical_count > 0 || $this->high_count > 0;
    }
}
