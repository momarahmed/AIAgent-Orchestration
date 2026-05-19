<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = AuditEvent::query();
        if ($id = $request->query('tenant_id')) $q->where('tenant_id', $id);
        if ($id = $request->query('project_id')) $q->where('project_id', $id);
        if ($t = $request->query('event_type')) $q->where('event_type', $t);
        if ($s = $request->query('subject_type')) $q->where('subject_type', $s);
        return response()->json(['data' => $q->orderByDesc('id')->paginate(100)]);
    }
}
