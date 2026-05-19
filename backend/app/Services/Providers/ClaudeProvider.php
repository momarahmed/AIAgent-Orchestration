<?php

namespace App\Services\Providers;

use App\Contracts\ModelProvider;
use Illuminate\Support\Facades\Http;

class ClaudeProvider implements ModelProvider
{
    public function name(): string
    {
        return 'anthropic';
    }

    public function supportsModel(string $model): bool
    {
        return str_starts_with($model, 'claude-') || str_starts_with($model, 'anthropic:');
    }

    public function complete(array $payload): array
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        $model = $payload['model'] ?? 'claude-sonnet-4-20250514';
        if (! $apiKey) {
            return [
                'response' => "[mock-claude:{$model}] " . ($payload['prompt'] ?? ''),
                'model' => $model,
                'mock' => true,
            ];
        }

        try {
            $resp = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => $payload['max_tokens'] ?? 1024,
                'system' => $payload['system'] ?? 'You are a helpful enterprise AI agent.',
                'messages' => [
                    ['role' => 'user', 'content' => $payload['prompt'] ?? ''],
                ],
            ]);

            if ($resp->successful()) {
                $blocks = $resp->json('content', []);
                $text = collect($blocks)->where('type', 'text')->pluck('text')->join("\n");
                return [
                    'response' => $text,
                    'model' => $model,
                    'usage' => $resp->json('usage'),
                    'raw' => $resp->json(),
                ];
            }
            return ['response' => '', 'model' => $model, 'error' => 'Provider call failed', 'status' => $resp->status()];
        } catch (\Throwable $e) {
            return ['response' => '', 'model' => $model, 'error' => $e->getMessage()];
        }
    }
}
