<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BridgeConnection extends Model
{
    protected $fillable = [
        'tenant_id', 'framework', 'name', 'endpoint_url', 'secret_ref',
        'config', 'enabled', 'requires_approval', 'created_by',
    ];
    protected $casts = [
        'config' => 'array',
        'enabled' => 'bool',
        'requires_approval' => 'bool',
    ];
}
