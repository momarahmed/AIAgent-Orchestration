<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioBudget extends Model
{
    protected $fillable = [
        'scope_type', 'scope_id', 'name', 'monthly_limit_usd', 'current_spend_usd',
        'action_on_exceed', 'notify', 'period_start', 'period_end', 'parent_id',
        'is_active',
    ];

    protected $casts = [
        'monthly_limit_usd' => 'decimal:2',
        'current_spend_usd' => 'decimal:2',
        'notify'            => 'array',
        'period_start'      => 'date',
        'period_end'        => 'date',
        'is_active'         => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function isExceeded(): bool
    {
        return (float) $this->current_spend_usd >= (float) $this->monthly_limit_usd;
    }

    public function remaining(): float
    {
        return max(0, (float) $this->monthly_limit_usd - (float) $this->current_spend_usd);
    }

    public function utilizationPercent(): float
    {
        if ((float) $this->monthly_limit_usd <= 0) return 0;
        return round((float) $this->current_spend_usd / (float) $this->monthly_limit_usd * 100, 2);
    }
}
