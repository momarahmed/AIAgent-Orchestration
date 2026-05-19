<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpaPolicyVersion extends Model
{
    protected $fillable = [
        'opa_policy_id', 'version', 'rego_code', 'status', 'metadata', 'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'version'  => 'integer',
    ];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(OpaPolicy::class, 'opa_policy_id');
    }
}
