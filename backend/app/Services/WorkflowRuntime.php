<?php

namespace App\Services;

use App\Models\TaskRun;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Phase-1 synchronous workflow engine (LangGraph-style state machine in-process).
 */
class WorkflowRuntime
{
    public function __construct(
        protected AgentRuntime $agents,
        protected McpGateway $mcp,
    ) {}

    public function run(Workflow $workflow, WorkflowVersion $version, array $input, ?int $userId): WorkflowRun
    {
        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'status' => 'running',
            'input' => $input,
            'started_at' => now(),
            'triggered_by' => $userId,
        ]);

        $traceId = (string) Str::uuid();
        Log::info('otel.trace.workflow_run', [
            'trace_id' => $traceId,
            'workflow_run_id' => $run->id,
            'workflow_id' => $workflow->id,
            'service' => env('OTEL_SERVICE_NAME', 'eamcp-backend'),
        ]);

        $graph = $version->graph_json ?? ['nodes' => [], 'edges' => []];
        $order = $this->topologicalOrder($graph);
        $nodes = collect($graph['nodes'] ?? [])->keyBy('id');

        $context = ['input' => $input, 'nodes' => []];
        $finalOutput = null;
        $hadError = false;
        $lastAgentId = null;

        foreach ($order as $nodeId) {
            $node = $nodes->get($nodeId);
            if (! $node) {
                continue;
            }

            $task = TaskRun::create([
                'workflow_run_id' => $run->id,
                'node_id' => $nodeId,
                'node_type' => $node['type'] ?? 'unknown',
                'status' => 'running',
                'input' => $context,
                'started_at' => now(),
            ]);
            $start = microtime(true);

            try {
                $output = $this->executeNode($node, $context, $task, $lastAgentId);
                if (($node['type'] ?? '') === 'agent') {
                    $lastAgentId = $node['data']['agent_id'] ?? $lastAgentId;
                }
                $task->update([
                    'status' => 'completed',
                    'output' => $output,
                    'completed_at' => now(),
                    'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                ]);
                $context['nodes'][$nodeId] = $output;
                $finalOutput = $output;
            } catch (\Throwable $e) {
                $task->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'completed_at' => now(),
                    'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                ]);
                $hadError = true;
                $finalOutput = ['error' => $e->getMessage()];
                break;
            }
        }

        $run->update([
            'status' => $hadError ? 'failed' : 'completed',
            'output' => ['result' => $finalOutput, 'context' => $context],
            'error' => $hadError ? ($finalOutput['error'] ?? null) : null,
            'completed_at' => now(),
        ]);

        return $run->fresh(['tasks.toolCalls']);
    }

    protected function topologicalOrder(array $graph): array
    {
        $nodes = collect($graph['nodes'] ?? []);
        $edges = collect($graph['edges'] ?? []);
        $incomingByTarget = $edges->groupBy('target');
        $outgoingBySource = $edges->groupBy('source');
        $roots = $nodes->pluck('id')->filter(fn ($id) => $incomingByTarget->get($id, collect())->isEmpty())->values();
        $visited = [];
        $queue = $roots->all();
        $order = [];
        while ($queue) {
            $id = array_shift($queue);
            if (isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;
            $order[] = $id;
            foreach (($outgoingBySource->get($id, collect())->all()) as $edge) {
                $queue[] = $edge['target'];
            }
        }
        return $order;
    }

    protected function executeNode(array $node, array $context, TaskRun $task, ?int $lastAgentId): array
    {
        $type = $node['type'] ?? 'unknown';
        $data = $node['data'] ?? [];

        return match ($type) {
            'trigger' => ['triggered' => true, 'input' => $context['input']],
            'agent' => $this->agents->execute(
                $data['agent_id'] ?? null,
                $data['prompt'] ?? ($context['input']['prompt'] ?? 'Hello'),
                $context,
            ),
            'mcp_tool' => $this->mcp->executeTool(
                $data['tool_id'] ?? null,
                $data['inputs'] ?? [],
                $task,
                $data['agent_id'] ?? $lastAgentId,
            ),
            'condition' => ['branch' => ($data['expression'] ?? '') ? 'true' : 'false'],
            'approval' => ['status' => 'auto_approved_phase1', 'note' => 'Approval queue lands in Phase 2'],
            default => ['note' => "Node type {$type} not implemented in Phase 1"],
        };
    }
}
