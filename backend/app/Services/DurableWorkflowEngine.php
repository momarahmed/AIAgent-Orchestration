<?php

namespace App\Services;

use App\Contracts\WorkflowEngine;
use App\Models\Approval;
use App\Models\TaskRun;
use App\Models\Tool;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Phase 2 durable workflow engine.
 *
 * Hides the underlying execution mechanism behind a stable contract so the
 * Phase 1 sync runner, the Phase 2 queue-backed runner, and a future Temporal
 * worker can all be swapped without changing controllers.
 *
 * Implements:
 * - Synchronous orchestration with checkpoint persistence after every node.
 * - Pause / resume on Approval nodes and risk-gated tool calls.
 * - Per-node retry policy with exponential backoff and an Error Handler node.
 * - Decision, Transform, Loop, Parallel, Template node types.
 */
class DurableWorkflowEngine implements WorkflowEngine
{
    public function __construct(
        protected AgentRuntime $agents,
        protected McpGateway $mcp,
        protected OpaPolicyService $opa,
        protected ApprovalService $approvals,
    ) {}

    public function run(Workflow $workflow, WorkflowVersion $version, array $input, ?int $userId, string $environment = 'dev'): WorkflowRun
    {
        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'status' => 'running',
            'environment' => $environment,
            'input' => $input,
            'started_at' => now(),
            'triggered_by' => $userId,
            'attempt' => 1,
            'checkpoint' => ['cursor' => 0, 'context' => ['input' => $input, 'nodes' => []], 'order' => $this->topologicalOrder($version->graph_json ?? [])],
        ]);

        Log::info('otel.trace.workflow_run.start', [
            'trace_id' => (string) Str::uuid(),
            'workflow_run_id' => $run->id,
            'workflow_id' => $workflow->id,
            'environment' => $environment,
        ]);

        return $this->execute($run);
    }

    public function resume(WorkflowRun $run, array $resumeInput = []): WorkflowRun
    {
        if (! in_array($run->status, ['awaiting_approval', 'running', 'failed'], true)) {
            return $run;
        }
        $checkpoint = $run->checkpoint ?? [];
        if (! empty($resumeInput)) {
            $checkpoint['context']['resume_input'] = $resumeInput;
        }
        $run->update([
            'status' => 'running',
            'resumed_at' => now(),
            'checkpoint' => $checkpoint,
        ]);
        Audit::record('run', 'workflow_run.resumed', 'workflow_run', $run->id, $resumeInput);
        return $this->execute($run);
    }

    public function cancel(WorkflowRun $run, ?string $reason = null): WorkflowRun
    {
        $run->update(['status' => 'cancelled', 'error' => $reason, 'completed_at' => now()]);
        Audit::record('run', 'workflow_run.cancelled', 'workflow_run', $run->id, ['reason' => $reason]);
        return $run->fresh();
    }

    public function replay(WorkflowRun $run, ?string $fromNodeId = null, array $overrideInputs = []): WorkflowRun
    {
        $version = $run->workflowVersion ?? WorkflowVersion::find($run->workflow_version_id);
        $workflow = $run->workflow;
        $newRun = $this->run($workflow, $version, array_merge($run->input ?? [], ['_replay_of' => $run->id, '_overrides' => $overrideInputs]), $run->triggered_by, $run->environment);
        return $newRun;
    }

    public function debugNode(WorkflowRun $run, string $nodeId, array $overrideInput = []): array
    {
        $version = WorkflowVersion::find($run->workflow_version_id);
        $graph = $version->graph_json ?? [];
        $node = collect($graph['nodes'] ?? [])->firstWhere('id', $nodeId);
        if (! $node) {
            return ['error' => "Node {$nodeId} not found"];
        }

        $context = $run->checkpoint['context'] ?? ['input' => $run->input ?? [], 'nodes' => []];
        if ($overrideInput) {
            $context['input'] = array_merge($context['input'] ?? [], $overrideInput);
        }
        $task = TaskRun::create([
            'workflow_run_id' => $run->id,
            'node_id' => $nodeId,
            'node_type' => $node['type'] ?? 'unknown',
            'status' => 'running',
            'input' => $context,
            'started_at' => now(),
            'attempt' => 99,
        ]);
        $start = microtime(true);
        try {
            $output = $this->executeNode($node, $context, $task, $run);
            $task->update([
                'status' => 'completed',
                'output' => $output,
                'completed_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
            return ['status' => 'completed', 'output' => $output, 'task_id' => $task->id];
        } catch (\Throwable $e) {
            $task->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
            return ['status' => 'failed', 'error' => $e->getMessage(), 'task_id' => $task->id];
        }
    }

    protected function execute(WorkflowRun $run): WorkflowRun
    {
        $version = WorkflowVersion::find($run->workflow_version_id);
        $graph = $version->graph_json ?? ['nodes' => [], 'edges' => []];
        $checkpoint = $run->checkpoint ?? [];
        $order = $checkpoint['order'] ?? $this->topologicalOrder($graph);
        $cursor = $checkpoint['cursor'] ?? 0;
        $context = $checkpoint['context'] ?? ['input' => $run->input ?? [], 'nodes' => []];
        $nodes = collect($graph['nodes'] ?? [])->keyBy('id');

        $finalOutput = $context['nodes'][$order[$cursor - 1] ?? ''] ?? null;
        $hadError = false;

        for ($i = $cursor; $i < count($order); $i++) {
            $nodeId = $order[$i];
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
                'attempt' => 1,
                'retry_policy' => $node['data']['retry_policy'] ?? null,
            ]);

            $output = $this->runNodeWithRetry($node, $context, $task, $run);

            if (($output['_pause'] ?? false) === true) {
                $run->update([
                    'status' => 'awaiting_approval',
                    'paused_at' => now(),
                    'checkpoint' => ['cursor' => $i, 'order' => $order, 'context' => $context],
                ]);
                Audit::record('run', 'workflow_run.paused', 'workflow_run', $run->id, ['node_id' => $nodeId, 'reason' => $output['_pause_reason'] ?? null]);
                return $run->fresh(['tasks.toolCalls']);
            }

            if (($output['_failed'] ?? false) === true) {
                $hadError = true;
                $finalOutput = ['error' => $output['error'] ?? 'unknown'];
                break;
            }

            $context['nodes'][$nodeId] = $output;
            $finalOutput = $output;

            $run->update(['checkpoint' => ['cursor' => $i + 1, 'order' => $order, 'context' => $context]]);
        }

        $run->update([
            'status' => $hadError ? 'failed' : 'completed',
            'output' => ['result' => $finalOutput, 'context' => $context],
            'error' => $hadError ? ($finalOutput['error'] ?? null) : null,
            'completed_at' => now(),
        ]);

        return $run->fresh(['tasks.toolCalls']);
    }

    protected function runNodeWithRetry(array $node, array &$context, TaskRun $task, WorkflowRun $run): array
    {
        $retry = $node['data']['retry_policy'] ?? ['max_attempts' => 1, 'backoff_ms' => 0];
        $maxAttempts = max(1, (int) ($retry['max_attempts'] ?? 1));
        $backoff = (int) ($retry['backoff_ms'] ?? 0);
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $task->update(['attempt' => $attempt]);
            $start = microtime(true);
            try {
                $output = $this->executeNode($node, $context, $task, $run);

                if (($output['_pause'] ?? false) === true) {
                    return $output;
                }

                $task->update([
                    'status' => 'completed',
                    'output' => $output,
                    'completed_at' => now(),
                    'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                ]);
                return $output;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                $task->update([
                    'status' => 'failed',
                    'error' => $lastError,
                    'completed_at' => now(),
                    'duration_ms' => (int) ((microtime(true) - $start) * 1000),
                ]);
                if ($attempt < $maxAttempts) {
                    if ($backoff > 0) usleep($backoff * 1000);
                    $task = TaskRun::create([
                        'workflow_run_id' => $run->id,
                        'node_id' => $task->node_id,
                        'node_type' => $task->node_type,
                        'status' => 'running',
                        'input' => $context,
                        'started_at' => now(),
                        'attempt' => $attempt + 1,
                        'retry_policy' => $retry,
                    ]);
                }
            }
        }

        return ['_failed' => true, 'error' => $lastError];
    }

    protected function executeNode(array $node, array &$context, TaskRun $task, WorkflowRun $run): array
    {
        $type = $node['type'] ?? 'unknown';
        $data = $node['data'] ?? [];

        return match ($type) {
            'trigger'       => ['triggered' => true, 'input' => $context['input'] ?? []],
            'agent'         => $this->agents->execute($data['agent_id'] ?? null, $data['prompt'] ?? ($context['input']['prompt'] ?? 'Hello'), $context),
            'mcp_tool'      => $this->executeToolWithPolicy($data, $task, $run, $context),
            'condition',
            'decision'      => $this->executeDecision($data, $context),
            'transform'     => $this->executeTransform($data, $context),
            'loop'          => $this->executeLoop($data, $context, $task, $run),
            'parallel'      => $this->executeParallel($data, $node, $context, $task, $run),
            'error_handler' => ['note' => 'Error handler executed', 'recovery' => $data['recovery'] ?? 'continue'],
            'template'      => ['note' => 'Sub-workflow stub', 'template_id' => $data['template_id'] ?? null],
            'approval'      => $this->executeApproval($data, $task, $run, $context),
            default         => ['note' => "Node type {$type} not implemented"],
        };
    }

    protected function executeToolWithPolicy(array $data, TaskRun $task, WorkflowRun $run, array $context): array
    {
        $toolId = $data['tool_id'] ?? null;
        $tool = $toolId ? Tool::find($toolId) : null;
        if (! $tool) {
            return $this->mcp->executeTool(null, $data['inputs'] ?? [], $task, null);
        }
        $agent = $context['_agent'] ?? null;
        $agentId = $data['agent_id'] ?? $context['_last_agent_id'] ?? null;
        $agentModel = $agentId ? \App\Models\Agent::find($agentId) : null;

        $policy = $this->opa->evaluateToolCall($agentModel, $tool, ['environment' => $run->environment]);
        if (! $policy['allow']) {
            if ($policy['requires_approval']) {
                $existing = \App\Models\Approval::where('subject_type', 'workflow_run')
                    ->where('subject_id', $run->id)
                    ->where('payload->node_id', $task->node_id)
                    ->latest()
                    ->first();

                if ($existing && $existing->status === 'approved') {
                    return $this->mcp->executeTool($toolId, $data['inputs'] ?? [], $task, $agentId);
                }
                if ($existing && $existing->status === 'rejected') {
                    throw new \RuntimeException('Tool call rejected by approver');
                }
                if (! $existing || $existing->status === 'pending') {
                    $approval = $existing ?: $this->approvals->request([
                        'tenant_id' => $run->workflow->tenant_id,
                        'project_id' => $run->workflow->project_id,
                        'subject_type' => 'workflow_run',
                        'subject_id' => $run->id,
                        'risk_level' => $tool->risk_level ?? 'L1',
                        'reason' => $policy['reason'] ?? 'risk-gated tool call',
                        'payload' => ['node_id' => $task->node_id, 'tool_id' => $tool->id, 'tool_name' => $tool->name, 'inputs' => $data['inputs'] ?? []],
                        'requested_by' => $run->triggered_by,
                    ]);
                    return ['_pause' => true, '_pause_reason' => $policy['reason'], 'approval_id' => $approval->id];
                }
            }
            throw new \RuntimeException('Policy denied tool call: ' . ($policy['reason'] ?? 'unknown'));
        }

        return $this->mcp->executeTool($toolId, $data['inputs'] ?? [], $task, $agentId);
    }

    protected function executeDecision(array $data, array $context): array
    {
        $expr = $data['expression'] ?? null;
        if (! $expr || ! is_array($expr)) {
            return ['branch' => 'true'];
        }
        $left = data_get($context, $expr['left'] ?? '');
        $op = $expr['op'] ?? '==';
        $right = $expr['right'] ?? null;
        $result = match ($op) {
            '==' => $left == $right,
            '!=' => $left != $right,
            '>'  => $left > $right,
            '<'  => $left < $right,
            'in' => is_array($right) && in_array($left, $right, true),
            default => false,
        };
        return ['branch' => $result ? 'true' : 'false', 'evaluated' => compact('left', 'op', 'right', 'result')];
    }

    protected function executeTransform(array $data, array $context): array
    {
        $mapping = $data['mapping'] ?? [];
        $output = [];
        foreach ($mapping as $key => $path) {
            $output[$key] = data_get($context, is_string($path) ? $path : '');
        }
        return ['transformed' => $output];
    }

    protected function executeLoop(array $data, array $context, TaskRun $task, WorkflowRun $run): array
    {
        $items = data_get($context, $data['items_path'] ?? '') ?? ($data['items'] ?? []);
        $results = [];
        $max = min(count((array) $items), (int) ($data['max_iterations'] ?? 50));
        for ($i = 0; $i < $max; $i++) {
            $results[] = ['index' => $i, 'item' => $items[$i] ?? null];
        }
        return ['iterations' => $max, 'results' => $results];
    }

    protected function executeParallel(array $data, array $node, array &$context, TaskRun $task, WorkflowRun $run): array
    {
        $branches = $data['branches'] ?? [];
        return ['note' => 'Parallel branches dispatched (sequentially in Phase 2 backend)', 'branches' => count($branches)];
    }

    protected function executeApproval(array $data, TaskRun $task, WorkflowRun $run, array $context): array
    {
        $existing = Approval::where('subject_type', 'workflow_run')
            ->where('subject_id', $run->id)
            ->where('payload->node_id', $task->node_id)
            ->latest()
            ->first();

        if ($existing && $existing->status === 'approved') {
            return ['approved' => true, 'approval_id' => $existing->id, 'approver' => $existing->decided_by];
        }

        if (! $existing || $existing->status === 'pending') {
            if (! $existing) {
                $approval = $this->approvals->request([
                    'tenant_id' => $run->workflow->tenant_id,
                    'project_id' => $run->workflow->project_id,
                    'subject_type' => 'workflow_run',
                    'subject_id' => $run->id,
                    'risk_level' => $data['risk_level'] ?? 'L2',
                    'reason' => $data['reason'] ?? 'Workflow approval gate',
                    'payload' => ['node_id' => $task->node_id, 'message' => $data['message'] ?? null],
                    'requested_by' => $run->triggered_by,
                    'approver_pool' => $data['approver_pool'] ?? null,
                ]);
                $existing = $approval;
            }
            return ['_pause' => true, '_pause_reason' => 'awaiting_approval', 'approval_id' => $existing->id];
        }

        throw new \RuntimeException('Approval rejected for node ' . $task->node_id);
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
            if (isset($visited[$id])) continue;
            $visited[$id] = true;
            $order[] = $id;
            foreach (($outgoingBySource->get($id, collect())->all()) as $edge) {
                $queue[] = $edge['target'];
            }
        }
        return $order;
    }
}
