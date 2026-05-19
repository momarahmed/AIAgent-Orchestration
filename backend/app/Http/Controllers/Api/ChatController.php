<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\TaskRun;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Services\AgentRuntime;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase-1 Chat UI backend — single-turn prompt execution with run record (UX-002).
 */
class ChatController extends Controller
{
    public function __construct(protected AgentRuntime $runtime) {}

    public function execute(Request $request): JsonResponse
    {
        $data = $request->validate([
            'prompt' => 'required|string|max:8000',
            'agent_id' => 'nullable|exists:agents,id',
            'project_id' => 'nullable|exists:projects,id',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        $agent = isset($data['agent_id'])
            ? Agent::with('currentVersion')->find($data['agent_id'])
            : Agent::with('currentVersion')->where('slug', 'gis-health')->first();

        if (! $agent) {
            return response()->json(['error' => 'No agent available. Create an agent first.'], 422);
        }

        $workflow = Workflow::firstOrCreate(
            ['project_id' => $agent->project_id, 'slug' => 'chat-session'],
            [
                'tenant_id' => $agent->tenant_id,
                'name' => 'Chat Session',
                'description' => 'Ephemeral chat workflow for single-turn prompts.',
                'trigger_type' => 'chat',
                'status' => 'draft',
            ]
        );

        $version = $workflow->currentVersion ?? WorkflowVersion::create([
            'workflow_id' => $workflow->id,
            'version' => 1,
            'graph_json' => [
                'nodes' => [['id' => 'c1', 'type' => 'trigger', 'data' => ['label' => 'Chat']]],
                'edges' => [],
            ],
            'variables' => [],
            'created_by' => $request->user()?->id,
        ]);
        if (! $workflow->current_version_id) {
            $workflow->update(['current_version_id' => $version->id]);
        }

        $run = WorkflowRun::create([
            'workflow_id' => $workflow->id,
            'workflow_version_id' => $version->id,
            'status' => 'running',
            'input' => ['prompt' => $data['prompt']],
            'started_at' => now(),
            'triggered_by' => $request->user()?->id,
        ]);

        $task = TaskRun::create([
            'workflow_run_id' => $run->id,
            'node_id' => 'chat',
            'node_type' => 'agent',
            'status' => 'running',
            'input' => ['prompt' => $data['prompt']],
            'started_at' => now(),
        ]);

        $start = microtime(true);
        $output = $this->runtime->execute($agent->id, $data['prompt']);
        $duration = (int) ((microtime(true) - $start) * 1000);

        $task->update([
            'status' => isset($output['error']) ? 'failed' : 'completed',
            'output' => $output,
            'error' => $output['error'] ?? null,
            'completed_at' => now(),
            'duration_ms' => $duration,
        ]);

        $run->update([
            'status' => isset($output['error']) ? 'failed' : 'completed',
            'output' => ['result' => $output],
            'error' => $output['error'] ?? null,
            'completed_at' => now(),
        ]);

        Audit::record('run', 'chat.execute', 'workflow', $workflow->id, [
            'run_id' => $run->id,
            'agent_id' => $agent->id,
        ], $request, $agent->tenant_id, $agent->project_id);

        return response()->json([
            'run_id' => $run->id,
            'response' => $output['response'] ?? ($output['error'] ?? null),
            'agent' => $agent->only(['id', 'name', 'slug']),
            'mock' => $output['mock'] ?? false,
            'run' => $run->load('tasks'),
        ]);
    }
}
