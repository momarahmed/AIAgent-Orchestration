<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentVersion;
use App\Models\McpServer;
use App\Models\McpServerVersion;
use App\Models\Tool;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CopyController extends Controller
{
    public function copyAgent(Request $request, Agent $agent): JsonResponse
    {
        $name = $request->input('name', $agent->name . ' (Copy)');
        return DB::transaction(function () use ($agent, $name, $request) {
            $copy = Agent::create([
                'tenant_id' => $agent->tenant_id,
                'project_id' => $agent->project_id,
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::lower(Str::random(3)),
                'description' => $agent->description,
                'risk_level' => $agent->risk_level,
                'max_risk_level_without_approval' => $agent->max_risk_level_without_approval,
                'status' => 'draft',
            ]);
            $base = $agent->currentVersion?->toArray() ?? [];
            unset($base['id'], $base['agent_id'], $base['version'], $base['created_at'], $base['updated_at']);
            $version = AgentVersion::create(array_merge($base, [
                'agent_id' => $copy->id,
                'version' => 1,
                'created_by' => $request->user()?->id,
            ]));
            $copy->update(['current_version_id' => $version->id]);
            Audit::record('create', 'agent.copy', 'agent', $copy->id, ['source_agent_id' => $agent->id]);
            return response()->json($copy->fresh(['currentVersion']), 201);
        });
    }

    public function copyMcp(Request $request, McpServer $mcpServer): JsonResponse
    {
        $name = $request->input('name', $mcpServer->name . ' (Copy)');
        return DB::transaction(function () use ($mcpServer, $name, $request) {
            $copy = McpServer::create([
                'tenant_id' => $mcpServer->tenant_id,
                'project_id' => $mcpServer->project_id,
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::lower(Str::random(3)),
                'description' => $mcpServer->description,
                'transport' => $mcpServer->transport,
                'runtime' => $mcpServer->runtime,
                'auth_method' => $mcpServer->auth_method,
                'endpoint' => null, // force re-mapping
                'secret_refs' => null, // strip
                'status' => 'draft',
                'health' => 'unknown',
            ]);
            McpServerVersion::create([
                'mcp_server_id' => $copy->id,
                'version' => 1,
                'config' => $copy->only(['transport', 'runtime', 'endpoint', 'auth_method']),
                'created_by' => $request->user()?->id,
            ]);
            foreach ($mcpServer->tools as $tool) {
                Tool::create([
                    'mcp_server_id' => $copy->id,
                    'name' => $tool->name,
                    'description' => $tool->description,
                    'risk_level' => $tool->risk_level,
                    'input_schema' => $tool->input_schema,
                    'output_schema' => $tool->output_schema,
                    'is_enabled' => $tool->is_enabled,
                ]);
            }
            Audit::record('create', 'mcp_server.copy', 'mcp_server', $copy->id, ['source_id' => $mcpServer->id]);
            return response()->json($copy->fresh(['tools']), 201);
        });
    }

    public function copyWorkflow(Request $request, Workflow $workflow): JsonResponse
    {
        $name = $request->input('name', $workflow->name . ' (Copy)');
        return DB::transaction(function () use ($workflow, $name, $request) {
            $copy = Workflow::create([
                'tenant_id' => $workflow->tenant_id,
                'project_id' => $workflow->project_id,
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::lower(Str::random(3)),
                'description' => $workflow->description,
                'trigger_type' => $workflow->trigger_type,
                'status' => 'draft',
            ]);
            $version = WorkflowVersion::create([
                'workflow_id' => $copy->id,
                'version' => 1,
                'graph_json' => $workflow->currentVersion?->graph_json ?? [],
                'variables' => $workflow->currentVersion?->variables ?? [],
                'created_by' => $request->user()?->id,
            ]);
            $copy->update(['current_version_id' => $version->id]);
            Audit::record('create', 'workflow.copy', 'workflow', $copy->id, ['source_id' => $workflow->id]);
            return response()->json($copy->fresh(['currentVersion']), 201);
        });
    }
}
