<?php

namespace App\Services;

use App\Models\LegacyImport;
use App\Support\Audit;

/**
 * Phase 5 — AutoGen / legacy import adapter.
 *
 * PRD Section 24.5.14: "Treat AutoGen as legacy/migration only; do not use as
 * primary runtime." This service parses an AutoGen / Tesslate / n8n / Flowise /
 * Dify / CrewAI flow and produces a native LangGraph + Temporal manifest with
 * a confidence score and human-review checklist.
 *
 * Output is a workflow payload compatible with DurableWorkflowEngine; review
 * checklist items must be cleared before the imported workflow is promoted
 * past `dev`.
 */
class AutogenImporterService
{
    public function import(string $format, array $sourcePayload, array $context = []): LegacyImport
    {
        $job = LegacyImport::create([
            'tenant_id'        => $context['tenant_id'],
            'project_id'       => $context['project_id'] ?? null,
            'user_id'          => $context['user_id'] ?? null,
            'source_format'    => $format,
            'source_payload'   => $sourcePayload,
            'status'           => 'pending',
        ]);

        try {
            $converted = match ($format) {
                'autogen'  => $this->convertAutogen($sourcePayload),
                'tesslate' => $this->convertTesslate($sourcePayload),
                'n8n'      => $this->convertN8n($sourcePayload),
                'flowise'  => $this->convertFlowise($sourcePayload),
                'dify'     => $this->convertDify($sourcePayload),
                'crewai'   => $this->convertCrewAi($sourcePayload),
                default    => throw new \InvalidArgumentException("Unsupported format: {$format}"),
            };

            $job->update([
                'output_payload'   => $converted['payload'],
                'confidence_score' => $converted['confidence'],
                'review_checklist' => $converted['checklist'],
                'status'           => $converted['confidence'] >= 70 ? 'converted' : 'review_required',
            ]);

            Audit::record('legacy_import', $job->status, 'LegacyImport', $job->id, [
                'format' => $format, 'confidence' => $converted['confidence'],
            ], tenantId: $context['tenant_id']);
        } catch (\Throwable $e) {
            $job->update(['status' => 'failed', 'output_payload' => ['error' => $e->getMessage()]]);
        }

        return $job->fresh();
    }

    protected function convertAutogen(array $src): array
    {
        $agents = $src['agents'] ?? [];
        $messages = $src['messages'] ?? [];

        $nodes = [];
        $edges = [];
        $previous = null;
        foreach ($agents as $i => $a) {
            $id = 'agent_' . ($i + 1);
            $nodes[] = [
                'id'   => $id,
                'type' => 'agent',
                'name' => $a['name'] ?? "Agent {$i}",
                'config' => [
                    'model'        => $a['llm_config']['model'] ?? 'gpt-4',
                    'instructions' => $a['system_message'] ?? '',
                ],
            ];
            if ($previous) {
                $edges[] = ['from' => $previous, 'to' => $id];
            }
            $previous = $id;
        }
        $confidence = $this->scoreConfidence(count($nodes), $messages, count($edges));
        return [
            'payload' => [
                'engine'    => 'durable_workflow',
                'imported_from' => 'autogen',
                'nodes'     => $nodes,
                'edges'     => $edges,
                'metadata'  => ['original_message_count' => count($messages)],
            ],
            'confidence' => $confidence,
            'checklist'  => $this->reviewChecklist('autogen', $nodes, $edges),
        ];
    }

    protected function convertTesslate(array $src): array
    {
        $components = $src['components'] ?? [];
        $nodes = array_map(fn ($c, $i) => [
            'id'   => 'node_' . ($i + 1),
            'type' => $c['type'] ?? 'agent',
            'name' => $c['name'] ?? "Node {$i}",
            'config' => $c['config'] ?? [],
        ], $components, array_keys($components));

        return [
            'payload' => [
                'engine'        => 'durable_workflow',
                'imported_from' => 'tesslate',
                'nodes'         => $nodes,
                'edges'         => $src['edges'] ?? [],
            ],
            'confidence' => $this->scoreConfidence(count($nodes), [], count($src['edges'] ?? [])),
            'checklist'  => $this->reviewChecklist('tesslate', $nodes, $src['edges'] ?? []),
        ];
    }

    protected function convertN8n(array $src): array
    {
        return $this->genericNodeImport('n8n', $src, fn ($n, $i) => [
            'id'   => $n['name'] ?? "n8n_node_{$i}",
            'type' => $this->mapN8nType($n['type'] ?? ''),
            'name' => $n['name'] ?? "Node {$i}",
            'config' => $n['parameters'] ?? [],
        ]);
    }

    protected function convertFlowise(array $src): array
    {
        return $this->genericNodeImport('flowise', $src, fn ($n, $i) => [
            'id'   => $n['id'] ?? "flowise_node_{$i}",
            'type' => 'agent',
            'name' => $n['data']['label'] ?? "Node {$i}",
            'config' => $n['data']['inputs'] ?? [],
        ]);
    }

    protected function convertDify(array $src): array
    {
        return $this->genericNodeImport('dify', $src['graph'] ?? $src, fn ($n, $i) => [
            'id'   => $n['id'] ?? "dify_node_{$i}",
            'type' => $n['data']['type'] ?? 'agent',
            'name' => $n['data']['title'] ?? "Node {$i}",
            'config' => $n['data'] ?? [],
        ]);
    }

    protected function convertCrewAi(array $src): array
    {
        $crew = $src['crew'] ?? [];
        $agents = $crew['agents'] ?? [];
        $tasks  = $crew['tasks']  ?? [];
        $nodes = [];
        foreach ($agents as $i => $a) {
            $nodes[] = [
                'id'   => 'crewai_agent_' . ($i + 1),
                'type' => 'agent',
                'name' => $a['role'] ?? "Agent {$i}",
                'config' => [
                    'goal'         => $a['goal'] ?? null,
                    'backstory'    => $a['backstory'] ?? null,
                    'instructions' => $a['system_message'] ?? '',
                ],
            ];
        }
        foreach ($tasks as $i => $t) {
            $nodes[] = [
                'id'   => 'crewai_task_' . ($i + 1),
                'type' => 'task',
                'name' => $t['description'] ?? "Task {$i}",
                'config' => $t,
            ];
        }
        return [
            'payload' => [
                'engine'        => 'durable_workflow',
                'imported_from' => 'crewai',
                'nodes'         => $nodes,
                'edges'         => [],
            ],
            'confidence' => $this->scoreConfidence(count($nodes), [], 0),
            'checklist'  => $this->reviewChecklist('crewai', $nodes, []),
        ];
    }

    protected function genericNodeImport(string $format, array $src, callable $mapper): array
    {
        $rawNodes = $src['nodes'] ?? [];
        $nodes = [];
        foreach ($rawNodes as $i => $n) {
            $nodes[] = $mapper($n, $i);
        }
        $edges = $src['connections'] ?? $src['edges'] ?? [];
        return [
            'payload' => [
                'engine'        => 'durable_workflow',
                'imported_from' => $format,
                'nodes'         => $nodes,
                'edges'         => $edges,
            ],
            'confidence' => $this->scoreConfidence(count($nodes), [], count($edges)),
            'checklist'  => $this->reviewChecklist($format, $nodes, $edges),
        ];
    }

    protected function scoreConfidence(int $nodes, array $messages, int $edges): float
    {
        $score = 50;
        if ($nodes > 0) $score += min(30, $nodes * 4);
        if ($edges > 0) $score += min(15, $edges * 3);
        if (! empty($messages)) $score += 5;
        return min(100, $score);
    }

    protected function reviewChecklist(string $format, array $nodes, array $edges): array
    {
        $list = [
            ['id' => 'verify_models',       'description' => 'Verify model assignments for each agent node.', 'done' => false],
            ['id' => 'rewire_human_input',  'description' => 'Replace human-input loops with Approval gates.', 'done' => false],
            ['id' => 'connector_mapping',   'description' => 'Map external integrations to MCP servers.',       'done' => false],
            ['id' => 'add_tests',           'description' => 'Add automated tests for the imported workflow.', 'done' => false],
        ];
        if ($format === 'autogen') {
            $list[] = ['id' => 'unsafe_code_exec', 'description' => 'Audit AutoGen code-exec agents for sandbox migration.', 'done' => false];
        }
        if (empty($edges) && count($nodes) > 1) {
            $list[] = ['id' => 'reconstruct_edges', 'description' => 'No edges detected — reconstruct workflow order.', 'done' => false];
        }
        return $list;
    }

    protected function mapN8nType(string $type): string
    {
        if (str_contains($type, 'http')) return 'tool';
        if (str_contains($type, 'cron') || str_contains($type, 'schedule')) return 'trigger';
        if (str_contains($type, 'openai') || str_contains($type, 'llm')) return 'agent';
        return 'tool';
    }
}
