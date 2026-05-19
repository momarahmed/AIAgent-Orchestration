<?php

namespace App\Services;

use App\Contracts\ModelProvider;
use App\Services\Providers\ClaudeProvider;
use App\Services\Providers\OpenAiProvider;

/**
 * Model Control Plane (Phase 2 baseline) — routes calls to a configured provider.
 * Phase 4 adds policy-based routing and full MCP-style provider selection.
 */
class ProviderRegistry
{
    /** @var ModelProvider[] */
    protected array $providers;

    public function __construct(OpenAiProvider $openai, ClaudeProvider $claude)
    {
        $this->providers = [$openai, $claude];
    }

    public function for(string $providerName, string $model): ModelProvider
    {
        foreach ($this->providers as $p) {
            if ($p->name() === $providerName || $p->supportsModel($model)) {
                return $p;
            }
        }
        return $this->providers[0];
    }

    /** @return ModelProvider[] */
    public function all(): array
    {
        return $this->providers;
    }
}
