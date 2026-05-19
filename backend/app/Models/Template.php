<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'asset_type',
        'description', 'payload', 'parameters_schema', 'visibility',
    ];

    protected $casts = [
        'payload' => 'array',
        'parameters_schema' => 'array',
    ];
}
