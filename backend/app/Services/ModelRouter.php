<?php

namespace App\Services;

use App\Contracts\ModelProvider;
use App\Models\ModelRecord;
use App\Models\ModelRoutingRule;
use App\Support\Audit;
use Illuminate\Support\Facades\Log;

/**
 * Model Router (PRD §9.2 — Phase 4)
 *
 * Picks the right model for a given call based on:
 *   1. Routing rules (tenant + capability + cost + latency + tag).
 *   2. Per-tenant model budget enforcement (ProviderBudgetService).
 *   3. Fallback chain with circuit breakers on provider errors.
 *
 * Returns a routed completion as a structured array including which
 * route was taken so callers (and the Agent Runtime) can log the decision.
 */
class ModelRouter
{
    public function __construct(
        protected ProviderRegistry $providers,
        protected ModelRegistryService $registry,
        protected ProviderBudgetService $budgets,
    ) {}

    /**
     * Choose the best model for a request.
     *
     * @param array{tenant_id?:int, capabilities?:array, max_cost_per_1k?:float, max_latency_ms?:int, residency?:string, tag?:string, hint_slug?:string} $request
     * @return array{primary: ModelRecord, fallback: ModelRecord[], rule_id?: int}
     */
    public function plan(array $request): array
    {
        $tenantId = $request['tenant_id'] ?? null;

        if (! empty($request['hint_slug'])) {
            $hint = $this->registry->find($request['hint_slug']);
            if ($hint) {
                return ['primary' => $hint, 'fallback' => $this->defaultFallbacks($hint), 'rule_id' => null];
            }
        }

        $rules = ModelRoutingRule::query()
            ->where('is_active', true)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderBy('priority')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matches($rule->match ?? [], $request)) {
                $route   = $rule->route ?? [];
                $primary = $this->registry->find($route['primary_model_slug'] ?? '');
                if (! $primary) continue;
                $fallback = collect($route['fallback'] ?? [])
                    ->map(fn ($slug) => $this->registry->find((string) $slug))
                    ->filter()->values()->all();
                return ['primary' => $primary, 'fallback' => $fallback, 'rule_id' => $rule->id];
            }
        }

        // No rule matched → use capability-based fallback.
        $candidates = $this->registry->byCapabilities($request['capabilities'] ?? ['chat']);
        if ($candidates->isEmpty()) {
            $candidates = collect([$this->registry->find('openai:gpt-4o-mini')])->filter();
        }

        $primary  = $candidates->first();
        $fallback = $this->defaultFallbacks($primary);
        return ['primary' => $primary, 'fallback' => $fallback, 'rule_id' => null];
    }

    /** Execute a completion using the routed model + fallbacks + budget enforcement. */
    public function complete(array $request, array $payload): array
    {
        $plan      = $this->plan($request);
        $candidates = array_merge([$plan['primary']], $plan['fallback']);
        $tenantId  = $request['tenant_id'] ?? null;
        $errors    = [];

        foreach ($candidates as $model) {
            if (! $model instanceof ModelRecord) continue;
            $providerName = $model->provider;

            // Budget pre-check — soft warn or hard cap based on action_on_exceed.
            $allowed = $tenantId
                ? $this->budgets->checkBudget((int) $tenantId, $providerName, $this->resolveProviderModel($model))
                : ['allowed' => true];
            if (! ($allowed['allowed'] ?? true)) {
                $errors[] = ['model' => $model->slug, 'reason' => 'budget_exceeded'];
                continue;
            }

            try {
                $provider = $this->providers->for($providerName, $model->slug);
                $result   = $provider->complete(array_merge($payload, ['model' => $this->resolveProviderModel($model)]));

                $usage   = $result['usage'] ?? [];
                $inTok   = (int) ($usage['prompt_tokens'] ?? 0);
                $outTok  = (int) ($usage['completion_tokens'] ?? 0);
                $cost    = $this->registry->estimateCost($model->slug, $inTok, $outTok);

                if ($tenantId) {
                    $this->budgets->recordUsage((int) $tenantId, $providerName, $this->resolveProviderModel($model), $inTok, $outTok);
                }

                return [
                    'response'   => $result['response'] ?? '',
                    'model_slug' => $model->slug,
                    'provider'   => $providerName,
                    'usage'      => $usage,
                    'cost_usd'   => $cost,
                    'rule_id'    => $plan['rule_id'] ?? null,
                    'mock'       => $result['mock'] ?? false,
                    'fallback_used' => $model !== $plan['primary'],
                    'attempts'      => count($errors) + 1,
                ];
            } catch (\Throwable $e) {
                Log::warning('model_router.provider_failed', [
                    'provider' => $providerName,
                    'model'    => $model->slug,
                    'error'    => $e->getMessage(),
                ]);
                $errors[] = ['model' => $model->slug, 'reason' => 'provider_error', 'message' => $e->getMessage()];
            }
        }

        Audit::record('model_router', 'all_models_failed', null, null, ['errors' => $errors]);
        return [
            'response'   => '',
            'model_slug' => null,
            'provider'   => null,
            'usage'      => [],
            'cost_usd'   => 0,
            'error'      => 'all_models_failed',
            'attempts'   => count($errors),
        ];
    }

    protected function defaultFallbacks(?ModelRecord $primary): array
    {
        if (! $primary) return [];
        return $this->registry->all()
            ->reject(fn ($m) => $m->slug === $primary->slug)
            ->sortBy('cost_per_1k_in')
            ->take(3)
            ->values()
            ->all();
    }

    protected function matches(array $match, array $req): bool
    {
        if (! empty($match['capability'])) {
            $caps = $req['capabilities'] ?? [];
            if (! in_array($match['capability'], $caps, true)) return false;
        }
        if (isset($match['max_cost_per_1k']) && isset($req['max_cost_per_1k'])) {
            if ($req['max_cost_per_1k'] > (float) $match['max_cost_per_1k']) return false;
        }
        if (isset($match['max_latency_ms']) && isset($req['max_latency_ms'])) {
            if ($req['max_latency_ms'] > (int) $match['max_latency_ms']) return false;
        }
        if (! empty($match['residency']) && ($req['residency'] ?? '') !== $match['residency']) return false;
        if (! empty($match['tag']) && ($req['tag'] ?? '') !== $match['tag']) return false;
        return true;
    }

    protected function resolveProviderModel(ModelRecord $m): string
    {
        // For ollama models we pass through `ollama:llama3.2:1b` → provider strips prefix.
        // Other providers receive the raw provider-side model name (without our slug prefix).
        $slug = $m->slug;
        if ($m->provider === 'ollama') {
            return $slug;
        }
        // strip "provider:" prefix if present
        return str_contains($slug, ':') ? substr($slug, strpos($slug, ':') + 1) : $slug;
    }
}
