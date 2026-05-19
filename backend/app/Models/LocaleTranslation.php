<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocaleTranslation extends Model
{
    protected $fillable = [
        'locale_id', 'namespace', 'key', 'value',
    ];

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class);
    }
}
