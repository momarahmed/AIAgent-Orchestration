<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpaPolicy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'category', 'description',
        'rego_code', 'package_path', 'status', 'version',
        'metadata', 'dry_run_result', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'metadata'       => 'array',
        'dry_run_result' => 'array',
        'version'        => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(OpaPolicyVersion::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
