<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChargebackReport extends Model
{
    protected $fillable = [
        'tenant_id', 'period_start', 'period_end', 'total_usd',
        'breakdown', 'status', 'storage_path',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'total_usd'    => 'decimal:2',
        'breakdown'    => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
