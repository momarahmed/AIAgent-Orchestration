<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpaPolicy;
use App\Services\OpaPolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpaPolicyController extends Controller
{
    public function __construct(protected OpaPolicyService $opaSvc) {}

    public function index(Request $request): JsonResponse
    {
        $query = OpaPolicy::with('creator');

        if ($request->query('category')) {
            $query->where('category', $request->query('category'));
        }
        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->query('tenant_id')) {
            $query->where(fn ($q) => $q->where('tenant_id', $request->query('tenant_id'))->orWhereNull('tenant_id'));
        }

        return response()->json($query->orderBy('category')->orderBy('name')->paginate(50));
    }

    public function show(OpaPolicy $opaPolicy): JsonResponse
    {
        return response()->json($opaPolicy->load(['creator', 'versions']));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'    => 'nullable|exists:tenants,id',
            'name'         => 'required|string|max:255',
            'slug'         => 'nullable|string|max:255|unique:opa_policies,slug',
            'category'     => 'required|in:tool_risk,deployment,approval,network,template_import,tenant_isolation',
            'description'  => 'nullable|string',
            'rego_code'    => 'required|string',
            'package_path' => 'nullable|string',
            'metadata'     => 'nullable|array',
        ]);

        $policy = $this->opaSvc->createPolicy($data, $request->user()->id);

        return response()->json($policy, 201);
    }

    public function update(Request $request, OpaPolicy $opaPolicy): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'string|max:255',
            'description'  => 'nullable|string',
            'rego_code'    => 'string',
            'package_path' => 'nullable|string',
            'metadata'     => 'nullable|array',
        ]);

        $policy = $this->opaSvc->updatePolicy($opaPolicy, $data, $request->user()->id);

        return response()->json($policy);
    }

    public function destroy(OpaPolicy $opaPolicy): JsonResponse
    {
        $opaPolicy->delete();
        return response()->json(null, 204);
    }

    public function activate(OpaPolicy $opaPolicy, Request $request): JsonResponse
    {
        $policy = $this->opaSvc->activatePolicy($opaPolicy, $request->user()->id);
        return response()->json($policy);
    }

    public function disable(OpaPolicy $opaPolicy, Request $request): JsonResponse
    {
        $policy = $this->opaSvc->disablePolicy($opaPolicy, $request->user()->id);
        return response()->json($policy);
    }

    public function dryRun(Request $request, OpaPolicy $opaPolicy): JsonResponse
    {
        $data = $request->validate([
            'input' => 'required|array',
        ]);

        $result = $this->opaSvc->dryRun($opaPolicy, $data['input']);

        return response()->json($result);
    }

    public function lint(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rego_code' => 'required|string',
        ]);

        return response()->json($this->opaSvc->lintRego($data['rego_code']));
    }

    public function library(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id') ? (int) $request->query('tenant_id') : null;
        return response()->json($this->opaSvc->getPolicyLibrary($tenantId));
    }
}
