<?php

namespace App\Services;

use App\Contracts\ModelProvider;
use App\Services\Providers\ClaudeProvider;
use App\Services\Providers\OllamaProvider;
use App\Services\Providers\OpenAiProvider;

/**
 * Provider Registry — the runtime adapter map.
 *
 * Phase 2 baseline: OpenAI + Claude.
 * Phase 3: Google ADK (registered via AppServiceProvider::boot).
 * Phase 4: Ollama / local LLMs, plus a register() entry-point so future
 *          providers (vLLM, AWS Bedrock, Azure OpenAI…) can plug in.
 */
class ProviderRegistry
{
    /** @var ModelProvider[] */
    protected array $providers = [];

    public function __construct(OpenAiProvider $openai, ClaudeProvider $claude, OllamaProvider $ollama)
    {
        $this->providers = [$openai, $claude, $ollama];
    }

    public function register(ModelProvider $provider): void
    {
        foreach ($this->providers as $p) {
            if ($p->name() === $provider->name()) {
                return; // already registered
            }
        }
        $this->providers[] = $provider;
    }

    public function for(string $providerName, string $model): ModelProvider
    {
        foreach ($this->providers as $p) {
            if ($p->name() === $providerName) {
                return $p;
            }
        }
        foreach ($this->providers as $p) {
            if ($p->supportsModel($model)) {
                return $p;
            }
        }
        return $this->providers[0];
    }

    public function getByName(string $name): ?ModelProvider
    {
        foreach ($this->providers as $p) {
            if ($p->name() === $name) return $p;
        }
        return null;
    }

    /** @return ModelProvider[] */
    public function all(): array
    {
        return $this->providers;
    }
}
