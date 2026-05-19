<?php

namespace App\Services\MetaAgents;

use App\Models\MetaAgentAction;
use App\Models\MetaAgentRun;
use App\Models\TestSuite;
use App\Services\TestRunnerService;
use App\Support\Audit;

/**
 * QA / Test Agent (PRD §12.2 — Phase 4).
 *
 * Generates basic smoke tests for each artifact produced in the run and
 * delegates execution to the Phase 2 TestRunnerService. Surfaces results
 * back onto the run record so DevOps can gate on it.
 */
class QaTestAgent extends BaseMetaAgent
{
    public function __construct(protected TestRunnerService $runner) {}

    public function kind(): string { return 'qa'; }
    public function description(): string { return 'Generates and executes test suites for produced assets.'; }

    public function plan(MetaAgentRun $run): array
    {
        return [['action' => 'test', 'subject_type' => 'all_assets', 'input' => []]];
    }

    public function executeStep(MetaAgentRun $run, MetaAgentAction $action): array
    {
        $results = ['agents' => [], 'workflows' => []];
        $artifacts = $run->artifacts ?? [];

        foreach ($artifacts['agents'] ?? [] as $agentId) {
            try {
                TestSuite::firstOrCreate(
                    ['tenant_id' => $run->tenant_id, 'asset_type' => 'agent', 'asset_id' => $agentId, 'name' => 'Auto-QA Agent #' . $agentId],
                    [
                        'project_id' => $run->project_id,
                        'description' => 'Auto-generated smoke test',
                        'cases' => [['prompt' => 'Smoke test: respond OK.', 'expected_contains' => 'ok']],
                        'created_by' => $run->user_id,
                    ]
                );
                $exec = $this->runner->runForAsset('agent', $agentId);
                $results['agents'][$agentId] = $exec['status'] ?? 'completed';
            } catch (\Throwable $e) {
                $results['agents'][$agentId] = 'error:' . $e->getMessage();
            }
        }

        Audit::record('meta_agent', 'qa_executed', 'meta_agent_run', $run->id, $results, tenantId: $run->tenant_id);
        return $results;
    }
}
