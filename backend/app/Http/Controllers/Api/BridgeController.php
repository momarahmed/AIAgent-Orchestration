<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BridgeConnection;
use App\Services\BridgeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BridgeController extends Controller
{
    public function __construct(protected BridgeRegistry $registry) {}

    public function frameworks(): JsonResponse
    {
        return response()->json(['data' => $this->registry->frameworks()]);
    }

    public function connections(Request $r): JsonResponse
    {
        $q = BridgeConnection::query();
        if ($r->query('tenant_id')) $q->where('tenant_id', $r->query('tenant_id'));
        if ($r->query('framework')) $q->where('framework', $r->query('framework'));
        return response()->json(['data' => $q->orderByDesc('updated_at')->get()]);
    }

    public function storeConnection(Request $r): JsonResponse
    {
        $data = $r->validate([
            'tenant_id'    => 'required|integer|exists:tenants,id',
            'framework'    => 'required|in:dify,flowise,sim,crewai',
            'name'         => 'required|string',
            'endpoint_url' => 'nullable|string',
            'secret_ref'   => 'nullable|string',
            'config'       => 'nullable|array',
            'enabled'      => 'boolean',
            'requires_approval' => 'boolean',
        ]);
        return response()->json(BridgeConnection::create(array_merge($data, [
            'created_by' => $r->user()?->id,
        ])), 201);
    }

    public function call(Request $r, BridgeConnection $connection): JsonResponse
    {
        $data = $r->validate([
            'action'          => 'required|string',
            'input'           => 'nullable|array',
            'workflow_run_id' => 'nullable|integer',
        ]);
        $exec = $this->registry->call($connection, $data['action'], $data['input'] ?? [], $data['workflow_run_id'] ?? null);
        return response()->json($exec, 202);
    }

    public function toggle(Request $r, BridgeConnection $connection): JsonResponse
    {
        $connection->update(['enabled' => (bool) $r->input('enabled', ! $connection->enabled)]);
        return response()->json($connection->fresh());
    }
}
