<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\McpServer;
use App\Models\Tool;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    public function index(McpServer $mcpServer): JsonResponse
    {
        return response()->json(['data' => $mcpServer->tools()->orderBy('name')->get()]);
    }

    public function store(Request $request, McpServer $mcpServer): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'input_schema' => 'nullable|array',
            'output_schema' => 'nullable|array',
            'risk_level' => 'sometimes|in:L0,L1,L2,L3,L4',
            'is_enabled' => 'sometimes|boolean',
        ]);

        $tool = Tool::create(array_merge($data, [
            'mcp_server_id' => $mcpServer->id,
            'risk_level' => $data['risk_level'] ?? 'L1',
            'is_enabled' => $data['is_enabled'] ?? true,
        ]));

        Audit::record('create', 'tool.create', 'tool', $tool->id, $data, $request, $mcpServer->tenant_id, $mcpServer->project_id);
        return response()->json($tool, 201);
    }

    public function update(Request $request, McpServer $mcpServer, Tool $tool): JsonResponse
    {
        abort_if($tool->mcp_server_id !== $mcpServer->id, 404);
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'description' => 'nullable|string',
            'input_schema' => 'nullable|array',
            'output_schema' => 'nullable|array',
            'risk_level' => 'sometimes|in:L0,L1,L2,L3,L4',
            'is_enabled' => 'sometimes|boolean',
        ]);
        $tool->update($data);
        Audit::record('update', 'tool.update', 'tool', $tool->id, $data, $request, $mcpServer->tenant_id, $mcpServer->project_id);
        return response()->json($tool);
    }

    public function destroy(Request $request, McpServer $mcpServer, Tool $tool): JsonResponse
    {
        abort_if($tool->mcp_server_id !== $mcpServer->id, 404);
        $tool->delete();
        Audit::record('delete', 'tool.delete', 'tool', $tool->id, [], $request, $mcpServer->tenant_id, $mcpServer->project_id);
        return response()->json(['ok' => true]);
    }
}
