<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prompt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id', 'project_id', 'name', 'slug', 'description',
        'category', 'current_version', 'created_by',
    ];

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function project(): BelongsTo { return $this->belongsTo(Project::class); }
    public function versions(): HasMany { return $this->hasMany(PromptVersion::class); }
    public function evaluations(): HasMany { return $this->hasMany(PromptEvaluation::class); }
    public function currentVersion()
    {
        return $this->hasOne(PromptVersion::class)->where('version', $this->current_version);
    }
}
