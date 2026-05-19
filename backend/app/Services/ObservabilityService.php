<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Deployment;
use App\Models\TaskRun;
use App\Models\ToolCall;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\DB;

/**
 * Observability Service (PRD §22 — Phase 4).
 *
 * Aggregates the platform's Prometheus-style metrics:
 *   - workflow success rate, duration, error rate
 *   - tool failure rate, agent error rate
 *   - token usage and model cost
 *   - approval latency
 *   - deployment failure rate
 *
 * The /api/observability/metrics endpoint exposes these in the
 * Prometheus text format so the Prometheus container scrapes them
 * directly. The /api/observability/timeline endpoint surfaces a
 * single distributed trace for the Run Detail view.
 */
class ObservabilityService
{
    public function metrics(): array
    {
        $totalRuns      = WorkflowRun::count();
        $succRuns       = WorkflowRun::where('status', 'completed')->count();
        $failedRuns     = WorkflowRun::where('status', 'failed')->count();
        $successRate    = $totalRuns > 0 ? $succRuns / $totalRuns : 0;

        $durations = WorkflowRun::query()
            ->whereNotNull('started_at')->whereNotNull('completed_at')
            ->select(DB::raw('TIMESTAMPDIFF(MICROSECOND, started_at, completed_at)/1000 as ms'))
            ->orderBy('ms')->pluck('ms')->toArray();
        $p95 = $this->percentile($durations, 0.95);

        $toolTotal = ToolCall::count();
        $toolFail  = ToolCall::where('status', 'failed')->count();
        $toolRate  = $toolTotal > 0 ? $toolFail / $toolTotal : 0;

        $agentTotal = TaskRun::count();
        $agentFail  = TaskRun::where('status', 'failed')->count();
        $agentRate  = $agentTotal > 0 ? $agentFail / $agentTotal : 0;

        $tokens = (int) ToolCall::sum('token_count');
        $cost   = (float) ToolCall::sum('cost_usd');

        $approvalLatencies = Approval::query()
            ->whereNotNull('decided_at')
            ->select(DB::raw('TIMESTAMPDIFF(MICROSECOND, created_at, decided_at)/1000 as ms'))
            ->pluck('ms')->toArray();
        $appP95 = $this->percentile($approvalLatencies, 0.95);

        $depTotal = Deployment::count();
        $depFail  = Deployment::whereIn('status', ['failed', 'rolled_back'])->count();
        $depRate  = $depTotal > 0 ? $depFail / $depTotal : 0;

        return [
            'eamcp_workflow_success_rate'      => round($successRate, 4),
            'eamcp_workflow_duration_ms_p95'   => (int) $p95,
            'eamcp_workflow_runs_total'        => $totalRuns,
            'eamcp_workflow_failed_total'      => $failedRuns,
            'eamcp_tool_failure_rate'          => round($toolRate, 4),
            'eamcp_agent_error_rate'           => round($agentRate, 4),
            'eamcp_model_tokens_total'         => $tokens,
            'eamcp_model_cost_usd_total'       => round($cost, 4),
            'eamcp_approval_latency_ms_p95'    => (int) $appP95,
            'eamcp_deployment_failure_rate'    => round($depRate, 4),
        ];
    }

    public function metricsPrometheus(): string
    {
        $lines = [];
        foreach ($this->metrics() as $name => $value) {
            $lines[] = "# TYPE {$name} gauge";
            $lines[] = "{$name} {$value}";
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * Pull together a distributed-trace style timeline for one
     * workflow run — joins workflow_runs / task_runs / tool_calls into
     * a single sorted timeline.
     */
    public function timeline(WorkflowRun $run): array
    {
        $events = [];
        $events[] = [
            'kind' => 'workflow_run', 'id' => $run->id, 'status' => $run->status,
            'start' => $run->started_at, 'end' => $run->completed_at, 'label' => "Workflow Run #{$run->id}",
            'trace_id' => $run->trace_id,
        ];
        foreach ($run->tasks()->orderBy('started_at')->get() as $t) {
            $events[] = [
                'kind' => 'task_run', 'id' => $t->id, 'status' => $t->status,
                'start' => $t->started_at, 'end' => $t->completed_at,
                'label' => "Node {$t->node_id}",
                'agent_id' => $t->agent_id,
            ];
        }
        foreach (ToolCall::where('workflow_run_id', $run->id)->orderBy('created_at')->get() as $tc) {
            $events[] = [
                'kind' => 'tool_call', 'id' => $tc->id, 'status' => $tc->status,
                'start' => $tc->created_at, 'end' => $tc->updated_at,
                'label' => "Tool {$tc->tool_id}",
                'cost_usd' => $tc->cost_usd,
                'token_count' => $tc->token_count,
            ];
        }
        return $events;
    }

    protected function percentile(array $values, float $p): float
    {
        if (! $values) return 0.0;
        sort($values);
        $idx = (int) floor($p * (count($values) - 1));
        return (float) $values[$idx];
    }
}
