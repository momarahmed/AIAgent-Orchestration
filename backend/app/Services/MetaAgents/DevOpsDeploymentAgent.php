<?php

namespace App\Services\MetaAgents;

use App\Models\Approval;
use App\Models\MetaAgentAction;
use App\Models\MetaAgentRun;
use App\Services\DeploymentService;
use App\Support\Audit;

/**
 * DevOps Deployment Agent (PRD §12.2 — Phase 4).
 *
 * Calls Phase 2 DeploymentService to promote artifacts through dev →
 * staging → prod. Production steps always create an Approval row and
 * leave the action in the awaiting_approval state until a human acts.
 */
class DevOpsDeploymentAgent extends BaseMetaAgent
{
    public function __construct(protected DeploymentService $deployments) {}

    public function kind(): string { return 'devops'; }
    public function description(): string { return 'Deploys generated assets and rolls back on regression.'; }

    public function plan(MetaAgentRun $run): array
    {
        return [
            ['action' => 'deploy', 'subject_type' => 'devops', 'input' => ['environment' => 'dev'], 'approval_required' => false],
            ['action' => 'deploy', 'subject_type' => 'devops', 'input' => ['environment' => 'production'], 'approval_required' => true],
        ];
    }

    public function executeStep(MetaAgentRun $run, MetaAgentAction $action): array
    {
        $env = $action->input['environment'] ?? 'dev';
        $deployed = [];

        foreach ($run->artifacts['workflows'] ?? [] as $wfId) {
            try {
                $deployment = $this->deployments->plan([
                    'tenant_id'   => $run->tenant_id,
                    'project_id'  => $run->project_id,
                    'asset_type'  => 'workflow',
                    'asset_id'    => $wfId,
                    'environment' => $env,
                    'pipeline'    => ['steps' => ['build', 'test', 'deploy']],
                    'notes'       => "Auto-deploy by DevOps meta-agent (run #{$run->id})",
                ], $run->user_id);

                if (! $action->approval_required) {
                    $this->deployments->execute($deployment, $run->user_id);
                }
                $deployed[] = $deployment->id;
            } catch (\Throwable $e) {
                $deployed[] = ['error' => $e->getMessage(), 'asset_id' => $wfId];
            }
        }

        if ($action->approval_required) {
            $approval = Approval::create([
                'tenant_id'   => $run->tenant_id,
                'subject_type'=> 'meta_agent_action',
                'subject_id'  => $action->id,
                'reason'      => "Production deployment for meta-agent run #{$run->id}",
                'risk_level'  => 'L3',
                'status'      => 'pending',
                'requested_by'=> $run->user_id,
            ]);
            $action->update(['approval_id' => $approval->id, 'status' => 'pending']);
            $approvals = $run->approvals ?? [];
            $approvals[] = $approval->id;
            $run->update(['approvals' => $approvals, 'status' => 'awaiting_approval']);
        }

        Audit::record('meta_agent', 'deploy_triggered', 'meta_agent_run', $run->id, [
            'environment' => $env, 'deployed' => $deployed,
        ], tenantId: $run->tenant_id);

        return ['environment' => $env, 'deployments' => $deployed];
    }
}
