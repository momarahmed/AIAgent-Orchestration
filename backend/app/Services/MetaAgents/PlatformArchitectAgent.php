<?php

namespace App\Services\MetaAgents;

use App\Models\MetaAgentAction;
use App\Models\MetaAgentRun;
use App\Services\AgentRuntime;
use App\Services\KnowledgeGraphService;
use App\Services\ModelRouter;

/**
 * Platform Architect Agent (PRD §12.2 — Phase 4).
 *
 * Decomposes a natural-language prompt into a coordinated solution:
 *  - which specialist agents are needed,
 *  - which MCP servers/tools they call,
 *  - what workflow stitches them together,
 *  - and approval gates required for the deploy chain.
 *
 * Output (in `plan`) is then handed to the Agent Builder, MCP Builder,
 * Workflow Builder, QA, Security, DevOps and Documentation agents
 * through the MetaAgentOrchestrator.
 */
class PlatformArchitectAgent extends BaseMetaAgent
{
    public function __construct(
        protected ModelRouter $router,
        protected AgentRuntime $runtime,
        protected KnowledgeGraphService $kg,
    ) {}

    public function kind(): string { return 'platform_architect'; }
    public function description(): string
    {
        return 'Designs end-to-end agent / MCP / workflow solutions from a prompt.';
    }

    public function plan(MetaAgentRun $run): array
    {
        $promptText = $run->prompt;
        // Use the routed model to produce an architecture decision.
        $resp = $this->router->complete(
            ['tenant_id' => $run->tenant_id, 'capabilities' => ['chat']],
            [
                'system' => 'You are the Platform Architect Agent. Decompose user prompts into agent/MCP/workflow specs. Respond as JSON with keys: agents[], mcp_servers[], workflow, approvals[], summary.',
                'prompt' => $promptText,
            ]
        );

        $design = $this->safeJson($resp['response'] ?? '') ?? [
            'agents' => [['name' => 'Specialist Agent', 'risk_level' => 'L1']],
            'mcp_servers' => [],
            'workflow' => ['name' => 'Generated Workflow', 'steps' => []],
            'approvals' => ['production_deploy'],
            'summary' => $promptText,
        ];

        // Persist plan onto the run row for downstream meta-agents.
        $run->update(['plan' => $design]);

        return [
            ['action' => 'design',   'subject_type' => 'architecture', 'input' => $design, 'reasoning' => 'Decomposition complete'],
            ['action' => 'generate', 'subject_type' => 'agents',       'input' => $design['agents'] ?? []],
            ['action' => 'generate', 'subject_type' => 'mcp_servers',  'input' => $design['mcp_servers'] ?? []],
            ['action' => 'generate', 'subject_type' => 'workflow',     'input' => $design['workflow'] ?? []],
            ['action' => 'scan',     'subject_type' => 'security',     'input' => $design],
            ['action' => 'test',     'subject_type' => 'qa',           'input' => $design],
            ['action' => 'deploy',   'subject_type' => 'devops',       'input' => ['environment' => 'dev'], 'approval_required' => false],
            ['action' => 'deploy',   'subject_type' => 'devops',       'input' => ['environment' => 'production'], 'approval_required' => true],
            ['action' => 'generate', 'subject_type' => 'documentation','input' => $design],
        ];
    }

    public function executeStep(MetaAgentRun $run, MetaAgentAction $action): array
    {
        // The orchestrator delegates the actual generation/test/deploy/
        // scan/documentation actions to specialist meta-agents. The
        // architect just records its reasoning so the run has a
        // human-readable trail.
        return [
            'note' => "Architect step '{$action->action}/{$action->subject_type}' delegated.",
            'echo' => $action->input,
        ];
    }

    protected function safeJson(string $text): ?array
    {
        $text = trim($text);
        $start = strpos($text, '{');
        $end   = strrpos($text, '}');
        if ($start === false || $end === false) return null;
        $json = substr($text, $start, $end - $start + 1);
        $parsed = json_decode($json, true);
        return is_array($parsed) ? $parsed : null;
    }
}
