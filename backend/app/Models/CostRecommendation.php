<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostRecommendation extends Model
{
    protected $fillable = [
        'tenant_id', 'subject_type', 'subject_id', 'recommendation_type',
        'message', 'estimated_savings_usd', 'status',
    ];

    protected $casts = [
        'estimated_savings_usd' => 'decimal:2',
    ];
}
