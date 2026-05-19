<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkflowRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RunController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = WorkflowRun::query()->with('workflow');
        if ($id = $request->query('workflow_id')) $q->where('workflow_id', $id);
        if ($s = $request->query('status')) $q->where('status', $s);
        if ($from = $request->query('from')) $q->where('created_at', '>=', $from);
        if ($to = $request->query('to')) $q->where('created_at', '<=', $to);
        return response()->json(['data' => $q->orderByDesc('id')->paginate(50)]);
    }

    public function show(WorkflowRun $run): JsonResponse
    {
        return response()->json($run->load(['workflow', 'tasks.toolCalls']));
    }
}
