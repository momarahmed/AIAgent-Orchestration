<?php

namespace App\Services;

class VersionDiffService
{
    /**
     * Recursive JSON diff. Returns added/removed/changed.
     */
    public function diff(array $a, array $b, string $path = ''): array
    {
        $changes = [];
        foreach ($a as $key => $valueA) {
            $p = $path === '' ? (string) $key : "{$path}.{$key}";
            if (! array_key_exists($key, $b)) {
                $changes[] = ['op' => 'remove', 'path' => $p, 'old' => $valueA];
                continue;
            }
            $valueB = $b[$key];
            if (is_array($valueA) && is_array($valueB)) {
                $changes = array_merge($changes, $this->diff($valueA, $valueB, $p));
            } elseif ($valueA !== $valueB) {
                $changes[] = ['op' => 'change', 'path' => $p, 'old' => $valueA, 'new' => $valueB];
            }
        }
        foreach ($b as $key => $valueB) {
            $p = $path === '' ? (string) $key : "{$path}.{$key}";
            if (! array_key_exists($key, $a)) {
                $changes[] = ['op' => 'add', 'path' => $p, 'new' => $valueB];
            }
        }
        return $changes;
    }

    /**
     * Workflow graph-aware diff: counts nodes/edges added/removed and configuration changes.
     */
    public function diffGraph(array $graphA, array $graphB): array
    {
        $nodesA = collect($graphA['nodes'] ?? [])->keyBy('id');
        $nodesB = collect($graphB['nodes'] ?? [])->keyBy('id');
        $edgesA = collect($graphA['edges'] ?? [])->keyBy('id');
        $edgesB = collect($graphB['edges'] ?? [])->keyBy('id');

        return [
            'nodes_added' => $nodesB->keys()->diff($nodesA->keys())->values(),
            'nodes_removed' => $nodesA->keys()->diff($nodesB->keys())->values(),
            'nodes_changed' => $nodesA->keys()->intersect($nodesB->keys())
                ->filter(fn ($k) => json_encode($nodesA[$k]) !== json_encode($nodesB[$k]))
                ->values(),
            'edges_added' => $edgesB->keys()->diff($edgesA->keys())->values(),
            'edges_removed' => $edgesA->keys()->diff($edgesB->keys())->values(),
        ];
    }
}
