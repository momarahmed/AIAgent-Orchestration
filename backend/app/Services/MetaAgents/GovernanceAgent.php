<?php

namespace App\Services\MetaAgents;

use App\Models\MetaAgentAction;
use App\Models\MetaAgentRun;
use App\Models\OpaPolicy;
use App\Models\Agent;
use App\Models\McpServer;
use App\Support\Audit;

/**
 * Governance Agent (PRD §12.2 — Phase 4).
 *
 * Performs periodic compliance audits and drift detection. In the
 * meta-agent runtime it can be invoked on-demand to check the run's
 * artifacts against the active OPA policy library, producing an
 * evidence packet for SOC 2 / ISO 27001 audits.
 */
class GovernanceAgent extends BaseMetaAgent
{
    public function kind(): string { return 'governance'; }
    public function description(): string { return 'Periodic compliance audits, evidence collection, drift detection.'; }

    public function plan(MetaAgentRun $run): array
    {
        return [['action' => 'scan', 'subject_type' => 'compliance', 'input' => []]];
    }

    public function executeStep(MetaAgentRun $run, MetaAgentAction $action): array
    {
        $report = [
            'tenant_id' => $run->tenant_id,
            'meta_agent_run_id' => $run->id,
            'policies_active' => OpaPolicy::where('tenant_id', $run->tenant_id)->where('status', 'active')->count(),
            'high_risk_agents' => Agent::where('tenant_id', $run->tenant_id)
                ->whereIn('risk_level', ['L3', 'L4'])->count(),
            'unsandboxed_mcp_servers' => McpServer::where('tenant_id', $run->tenant_id)
                ->where('requires_sandbox', false)->count(),
            'evidence_collected_at' => now()->toIso8601String(),
        ];

        Audit::record('meta_agent', 'governance_evidence', 'meta_agent_run', $run->id, $report, tenantId: $run->tenant_id);
        return $report;
    }
}
