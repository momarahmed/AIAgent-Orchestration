<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestSuite extends Model
{
    protected $fillable = [
        'tenant_id', 'project_id', 'asset_type', 'asset_id',
        'name', 'description', 'cases', 'created_by',
    ];

    protected $casts = [
        'cases' => 'array',
    ];

    public function executions()
    {
        return $this->hasMany(TestExecution::class);
    }
}
