<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\TaskRun;
use App\Models\Tool;
use App\Models\ToolCall;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Workflow::query()->with('currentVersion');
        if ($id = $request->query('project_id')) $query->where('project_id', $id);
        if ($id = $request->query('tenant_id'))  $query->where('tenant_id', $id);
        if ($s = $request->query('q')) $query->where('name', 'like', "%{$s}%");
        return response()->json(['data' => $query->orderByDesc('updated_at')->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:160',
            'description' => 'nullable|string',
            'trigger_type' => 'sometimes|in:manual,schedule,webhook,event',
            'graph_json' => 'sometimes|array',
            'variables' => 'sometimes|array',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $wf = Workflow::create([
                'tenant_id' => $data['tenant_id'],
                'project_id' => $data['project_id'],
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(3)),
                'description' => $data['description'] ?? null,
                'trigger_type' => $data['trigger_type'] ?? 'manual',
                'status' => 'draft',
            ]);
            $v = WorkflowVersion::create([
                'workflow_id' => $wf->id,
                'version' => 1,
                'graph_json' => $data['graph_json'] ?? ['nodes' => [], 'edges' => []],
                'variables' => $data['variables'] ?? [],
                'created_by' => $request->user()?->id,
            ]);
            $wf->update(['current_version_id' => $v->id]);
            Audit::record('create', 'workflow.create', 'workflow', $wf->id, $data, $request, $wf->tenant_id, $wf->project_id);
            return response()->json($wf->load('currentVersion'), 201);
        });
    }

    public function show(Workflow $workflow): JsonResponse
    {
        return response()->json($workflow->load(['currentVersion', 'versions']));
    }

    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:160',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,staging,production,archived',
            'trigger_type' => 'sometimes|in:manual,schedule,webhook,event',
            'graph_json' => 'sometimes|array',
            'variables' => 'sometimes|array',
        ]);

        return DB::transaction(function () use ($workflow, $data, $request) {
            $workflow->update(array_intersect_key($data, array_flip(['name', 'description', 'status', 'trigger_type'])));
            if (array_key_exists('graph_json', $data) || array_key_exists('variables', $data)) {
                $latest = $workflow->versions()->max('version') ?? 0;
                $base = $workflow->currentVersion?->only(['graph_json', 'variables']) ?? [];
                $version = WorkflowVersion::create(array_merge($base, [
                    'workflow_id' => $workflow->id,
                    'version' => $latest + 1,
                    'graph_json' => $data['graph_json'] ?? ($base['graph_json'] ?? ['nodes' => [], 'edges' => []]),
                    'variables' => $data['variables'] ?? ($base['variables'] ?? []),
                    'created_by' => $request->user()?->id,
                ]));
                $workflow->update(['current_version_id' => $version->id]);
            }
            Audit::record('update', 'workflow.update', 'workflow', $workflow->id, $data, $request, $workflow->tenant_id, $workflow->project_id);
            return response()->json($workflow->fresh()->load(['currentVersion', 'versions']));
        });
    }

    public function destroy(Request $request, Workflow $workflow): JsonResponse
    {
        $workflow->delete();
        Audit::record('delete', 'workflow.archive', 'workflow', $workflow->id, [], $request, $workflow->tenant_id, $workflow->project_id);
        return response()->json(['ok' => true]);
    }

    /**
     * Execute a workflow synchronously. This is the Phase-1 in-process runner —
     * a pragmatic LangGraph-style traversal of the DAG. Long-running / durable
     * execution lands in Phase 2 (Temporal).
     */
    public function run(Request $request, Workflow $workflow): JsonResponse
    {
        $payload = $request->validate([
            'input' => 'sometimes|array',
        ]);
        $input = $payload['input'] ?? [];

        $version = $workflow->currentVersion;
        if (! $version) {
            return response()->json(['error' => 'Workflow has no version to execute.'], 422);
        }

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'status' => 'running',
            'input' => $input,
            'started_at' => now(),
            'triggered_by' => $request->user()?->id,
        ]);

        $graph = $version->graph_json ?? ['nodes' => [], 'edges' => []];
        $nodes = collect($graph['nodes'] ?? [])->keyBy('id');
        $edges = collect($graph['edges'] ?? []);

        // Build a simple topological order: roots = nodes with no incoming edge.
        $incomingByTarget = $edges->groupBy('target');
        $outgoingBySource = $edges->groupBy('source');
        $roots = $nodes->keys()->filter(fn ($id) => $incomingByTarget->get($id, collect())->isEmpty())->values();
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

        $context = ['input' => $input, 'nodes' => []];
        $finalOutput = null;
        $hadError = false;

        foreach ($order as $nodeId) {
            $node = $nodes->get($nodeId);
            if (! $node) continue;

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
                $output = $this->executeNode($node, $context, $task);
                $duration = (int) ((microtime(true) - $start) * 1000);
                $task->update([
                    'status' => 'completed',
                    'output' => $output,
                    'completed_at' => now(),
                    'duration_ms' => $duration,
                ]);
                $context['nodes'][$nodeId] = $output;
                $finalOutput = $output;
            } catch (\Throwable $e) {
                $duration = (int) ((microtime(true) - $start) * 1000);
                $task->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'completed_at' => now(),
                    'duration_ms' => $duration,
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

        Audit::record('run', 'workflow.run', 'workflow', $workflow->id, ['run_id' => $run->id, 'status' => $run->status], $request, $workflow->tenant_id, $workflow->project_id);
        return response()->json($run->load('tasks'));
    }

    protected function executeNode(array $node, array $context, TaskRun $task): array
    {
        $type = $node['type'] ?? 'unknown';
        $data = $node['data'] ?? [];

        return match ($type) {
            'trigger' => ['triggered' => true, 'input' => $context['input']],
            'agent' => $this->runAgentNode($data, $context, $task),
            'mcp_tool' => $this->runToolNode($data, $context, $task),
            'condition' => ['branch' => $this->evaluateCondition($data, $context)],
            'approval' => ['status' => 'auto_approved_phase1', 'note' => 'Approval queue lands in Phase 2'],
            default => ['note' => "Node type {$type} not implemented in Phase 1 runner"],
        };
    }

    protected function runAgentNode(array $data, array $context, TaskRun $task): array
    {
        $agentId = $data['agent_id'] ?? null;
        $agent = $agentId ? Agent::with('currentVersion')->find($agentId) : null;
        $prompt = $data['prompt'] ?? ($context['input']['prompt'] ?? 'Hello');

        // Phase-1 model call placeholder — returns a deterministic mock response
        // unless OPENAI_API_KEY is set, in which case a single chat completion
        // is performed. Full provider adapters land in Phase 2/4.
        $apiKey = env('OPENAI_API_KEY');
        $model = $agent?->currentVersion?->model_config['model'] ?? 'gpt-4o-mini';
        if ($apiKey) {
            try {
                $resp = \Illuminate\Support\Facades\Http::withToken($apiKey)
                    ->timeout(30)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $agent?->currentVersion?->system_instructions ?? 'You are a helpful enterprise AI agent.'],
                            ['role' => 'user', 'content' => is_string($prompt) ? $prompt : json_encode($prompt)],
                        ],
                    ]);
                if ($resp->successful()) {
                    return [
                        'agent_id' => $agentId,
                        'model' => $model,
                        'response' => $resp->json('choices.0.message.content'),
                        'usage' => $resp->json('usage'),
                    ];
                }
                return ['agent_id' => $agentId, 'error' => 'Provider call failed', 'status' => $resp->status()];
            } catch (\Throwable $e) {
                return ['agent_id' => $agentId, 'error' => $e->getMessage()];
            }
        }
        return [
            'agent_id' => $agentId,
            'model' => $model,
            'response' => "[mock] Agent '{$agent?->name}' processed prompt: " . (is_string($prompt) ? $prompt : json_encode($prompt)),
            'mock' => true,
        ];
    }

    protected function runToolNode(array $data, array $context, TaskRun $task): array
    {
        $toolId = $data['tool_id'] ?? null;
        $tool = $toolId ? Tool::with('server')->find($toolId) : null;
        $start = microtime(true);
        $output = ['tool' => $tool?->name, 'mock' => true, 'inputs' => $data['inputs'] ?? []];
        $duration = (int) ((microtime(true) - $start) * 1000);
        ToolCall::create([
            'task_run_id' => $task->id,
            'tool_id' => $tool?->id,
            'mcp_server_id' => $tool?->mcp_server_id,
            'tool_name' => $tool?->name ?? 'unknown',
            'input' => $data['inputs'] ?? [],
            'output' => $output,
            'status' => 'completed',
            'duration_ms' => $duration,
        ]);
        return $output;
    }

    protected function evaluateCondition(array $data, array $context): string
    {
        return ($data['expression'] ?? '') ? 'true' : 'false';
    }
}
