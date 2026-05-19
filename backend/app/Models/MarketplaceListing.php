<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceListing extends Model
{
    protected $fillable = [
        'tenant_id', 'template_id', 'publisher_id', 'slug', 'title', 'description',
        'category', 'visibility', 'status', 'screenshots', 'tags', 'parameters_schema',
        'required_connectors', 'readme', 'latest_version', 'latest_signature',
        'sbom_hash', 'rating_avg', 'rating_count', 'install_count', 'quality_review',
        'signed',
    ];

    protected $casts = [
        'screenshots'         => 'array',
        'tags'                => 'array',
        'parameters_schema'   => 'array',
        'required_connectors' => 'array',
        'readme'              => 'array',
        'quality_review'      => 'array',
        'rating_avg'          => 'decimal:2',
        'signed'              => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'publisher_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MarketplaceVersion::class, 'listing_id');
    }

    public function installs(): HasMany
    {
        return $this->hasMany(MarketplaceInstall::class, 'listing_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(MarketplaceRating::class, 'listing_id');
    }
}
