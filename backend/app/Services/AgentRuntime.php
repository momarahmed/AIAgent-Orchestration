<?php

namespace App\Services;

use App\Models\Agent;

/**
 * Agent runtime — uses ProviderRegistry (Phase 2) so OpenAI / Claude
 * adapters can be swapped without touching the workflow engine.
 */
class AgentRuntime
{
    public function __construct(protected ProviderRegistry $providers) {}

    public function execute(?int $agentId, string $prompt, array $context = []): array
    {
        $agent = $agentId ? Agent::with('currentVersion')->find($agentId) : null;
        $version = $agent?->currentVersion;
        $modelConfig = $version?->model_config ?? ['provider' => 'openai', 'model' => 'gpt-4o-mini'];
        $model = $modelConfig['model'] ?? 'gpt-4o-mini';
        $providerName = $modelConfig['provider'] ?? 'openai';
        $fallback = $modelConfig['fallback_model'] ?? null;

        $provider = $this->providers->for($providerName, $model);
        $result = $provider->complete([
            'system' => $version?->system_instructions,
            'prompt' => is_string($prompt) ? $prompt : json_encode($prompt),
            'model' => $model,
            'temperature' => $modelConfig['temperature'] ?? 0.2,
        ]);

        if (! empty($result['error']) && $fallback) {
            $result = $provider->complete([
                'system' => $version?->system_instructions,
                'prompt' => is_string($prompt) ? $prompt : json_encode($prompt),
                'model' => $fallback,
                'temperature' => $modelConfig['temperature'] ?? 0.2,
            ]);
        }

        return array_merge(['agent_id' => $agentId], $result);
    }
}
