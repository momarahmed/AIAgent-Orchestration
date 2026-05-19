<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MigrationImport extends Model
{
    protected $fillable = [
        'tenant_id', 'project_id', 'source_format', 'filename',
        'raw_source', 'translated', 'confidence', 'review_checklist',
        'status', 'workflow_id', 'imported_by',
    ];
    protected $casts = [
        'raw_source' => 'array',
        'translated' => 'array',
        'review_checklist' => 'array',
        'confidence' => 'float',
    ];

    public function workflow(): BelongsTo { return $this->belongsTo(Workflow::class); }
}
