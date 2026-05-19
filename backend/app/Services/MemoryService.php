<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\MemoryCollection;
use App\Models\MemoryItem;
use App\Support\Audit;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Memory Service (PRD §9.2 / §17 — Phase 4)
 *
 * Three tiers:
 *   - Short-term: Redis-backed per-session chat scratchpad with TTL.
 *   - Long-term:  MemoryItem rows (PostgreSQL/MySQL) + Qdrant vectors.
 *   - Knowledge graph metadata: handled by KnowledgeGraphService.
 *
 * Honors per-agent `memory_scope` (none/session/project/tenant) at
 * retrieval time. Cross-tenant access is hard-blocked at the query
 * layer and audited as a security event when attempted.
 */
class MemoryService
{
    public const SHORT_TERM_TTL = 3600;

    public function __construct(
        protected QdrantClient $qdrant,
        protected EmbeddingService $embeddings,
    ) {}

    // ─── Collection lifecycle ────────────────────────────────────────

    public function ensureCollection(int $tenantId, ?int $projectId, string $slug, string $scope = 'project', string $embeddingModel = 'text-embedding-3-small', int $dim = EmbeddingService::DIM): MemoryCollection
    {
        $namespace = "t{$tenantId}_" . ($projectId ? "p{$projectId}_" : '') . $slug;

        $col = MemoryCollection::firstOrCreate(
            ['tenant_id' => $tenantId, 'slug' => $slug],
            [
                'project_id'       => $projectId,
                'name'             => Str::title(str_replace(['-', '_'], ' ', $slug)),
                'scope'            => $scope,
                'vector_namespace' => $namespace,
                'embedding_model'  => $embeddingModel,
                'embedding_dim'    => $dim,
            ]
        );
        $this->qdrant->ensureCollection($namespace, $dim);
        return $col;
    }

    // ─── Short-term (Redis) ──────────────────────────────────────────

    public function pushShortTerm(string $sessionId, array $turn, int $ttl = self::SHORT_TERM_TTL): void
    {
        $key = "memory:short:{$sessionId}";
        Redis::rpush($key, json_encode($turn));
        Redis::ltrim($key, -50, -1);
        Redis::expire($key, $ttl);
    }

    public function loadShortTerm(string $sessionId, int $limit = 20): array
    {
        $key = "memory:short:{$sessionId}";
        $items = Redis::lrange($key, -1 * $limit, -1) ?: [];
        return array_map(fn ($i) => json_decode($i, true) ?? [], $items);
    }

    // ─── Long-term + Vector ──────────────────────────────────────────

    public function remember(MemoryCollection $collection, string $content, array $opts = []): MemoryItem
    {
        $vec = $this->embeddings->embed($content, $collection->embedding_model, $collection->embedding_dim);

        $item = MemoryItem::create([
            'memory_collection_id' => $collection->id,
            'tenant_id'   => $collection->tenant_id,
            'project_id'  => $collection->project_id,
            'session_id'  => $opts['session_id'] ?? null,
            'agent_id'    => $opts['agent_id'] ?? null,
            'source_type' => $opts['source_type'] ?? 'note',
            'source_id'   => $opts['source_id'] ?? null,
            'vector_id'   => (string) Str::uuid(),
            'content'     => $content,
            'metadata'    => $opts['metadata'] ?? [],
            'expires_at'  => $opts['expires_at'] ?? null,
        ]);

        $this->qdrant->upsert($collection->vector_namespace, [[
            'id'      => $item->vector_id,
            'vector'  => $vec,
            'payload' => [
                'memory_item_id' => $item->id,
                'tenant_id'      => $collection->tenant_id,
                'project_id'     => $collection->project_id,
                'session_id'     => $item->session_id,
                'agent_id'       => $item->agent_id,
                'source_type'    => $item->source_type,
                'content'        => mb_substr($content, 0, 1000),
            ],
        ]]);

        return $item;
    }

    /**
     * RAG retrieval — top-k similar memory items honoring memory_scope.
     *
     * @param Agent|null $agent  used for scope enforcement
     * @return array list of {item: MemoryItem, score: float}
     */
    public function retrieve(MemoryCollection $collection, string $query, array $opts = [], ?Agent $agent = null): array
    {
        $scope = $agent?->memory_scope ?? ($opts['scope'] ?? 'project');
        if ($scope === 'none') {
            return [];
        }

        // Cross-tenant guard.
        if (($opts['tenant_id'] ?? null) !== null && (int) $opts['tenant_id'] !== (int) $collection->tenant_id) {
            Audit::record('memory', 'cross_tenant_blocked', 'memory_collection', $collection->id, [
                'requested_tenant' => $opts['tenant_id'],
                'collection_tenant' => $collection->tenant_id,
            ], tenantId: $collection->tenant_id);
            return [];
        }

        $vec = $this->embeddings->embed($query, $collection->embedding_model, $collection->embedding_dim);

        $filter = [];
        if ($scope === 'session' && ! empty($opts['session_id'])) {
            $filter = ['must' => [['key' => 'session_id', 'match' => ['value' => $opts['session_id']]]]];
        } elseif ($scope === 'project' && ! empty($collection->project_id)) {
            $filter = ['must' => [['key' => 'project_id', 'match' => ['value' => (int) $collection->project_id]]]];
        }

        $hits = $this->qdrant->search($collection->vector_namespace, $vec, $opts['top_k'] ?? 5, $filter);

        $items = [];
        foreach ($hits as $h) {
            $itemId = $h['payload']['memory_item_id'] ?? null;
            if (! $itemId) continue;
            $item = MemoryItem::find($itemId);
            if (! $item) continue;
            // Re-check tenant boundary defensively.
            if ((int) $item->tenant_id !== (int) $collection->tenant_id) continue;
            $items[] = ['item' => $item, 'score' => (float) ($h['score'] ?? 0)];
        }
        return $items;
    }

    public function forget(MemoryItem $item): void
    {
        $col = $item->collection;
        if ($col && $item->vector_id) {
            $this->qdrant->delete($col->vector_namespace, [$item->vector_id]);
        }
        $item->delete();
    }
}
