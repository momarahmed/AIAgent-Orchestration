<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\McpServer;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\Tool;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate KPI / dashboard metrics for the Platform Console.
 */
class MetricsController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');

        $scope = function ($q) use ($tenantId) {
            return $tenantId ? $q->where('tenant_id', $tenantId) : $q;
        };

        $totalRuns       = $scope(WorkflowRun::query()->whereHas('workflow', fn ($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q))->count();
        $successRuns     = $scope(WorkflowRun::query()->whereHas('workflow', fn ($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q))->where('status', 'completed')->count();
        $failedRuns      = $scope(WorkflowRun::query()->whereHas('workflow', fn ($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q))->where('status', 'failed')->count();
        $awaiting        = $scope(WorkflowRun::query()->whereHas('workflow', fn ($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q))->where('status', 'awaiting_approval')->count();

        $byDay = WorkflowRun::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as runs'),
                DB::raw("sum(case when status='completed' then 1 else 0 end) as completed"),
                DB::raw("sum(case when status='failed' then 1 else 0 end) as failed"))
            ->where('created_at', '>=', Carbon::now()->subDays(13)->startOfDay())
            ->groupBy('day')->orderBy('day')->get();

        return response()->json([
            'kpis' => [
                'tenants'    => Tenant::count(),
                'projects'   => Project::count(),
                'agents'     => $scope(Agent::query())->count(),
                'mcp_servers'=> $scope(McpServer::query())->count(),
                'tools'      => Tool::count(),
                'workflows'  => $scope(Workflow::query())->count(),
                'runs_total' => $totalRuns,
                'runs_success' => $successRuns,
                'runs_failed'  => $failedRuns,
                'runs_awaiting_approval' => $awaiting,
                'success_rate' => $totalRuns > 0 ? round($successRuns / $totalRuns * 100, 1) : 0,
            ],
            'series' => [
                'runs_14d' => $byDay,
            ],
            'system' => [
                'platform_version' => config('app.version', '1.0.0'),
                'phase' => 'Phase 1 — Foundation',
                'environment' => config('app.env'),
                'time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function health(): JsonResponse
    {
        $dbOk = false;
        $redisOk = false;
        try { DB::connection()->getPdo(); $dbOk = true; } catch (\Throwable $e) {}
        try { app('redis')->ping(); $redisOk = true; } catch (\Throwable $e) {}
        return response()->json([
            'status' => $dbOk && $redisOk ? 'healthy' : 'degraded',
            'checks' => ['database' => $dbOk, 'redis' => $redisOk],
            'time' => now()->toIso8601String(),
        ]);
    }
}
