<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceVersion extends Model
{
    protected $fillable = [
        'listing_id', 'version', 'migration_notes', 'manifest', 'parameters_schema',
        'signature', 'sbom_hash', 'status', 'review_log', 'published_by', 'published_at',
    ];

    protected $casts = [
        'manifest'          => 'array',
        'parameters_schema' => 'array',
        'review_log'        => 'array',
        'published_at'      => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }
}
