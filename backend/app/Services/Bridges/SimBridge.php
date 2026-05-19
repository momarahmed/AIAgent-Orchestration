<?php

namespace App\Services\Bridges;

use App\Models\BridgeConnection;
use App\Models\BridgeExecution;

/**
 * SIM Bridge (PRD §24.5.1 — Phase 4).
 *
 * Calls a SIM (Salesforce AI Workforce) workflow / agent if a SIM
 * endpoint is configured. Disabled by default; only enabled when a
 * SIM API key is provisioned in Vault and the bridge connection is
 * marked enabled by an Admin.
 */
class SimBridge extends BridgeAdapter
{
    public function framework(): string { return 'sim'; }

    public function call(BridgeConnection $conn, string $action, array $input, ?int $workflowRunId = null): BridgeExecution
    {
        $this->ensureEnabled($conn);
        return $this->record($conn, $action, $input, function () use ($conn, $action, $input) {
            $base = rtrim($conn->endpoint_url ?? env('SIM_BRIDGE_URL', ''), '/');
            if (! $base) return ['mock' => true, 'message' => 'SIM endpoint not configured'];
            $resp = $this->http($conn)->post("{$base}/api/{$action}", $input);
            return $resp->successful() ? $resp->json() : ['error' => $resp->status(), 'body' => $resp->body()];
        }, $workflowRunId);
    }
}
