<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentQualityScore;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsService $analytics) {}

    public function usage(Request $request): JsonResponse
    {
        return response()->json($this->analytics->usage(
            $request->user()?->tenant_id,
            (int) $request->query('days', 30),
        ));
    }

    public function cost(Request $request): JsonResponse
    {
        return response()->json($this->analytics->cost(
            $request->user()?->tenant_id,
            (int) $request->query('days', 30),
        ));
    }

    public function reliability(Request $request): JsonResponse
    {
        return response()->json($this->analytics->reliability(
            $request->user()?->tenant_id,
            (int) $request->query('days', 30),
        ));
    }

    public function templateAdoption(Request $request): JsonResponse
    {
        return response()->json($this->analytics->templateAdoption(
            $request->user()?->tenant_id,
            (int) $request->query('days', 90),
        ));
    }

    public function qualityScores(Request $request): JsonResponse
    {
        $type = $request->query('subject_type');
        $q = AgentQualityScore::query()->orderByDesc('composite_score');
        if ($type) $q->where('subject_type', $type);
        return response()->json($q->limit(100)->get());
    }

    public function recompute(): JsonResponse
    {
        $n = $this->analytics->computeQualityScores();
        return response()->json(['recomputed' => $n]);
    }
}
