<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditReportTemplate extends Model
{
    protected $fillable = [
        'name', 'slug', 'category', 'description', 'filters', 'columns', 'is_system',
    ];

    protected $casts = [
        'filters'   => 'array',
        'columns'   => 'array',
        'is_system' => 'boolean',
    ];
}
