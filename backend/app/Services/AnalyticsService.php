<?php

namespace App\Services;

use App\Models\AgentQualityScore;
use App\Models\AnalyticsSnapshot;
use App\Models\MarketplaceInstall;
use App\Models\MarketplaceListing;
use App\Models\TaskRun;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 advanced analytics.
 *
 * Surfaces:
 *  - Usage analytics (runs, adoption, feature usage)
 *  - Cost insights (spend per tenant/project/agent/workflow, anomaly hint)
 *  - Reliability insights (success rate, latency, approval latency)
 *  - Template adoption analytics (marketplace)
 *  - Agent / MCP quality scores (composite)
 *
 * Snapshots are persisted to `analytics_snapshots` (daily roll-ups) so the
 * dashboards stay fast as the platform scales.
 */
class AnalyticsService
{
    public function recordSnapshot(string $kind, string $metric, float $value, array $dimensions = [], ?int $tenantId = null, ?int $projectId = null, ?Carbon $day = null): AnalyticsSnapshot
    {
        return AnalyticsSnapshot::create([
            'tenant_id'  => $tenantId,
            'project_id' => $projectId,
            'day'        => ($day ?? now())->toDateString(),
            'kind'       => $kind,
            'metric'     => $metric,
            'dimensions' => $dimensions,
            'value'      => $value,
        ]);
    }

    public function usage(?int $tenantId = null, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $q = WorkflowRun::query()->where('created_at', '>=', $since);
        if ($tenantId) {
            $q->whereHas('workflow', fn ($w) => $w->where('tenant_id', $tenantId));
        }

        $daily = (clone $q)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as runs'))
            ->groupBy('day')->orderBy('day')->get();

        $byStatus = (clone $q)
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status');

        $byWorkflow = (clone $q)
            ->select('workflow_id', DB::raw('count(*) as runs'))
            ->groupBy('workflow_id')->orderByDesc('runs')->limit(10)->get()
            ->map(fn ($r) => [
                'workflow_id' => $r->workflow_id,
                'workflow'    => optional(Workflow::find($r->workflow_id))->name ?? 'unknown',
                'runs'        => (int) $r->runs,
            ]);

        return [
            'window_days' => $days,
            'daily'       => $daily,
            'by_status'   => $byStatus,
            'top_workflows' => $byWorkflow,
        ];
    }

    public function cost(?int $tenantId = null, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $rows = AnalyticsSnapshot::where('kind', 'cost')
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('day', '>=', $since->toDateString())
            ->orderBy('day')->get();

        $byTenant   = $rows->groupBy('tenant_id')->map(fn ($g) => round((float) $g->sum('value'), 2));
        $byProject  = $rows->groupBy('project_id')->map(fn ($g) => round((float) $g->sum('value'), 2));
        $byMetric   = $rows->groupBy('metric')->map(fn ($g) => round((float) $g->sum('value'), 2));
        $daily      = $rows->groupBy(fn ($r) => $r->day->toDateString())
            ->map(fn ($g) => round((float) $g->sum('value'), 2))
            ->sortKeys()->all();

        $total = round((float) $rows->sum('value'), 2);

        $anomaly = $this->detectCostAnomaly($daily);

        return [
            'window_days' => $days,
            'total_usd'   => $total,
            'daily'       => $daily,
            'by_tenant'   => $byTenant,
            'by_project'  => $byProject,
            'by_dimension'=> $byMetric,
            'anomaly'     => $anomaly,
        ];
    }

    public function reliability(?int $tenantId = null, int $days = 30): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $runs = WorkflowRun::query()->where('created_at', '>=', $since);
        if ($tenantId) {
            $runs->whereHas('workflow', fn ($w) => $w->where('tenant_id', $tenantId));
        }
        $total = (clone $runs)->count();
        $completed = (clone $runs)->where('status', 'completed')->count();
        $failed    = (clone $runs)->where('status', 'failed')->count();
        $awaitingApproval = (clone $runs)->where('status', 'awaiting_approval')->count();

        $avgDuration = (clone $runs)
            ->whereNotNull('started_at')->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as s')
            ->value('s');

        $taskFailRate = TaskRun::query()
            ->where('created_at', '>=', $since)
            ->selectRaw("AVG(case when status='failed' then 1 else 0 end) as rate")
            ->value('rate') ?? 0;

        return [
            'window_days'         => $days,
            'runs_total'          => $total,
            'success_rate'        => $total > 0 ? round($completed / $total * 100, 2) : 0,
            'failure_rate'        => $total > 0 ? round($failed / $total * 100, 2) : 0,
            'awaiting_approval'   => $awaitingApproval,
            'avg_duration_seconds'=> $avgDuration ? round((float) $avgDuration, 2) : 0,
            'task_failure_rate'   => round((float) $taskFailRate * 100, 2),
        ];
    }

    public function templateAdoption(?int $tenantId = null, int $days = 90): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();
        $installs = MarketplaceInstall::query()->where('created_at', '>=', $since);
        if ($tenantId) {
            $installs->where('tenant_id', $tenantId);
        }
        $byListing = (clone $installs)
            ->select('listing_id', DB::raw('count(*) as installs'))
            ->groupBy('listing_id')->orderByDesc('installs')->limit(20)->get()
            ->map(fn ($r) => [
                'listing_id' => $r->listing_id,
                'title'      => optional(MarketplaceListing::find($r->listing_id))->title ?? 'unknown',
                'category'   => optional(MarketplaceListing::find($r->listing_id))->category ?? null,
                'installs'   => (int) $r->installs,
            ]);
        return [
            'window_days' => $days,
            'top_templates' => $byListing,
            'total_installs' => (clone $installs)->count(),
            'total_listings' => MarketplaceListing::when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))->count(),
        ];
    }

    public function computeQualityScores(): int
    {
        $count = 0;
        Workflow::query()->chunkById(100, function ($workflows) use (&$count) {
            foreach ($workflows as $workflow) {
                $runs = WorkflowRun::where('workflow_id', $workflow->id);
                $total = (clone $runs)->count();
                if ($total === 0) continue;

                $completed = (clone $runs)->where('status', 'completed')->count();
                $successRate = round($completed / $total * 100, 2);
                $latency = (float) ((clone $runs)
                    ->whereNotNull('started_at')->whereNotNull('completed_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MILLISECOND, started_at, completed_at)) as ms')
                    ->value('ms') ?? 0);

                AgentQualityScore::updateOrCreate(
                    ['subject_type' => 'workflow', 'subject_id' => $workflow->id],
                    [
                        'success_rate'     => $successRate,
                        'latency_ms_p95'   => round($latency * 1.5, 2),
                        'cost_per_run_usd' => 0,
                        'user_rating'      => 0,
                        'composite_score'  => round(($successRate * 0.7) + (max(0, 100 - $latency / 100) * 0.3), 2),
                        'computed_at'      => now(),
                    ]
                );
                $count++;
            }
        });
        return $count;
    }

    public function detectCostAnomaly(array $daily): array
    {
        if (count($daily) < 7) {
            return ['anomaly' => false, 'reason' => 'not_enough_data'];
        }
        $values = array_values($daily);
        $latest = (float) end($values);
        $rest = array_slice($values, 0, -1);
        $avg = array_sum($rest) / max(1, count($rest));
        $std = sqrt(array_sum(array_map(fn ($v) => ($v - $avg) ** 2, $rest)) / max(1, count($rest)));
        $z = $std > 0 ? ($latest - $avg) / $std : 0;
        return [
            'anomaly'  => $z > 2.5,
            'z_score'  => round($z, 2),
            'latest'   => round($latest, 2),
            'baseline' => round($avg, 2),
        ];
    }
}
