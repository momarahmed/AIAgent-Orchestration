<?php

namespace App\Services\Bridges;

use App\Models\BridgeConnection;
use App\Models\BridgeExecution;

/**
 * Flowise Bridge (PRD §24.5.3 — Phase 4).
 *
 * Calls a Flowise chatflow via the Flowise prediction endpoint and
 * delegates import to the MigrationService (Flowise format parser).
 */
class FlowiseBridge extends BridgeAdapter
{
    public function framework(): string { return 'flowise'; }

    public function call(BridgeConnection $conn, string $action, array $input, ?int $workflowRunId = null): BridgeExecution
    {
        $this->ensureEnabled($conn);
        return $this->record($conn, $action, $input, function () use ($conn, $input) {
            $base = rtrim($conn->endpoint_url ?? env('FLOWISE_BRIDGE_URL', ''), '/');
            $chatflowId = $input['chatflow_id'] ?? ($conn->config['chatflow_id'] ?? null);
            if (! $base || ! $chatflowId) return ['mock' => true, 'message' => 'Flowise endpoint/chatflow not configured'];
            $resp = $this->http($conn)->post("{$base}/api/v1/prediction/{$chatflowId}", [
                'question' => $input['question'] ?? '',
                'overrideConfig' => $input['config'] ?? [],
            ]);
            return $resp->successful() ? $resp->json() : ['error' => $resp->status(), 'body' => $resp->body()];
        }, $workflowRunId);
    }
}
