<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\McpServer;
use App\Models\McpServerVersion;
use App\Models\Tool;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class McpServerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = McpServer::query()->with('tools');
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
            'transport' => 'sometimes|in:stdio,http,streamable-http,sse',
            'runtime' => 'sometimes|string|max:60',
            'endpoint' => 'nullable|url',
            'auth_method' => 'sometimes|in:none,api_key,oauth2,bearer',
            'secret_refs' => 'sometimes|array',
            'tools' => 'sometimes|array',
            'tools.*.name' => 'required_with:tools|string',
            'tools.*.description' => 'nullable|string',
            'tools.*.input_schema' => 'nullable|array',
            'tools.*.output_schema' => 'nullable|array',
            'tools.*.risk_level' => 'sometimes|in:L0,L1,L2,L3,L4',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $server = McpServer::create([
                'tenant_id' => $data['tenant_id'],
                'project_id' => $data['project_id'],
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(3)),
                'description' => $data['description'] ?? null,
                'transport' => $data['transport'] ?? 'http',
                'runtime' => $data['runtime'] ?? 'python',
                'endpoint' => $data['endpoint'] ?? null,
                'auth_method' => $data['auth_method'] ?? 'none',
                'secret_refs' => $data['secret_refs'] ?? [],
                'status' => 'draft',
                'health' => 'unknown',
            ]);

            $v = McpServerVersion::create([
                'mcp_server_id' => $server->id,
                'version' => 1,
                'config' => $server->only(['transport', 'runtime', 'endpoint', 'auth_method', 'secret_refs']),
                'created_by' => $request->user()?->id,
            ]);
            $server->update(['current_version_id' => $v->id]);

            foreach ($data['tools'] ?? [] as $tool) {
                Tool::create([
                    'mcp_server_id' => $server->id,
                    'name' => $tool['name'],
                    'description' => $tool['description'] ?? null,
                    'input_schema' => $tool['input_schema'] ?? null,
                    'output_schema' => $tool['output_schema'] ?? null,
                    'risk_level' => $tool['risk_level'] ?? 'L1',
                    'is_enabled' => true,
                ]);
            }

            Audit::record('create', 'mcp_server.create', 'mcp_server', $server->id, $data, $request, $server->tenant_id, $server->project_id);
            return response()->json($server->load('tools'), 201);
        });
    }

    public function show(McpServer $mcpServer): JsonResponse
    {
        return response()->json($mcpServer->load(['tools', 'versions']));
    }

    public function update(Request $request, McpServer $mcpServer): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:160',
            'description' => 'nullable|string',
            'transport' => 'sometimes|in:stdio,http,streamable-http,sse',
            'runtime' => 'sometimes|string|max:60',
            'endpoint' => 'nullable|url',
            'auth_method' => 'sometimes|in:none,api_key,oauth2,bearer',
            'secret_refs' => 'sometimes|array',
            'status' => 'sometimes|in:draft,staging,production,archived',
        ]);
        $mcpServer->update($data);
        Audit::record('update', 'mcp_server.update', 'mcp_server', $mcpServer->id, $data, $request, $mcpServer->tenant_id, $mcpServer->project_id);
        return response()->json($mcpServer->fresh()->load('tools'));
    }

    public function destroy(Request $request, McpServer $mcpServer): JsonResponse
    {
        $mcpServer->delete();
        Audit::record('delete', 'mcp_server.archive', 'mcp_server', $mcpServer->id, [], $request, $mcpServer->tenant_id, $mcpServer->project_id);
        return response()->json(['ok' => true]);
    }

    public function healthCheck(Request $request, McpServer $mcpServer): JsonResponse
    {
        $start = microtime(true);
        $health = 'unknown';
        $detail = null;
        try {
            if ($mcpServer->endpoint) {
                $response = Http::timeout(5)->withOptions(['verify' => false])->get($mcpServer->endpoint);
                $health = $response->successful() ? 'healthy' : 'degraded';
                $detail = ['status' => $response->status(), 'latency_ms' => (int) ((microtime(true) - $start) * 1000)];
            } else {
                $health = 'no_endpoint';
            }
        } catch (\Throwable $e) {
            $health = 'unhealthy';
            $detail = ['error' => $e->getMessage()];
        }
        $mcpServer->update(['health' => $health, 'last_health_check_at' => now()]);
        Audit::record('health', 'mcp_server.health_check', 'mcp_server', $mcpServer->id, ['health' => $health, 'detail' => $detail], $request, $mcpServer->tenant_id, $mcpServer->project_id);
        return response()->json(['health' => $health, 'detail' => $detail, 'checked_at' => $mcpServer->last_health_check_at]);
    }
}
