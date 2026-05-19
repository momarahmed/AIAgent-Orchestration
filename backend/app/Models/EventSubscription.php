<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventSubscription extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'topic', 'filter',
        'handler_type', 'handler_config', 'is_active',
    ];
    protected $casts = [
        'filter' => 'array',
        'handler_config' => 'array',
        'is_active' => 'bool',
    ];
}
