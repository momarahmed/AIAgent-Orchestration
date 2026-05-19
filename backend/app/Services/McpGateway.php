<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\TaskRun;
use App\Models\Tool;
use App\Models\ToolCall;
use Illuminate\Support\Facades\Http;

/**
 * Phase-1 MCP Gateway prototype — Tool Registry (DB) + Client Manager (HTTP ping).
 * Full MCP Python SDK integration lands in Phase 2/3.
 */
class McpGateway
{
    public function executeTool(
        ?int $toolId,
        array $inputs,
        ?TaskRun $task,
        ?int $allowedAgentId = null,
    ): array {
        $tool = $toolId ? Tool::with('server')->find($toolId) : null;

        if ($tool && $allowedAgentId) {
            $agent = Agent::with('currentVersion')->find($allowedAgentId);
            $allowed = $agent?->currentVersion?->allowed_tools ?? [];
            if (! empty($allowed) && ! in_array($tool->id, $allowed, true)) {
                throw new \RuntimeException("Tool '{$tool->name}' is not in agent allowed_tools list.");
            }
        }

        $start = microtime(true);
        $output = $this->invoke($tool, $inputs);
        $duration = (int) ((microtime(true) - $start) * 1000);

        if ($task) {
            ToolCall::create([
                'task_run_id' => $task->id,
                'tool_id' => $tool?->id,
                'mcp_server_id' => $tool?->mcp_server_id,
                'tool_name' => $tool?->name ?? 'unknown',
                'input' => $inputs,
                'output' => $output,
                'status' => ($output['error'] ?? null) ? 'failed' : 'completed',
                'error' => $output['error'] ?? null,
                'duration_ms' => $duration,
            ]);
        }

        return $output;
    }

    protected function invoke(?Tool $tool, array $inputs): array
    {
        if (! $tool) {
            return ['error' => 'Tool not found', 'mock' => true];
        }

        $server = $tool->server;
        if ($server?->endpoint) {
            try {
                $resp = Http::timeout(8)->withOptions(['verify' => false])
                    ->post(rtrim($server->endpoint, '/') . '/tools/' . $tool->name, $inputs);
                if ($resp->successful()) {
                    return array_merge(['tool' => $tool->name, 'result' => $resp->json()], ['mock' => false]);
                }
            } catch (\Throwable $e) {
                // Fall through to mock for Phase-1 stub servers
            }
        }

        return [
            'tool' => $tool->name,
            'mcp_server_id' => $tool->mcp_server_id,
            'inputs' => $inputs,
            'result' => ['status' => 'ok', 'message' => 'Phase-1 mock tool execution'],
            'mock' => true,
        ];
    }

    public function healthCheck(string $endpoint): array
    {
        $start = microtime(true);
        try {
            $response = Http::timeout(5)->withOptions(['verify' => false])->get($endpoint);
            $latency = (int) ((microtime(true) - $start) * 1000);
            return [
                'health' => $response->successful() ? 'healthy' : 'degraded',
                'detail' => ['status' => $response->status(), 'latency_ms' => $latency],
            ];
        } catch (\Throwable $e) {
            return [
                'health' => 'unhealthy',
                'detail' => ['error' => $e->getMessage()],
            ];
        }
    }
}
