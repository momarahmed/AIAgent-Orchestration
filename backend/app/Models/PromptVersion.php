<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptVersion extends Model
{
    protected $fillable = [
        'prompt_id', 'version', 'body', 'variables',
        'metadata', 'status', 'changelog', 'created_by',
    ];
    protected $casts = [
        'variables' => 'array',
        'metadata' => 'array',
    ];

    public function prompt(): BelongsTo { return $this->belongsTo(Prompt::class); }
}
