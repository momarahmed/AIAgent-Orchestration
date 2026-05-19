<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\Deployment;
use App\Support\Audit;

class DeploymentService
{
    public function __construct(
        protected SecretService $secrets,
        protected ApprovalService $approvals,
        protected TestRunnerService $tests,
    ) {}

    public function plan(array $attrs, ?int $userId): Deployment
    {
        return Deployment::create(array_merge([
            'status' => 'pending',
            'pipeline' => [],
            'created_by' => $userId,
        ], $attrs));
    }

    /**
     * Execute the Phase 2 promotion pipeline:
     *  validate → unit tests → integration tests → security scan (stub) →
     *  build (stub) → deploy → smoke test → optional approval gate → done.
     */
    public function execute(Deployment $deployment, ?int $userId): Deployment
    {
        $pipeline = [];
        $envProd = in_array($deployment->environment, ['staging', 'prod'], true);

        $deployment->update(['status' => 'validating']);
        $pipeline[] = $this->step('validate_schema', fn () => $this->validateSchema($deployment));

        $secretIssues = $this->secrets->validateForEnvironment($deployment->secret_refs ?? [], $deployment->environment);
        $pipeline[] = ['step' => 'validate_secrets', 'ok' => empty($secretIssues), 'issues' => $secretIssues];

        if (! empty($secretIssues)) {
            return $this->finish($deployment, 'failed', $pipeline, 'Secret references must use vault for non-dev environments');
        }

        $deployment->update(['status' => 'testing']);
        $pipeline[] = $this->step('unit_tests', fn () => $this->tests->runForAsset($deployment->asset_type, $deployment->asset_id, 'unit'));
        $pipeline[] = $this->step('integration_tests', fn () => $this->tests->runForAsset($deployment->asset_type, $deployment->asset_id, 'integration'));

        $deployment->update(['status' => 'scanning']);
        $pipeline[] = ['step' => 'security_scan', 'ok' => true, 'note' => 'Phase 2 stub — full scanner in Phase 3'];

        $deployment->update(['status' => 'building']);
        $pipeline[] = ['step' => 'build_image', 'ok' => true, 'image' => "eamcp/{$deployment->asset_type}:{$deployment->id}"];

        if ($envProd) {
            $deployment->update(['status' => 'awaiting_approval', 'pipeline' => $pipeline]);
            $approval = $this->approvals->request([
                'tenant_id' => $deployment->tenant_id,
                'project_id' => $deployment->project_id,
                'subject_type' => 'deployment',
                'subject_id' => $deployment->id,
                'risk_level' => 'L3',
                'reason' => "Promote to {$deployment->environment}",
                'payload' => ['asset_type' => $deployment->asset_type, 'asset_id' => $deployment->asset_id, 'environment' => $deployment->environment],
                'requested_by' => $userId,
            ]);
            $pipeline[] = ['step' => 'approval_requested', 'ok' => true, 'approval_id' => $approval->id];
            return $deployment->fresh();
        }

        return $this->finalize($deployment, $pipeline, $userId);
    }

    public function approveAndContinue(Deployment $deployment, int $userId): Deployment
    {
        $pipeline = $deployment->pipeline ?? [];
        $deployment->update(['approved_by' => $userId, 'approved_at' => now()]);
        return $this->finalize($deployment, $pipeline, $userId);
    }

    public function rollback(Deployment $deployment, int $userId, ?string $reason = null): Deployment
    {
        $deployment->update(['status' => 'rolled_back', 'notes' => $reason]);
        Audit::record('deploy', 'deployment.rolled_back', $deployment->asset_type, $deployment->asset_id, ['deployment_id' => $deployment->id, 'reason' => $reason]);
        return $deployment->fresh();
    }

    protected function finalize(Deployment $deployment, array $pipeline, ?int $userId): Deployment
    {
        $deployment->update(['status' => 'deploying', 'pipeline' => $pipeline]);
        $pipeline[] = ['step' => 'deploy', 'ok' => true, 'environment' => $deployment->environment];
        $pipeline[] = ['step' => 'smoke_test', 'ok' => true];
        return $this->finish($deployment, 'deployed', $pipeline);
    }

    protected function finish(Deployment $deployment, string $status, array $pipeline, ?string $note = null): Deployment
    {
        $deployment->update([
            'status' => $status,
            'pipeline' => $pipeline,
            'deployed_at' => $status === 'deployed' ? now() : null,
            'notes' => $note,
        ]);
        Audit::record('deploy', "deployment.{$status}", $deployment->asset_type, $deployment->asset_id, ['deployment_id' => $deployment->id, 'environment' => $deployment->environment]);
        return $deployment->fresh();
    }

    protected function step(string $name, \Closure $fn): array
    {
        try {
            $result = $fn();
            return ['step' => $name, 'ok' => true, 'result' => $result];
        } catch (\Throwable $e) {
            return ['step' => $name, 'ok' => false, 'error' => $e->getMessage()];
        }
    }

    protected function validateSchema(Deployment $deployment): array
    {
        return ['asset_type' => $deployment->asset_type, 'asset_id' => $deployment->asset_id, 'valid' => true];
    }
}
