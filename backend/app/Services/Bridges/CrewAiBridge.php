<?php

namespace App\Services\Bridges;

use App\Models\BridgeConnection;
use App\Models\BridgeExecution;
use App\Services\AgentRuntime;
use App\Services\ModelRouter;

/**
 * CrewAI Template Runtime (PRD §24.5.13 — Phase 4).
 *
 * In-process implementation of the CrewAI "crew-of-agents" pattern that
 * runs entirely within the platform — no external CrewAI server
 * required. Useful for letting teams import simple CrewAI templates
 * without giving up the platform's governance (RBAC / OPA / audit / cost).
 *
 * Template shape:
 *   { "name": "...", "process": "sequential|hierarchical",
 *     "agents": [{"name": "...", "system": "...", "model": "..."}],
 *     "tasks":  [{"agent": "Researcher", "description": "..."}] }
 */
class CrewAiBridge extends BridgeAdapter
{
    public function __construct(protected ModelRouter $router, protected AgentRuntime $runtime) {}

    public function framework(): string { return 'crewai'; }

    public function call(BridgeConnection $conn, string $action, array $input, ?int $workflowRunId = null): BridgeExecution
    {
        $this->ensureEnabled($conn);

        return $this->record($conn, $action, $input, function () use ($conn, $input) {
            $crew    = $input['crew']    ?? ($conn->config ?? []);
            $process = $crew['process'] ?? 'sequential';
            $agents  = collect($crew['agents'] ?? [])->keyBy(fn ($a) => $a['name']);
            $tasks   = $crew['tasks']   ?? [];

            $results = [];
            $context = $input['context'] ?? '';

            foreach ($tasks as $i => $task) {
                $a = $agents[$task['agent']] ?? null;
                if (! $a) {
                    $results[] = ['task' => $i, 'agent' => $task['agent'] ?? null, 'error' => 'unknown_agent'];
                    continue;
                }

                $resp = $this->router->complete(
                    request: ['tenant_id' => $conn->tenant_id, 'capabilities' => ['chat'], 'hint_slug' => $a['model'] ?? null],
                    payload: [
                        'system' => $a['system'] ?? 'You are a CrewAI agent inside an enterprise platform.',
                        'prompt' => "Task: " . ($task['description'] ?? '') . "\n\nContext: " . $context,
                    ]
                );
                $results[] = [
                    'task'  => $i,
                    'agent' => $a['name'],
                    'output'=> $resp['response'] ?? '',
                    'model' => $resp['model_slug'] ?? null,
                    'cost'  => $resp['cost_usd'] ?? 0,
                ];
                // Pass output along the chain in sequential mode.
                if ($process === 'sequential') {
                    $context = "Previous result: " . ($resp['response'] ?? '');
                }
            }
            return ['process' => $process, 'results' => $results];
        }, $workflowRunId);
    }
}
