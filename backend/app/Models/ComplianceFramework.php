<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplianceFramework extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'controls', 'is_active',
    ];

    protected $casts = [
        'controls'  => 'array',
        'is_active' => 'boolean',
    ];
}
