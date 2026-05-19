<?php

namespace App\Services;

use App\Models\Locale;
use App\Models\LocaleTranslation;
use Illuminate\Support\Facades\Cache;

/**
 * Phase 5 — Localization service.
 *
 * Phase 5 ships English + Arabic (RTL) as the first non-English locale.
 * The architecture is locale-pluggable: any locale can be added by inserting
 * rows into `locales` + `locale_translations`.
 *
 * The service exposes a flat key-value dictionary per locale + namespace,
 * cached for 5 minutes, that the frontend hydrates on initial render.
 */
class LocalizationService
{
    public const CACHE_TTL = 300;

    public function activeLocales(): array
    {
        return Locale::where('is_active', true)->get()
            ->map(fn ($l) => [
                'code'      => $l->code,
                'name'      => $l->name,
                'rtl'       => (bool) $l->rtl,
                'is_active' => (bool) $l->is_active,
            ])->all();
    }

    public function dictionary(string $code, ?string $namespace = null): array
    {
        return Cache::remember("locale.{$code}.{$namespace}", self::CACHE_TTL, function () use ($code, $namespace) {
            $locale = Locale::where('code', $code)->where('is_active', true)->first();
            if (! $locale) return [];

            $q = $locale->translations();
            if ($namespace) {
                $q->where('namespace', $namespace);
            }
            $rows = $q->get(['namespace', 'key', 'value']);

            $out = [];
            foreach ($rows as $r) {
                $out[$r->namespace][$r->key] = $r->value;
            }
            return $out;
        });
    }

    public function upsert(string $code, string $namespace, string $key, string $value): LocaleTranslation
    {
        $locale = Locale::firstOrCreate(['code' => $code], ['name' => strtoupper($code), 'rtl' => in_array($code, ['ar', 'he', 'fa', 'ur'])]);
        $t = LocaleTranslation::updateOrCreate(
            ['locale_id' => $locale->id, 'namespace' => $namespace, 'key' => $key],
            ['value' => $value],
        );
        Cache::forget("locale.{$code}.{$namespace}");
        Cache::forget("locale.{$code}.");
        return $t;
    }
}
