<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbacPolicy extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'description', 'resource_type', 'action',
        'conditions', 'effect', 'priority', 'is_active', 'created_by',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active'  => 'boolean',
        'priority'   => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
