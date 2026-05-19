<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    protected $fillable = [
        'tenant_id', 'project_id', 'day', 'kind', 'metric', 'dimensions', 'value',
    ];

    protected $casts = [
        'day'        => 'date',
        'dimensions' => 'array',
        'value'      => 'decimal:4',
    ];
}
