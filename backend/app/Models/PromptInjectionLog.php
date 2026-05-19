<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptInjectionLog extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'source', 'detection_method', 'severity',
        'action_taken', 'suspicious_content', 'detection_details',
        'related_type', 'related_id',
    ];

    protected $casts = [
        'detection_details' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
