<?php

namespace App\Services;

use App\Models\ProviderBudget;
use Illuminate\Support\Facades\Log;

/**
 * Per-tenant model budget enforcement (Phase 3).
 *
 * Tracks token consumption and cost per provider/model,
 * enforces monthly limits with configurable actions (reject/warn/throttle).
 */
class ProviderBudgetService
{
    protected array $costPerToken = [
        'openai' => [
            'gpt-4o'      => ['input' => 0.0000025, 'output' => 0.00001],
            'gpt-4o-mini' => ['input' => 0.00000015, 'output' => 0.0000006],
            'gpt-4-turbo' => ['input' => 0.00001, 'output' => 0.00003],
        ],
        'claude' => [
            'claude-sonnet-4-20250514' => ['input' => 0.000003, 'output' => 0.000015],
            'claude-3-haiku-20240307'  => ['input' => 0.00000025, 'output' => 0.00000125],
        ],
        'google_adk' => [
            'gemini-1.5-pro'   => ['input' => 0.00000125, 'output' => 0.000005],
            'gemini-1.5-flash' => ['input' => 0.000000075, 'output' => 0.0000003],
        ],
    ];

    /**
     * Check if a provider call is within budget.
     *
     * @return array{allowed:bool,action?:string,remaining?:float,message?:string}
     */
    public function checkBudget(int $tenantId, string $provider, ?string $model = null): array
    {
        $budget = $this->getActiveBudget($tenantId, $provider, $model);

        if (! $budget) {
            return ['allowed' => true];
        }

        if ($budget->isExceeded()) {
            return match ($budget->action_on_exceed) {
                'reject' => [
                    'allowed' => false,
                    'action'  => 'reject',
                    'message' => "Monthly budget of \${$budget->monthly_limit_usd} for {$provider} has been exhausted. Current spend: \${$budget->current_spend_usd}.",
                ],
                'warn' => [
                    'allowed'   => true,
                    'action'    => 'warn',
                    'remaining' => 0,
                    'message'   => "Budget exceeded for {$provider}. Proceeding with warning.",
                ],
                'throttle' => [
                    'allowed' => true,
                    'action'  => 'throttle',
                    'message' => "Budget exceeded. Requests throttled.",
                ],
                default => ['allowed' => true],
            };
        }

        return [
            'allowed'   => true,
            'remaining' => $budget->remainingBudget(),
        ];
    }

    /**
     * Record cost after a provider call completes.
     */
    public function recordUsage(
        int $tenantId,
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens,
    ): float {
        $cost = $this->calculateCost($provider, $model, $inputTokens, $outputTokens);

        $budget = $this->getActiveBudget($tenantId, $provider, $model);
        if ($budget) {
            $budget->increment('current_spend_usd', $cost);
        }

        return $cost;
    }

    public function calculateCost(string $provider, string $model, int $inputTokens, int $outputTokens): float
    {
        $rates = $this->costPerToken[$provider][$model] ?? null;
        if (! $rates) {
            return 0.0;
        }

        return ($inputTokens * $rates['input']) + ($outputTokens * $rates['output']);
    }

    public function getActiveBudget(int $tenantId, string $provider, ?string $model = null): ?ProviderBudget
    {
        return ProviderBudget::where('tenant_id', $tenantId)
            ->where('provider', $provider)
            ->where('is_active', true)
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->when($model, fn ($q) => $q->where(function ($q2) use ($model) {
                $q2->where('model', $model)->orWhereNull('model');
            }))
            ->orderByRaw('model IS NULL') // prefer model-specific budget
            ->first();
    }

    public function getTenantSpendSummary(int $tenantId): array
    {
        $budgets = ProviderBudget::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->get();

        return $budgets->map(fn ($b) => [
            'provider'    => $b->provider,
            'model'       => $b->model,
            'limit'       => (float) $b->monthly_limit_usd,
            'spent'       => (float) $b->current_spend_usd,
            'remaining'   => $b->remainingBudget(),
            'utilization' => $b->monthly_limit_usd > 0
                ? round(($b->current_spend_usd / $b->monthly_limit_usd) * 100, 1)
                : 0,
        ])->toArray();
    }
}
