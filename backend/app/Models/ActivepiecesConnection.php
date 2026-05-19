<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivepiecesConnection extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'piece_name', 'flow_id', 'status',
        'config', 'secret_refs', 'risk_level', 'requires_approval', 'created_by',
    ];

    protected $casts = [
        'config'            => 'array',
        'secret_refs'       => 'array',
        'requires_approval' => 'boolean',
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
