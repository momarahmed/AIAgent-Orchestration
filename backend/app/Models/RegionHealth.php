<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegionHealth extends Model
{
    protected $table = 'region_health';

    protected $fillable = [
        'region', 'role', 'status', 'replication_lag_seconds',
        'measurements', 'last_checked_at',
    ];

    protected $casts = [
        'replication_lag_seconds' => 'decimal:2',
        'measurements'            => 'array',
        'last_checked_at'         => 'datetime',
    ];
}
