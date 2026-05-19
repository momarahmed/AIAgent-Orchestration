<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryItem extends Model
{
    protected $fillable = [
        'memory_collection_id', 'tenant_id', 'project_id', 'session_id',
        'agent_id', 'source_type', 'source_id', 'vector_id',
        'content', 'metadata', 'expires_at',
    ];
    protected $casts = [
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    public function collection(): BelongsTo { return $this->belongsTo(MemoryCollection::class, 'memory_collection_id'); }
}
