<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestExecution;
use App\Models\TestSuite;
use App\Services\TestRunnerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function __construct(protected TestRunnerService $runner) {}

    public function index(Request $request): JsonResponse
    {
        $q = TestSuite::query();
        foreach (['tenant_id', 'project_id', 'asset_type', 'asset_id'] as $f) {
            if ($v = $request->query($f)) $q->where($f, $v);
        }
        return response()->json(['data' => $q->orderByDesc('id')->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'project_id' => 'nullable|exists:projects,id',
            'asset_type' => 'required|in:agent,mcp_tool,workflow',
            'asset_id' => 'required|integer',
            'name' => 'required|string|max:160',
            'description' => 'nullable|string',
            'cases' => 'required|array',
        ]);
        $data['created_by'] = $request->user()?->id;
        $suite = TestSuite::create($data);
        return response()->json($suite, 201);
    }

    public function show(TestSuite $testSuite): JsonResponse
    {
        return response()->json($testSuite->load('executions'));
    }

    public function run(Request $request, TestSuite $testSuite): JsonResponse
    {
        $execution = $this->runner->execute($testSuite, $request->user()?->id);
        return response()->json($execution);
    }

    public function execution(TestExecution $execution): JsonResponse
    {
        return response()->json($execution->load('suite'));
    }
}
