<?php

namespace App\Services;

use App\Models\AnalyticsSnapshot;
use App\Models\ChargebackReport;
use App\Models\CostRecommendation;
use App\Models\PortfolioBudget;
use App\Models\Tenant;
use App\Support\Audit;
use Illuminate\Support\Carbon;

/**
 * Portfolio-scale cost governance.
 *
 * Implements PRD Section 18 (NFR cost) + Phase 5 Feature 6:
 *  - Hierarchical budgets (organization → tenant → project → agent)
 *  - Chargeback / showback reports per tenant
 *  - Model-mix optimization recommendations
 *  - Hard-cap actions: alert | throttle | switch_to_local | block
 */
class PortfolioCostService
{
    public function recordSpend(string $scopeType, int $scopeId, float $usd, array $dimensions = []): void
    {
        AnalyticsSnapshot::create([
            'tenant_id'  => $dimensions['tenant_id'] ?? null,
            'project_id' => $dimensions['project_id'] ?? null,
            'day'        => now()->toDateString(),
            'kind'       => 'cost',
            'metric'     => $dimensions['metric'] ?? 'model_spend',
            'dimensions' => $dimensions + ['scope_type' => $scopeType, 'scope_id' => $scopeId],
            'value'      => $usd,
        ]);

        PortfolioBudget::where('scope_type', $scopeType)
            ->where('scope_id', $scopeId)
            ->where('is_active', true)
            ->each(function (PortfolioBudget $b) use ($usd) {
                $b->increment('current_spend_usd', $usd);
                $b->refresh();
                $this->cascadeToParent($b, $usd);
                if ($b->isExceeded()) {
                    $this->triggerExceedAction($b);
                }
            });
    }

    protected function cascadeToParent(PortfolioBudget $budget, float $usd): void
    {
        if ($budget->parent_id) {
            $parent = PortfolioBudget::find($budget->parent_id);
            if ($parent) {
                $parent->increment('current_spend_usd', $usd);
                $parent->refresh();
                $this->cascadeToParent($parent, $usd);
                if ($parent->isExceeded()) {
                    $this->triggerExceedAction($parent);
                }
            }
        }
    }

    protected function triggerExceedAction(PortfolioBudget $b): void
    {
        Audit::record(
            'portfolio_budget',
            'exceeded',
            'PortfolioBudget',
            $b->id,
            [
                'scope_type'        => $b->scope_type,
                'scope_id'          => $b->scope_id,
                'action_on_exceed'  => $b->action_on_exceed,
                'current_spend_usd' => $b->current_spend_usd,
                'limit_usd'         => $b->monthly_limit_usd,
            ],
        );
    }

    public function chargeback(int $tenantId, ?Carbon $start = null, ?Carbon $end = null): ChargebackReport
    {
        $start = $start ?? now()->startOfMonth();
        $end   = $end ?? now()->endOfMonth();

        $rows = AnalyticsSnapshot::where('kind', 'cost')
            ->where('tenant_id', $tenantId)
            ->whereBetween('day', [$start->toDateString(), $end->toDateString()])
            ->get();

        $total      = round((float) $rows->sum('value'), 2);
        $byProject  = $rows->groupBy('project_id')->map(fn ($g) => round((float) $g->sum('value'), 2));
        $byMetric   = $rows->groupBy('metric')->map(fn ($g) => round((float) $g->sum('value'), 2));
        $byProvider = $rows->groupBy(fn ($r) => $r->dimensions['provider'] ?? 'unknown')->map(fn ($g) => round((float) $g->sum('value'), 2));
        $byAgent    = $rows->groupBy(fn ($r) => $r->dimensions['agent_id'] ?? null)
            ->filter(fn ($_, $k) => $k !== null)
            ->map(fn ($g) => round((float) $g->sum('value'), 2));

        return ChargebackReport::create([
            'tenant_id'    => $tenantId,
            'period_start' => $start->toDateString(),
            'period_end'   => $end->toDateString(),
            'total_usd'    => $total,
            'breakdown'    => [
                'by_project'  => $byProject,
                'by_metric'   => $byMetric,
                'by_provider' => $byProvider,
                'by_agent'    => $byAgent,
                'tenant'      => optional(Tenant::find($tenantId))->name,
            ],
            'status'       => 'generated',
        ]);
    }

    public function generateRecommendations(int $tenantId): array
    {
        $created = [];

        $rows = AnalyticsSnapshot::where('kind', 'cost')
            ->where('tenant_id', $tenantId)
            ->where('day', '>=', now()->subDays(30)->toDateString())
            ->get();

        $byAgent = $rows->groupBy(fn ($r) => $r->dimensions['agent_id'] ?? null)
            ->filter(fn ($_, $k) => $k !== null);

        foreach ($byAgent as $agentId => $g) {
            $spend = (float) $g->sum('value');
            if ($spend > 50) {
                $rec = CostRecommendation::updateOrCreate(
                    [
                        'tenant_id'           => $tenantId,
                        'subject_type'        => 'agent',
                        'subject_id'          => (int) $agentId,
                        'recommendation_type' => 'model_swap',
                    ],
                    [
                        'message'                => "Agent #{$agentId} spent \${$spend} in 30d. Consider routing to a smaller model for low-complexity prompts.",
                        'estimated_savings_usd'  => round($spend * 0.35, 2),
                        'status'                 => 'open',
                    ]
                );
                $created[] = $rec->id;
            }
        }
        return $created;
    }
}
