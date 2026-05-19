<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Qdrant REST client (PRD §9.2 — Phase 4).
 *
 * Lean wrapper around Qdrant collections + points. Avoids pulling in a
 * heavy vendor SDK; we only need create/upsert/search/delete and a
 * health check for the dashboard. Gracefully degrades to a deterministic
 * in-memory mock when QDRANT_URL is unreachable (so dev can iterate
 * without the container running).
 */
class QdrantClient
{
    protected string $base;
    protected ?string $apiKey;
    protected bool $reachable = true;

    /** Process-level cache for mock mode. Resets per request. */
    protected static array $mockStore = [];

    public function __construct()
    {
        $this->base   = rtrim(env('QDRANT_URL', 'http://qdrant:6333'), '/');
        $this->apiKey = env('QDRANT_API_KEY') ?: null;
    }

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        $req = Http::timeout(10)->acceptJson();
        if ($this->apiKey) {
            $req->withHeaders(['api-key' => $this->apiKey]);
        }
        return $req;
    }

    public function healthy(): bool
    {
        try {
            $r = $this->request()->get("{$this->base}/");
            return $r->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function ensureCollection(string $name, int $dim = 1536, string $distance = 'Cosine'): bool
    {
        try {
            $exists = $this->request()->get("{$this->base}/collections/{$name}");
            if ($exists->successful()) return true;

            $r = $this->request()->put("{$this->base}/collections/{$name}", [
                'vectors' => ['size' => $dim, 'distance' => $distance],
            ]);
            return $r->successful();
        } catch (\Throwable $e) {
            Log::warning('qdrant.ensure_collection_failed', ['name' => $name, 'error' => $e->getMessage()]);
            $this->reachable = false;
            self::$mockStore[$name] = self::$mockStore[$name] ?? [];
            return false;
        }
    }

    /**
     * @param array $points  list of {id, vector, payload}
     */
    public function upsert(string $collection, array $points): bool
    {
        if (! $this->reachable) {
            self::$mockStore[$collection] = self::$mockStore[$collection] ?? [];
            foreach ($points as $p) {
                self::$mockStore[$collection][$p['id']] = $p;
            }
            return true;
        }

        try {
            $r = $this->request()->put("{$this->base}/collections/{$collection}/points?wait=true", [
                'points' => $points,
            ]);
            return $r->successful();
        } catch (\Throwable $e) {
            Log::warning('qdrant.upsert_failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * @return array list of {id, score, payload}
     */
    public function search(string $collection, array $vector, int $limit = 5, array $filter = []): array
    {
        if (! $this->reachable) {
            $items = self::$mockStore[$collection] ?? [];
            $scored = [];
            foreach ($items as $id => $p) {
                $scored[] = [
                    'id' => $id,
                    'score' => $this->cosineSim($vector, $p['vector'] ?? []),
                    'payload' => $p['payload'] ?? [],
                ];
            }
            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
            return array_slice($scored, 0, $limit);
        }

        try {
            $body = [
                'vector' => $vector,
                'limit'  => $limit,
                'with_payload' => true,
            ];
            if (! empty($filter)) $body['filter'] = $filter;

            $r = $this->request()->post("{$this->base}/collections/{$collection}/points/search", $body);
            if ($r->successful()) {
                return $r->json('result') ?? [];
            }
            return [];
        } catch (\Throwable $e) {
            Log::warning('qdrant.search_failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function delete(string $collection, array $ids): bool
    {
        if (! $this->reachable) {
            foreach ($ids as $id) {
                unset(self::$mockStore[$collection][$id]);
            }
            return true;
        }

        try {
            $r = $this->request()->post("{$this->base}/collections/{$collection}/points/delete?wait=true", [
                'points' => $ids,
            ]);
            return $r->successful();
        } catch (\Throwable $e) {
            Log::warning('qdrant.delete_failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function cosineSim(array $a, array $b): float
    {
        if (! count($a) || count($a) !== count($b)) return 0.0;
        $dot = 0; $na = 0; $nb = 0;
        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $na  += $a[$i] * $a[$i];
            $nb  += $b[$i] * $b[$i];
        }
        $den = sqrt($na) * sqrt($nb);
        return $den > 0 ? $dot / $den : 0.0;
    }
}
