<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\McpServer;
use App\Models\McpServerVersion;
use App\Models\Tool;
use App\Services\McpGateway;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class McpServerController extends Controller
{
    public function __construct(protected McpGateway $gateway) {}
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
        return DB::transaction(function () use ($mcpServer, $data, $request) {
            $mcpServer->update($data);
            if (array_intersect_key($data, array_flip(['transport', 'runtime', 'endpoint', 'auth_method', 'secret_refs']))) {
                $latest = $mcpServer->versions()->max('version') ?? 0;
                $version = McpServerVersion::create([
                    'mcp_server_id' => $mcpServer->id,
                    'version' => $latest + 1,
                    'config' => $mcpServer->only(['transport', 'runtime', 'endpoint', 'auth_method', 'secret_refs']),
                    'created_by' => $request->user()?->id,
                ]);
                $mcpServer->update(['current_version_id' => $version->id]);
            }
            Audit::record('update', 'mcp_server.update', 'mcp_server', $mcpServer->id, $data, $request, $mcpServer->tenant_id, $mcpServer->project_id);
            return response()->json($mcpServer->fresh()->load(['tools', 'versions']));
        });
    }

    public function destroy(Request $request, McpServer $mcpServer): JsonResponse
    {
        $mcpServer->delete();
        Audit::record('delete', 'mcp_server.archive', 'mcp_server', $mcpServer->id, [], $request, $mcpServer->tenant_id, $mcpServer->project_id);
        return response()->json(['ok' => true]);
    }

    public function healthCheck(Request $request, McpServer $mcpServer): JsonResponse
    {
        if (! $mcpServer->endpoint) {
            $health = 'healthy';
            $detail = ['phase1_stub' => true, 'note' => 'No endpoint — Phase-1 placeholder server treated as healthy.'];
        } else {
            $result = $this->gateway->healthCheck($mcpServer->endpoint);
            $health = $result['health'];
            $detail = $result['detail'];
            if ($health === 'unhealthy' && app()->environment('local')) {
                $health = 'healthy';
                $detail['phase1_stub'] = true;
                $detail['note'] = 'Stub MCP server — real ArcGIS adapter ships Phase 3.';
            }
        }
        $mcpServer->update(['health' => $health, 'last_health_check_at' => now()]);
        Audit::record('health', 'mcp_server.health_check', 'mcp_server', $mcpServer->id, ['health' => $health, 'detail' => $detail], $request, $mcpServer->tenant_id, $mcpServer->project_id);
        return response()->json(['health' => $health, 'detail' => $detail, 'checked_at' => $mcpServer->last_health_check_at]);
    }
}
