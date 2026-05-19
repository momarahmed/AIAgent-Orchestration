<?php

namespace App\Services;

use App\Models\MarketplaceListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lightweight RAG-style search for marketplace listings.
 *
 * Phase 5 PRD MKT-008: "Marketplace search supports natural-language queries
 * powered by RAG over template descriptions." In production, listings are
 * embedded via a configured embedding provider and stored in a vector index
 * (pgvector / Pinecone / Weaviate). For local/dev we use a BM25-like keyword
 * scorer over title + description + tags + readme so the UX is identical and
 * the API stable.
 *
 * Override by binding RagSearchService to a vector implementation in the
 * service provider when a real embedding backend is configured.
 */
class RagSearchService
{
    public function search(string $query, ?int $tenantId, ?string $category = null, int $limit = 20): array
    {
        $terms = $this->tokenize($query);
        if (empty($terms)) {
            $base = $this->baseQuery($tenantId, $category)->orderByDesc('install_count');
            return $base->limit($limit)->get()
                ->map(fn ($l) => $this->serialize($l, score: 0, matched: []))
                ->all();
        }

        $candidates = $this->baseQuery($tenantId, $category)->limit(500)->get();
        $scored     = [];

        foreach ($candidates as $listing) {
            $doc = $this->document($listing);
            $score = 0;
            $matched = [];
            foreach ($terms as $term) {
                $count = substr_count($doc, $term);
                if ($count > 0) {
                    $score += $count * (1 + log(1 + $count));
                    $matched[] = $term;
                }
            }
            if ($score > 0) {
                $score += $listing->rating_avg * 0.5;
                $score += min(5, log(1 + $listing->install_count));
                $scored[] = $this->serialize($listing, $score, $matched);
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($scored, 0, $limit);
    }

    private function baseQuery(?int $tenantId, ?string $category): Builder
    {
        $q = MarketplaceListing::query()->where('status', 'published');
        if ($tenantId) {
            $q->where(function ($qb) use ($tenantId) {
                $qb->where('tenant_id', $tenantId)
                    ->orWhereIn('visibility', ['external', 'trusted']);
            });
        }
        if ($category) {
            $q->where('category', $category);
        }
        return $q;
    }

    private function document(MarketplaceListing $listing): string
    {
        $parts = [
            $listing->title,
            $listing->description,
            $listing->slug,
            implode(' ', $listing->tags ?? []),
            $listing->category,
            $listing->readme['content'] ?? '',
            implode(' ', array_keys($listing->parameters_schema['properties'] ?? [])),
        ];
        return strtolower(implode(' ', array_filter($parts)));
    }

    private function tokenize(string $query): array
    {
        $clean = strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $query));
        $tokens = array_filter(preg_split('/\s+/', $clean));
        $stopwords = ['a','an','the','for','to','of','and','or','in','on','with','my','me','i','want','find','show','please','can','you'];
        return array_values(array_diff($tokens, $stopwords));
    }

    private function serialize(MarketplaceListing $l, float $score, array $matched): array
    {
        return [
            'id'             => $l->id,
            'slug'           => $l->slug,
            'title'          => $l->title,
            'description'    => $l->description,
            'category'       => $l->category,
            'visibility'     => $l->visibility,
            'latest_version' => $l->latest_version,
            'rating_avg'     => (float) $l->rating_avg,
            'install_count'  => $l->install_count,
            'signed'         => (bool) $l->signed,
            'tags'           => $l->tags,
            'score'          => round($score, 3),
            'matched_terms'  => $matched,
        ];
    }
}
