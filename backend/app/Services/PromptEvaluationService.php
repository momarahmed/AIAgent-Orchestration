<?php

namespace App\Services;

use App\Models\Prompt;
use App\Models\PromptEvaluation;
use App\Models\PromptVersion;

/**
 * Prompt Evaluation Service (PRD §9.2, AC-5 — Phase 4)
 *
 * Runs lightweight A/B evaluations of (prompt × model × dataset) tuples
 * and stores aggregate quality / cost / latency scores that the Agent
 * Studio surfaces when a designer picks a model.
 *
 * In dev mode (when no real LLM API keys are configured) scores are
 * computed heuristically against a built-in micro-dataset so the feature
 * remains exercisable end-to-end.
 */
class PromptEvaluationService
{
    public function __construct(
        protected ModelRouter $router,
        protected ModelRegistryService $registry,
    ) {}

    /**
     * Run an evaluation on a prompt version against one or more models.
     *
     * @param array $modelSlugs
     * @param array|null $dataset  list of {input, expected?} pairs; falls back to a built-in micro set
     * @return PromptEvaluation[]
     */
    public function run(PromptVersion $version, array $modelSlugs, ?array $dataset = null, string $datasetName = 'default'): array
    {
        $samples = $dataset ?: $this->builtinDataset();
        $results = [];

        foreach ($modelSlugs as $slug) {
            $model = $this->registry->find($slug);
            if (! $model) continue;

            $totalQuality = 0; $totalCost = 0; $totalLatency = 0; $count = 0;
            $breakdown = [];

            foreach ($samples as $i => $sample) {
                $start = microtime(true);
                $resp  = $this->router->complete(
                    request: ['hint_slug' => $slug, 'capabilities' => ['chat']],
                    payload: [
                        'system' => 'You are a careful enterprise evaluator. Answer concisely.',
                        'prompt' => $this->resolvePrompt($version->body, $sample),
                    ],
                );
                $elapsed = (int) ((microtime(true) - $start) * 1000);

                $quality = $this->scoreQuality((string) $resp['response'], $sample['expected'] ?? null);
                $cost    = (float) ($resp['cost_usd'] ?? 0);
                $totalQuality += $quality;
                $totalCost    += $cost;
                $totalLatency += $elapsed;
                $count++;

                $breakdown[] = [
                    'sample'      => $i,
                    'quality'     => $quality,
                    'cost_usd'    => $cost,
                    'latency_ms'  => $elapsed,
                    'mock'        => (bool) ($resp['mock'] ?? false),
                    'response_preview' => mb_substr((string) $resp['response'], 0, 120),
                ];
            }

            $count = max($count, 1);
            $avgCost    = $totalCost / $count;
            $avgLatency = $totalLatency / $count;

            $eval = PromptEvaluation::updateOrCreate(
                [
                    'prompt_id'         => $version->prompt_id,
                    'prompt_version_id' => $version->id,
                    'model_slug'        => $slug,
                    'dataset'           => $datasetName,
                ],
                [
                    'quality_score' => round($totalQuality / $count, 2),
                    'cost_score'    => $this->normaliseCostScore($avgCost),
                    'latency_score' => $this->normaliseLatencyScore($avgLatency),
                    'sample_count'  => $count,
                    'breakdown'     => $breakdown,
                ]
            );
            $results[] = $eval;
        }

        return $results;
    }

    /** Best (model, eval) for a prompt — what the Studio surfaces by default. */
    public function leaderboard(Prompt $prompt, int $limit = 5): array
    {
        return PromptEvaluation::query()
            ->where('prompt_id', $prompt->id)
            ->orderByDesc('quality_score')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    protected function scoreQuality(string $response, ?string $expected): float
    {
        if ($expected === null) {
            // No ground truth — favor non-empty, well-formed responses.
            return min(100, max(20, mb_strlen($response) / 5));
        }
        $a = mb_strtolower(trim($expected));
        $b = mb_strtolower(trim($response));
        if ($b === $a) return 100.0;
        if (str_contains($b, $a)) return 80.0;

        similar_text($a, $b, $percent);
        return round($percent, 2);
    }

    protected function normaliseCostScore(float $avgCost): float
    {
        // Lower cost → higher score, capped 0..100.
        if ($avgCost <= 0) return 100;
        return round(min(100, 1.0 / max($avgCost, 0.00001) * 0.5), 2);
    }

    protected function normaliseLatencyScore(float $avgLatencyMs): float
    {
        return round(max(0, 100 - ($avgLatencyMs / 50)), 2);
    }

    protected function resolvePrompt(string $body, array $sample): string
    {
        $input = $sample['input'] ?? '';
        return str_replace(['{{input}}', '{{question}}'], [$input, $input], $body) . "\n\nInput: " . $input;
    }

    protected function builtinDataset(): array
    {
        return [
            ['input' => 'What is 2 + 2?', 'expected' => '4'],
            ['input' => 'Translate "hello" to Spanish.', 'expected' => 'hola'],
            ['input' => 'Capital of France?', 'expected' => 'Paris'],
            ['input' => 'Name a primary color.', 'expected' => 'red'],
        ];
    }
}
