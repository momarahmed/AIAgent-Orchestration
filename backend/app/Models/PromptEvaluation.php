<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromptEvaluation extends Model
{
    protected $fillable = [
        'prompt_id', 'prompt_version_id', 'model_slug', 'dataset',
        'quality_score', 'cost_score', 'latency_score',
        'sample_count', 'breakdown',
    ];
    protected $casts = [
        'breakdown' => 'array',
        'quality_score' => 'float',
        'cost_score' => 'float',
        'latency_score' => 'float',
    ];
}
