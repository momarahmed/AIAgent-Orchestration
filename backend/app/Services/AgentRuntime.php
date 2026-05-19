<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\Http;

/**
 * Phase-1 agent runtime prototype (LangGraph-equivalent node executor).
 * Provider adapter interface — swap OpenAI / Ollama / Claude in Phase 2+.
 */
class AgentRuntime
{
    public function execute(?int $agentId, string $prompt, array $context = []): array
    {
        $agent = $agentId ? Agent::with('currentVersion')->find($agentId) : null;
        $version = $agent?->currentVersion;
        $modelConfig = $version?->model_config ?? ['provider' => 'openai', 'model' => 'gpt-4o-mini'];
        $model = $modelConfig['model'] ?? 'gpt-4o-mini';
        $fallback = $modelConfig['fallback_model'] ?? null;

        $apiKey = env('OPENAI_API_KEY');
        if ($apiKey) {
            $result = $this->callOpenAi($apiKey, $model, $version?->system_instructions, $prompt, $agentId);
            if (! isset($result['error']) || ! $fallback) {
                return $result;
            }
            return $this->callOpenAi($apiKey, $fallback, $version?->system_instructions, $prompt, $agentId);
        }

        return [
            'agent_id' => $agentId,
            'model' => $model,
            'response' => "[mock] Agent '{$agent?->name}' processed: " . (is_string($prompt) ? $prompt : json_encode($prompt)),
            'mock' => true,
        ];
    }

    protected function callOpenAi(string $apiKey, string $model, ?string $system, string $prompt, ?int $agentId): array
    {
        try {
            $resp = Http::withToken($apiKey)->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system ?? 'You are a helpful enterprise AI agent.'],
                    ['role' => 'user', 'content' => is_string($prompt) ? $prompt : json_encode($prompt)],
                ],
            ]);
            if ($resp->successful()) {
                return [
                    'agent_id' => $agentId,
                    'model' => $model,
                    'response' => $resp->json('choices.0.message.content'),
                    'usage' => $resp->json('usage'),
                ];
            }
            return ['agent_id' => $agentId, 'error' => 'Provider call failed', 'status' => $resp->status()];
        } catch (\Throwable $e) {
            return ['agent_id' => $agentId, 'error' => $e->getMessage()];
        }
    }
}
