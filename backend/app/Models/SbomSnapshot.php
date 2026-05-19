<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SbomSnapshot extends Model
{
    protected $fillable = [
        'image_ref', 'sbom_hash', 'components', 'total_packages',
    ];

    protected $casts = [
        'components' => 'array',
    ];
}
