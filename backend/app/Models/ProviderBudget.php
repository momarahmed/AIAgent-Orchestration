<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderBudget extends Model
{
    protected $fillable = [
        'tenant_id', 'provider', 'model', 'monthly_limit_usd',
        'current_spend_usd', 'period', 'period_start', 'period_end',
        'action_on_exceed', 'is_active',
    ];

    protected $casts = [
        'monthly_limit_usd'  => 'decimal:2',
        'current_spend_usd'  => 'decimal:2',
        'period_start'       => 'date',
        'period_end'         => 'date',
        'is_active'          => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isExceeded(): bool
    {
        return (float) $this->current_spend_usd >= (float) $this->monthly_limit_usd;
    }

    public function remainingBudget(): float
    {
        return max(0, (float) $this->monthly_limit_usd - (float) $this->current_spend_usd);
    }
}
