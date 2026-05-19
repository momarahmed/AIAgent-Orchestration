<?php

namespace App\Services\Bridges;

use App\Models\BridgeConnection;
use App\Models\BridgeExecution;

/**
 * Dify Bridge (PRD §24.5.4 — Phase 4).
 *
 * Calls Dify apps / workflows via the Dify REST API and exposes Dify
 * knowledge bases as a retrieval source. Optional, off by default.
 */
class DifyBridge extends BridgeAdapter
{
    public function framework(): string { return 'dify'; }

    public function call(BridgeConnection $conn, string $action, array $input, ?int $workflowRunId = null): BridgeExecution
    {
        $this->ensureEnabled($conn);
        return $this->record($conn, $action, $input, function () use ($conn, $action, $input) {
            $base = rtrim($conn->endpoint_url ?? env('DIFY_BRIDGE_URL', ''), '/');
            if (! $base) return ['mock' => true, 'message' => 'Dify endpoint not configured'];

            $path = match ($action) {
                'run_app'  => '/v1/chat-messages',
                'workflow' => '/v1/workflows/run',
                default    => '/v1/' . ltrim($action, '/'),
            };
            $resp = $this->http($conn)->post($base . $path, [
                'inputs'  => $input['inputs'] ?? [],
                'query'   => $input['query'] ?? '',
                'user'    => $input['user'] ?? 'eamcp-bridge',
                'response_mode' => 'blocking',
            ]);
            return $resp->successful() ? $resp->json() : ['error' => $resp->status(), 'body' => $resp->body()];
        }, $workflowRunId);
    }
}
