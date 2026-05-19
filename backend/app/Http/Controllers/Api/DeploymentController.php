<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Services\DeploymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeploymentController extends Controller
{
    public function __construct(protected DeploymentService $deployments) {}

    public function index(Request $request): JsonResponse
    {
        $q = Deployment::query();
        foreach (['tenant_id', 'project_id', 'asset_type', 'asset_id', 'environment', 'status'] as $f) {
            if ($v = $request->query($f)) $q->where($f, $v);
        }
        return response()->json(['data' => $q->orderByDesc('id')->paginate(50)]);
    }

    public function show(Deployment $deployment): JsonResponse
    {
        return response()->json($deployment);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'project_id' => 'required|exists:projects,id',
            'asset_type' => 'required|in:agent,mcp_server,workflow',
            'asset_id' => 'required|integer',
            'asset_version_id' => 'nullable|integer',
            'environment' => 'required|in:dev,test,staging,prod',
            'config' => 'sometimes|array',
            'secret_refs' => 'sometimes|array',
            'notes' => 'nullable|string',
        ]);
        $deployment = $this->deployments->plan($data, $request->user()?->id);
        return response()->json($this->deployments->execute($deployment, $request->user()?->id), 201);
    }

    public function approve(Request $request, Deployment $deployment): JsonResponse
    {
        return response()->json($this->deployments->approveAndContinue($deployment, $request->user()->id));
    }

    public function rollback(Request $request, Deployment $deployment): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string']);
        return response()->json($this->deployments->rollback($deployment, $request->user()->id, $data['reason'] ?? null));
    }
}
