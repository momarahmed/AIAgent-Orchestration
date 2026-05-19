<?php

namespace App\Services\Providers;

use App\Contracts\ModelProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ollama / vLLM-compatible local LLM provider (Phase 4 — PRD §9.2, §24.5).
 *
 * Routes calls to a local LLM runtime exposed on OLLAMA_URL (default
 * http://ollama:11434). Falls back to a mock response when the runtime
 * is unreachable so dev environments remain productive.
 */
class OllamaProvider implements ModelProvider
{
    public function name(): string
    {
        return 'ollama';
    }

    public function supportsModel(string $model): bool
    {
        return str_starts_with($model, 'ollama:')
            || str_starts_with($model, 'llama')
            || str_starts_with($model, 'mistral')
            || str_starts_with($model, 'qwen')
            || str_starts_with($model, 'gemma')
            || str_starts_with($model, 'phi');
    }

    public function complete(array $payload): array
    {
        $base  = rtrim(env('OLLAMA_URL', 'http://ollama:11434'), '/');
        $model = ltrim(str_replace('ollama:', '', $payload['model'] ?? env('OLLAMA_DEFAULT_MODEL', 'llama3.2:1b')), '/');

        $messages = [
            ['role' => 'system', 'content' => $payload['system'] ?? 'You are a helpful enterprise AI agent running on a local LLM.'],
            ['role' => 'user',   'content' => $payload['prompt'] ?? ''],
        ];

        try {
            $resp = Http::timeout(60)->post("{$base}/api/chat", [
                'model'    => $model,
                'messages' => $messages,
                'stream'   => false,
                'options'  => [
                    'temperature' => $payload['temperature'] ?? 0.2,
                    'num_predict' => $payload['max_tokens'] ?? 1024,
                ],
            ]);

            if ($resp->successful()) {
                $data = $resp->json();
                $text = $data['message']['content'] ?? '';
                return [
                    'response' => $text,
                    'model'    => "ollama:{$model}",
                    'usage'    => [
                        'prompt_tokens'     => $data['prompt_eval_count'] ?? 0,
                        'completion_tokens' => $data['eval_count'] ?? 0,
                        'total_tokens'      => ($data['prompt_eval_count'] ?? 0) + ($data['eval_count'] ?? 0),
                    ],
                    'raw' => $data,
                ];
            }

            Log::warning('ollama.api_error', ['status' => $resp->status(), 'body' => $resp->body()]);
        } catch (\Throwable $e) {
            Log::warning('ollama.unreachable', ['error' => $e->getMessage()]);
        }

        return [
            'response' => "[ollama-mock:{$model}] " . ($payload['prompt'] ?? ''),
            'model'    => "ollama:{$model}",
            'usage'    => ['prompt_tokens' => 25, 'completion_tokens' => 15, 'total_tokens' => 40],
            'mock'     => true,
        ];
    }
}
