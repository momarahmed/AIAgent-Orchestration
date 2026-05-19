<?php

namespace App\Services;

use App\Models\KnowledgeGraphEdge;
use App\Models\KnowledgeGraphNode;

/**
 * Knowledge Graph Service (PRD §9.2 — Phase 4).
 *
 * Lightweight graph store used by:
 *   - Template Manager Agent: dependency resolution between agents/MCPs/workflows.
 *   - Platform Architect Agent: capability discovery ("which agents can talk to GIS Database?").
 *   - Documentation Agent: build architecture diagrams from the graph.
 *
 * MySQL/PG-backed (nodes + edges tables). For larger deployments a
 * dedicated graph DB (Neo4j) can be swapped behind this interface
 * without changing call-sites.
 */
class KnowledgeGraphService
{
    public function upsertNode(int $tenantId, string $type, string $label, ?string $refType = null, ?int $refId = null, array $props = []): KnowledgeGraphNode
    {
        return KnowledgeGraphNode::updateOrCreate(
            ['tenant_id' => $tenantId, 'node_type' => $type, 'ref_type' => $refType, 'ref_id' => $refId],
            ['label' => $label, 'properties' => $props]
        );
    }

    public function link(KnowledgeGraphNode $from, KnowledgeGraphNode $to, string $relation, array $props = []): KnowledgeGraphEdge
    {
        return KnowledgeGraphEdge::updateOrCreate(
            ['from_node_id' => $from->id, 'to_node_id' => $to->id, 'relation' => $relation],
            ['properties' => $props]
        );
    }

    /** BFS over the graph for capability or dependency lookups. */
    public function neighbors(KnowledgeGraphNode $node, ?string $relation = null, int $depth = 1): array
    {
        $visited = [$node->id => $node];
        $frontier = [$node->id];

        for ($d = 0; $d < $depth; $d++) {
            $next = [];
            $edges = KnowledgeGraphEdge::query()
                ->whereIn('from_node_id', $frontier)
                ->when($relation, fn ($q) => $q->where('relation', $relation))
                ->get();
            foreach ($edges as $e) {
                if (! isset($visited[$e->to_node_id])) {
                    $n = KnowledgeGraphNode::find($e->to_node_id);
                    if ($n) {
                        $visited[$n->id] = $n;
                        $next[] = $n->id;
                    }
                }
            }
            if (! $next) break;
            $frontier = $next;
        }
        return array_values($visited);
    }

    public function tenantSnapshot(int $tenantId): array
    {
        $nodes = KnowledgeGraphNode::where('tenant_id', $tenantId)->limit(500)->get();
        $edges = KnowledgeGraphEdge::whereIn('from_node_id', $nodes->pluck('id'))->limit(2000)->get();
        return [
            'nodes' => $nodes,
            'edges' => $edges,
        ];
    }
}
