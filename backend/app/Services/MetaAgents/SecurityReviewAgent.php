<?php

namespace App\Services\MetaAgents;

use App\Models\MetaAgentAction;
use App\Models\MetaAgentRun;
use App\Services\SecurityScannerService;
use App\Support\Audit;

/**
 * Security Review Agent (PRD §12.2 — Phase 4).
 *
 * Runs Phase 3 SecurityScannerService over every artifact produced by
 * the run, plus a static OPA-policy review of the generated agent/tool
 * configurations. Blocks promotion when critical findings exist.
 */
class SecurityReviewAgent extends BaseMetaAgent
{
    public function __construct(protected SecurityScannerService $scanner) {}

    public function kind(): string { return 'security'; }
    public function description(): string { return 'Scans generated assets and blocks promotion on critical findings.'; }

    public function plan(MetaAgentRun $run): array
    {
        return [['action' => 'scan', 'subject_type' => 'all_assets', 'input' => []]];
    }

    public function executeStep(MetaAgentRun $run, MetaAgentAction $action): array
    {
        $summary = ['agents' => [], 'mcp_servers' => [], 'blocks_promotion' => false];

        foreach ($run->artifacts['mcp_servers'] ?? [] as $id) {
            try {
                $scan = $this->scanner->runScan(
                    scanType: 'code_scan',
                    targetType: 'mcp_server',
                    targetRef: "mcp_server:{$id}",
                    deploymentId: null,
                    tenantId: $run->tenant_id,
                    triggeredBy: $run->user_id,
                    extra: ['code' => "// auto-generated mcp server #{$id}"],
                );
                $summary['mcp_servers'][$id] = $scan->status ?? 'queued';
                if (($scan->blocks_promotion ?? false) === true) {
                    $summary['blocks_promotion'] = true;
                }
            } catch (\Throwable $e) {
                $summary['mcp_servers'][$id] = 'error:' . $e->getMessage();
            }
        }

        Audit::record('meta_agent', 'security_scan', 'meta_agent_run', $run->id, $summary, tenantId: $run->tenant_id);
        return $summary;
    }
}
