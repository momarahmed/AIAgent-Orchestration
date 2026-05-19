<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaAgentRun extends Model
{
    protected $fillable = [
        'tenant_id', 'project_id', 'user_id', 'meta_agent', 'prompt',
        'status', 'plan', 'artifacts', 'approvals', 'cost_usd',
        'token_usage', 'trace_id', 'error', 'started_at', 'completed_at',
    ];
    protected $casts = [
        'plan' => 'array',
        'artifacts' => 'array',
        'approvals' => 'array',
        'cost_usd' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function actions(): HasMany { return $this->hasMany(MetaAgentAction::class)->orderBy('sequence'); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
