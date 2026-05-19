<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class A2APartner extends Model
{
    use SoftDeletes;

    protected $table = 'a2a_partners';
    protected $fillable = [
        'tenant_id', 'partner_id', 'name', 'framework', 'endpoint_url',
        'auth_type', 'secret_ref', 'status', 'quarantined',
        'capabilities', 'metadata', 'created_by',
    ];
    protected $casts = [
        'capabilities' => 'array',
        'metadata' => 'array',
        'quarantined' => 'bool',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
}
