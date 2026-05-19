<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacyImport extends Model
{
    protected $fillable = [
        'tenant_id', 'project_id', 'user_id', 'source_format',
        'source_payload', 'output_payload', 'confidence_score',
        'review_checklist', 'status',
    ];

    protected $casts = [
        'source_payload'   => 'array',
        'output_payload'   => 'array',
        'review_checklist' => 'array',
        'confidence_score' => 'decimal:2',
    ];
}
