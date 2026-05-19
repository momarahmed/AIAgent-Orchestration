<?php

namespace App\Services;

use App\Models\ModelRecord;

/**
 * Model Registry (PRD §9.2 / §17 / §24.4 — Phase 4)
 *
 * Read-side service exposing the model catalog with capability filtering,
 * cost projections, and helpers used by the ModelRouter and Agent runtime.
 */
class ModelRegistryService
{
    public function all(?int $tenantId = null): \Illuminate\Support\Collection
    {
        return ModelRecord::query()->where('is_active', true)->orderBy('provider')->orderBy('slug')->get();
    }

    public function find(string $slug): ?ModelRecord
    {
        return ModelRecord::query()->where('slug', $slug)->first();
    }

    /**
     * Filter models by required capabilities.
     *
     * @param string[] $capabilities
     */
    public function byCapabilities(array $capabilities): \Illuminate\Support\Collection
    {
        return ModelRecord::query()
            ->where('is_active', true)
            ->get()
            ->filter(function (ModelRecord $m) use ($capabilities) {
                $cap = $m->capabilities ?? [];
                foreach ($capabilities as $needed) {
                    if (! in_array($needed, $cap, true)) {
                        return false;
                    }
                }
                return true;
            })
            ->values();
    }

    public function estimateCost(string $slug, int $inputTokens, int $outputTokens): float
    {
        $m = $this->find($slug);
        if (! $m) return 0.0;
        return round(
            ($inputTokens / 1000.0) * (float) $m->cost_per_1k_in
            + ($outputTokens / 1000.0) * (float) $m->cost_per_1k_out,
            6
        );
    }
}
