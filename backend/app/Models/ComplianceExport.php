<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceExport extends Model
{
    protected $fillable = [
        'tenant_id', 'requested_by', 'frameworks', 'period_start', 'period_end',
        'status', 'control_mappings', 'evidence_summary', 'format', 'storage_path',
        'file_size', 'expires_at',
    ];

    protected $casts = [
        'frameworks'       => 'array',
        'control_mappings' => 'array',
        'evidence_summary' => 'array',
        'period_start'     => 'date',
        'period_end'       => 'date',
        'expires_at'       => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
