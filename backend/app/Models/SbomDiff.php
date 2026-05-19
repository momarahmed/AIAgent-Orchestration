<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SbomDiff extends Model
{
    protected $fillable = [
        'image_ref', 'previous_snapshot_id', 'current_snapshot_id',
        'diff', 'risk_level', 'alert_sent',
    ];

    protected $casts = [
        'diff'       => 'array',
        'alert_sent' => 'boolean',
    ];

    public function previousSnapshot(): BelongsTo
    {
        return $this->belongsTo(SbomSnapshot::class, 'previous_snapshot_id');
    }

    public function currentSnapshot(): BelongsTo
    {
        return $this->belongsTo(SbomSnapshot::class, 'current_snapshot_id');
    }
}
