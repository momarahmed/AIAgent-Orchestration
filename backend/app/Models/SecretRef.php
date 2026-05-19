<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecretRef extends Model
{
    use SoftDeletes;

    protected $table = 'secret_refs';

    protected $fillable = [
        'tenant_id', 'project_id', 'name', 'vault_path',
        'environment', 'provider', 'metadata', 'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
