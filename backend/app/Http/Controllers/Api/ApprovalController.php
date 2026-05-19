<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(protected ApprovalService $approvals) {}

    public function index(Request $request): JsonResponse
    {
        $q = Approval::query();
        if ($s = $request->query('status')) $q->where('status', $s);
        if ($t = $request->query('subject_type')) $q->where('subject_type', $t);
        if ($id = $request->query('tenant_id')) $q->where('tenant_id', $id);
        return response()->json(['data' => $q->orderByDesc('id')->paginate(50)]);
    }

    public function show(Approval $approval): JsonResponse
    {
        return response()->json($approval);
    }

    public function approve(Request $request, Approval $approval): JsonResponse
    {
        $data = $request->validate(['comment' => 'nullable|string']);
        return response()->json($this->approvals->approve($approval, $request->user()?->id, $data['comment'] ?? null));
    }

    public function reject(Request $request, Approval $approval): JsonResponse
    {
        $data = $request->validate(['comment' => 'nullable|string']);
        return response()->json($this->approvals->reject($approval, $request->user()?->id, $data['comment'] ?? null));
    }
}
