<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Tool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Basic policy engine — Phase 2.
 * Talks to an external OPA server when OPA_URL is set; otherwise enforces a
 * built-in rule that mirrors the data.eamcp.tool_call.allow policy bundle.
 */
class OpaPolicyService
{
    protected array $riskOrder = ['L0' => 0, 'L1' => 1, 'L2' => 2, 'L3' => 3, 'L4' => 4];

    /**
     * @return array{allow:bool,reason?:string,requires_approval:bool}
     */
    public function evaluateToolCall(?Agent $agent, Tool $tool, array $context = []): array
    {
        $opaUrl = env('OPA_URL');
        $payload = [
            'input' => [
                'agent' => [
                    'id' => $agent?->id,
                    'risk_level' => $agent?->risk_level ?? 'L1',
                    'max_risk_level_without_approval' => $agent?->max_risk_level_without_approval ?? 'L1',
                    'allowed_tools' => $agent?->currentVersion?->allowed_tools ?? [],
                ],
                'tool' => [
                    'id' => $tool->id,
                    'name' => $tool->name,
                    'risk_level' => $tool->risk_level ?? 'L1',
                    'mcp_server_id' => $tool->mcp_server_id,
                ],
                'context' => $context,
            ],
        ];

        if ($opaUrl) {
            try {
                $resp = Http::timeout(3)->post(rtrim($opaUrl, '/') . '/v1/data/eamcp/tool_call', $payload);
                if ($resp->successful()) {
                    $result = $resp->json('result', []);
                    return [
                        'allow' => (bool) ($result['allow'] ?? false),
                        'requires_approval' => (bool) ($result['requires_approval'] ?? false),
                        'reason' => $result['reason'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('opa.evaluate_failed', ['error' => $e->getMessage()]);
            }
        }

        return $this->evaluateBuiltin($agent, $tool);
    }

    protected function evaluateBuiltin(?Agent $agent, Tool $tool): array
    {
        $maxRisk = $agent?->max_risk_level_without_approval ?? 'L1';
        $toolRisk = $tool->risk_level ?? 'L1';

        $allowedTools = $agent?->currentVersion?->allowed_tools ?? null;
        if ($allowedTools && ! in_array($tool->id, $allowedTools, true)) {
            return ['allow' => false, 'requires_approval' => false, 'reason' => 'tool_not_in_agent_allowlist'];
        }

        if (($this->riskOrder[$toolRisk] ?? 0) > ($this->riskOrder[$maxRisk] ?? 0)) {
            return [
                'allow' => false,
                'requires_approval' => true,
                'reason' => "tool risk {$toolRisk} exceeds agent max {$maxRisk}",
            ];
        }

        return ['allow' => true, 'requires_approval' => false];
    }
}
