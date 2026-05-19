<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowiseAgent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'project_id', 'owner_id',
        'name', 'slug', 'description', 'status',
        'flowise_chatflow_id', 'flowise_deployed_url', 'flowise_api_endpoint',
        'workflow_config', 'tools_config', 'model_config',
        'last_run_status', 'last_run_at', 'last_synced_at',
    ];

    protected $casts = [
        'workflow_config' => 'array',
        'tools_config'    => 'array',
        'model_config'    => 'array',
        'last_run_at'     => 'datetime',
        'last_synced_at'  => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(FlowiseRun::class);
    }

    public function syncs(): HasMany
    {
        return $this->hasMany(FlowiseSync::class);
    }
}
