<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentQualityScore extends Model
{
    protected $fillable = [
        'subject_type', 'subject_id', 'success_rate', 'latency_ms_p95',
        'cost_per_run_usd', 'user_rating', 'composite_score', 'computed_at',
    ];

    protected $casts = [
        'success_rate'     => 'decimal:2',
        'latency_ms_p95'   => 'decimal:2',
        'cost_per_run_usd' => 'decimal:4',
        'user_rating'      => 'decimal:2',
        'composite_score'  => 'decimal:2',
        'computed_at'      => 'datetime',
    ];
}
