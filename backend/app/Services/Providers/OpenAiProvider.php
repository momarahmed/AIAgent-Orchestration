<?php

namespace App\Services\Providers;

use App\Contracts\ModelProvider;
use Illuminate\Support\Facades\Http;

class OpenAiProvider implements ModelProvider
{
    public function name(): string
    {
        return 'openai';
    }

    public function supportsModel(string $model): bool
    {
        return str_starts_with($model, 'gpt-') || str_starts_with($model, 'o1-') || str_starts_with($model, 'openai:');
    }

    public function complete(array $payload): array
    {
        $apiKey = env('OPENAI_API_KEY');
        $model = $payload['model'] ?? 'gpt-4o-mini';
        if (! $apiKey) {
            return [
                'response' => "[mock-openai:{$model}] " . ($payload['prompt'] ?? ''),
                'model' => $model,
                'mock' => true,
            ];
        }

        try {
            $resp = Http::withToken($apiKey)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => $payload['temperature'] ?? 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $payload['system'] ?? 'You are a helpful enterprise AI agent.'],
                    ['role' => 'user', 'content' => $payload['prompt'] ?? ''],
                ],
            ]);
            if ($resp->successful()) {
                return [
                    'response' => $resp->json('choices.0.message.content'),
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
