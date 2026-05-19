<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelRecord extends Model
{
    protected $table = 'models';
    protected $fillable = [
        'slug', 'provider', 'name', 'family', 'capabilities',
        'context_window', 'cost_per_1k_in', 'cost_per_1k_out',
        'latency_p50_ms', 'is_local', 'is_active', 'metadata',
    ];
    protected $casts = [
        'capabilities' => 'array',
        'metadata' => 'array',
        'is_local' => 'bool',
        'is_active' => 'bool',
        'cost_per_1k_in' => 'float',
        'cost_per_1k_out' => 'float',
    ];
}
