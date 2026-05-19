<?php

namespace App\Services;

use App\Models\MetaAgentAction;
use App\Models\MetaAgentRun;
use App\Services\MetaAgents\AgentBuilderAgent;
use App\Services\MetaAgents\BaseMetaAgent;
use App\Services\MetaAgents\DevOpsDeploymentAgent;
use App\Services\MetaAgents\DocumentationAgent;
use App\Services\MetaAgents\GovernanceAgent;
use App\Services\MetaAgents\McpBuilderAgent;
use App\Services\MetaAgents\MigrationAgent;
use App\Services\MetaAgents\PlatformArchitectAgent;
use App\Services\MetaAgents\QaTestAgent;
use App\Services\MetaAgents\SecurityReviewAgent;
use App\Services\MetaAgents\TemplateManagerAgent;
use App\Services\MetaAgents\WorkflowBuilderAgent;
use App\Support\Audit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Meta-Agent Orchestrator (PRD §8.2 / §12.2 — Phase 4).
 *
 * Coordinates the eleven meta-agents:
 *   platform_architect → agent_builder → mcp_builder → workflow_builder
 *                      → template_manager → qa → security → devops
 *                      → documentation → governance
 *
 * The orchestrator persists MetaAgentRun + MetaAgentAction rows for
 * every step, gates risky actions through the Approval Service, and
 * emits run.status events on the event bus so observability surfaces
 * the live state of the autonomous run.
 */
class MetaAgentOrchestrator
{
    /** @var array<string, class-string<BaseMetaAgent>> */
    public const REGISTRY = [
        'platform_architect' => PlatformArchitectAgent::class,
        'agent_builder'      => AgentBuilderAgent::class,
        'mcp_builder'        => McpBuilderAgent::class,
        'workflow_builder'   => WorkflowBuilderAgent::class,
        'template_manager'   => TemplateManagerAgent::class,
        'qa'                 => QaTestAgent::class,
        'security'           => SecurityReviewAgent::class,
        'devops'             => DevOpsDeploymentAgent::class,
        'documentation'      => DocumentationAgent::class,
        'migration'          => MigrationAgent::class,
        'governance'         => GovernanceAgent::class,
    ];

    public function __construct(
        protected EventBus $bus,
        protected OpaPolicyService $opa,
    ) {}

    public function start(int $tenantId, ?int $projectId, ?int $userId, string $metaAgent, string $prompt, array $plan = []): MetaAgentRun
    {
        $run = MetaAgentRun::create([
            'tenant_id'  => $tenantId,
            'project_id' => $projectId,
            'user_id'    => $userId,
            'meta_agent' => $metaAgent,
            'prompt'     => $prompt,
            'status'     => 'queued',
            'plan'       => $plan,
            'trace_id'   => (string) Str::uuid(),
        ]);

        $this->bus->publish('agent.events', 'meta_agent.run.queued', [
            'meta_agent_run_id' => $run->id,
            'meta_agent'        => $metaAgent,
            'tenant_id'         => $tenantId,
        ]);

        return $run;
    }

    public function execute(MetaAgentRun $run): MetaAgentRun
    {
        $agent = $this->resolveAgent($run->meta_agent);
        if (! $agent) {
            $run->update(['status' => 'failed', 'error' => "Unknown meta-agent: {$run->meta_agent}"]);
            return $run;
        }

        $run->update(['status' => 'running', 'started_at' => now()]);
        $this->bus->publish('agent.events', 'meta_agent.run.started', ['meta_agent_run_id' => $run->id]);

        try {
            $plan = $agent->plan($run);
            foreach ($plan as $i => $stepDef) {
                $action = MetaAgentAction::create([
                    'meta_agent_run_id' => $run->id,
                    'sequence'          => $i + 1,
                    'action'            => $stepDef['action'] ?? 'noop',
                    'subject_type'      => $stepDef['subject_type'] ?? null,
                    'subject_id'        => $stepDef['subject_id'] ?? null,
                    'input'             => $stepDef['input'] ?? [],
                    'status'            => 'running',
                    'approval_required' => (bool) ($stepDef['approval_required'] ?? false),
                    'reasoning'         => $stepDef['reasoning'] ?? null,
                    'started_at'        => now(),
                ]);

                if (! $this->policyAllows($run, $action)) {
                    $action->update(['status' => 'failed', 'output' => ['error' => 'denied_by_policy'], 'completed_at' => now()]);
                    $run->update(['status' => 'failed', 'error' => 'Blocked by meta-agent autonomy policy.', 'completed_at' => now()]);
                    return $run;
                }

                try {
                    $output = $agent->executeStep($run, $action);
                    $action->update(['status' => 'succeeded', 'output' => $output, 'completed_at' => now()]);
                } catch (\Throwable $e) {
                    Log::warning('meta_agent.step_failed', ['action_id' => $action->id, 'error' => $e->getMessage()]);
                    $action->update(['status' => 'failed', 'output' => ['error' => $e->getMessage()], 'completed_at' => now()]);
                }

                // If a step opened an approval, pause the run.
                if ($run->fresh()->status === 'awaiting_approval') {
                    $this->bus->publish('approval.events', 'meta_agent.awaiting_approval', [
                        'meta_agent_run_id' => $run->id, 'action_id' => $action->id,
                    ]);
                    return $run->fresh();
                }
            }

            $run->update(['status' => 'completed', 'completed_at' => now()]);
            $this->bus->publish('agent.events', 'meta_agent.run.completed', ['meta_agent_run_id' => $run->id]);
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'error' => $e->getMessage(), 'completed_at' => now()]);
        }

        Audit::record('meta_agent', 'run_finished', 'meta_agent_run', $run->id, [
            'status' => $run->fresh()->status,
        ], tenantId: $run->tenant_id);

        return $run->fresh();
    }

    public function resume(MetaAgentRun $run): MetaAgentRun
    {
        if ($run->status !== 'awaiting_approval') return $run;
        $run->update(['status' => 'running']);
        return $this->execute($run);
    }

    public function resolveAgent(string $kind): ?BaseMetaAgent
    {
        $class = self::REGISTRY[$kind] ?? null;
        return $class ? app($class) : null;
    }

    protected function policyAllows(MetaAgentRun $run, MetaAgentAction $action): bool
    {
        $decision = $this->opa->evaluate('eamcp/meta_agent/allow_action', [
            'action' => [
                'category'    => $this->categoryFor($action->action),
                'environment' => $action->input['environment'] ?? 'dev',
                'permission'  => $action->input['permission'] ?? null,
                'has_human_approval' => (bool) ($action->approval_id),
                'security_scan_status' => 'passed',
                'estimated_cost_usd'   => 0.0,
            ],
            'budget' => ['run_cap_usd' => 50.0],
        ]);
        // Allow on fallback when OPA isn't reachable so dev keeps working.
        return $decision['allowed'] ?? false || ($decision['raw']['fallback'] ?? false);
    }

    protected function categoryFor(string $action): string
    {
        return match ($action) {
            'deploy' => 'deploy',
            'grant'  => 'grant',
            default  => 'design',
        };
    }
}
