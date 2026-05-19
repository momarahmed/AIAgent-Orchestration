<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModelRecord;
use App\Models\ModelRoutingRule;
use App\Services\ModelRegistryService;
use App\Services\ModelRouter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Model Control Plane Controller (Phase 4 — PRD §9.2).
 *
 * Exposes the model catalog, routing rules, and a `plan` endpoint that
 * returns which model would be selected for a given request — used by
 * the Agent Studio "select model" UI.
 */
class ModelRegistryController extends Controller
{
    public function __construct(
        protected ModelRegistryService $registry,
        protected ModelRouter $router,
    ) {}

    public function models(Request $r): JsonResponse
    {
        return response()->json(['data' => $this->registry->all($r->query('tenant_id'))]);
    }

    public function showModel(string $slug): JsonResponse
    {
        $m = $this->registry->find($slug);
        abort_if(! $m, 404);
        return response()->json($m);
    }

    public function storeModel(Request $r): JsonResponse
    {
        $data = $r->validate([
            'slug'             => 'required|string|unique:models,slug',
            'provider'         => 'required|string',
            'name'             => 'required|string',
            'family'           => 'nullable|string',
            'capabilities'     => 'array',
            'context_window'   => 'integer',
            'cost_per_1k_in'   => 'numeric',
            'cost_per_1k_out'  => 'numeric',
            'latency_p50_ms'   => 'integer',
            'is_local'         => 'boolean',
        ]);
        return response()->json(ModelRecord::create($data), 201);
    }

    public function rules(Request $r): JsonResponse
    {
        $q = ModelRoutingRule::query();
        if ($r->query('tenant_id')) $q->where(fn ($x) => $x->where('tenant_id', $r->query('tenant_id'))->orWhereNull('tenant_id'));
        return response()->json(['data' => $q->orderBy('priority')->get()]);
    }

    public function storeRule(Request $r): JsonResponse
    {
        $data = $r->validate([
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'name'      => 'required|string',
            'priority'  => 'integer',
            'match'     => 'required|array',
            'route'     => 'required|array',
            'is_active' => 'boolean',
        ]);
        return response()->json(ModelRoutingRule::create($data), 201);
    }

    public function plan(Request $r): JsonResponse
    {
        $req = $r->validate([
            'tenant_id'        => 'nullable|integer',
            'capabilities'     => 'array',
            'max_cost_per_1k'  => 'nullable|numeric',
            'max_latency_ms'   => 'nullable|integer',
            'residency'        => 'nullable|string',
            'tag'              => 'nullable|string',
            'hint_slug'        => 'nullable|string',
        ]);
        $plan = $this->router->plan($req);
        return response()->json([
            'primary'  => $plan['primary'],
            'fallback' => $plan['fallback'],
            'rule_id'  => $plan['rule_id'] ?? null,
        ]);
    }

    public function complete(Request $r): JsonResponse
    {
        $req = $r->validate([
            'tenant_id'    => 'nullable|integer',
            'capabilities' => 'array',
            'hint_slug'    => 'nullable|string',
            'prompt'       => 'required|string',
            'system'       => 'nullable|string',
        ]);
        $result = $this->router->complete(
            $req,
            ['system' => $req['system'] ?? null, 'prompt' => $req['prompt']]
        );
        return response()->json($result);
    }
}
