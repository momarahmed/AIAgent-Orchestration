<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetComment extends Model
{
    protected $fillable = [
        'tenant_id', 'asset_type', 'asset_id', 'user_id', 'parent_id',
        'body', 'mentions', 'resolved', 'resolved_at',
    ];
    protected $casts = [
        'mentions' => 'array',
        'resolved' => 'bool',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function replies(): HasMany { return $this->hasMany(AssetComment::class, 'parent_id'); }
}
