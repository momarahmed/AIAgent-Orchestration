<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\McpServer;
use App\Models\NetworkAllowlist;
use App\Services\NetworkPolicyService;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkPolicyController extends Controller
{
    public function __construct(protected NetworkPolicyService $netPolSvc) {}

    public function index(Request $request): JsonResponse
    {
        $query = NetworkAllowlist::with('mcpServer');

        if ($request->query('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }
        if ($request->query('mcp_server_id')) {
            $query->where('mcp_server_id', $request->query('mcp_server_id'));
        }
        if ($request->query('environment')) {
            $query->where('environment', $request->query('environment'));
        }

        return response()->json($query->orderBy('environment')->orderBy('host')->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'     => 'required|exists:tenants,id',
            'mcp_server_id' => 'required|exists:mcp_servers,id',
            'environment'   => 'required|in:dev,test,staging,prod',
            'direction'     => 'in:egress,ingress',
            'host'          => 'required|string',
            'port'          => 'nullable|integer|min:1|max:65535',
            'protocol'      => 'in:tcp,udp',
            'description'   => 'nullable|string',
        ]);

        $entry = NetworkAllowlist::create(array_merge($data, [
            'created_by' => $request->user()->id,
        ]));

        Audit::record('create', 'network_allowlist_created', 'network_allowlist', $entry->id, [
            'mcp_server_id' => $data['mcp_server_id'],
            'host'          => $data['host'],
            'environment'   => $data['environment'],
        ]);

        return response()->json($entry, 201);
    }

    public function update(Request $request, NetworkAllowlist $networkAllowlist): JsonResponse
    {
        $data = $request->validate([
            'host'        => 'string',
            'port'        => 'nullable|integer|min:1|max:65535',
            'protocol'    => 'in:tcp,udp',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $networkAllowlist->update($data);
        return response()->json($networkAllowlist);
    }

    public function destroy(NetworkAllowlist $networkAllowlist): JsonResponse
    {
        Audit::record('delete', 'network_allowlist_deleted', 'network_allowlist', $networkAllowlist->id);
        $networkAllowlist->delete();
        return response()->json(null, 204);
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mcp_server_id' => 'required|exists:mcp_servers,id',
            'host'          => 'required|string',
            'port'          => 'nullable|integer',
            'environment'   => 'required|in:dev,test,staging,prod',
        ]);

        $allowed = $this->netPolSvc->isAllowed(
            $data['mcp_server_id'],
            $data['host'],
            $data['port'] ?? null,
            $data['environment'],
        );

        return response()->json(['allowed' => $allowed]);
    }

    public function generateK8sPolicy(McpServer $mcpServer, Request $request): JsonResponse
    {
        $environment = $request->query('environment', 'prod');
        $policy = $this->netPolSvc->generateK8sNetworkPolicy($mcpServer, $environment);
        return response()->json($policy);
    }
}
