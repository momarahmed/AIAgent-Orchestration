<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tool;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Services\AgentRuntime;
use App\Services\DurableWorkflowEngine;
use App\Services\McpGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function __construct(
        protected AgentRuntime $agents,
        protected McpGateway $mcp,
        protected DurableWorkflowEngine $engine,
    ) {}

    public function debugAgent(Request $request, int $agentId): JsonResponse
    {
        $data = $request->validate([
            'prompt' => 'required|string',
            'context' => 'sometimes|array',
        ]);
        $start = microtime(true);
        $output = $this->agents->execute($agentId, $data['prompt'], $data['context'] ?? []);
        $latency = (int) ((microtime(true) - $start) * 1000);
        return response()->json([
            'output' => $output,
            'latency_ms' => $latency,
            'mock' => $output['mock'] ?? false,
        ]);
    }

    public function debugTool(Request $request, Tool $tool): JsonResponse
    {
        $data = $request->validate(['inputs' => 'sometimes|array']);
        $start = microtime(true);
        $output = $this->mcp->executeTool($tool->id, $data['inputs'] ?? [], null, null);
        $latency = (int) ((microtime(true) - $start) * 1000);
        return response()->json([
            'output' => $output,
            'latency_ms' => $latency,
            'tool' => $tool->only(['id', 'name', 'risk_level']),
        ]);
    }

    public function debugWorkflowNode(Request $request, WorkflowRun $run): JsonResponse
    {
        $data = $request->validate([
            'node_id' => 'required|string',
            'input' => 'sometimes|array',
        ]);
        return response()->json($this->engine->debugNode($run, $data['node_id'], $data['input'] ?? []));
    }

    public function replayRun(Request $request, WorkflowRun $run): JsonResponse
    {
        $data = $request->validate([
            'from_node_id' => 'nullable|string',
            'overrides' => 'sometimes|array',
        ]);
        return response()->json($this->engine->replay($run, $data['from_node_id'] ?? null, $data['overrides'] ?? []));
    }

    public function resumeRun(Request $request, WorkflowRun $run): JsonResponse
    {
        $data = $request->validate(['input' => 'sometimes|array']);
        return response()->json($this->engine->resume($run, $data['input'] ?? []));
    }

    public function cancelRun(Request $request, WorkflowRun $run): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string']);
        return response()->json($this->engine->cancel($run, $data['reason'] ?? null));
    }
}
