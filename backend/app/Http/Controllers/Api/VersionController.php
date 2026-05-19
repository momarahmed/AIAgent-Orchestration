<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentVersion;
use App\Models\McpServer;
use App\Models\McpServerVersion;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use App\Services\VersionDiffService;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function __construct(protected VersionDiffService $diff) {}

    public function agentVersions(Agent $agent): JsonResponse
    {
        return response()->json(['data' => $agent->versions()->orderByDesc('version')->get()]);
    }

    public function workflowVersions(Workflow $workflow): JsonResponse
    {
        return response()->json(['data' => $workflow->versions()->orderByDesc('version')->get()]);
    }

    public function mcpVersions(McpServer $mcpServer): JsonResponse
    {
        return response()->json(['data' => $mcpServer->versions()->orderByDesc('version')->get()]);
    }

    public function diffAgent(Agent $agent, int $a, int $b): JsonResponse
    {
        $va = $agent->versions()->where('version', $a)->firstOrFail();
        $vb = $agent->versions()->where('version', $b)->firstOrFail();
        $changes = $this->diff->diff($va->only(['role', 'system_instructions', 'model_config', 'allowed_mcp_servers', 'allowed_tools', 'memory_scope']), $vb->only(['role', 'system_instructions', 'model_config', 'allowed_mcp_servers', 'allowed_tools', 'memory_scope']));
        return response()->json(['from' => $a, 'to' => $b, 'changes' => $changes]);
    }

    public function diffWorkflow(Workflow $workflow, int $a, int $b): JsonResponse
    {
        $va = $workflow->versions()->where('version', $a)->firstOrFail();
        $vb = $workflow->versions()->where('version', $b)->firstOrFail();
        $graphDiff = $this->diff->diffGraph($va->graph_json ?? [], $vb->graph_json ?? []);
        $varDiff = $this->diff->diff($va->variables ?? [], $vb->variables ?? []);
        return response()->json(['from' => $a, 'to' => $b, 'graph' => $graphDiff, 'variables' => $varDiff]);
    }

    public function rollbackAgent(Request $request, Agent $agent, int $version): JsonResponse
    {
        $v = $agent->versions()->where('version', $version)->firstOrFail();
        $agent->update(['current_version_id' => $v->id]);
        Audit::record('update', 'agent.rollback', 'agent', $agent->id, ['rolled_back_to' => $version]);
        return response()->json($agent->fresh(['currentVersion']));
    }

    public function rollbackWorkflow(Request $request, Workflow $workflow, int $version): JsonResponse
    {
        $v = $workflow->versions()->where('version', $version)->firstOrFail();
        $workflow->update(['current_version_id' => $v->id]);
        Audit::record('update', 'workflow.rollback', 'workflow', $workflow->id, ['rolled_back_to' => $version]);
        return response()->json($workflow->fresh(['currentVersion']));
    }

    public function rollbackMcp(Request $request, McpServer $mcpServer, int $version): JsonResponse
    {
        $v = $mcpServer->versions()->where('version', $version)->firstOrFail();
        $mcpServer->update(['current_version_id' => $v->id]);
        Audit::record('update', 'mcp_server.rollback', 'mcp_server', $mcpServer->id, ['rolled_back_to' => $version]);
        return response()->json($mcpServer->fresh(['versions']));
    }
}
