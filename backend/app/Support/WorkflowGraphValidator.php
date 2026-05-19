<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Phase-1 workflow graph validation (WF-001 save rules).
 */
class WorkflowGraphValidator
{
    public static function validate(array $graph): void
    {
        $nodes = collect($graph['nodes'] ?? []);
        $edges = collect($graph['edges'] ?? []);

        if ($nodes->isEmpty()) {
            return;
        }

        $nodeIds = $nodes->pluck('id')->filter()->values();
        $errors = [];

        foreach ($edges as $edge) {
            $source = $edge['source'] ?? null;
            $target = $edge['target'] ?? null;
            if (! $source || ! $target) {
                $errors[] = 'Each edge must have source and target.';
                continue;
            }
            if (! $nodeIds->contains($source) || ! $nodeIds->contains($target)) {
                $errors[] = "Edge references unknown node ({$source} → {$target}).";
            }
        }

        $connected = collect();
        foreach ($edges as $edge) {
            $connected->push($edge['source'] ?? null, $edge['target'] ?? null);
        }
        $connected = $connected->filter()->unique();

        foreach ($nodes as $node) {
            $id = $node['id'] ?? null;
            $type = $node['type'] ?? 'unknown';
            $data = $node['data'] ?? [];

            if ($edges->isNotEmpty() && $id && ! $connected->contains($id)) {
                $errors[] = "Node '{$id}' is disconnected.";
            }

            if ($type === 'agent' && empty($data['agent_id'])) {
                $errors[] = "Agent node '{$id}' requires agent_id in config.";
            }
            if ($type === 'mcp_tool' && empty($data['tool_id'])) {
                $errors[] = "MCP Tool node '{$id}' requires tool_id in config.";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages(['graph_json' => array_unique($errors)]);
        }
    }
}
