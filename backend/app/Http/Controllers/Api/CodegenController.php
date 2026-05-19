<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CodegenJob;
use App\Services\OpenHandsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CodegenController extends Controller
{
    public function __construct(protected OpenHandsClient $openhands) {}

    public function generateMcp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'project_id' => 'nullable|exists:projects,id',
            'prompt' => 'required|string|min:10',
            'inputs' => 'sometimes|array',
        ]);
        $job = CodegenJob::create(array_merge($data, [
            'kind' => 'mcp_server',
            'status' => 'queued',
            'triggered_by' => $request->user()?->id,
        ]));
        return response()->json($this->openhands->generateMcpServer($job), 201);
    }

    public function show(CodegenJob $codegen): JsonResponse
    {
        return response()->json($codegen);
    }

    public function index(Request $request): JsonResponse
    {
        $q = CodegenJob::query();
        if ($id = $request->query('tenant_id')) $q->where('tenant_id', $id);
        return response()->json(['data' => $q->orderByDesc('id')->paginate(50)]);
    }
}
