<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelRoutingRule extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'priority', 'match', 'route', 'is_active',
    ];
    protected $casts = [
        'match' => 'array',
        'route' => 'array',
        'is_active' => 'bool',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
