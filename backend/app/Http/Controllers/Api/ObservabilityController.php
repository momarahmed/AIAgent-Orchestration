<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkflowRun;
use App\Services\KnowledgeGraphService;
use App\Services\ObservabilityService;
use App\Services\ReplayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ObservabilityController extends Controller
{
    public function __construct(
        protected ObservabilityService $obs,
        protected ReplayService $replays,
        protected KnowledgeGraphService $kg,
    ) {}

    public function metrics(): Response
    {
        return response($this->obs->metricsPrometheus(), 200, ['Content-Type' => 'text/plain; version=0.0.4']);
    }

    public function metricsJson(): JsonResponse
    {
        return response()->json($this->obs->metrics());
    }

    public function timeline(WorkflowRun $run): JsonResponse
    {
        return response()->json(['data' => $this->obs->timeline($run)]);
    }

    public function replay(Request $r, WorkflowRun $run): JsonResponse
    {
        $data = $r->validate([
            'pinned_versions' => 'nullable|array',
            'reason'          => 'nullable|string',
        ]);
        $link = $this->replays->replay($run, $data['pinned_versions'] ?? [], $r->user()?->id, $data['reason'] ?? null);
        return response()->json($link, 202);
    }

    public function knowledgeGraph(Request $r): JsonResponse
    {
        $tenantId = (int) $r->query('tenant_id');
        return response()->json($this->kg->tenantSnapshot($tenantId));
    }
}
