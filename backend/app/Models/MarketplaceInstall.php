<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceInstall extends Model
{
    protected $fillable = [
        'listing_id', 'version_id', 'tenant_id', 'project_id', 'installed_by',
        'environment', 'parameters', 'created_assets', 'status',
    ];

    protected $casts = [
        'parameters'     => 'array',
        'created_assets' => 'array',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(MarketplaceVersion::class, 'version_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
