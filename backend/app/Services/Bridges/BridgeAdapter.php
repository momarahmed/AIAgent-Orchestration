<?php

namespace App\Services\Bridges;

use App\Models\BridgeConnection;
use App\Models\BridgeExecution;
use Illuminate\Support\Facades\Http;

/**
 * Base class for Phase 4 cross-framework bridges (Dify / Flowise / SIM / CrewAI).
 *
 * All bridges share the same constraints (PRD §24.5 — Avoid Tool Sprawl):
 *   - feature-flagged per tenant (BridgeConnection.enabled),
 *   - audit & RBAC enforced by the wrapping controller / workflow node,
 *   - results persisted as BridgeExecution rows so cost / latency
 *     dashboards can include bridged work alongside native runs.
 */
abstract class BridgeAdapter
{
    abstract public function framework(): string;
    abstract public function call(BridgeConnection $conn, string $action, array $input, ?int $workflowRunId = null): BridgeExecution;

    protected function ensureEnabled(BridgeConnection $conn): void
    {
        if (! $conn->enabled) {
            throw new \RuntimeException("Bridge {$conn->framework} is disabled for tenant {$conn->tenant_id}");
        }
    }

    protected function record(BridgeConnection $conn, string $action, array $input, callable $fn, ?int $workflowRunId = null): BridgeExecution
    {
        $exec = BridgeExecution::create([
            'bridge_connection_id' => $conn->id,
            'workflow_run_id'      => $workflowRunId,
            'action'               => $action,
            'input'                => $input,
            'status'               => 'running',
        ]);
        $start = microtime(true);
        try {
            $output = $fn();
            $exec->update([
                'status'      => 'succeeded',
                'output'      => $output,
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
        } catch (\Throwable $e) {
            $exec->update([
                'status'      => 'failed',
                'error'       => $e->getMessage(),
                'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            ]);
        }
        return $exec->fresh();
    }

    protected function http(BridgeConnection $conn): \Illuminate\Http\Client\PendingRequest
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($conn->secret_ref) {
            $token = app(\App\Services\SecretService::class)->resolveRef($conn->secret_ref);
            if ($token) $headers['Authorization'] = "Bearer {$token}";
        }
        return Http::timeout(30)->withHeaders($headers);
    }
}
