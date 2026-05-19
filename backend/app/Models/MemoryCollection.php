<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemoryCollection extends Model
{
    protected $fillable = [
        'tenant_id', 'project_id', 'slug', 'name', 'scope',
        'vector_namespace', 'embedding_model', 'embedding_dim', 'settings',
    ];
    protected $casts = ['settings' => 'array'];

    public function items(): HasMany { return $this->hasMany(MemoryItem::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
}
