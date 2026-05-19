<?php

namespace App\Services\Providers;

use App\Contracts\ModelProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google ADK (Agent Development Kit) provider adapter (Phase 3).
 *
 * Supports Gemini models through the Google AI/Vertex API.
 * Per-tenant budget enforcement and telemetry via ProviderBudgetService.
 */
class GoogleAdkProvider implements ModelProvider
{
    protected array $supportedModels = [
        'gemini-1.5-pro',
        'gemini-1.5-flash',
        'gemini-2.0-flash',
        'gemini-pro',
    ];

    public function name(): string
    {
        return 'google_adk';
    }

    public function supportsModel(string $model): bool
    {
        return in_array($model, $this->supportedModels, true)
            || str_starts_with($model, 'gemini-');
    }

    /**
     * @param array{system?:string,prompt:string,model:string,temperature?:float,max_tokens?:int,tools?:array} $payload
     * @return array{response:string,model:string,usage?:array,raw?:array,mock?:bool}
     */
    public function complete(array $payload): array
    {
        $apiKey = config('services.google.api_key', env('GOOGLE_AI_API_KEY'));
        $model  = $payload['model'] ?? 'gemini-1.5-flash';

        if (! $apiKey) {
            return $this->mockResponse($model, $payload);
        }

        try {
            $contents = [];

            if (! empty($payload['system'])) {
                $contents[] = [
                    'role'  => 'user',
                    'parts' => [['text' => "[System] {$payload['system']}"]],
                ];
            }

            $contents[] = [
                'role'  => 'user',
                'parts' => [['text' => $payload['prompt']]],
            ];

            $body = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature'    => $payload['temperature'] ?? 0.7,
                    'maxOutputTokens'=> $payload['max_tokens'] ?? 2048,
                ],
            ];

            if (! empty($payload['tools'])) {
                $body['tools'] = $this->formatTools($payload['tools']);
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, $body);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $usage = $data['usageMetadata'] ?? [];

                return [
                    'response' => $text,
                    'model'    => $model,
                    'usage'    => [
                        'prompt_tokens'     => $usage['promptTokenCount'] ?? 0,
                        'completion_tokens' => $usage['candidatesTokenCount'] ?? 0,
                        'total_tokens'      => $usage['totalTokenCount'] ?? 0,
                    ],
                    'raw' => $data,
                ];
            }

            Log::warning('google_adk.api_error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('google_adk.request_failed', ['error' => $e->getMessage()]);
        }

        return $this->mockResponse($model, $payload);
    }

    protected function formatTools(array $tools): array
    {
        $declarations = [];
        foreach ($tools as $tool) {
            $declarations[] = [
                'name'        => $tool['name'] ?? 'unknown',
                'description' => $tool['description'] ?? '',
                'parameters'  => $tool['input_schema'] ?? ['type' => 'object', 'properties' => new \stdClass()],
            ];
        }

        return [['functionDeclarations' => $declarations]];
    }

    protected function mockResponse(string $model, array $payload): array
    {
        return [
            'response' => "[Google ADK mock — {$model}] Received prompt: " . mb_substr($payload['prompt'] ?? '', 0, 100),
            'model'    => $model,
            'usage'    => ['prompt_tokens' => 50, 'completion_tokens' => 30, 'total_tokens' => 80],
            'mock'     => true,
        ];
    }
}
