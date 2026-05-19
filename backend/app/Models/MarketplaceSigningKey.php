<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceSigningKey extends Model
{
    protected $fillable = [
        'tenant_id', 'key_id', 'algorithm', 'public_key', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
