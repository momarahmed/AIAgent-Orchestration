<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetaAgentRun;
use App\Services\IntentRouterService;
use App\Services\MetaAgentOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Meta-Agent HTTP surface (PRD §12.2 + Scenario 27.1 — Phase 4).
 *
 * - POST /api/meta-agents/route   — Intent Router classifies the prompt.
 * - POST /api/meta-agents/run     — Start a meta-agent run.
 * - POST /api/meta-agents/{id}/resume — Resume after approval.
 * - GET  /api/meta-agents/{id}    — Inspect a run + its actions.
 */
class MetaAgentController extends Controller
{
    public function __construct(
        protected IntentRouterService $intent,
        protected MetaAgentOrchestrator $orchestrator,
    ) {}

    public function route(Request $r): JsonResponse
    {
        $data = $r->validate(['prompt' => 'required|string']);
        return response()->json($this->intent->classify($data['prompt']));
    }

    public function start(Request $r): JsonResponse
    {
        $data = $r->validate([
            'tenant_id'  => 'required|integer|exists:tenants,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'meta_agent' => 'required|string',
            'prompt'     => 'required|string',
            'plan'       => 'nullable|array',
            'autonomous' => 'boolean',
        ]);
        $run = $this->orchestrator->start(
            $data['tenant_id'],
            $data['project_id'] ?? null,
            $r->user()?->id,
            $data['meta_agent'],
            $data['prompt'],
            $data['plan'] ?? []
        );

        if ($data['autonomous'] ?? true) {
            $run = $this->orchestrator->execute($run);
        }
        return response()->json($run->load('actions'), 202);
    }

    public function autopilot(Request $r): JsonResponse
    {
        // Convenience entry-point used by the Chat → "create something" UI.
        $data = $r->validate([
            'tenant_id'  => 'required|integer|exists:tenants,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'prompt'     => 'required|string',
        ]);
        $intent = $this->intent->classify($data['prompt']);
        if ($intent['target'] === 'chat') {
            return response()->json(['intent' => $intent, 'chat' => true]);
        }
        $run = $this->orchestrator->start(
            $data['tenant_id'],
            $data['project_id'] ?? null,
            $r->user()?->id,
            $intent['target'],
            $data['prompt']
        );
        $run = $this->orchestrator->execute($run);
        return response()->json(['intent' => $intent, 'run' => $run->load('actions')]);
    }

    public function show(MetaAgentRun $run): JsonResponse
    {
        return response()->json($run->load('actions'));
    }

    public function index(Request $r): JsonResponse
    {
        $q = MetaAgentRun::query();
        if ($r->query('tenant_id')) $q->where('tenant_id', $r->query('tenant_id'));
        if ($r->query('meta_agent')) $q->where('meta_agent', $r->query('meta_agent'));
        if ($r->query('status')) $q->where('status', $r->query('status'));
        return response()->json(['data' => $q->orderByDesc('created_at')->paginate(50)->items()]);
    }

    public function resume(MetaAgentRun $run): JsonResponse
    {
        return response()->json($this->orchestrator->resume($run)->load('actions'));
    }

    public function registry(): JsonResponse
    {
        $entries = [];
        foreach (MetaAgentOrchestrator::REGISTRY as $kind => $class) {
            $agent = $this->orchestrator->resolveAgent($kind);
            $entries[] = [
                'kind'        => $kind,
                'class'       => $class,
                'description' => $agent?->description() ?? '',
            ];
        }
        return response()->json(['data' => $entries]);
    }
}
