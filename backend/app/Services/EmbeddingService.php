<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Embedding service (PRD §9.2 — Phase 4).
 *
 * Generates embeddings for memory items / RAG retrieval. Routes through
 * the Model Control Plane when an OpenAI-compatible key is configured,
 * otherwise falls back to a deterministic hash-based embedding that is
 * good enough for dev/CI similarity tests (project-scoped, low recall).
 */
class EmbeddingService
{
    /** Default embedding dimension matches text-embedding-3-small. */
    public const DIM = 1536;

    public function embed(string $text, string $model = 'text-embedding-3-small', int $dim = self::DIM): array
    {
        $apiKey = env('OPENAI_API_KEY');
        if ($apiKey) {
            try {
                $r = Http::withToken($apiKey)->timeout(30)->post('https://api.openai.com/v1/embeddings', [
                    'model' => $model,
                    'input' => $text,
                ]);
                if ($r->successful()) {
                    return $r->json('data.0.embedding') ?? $this->hashEmbedding($text, $dim);
                }
            } catch (\Throwable) {
                // fall through to hash embedding
            }
        }
        return $this->hashEmbedding($text, $dim);
    }

    /**
     * Deterministic hash embedding — keeps dev-mode tests stable and
     * preserves cosine-similarity locality for token overlap.
     */
    protected function hashEmbedding(string $text, int $dim): array
    {
        $vec = array_fill(0, $dim, 0.0);
        $tokens = preg_split('/\W+/u', mb_strtolower($text)) ?: [];
        foreach ($tokens as $tok) {
            if ($tok === '') continue;
            $h = crc32($tok);
            $idx = $h % $dim;
            $sign = ($h & 1) ? 1.0 : -1.0;
            $vec[$idx] += $sign;
        }
        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vec))) ?: 1.0;
        return array_map(fn ($v) => $v / $norm, $vec);
    }
}
