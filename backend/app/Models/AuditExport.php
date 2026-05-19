<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditExport extends Model
{
    protected $fillable = [
        'tenant_id', 'audit_report_template_id', 'format', 'status',
        'filters', 'file_path', 'file_size', 'record_count',
        'period_start', 'period_end', 'requested_by', 'completed_at',
    ];

    protected $casts = [
        'filters'      => 'array',
        'file_size'    => 'integer',
        'record_count' => 'integer',
        'period_start' => 'datetime',
        'period_end'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AuditReportTemplate::class, 'audit_report_template_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
