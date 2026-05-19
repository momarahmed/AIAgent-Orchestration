<?php

namespace App\Services;

use App\Models\SecretRef;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolve secret references via HashiCorp Vault (KV v2).
 * Falls back to .env values for local dev. Staging/prod must use vault.
 */
class SecretService
{
    public function __construct() {}

    public function resolveByName(string $name, string $environment = 'dev', ?int $tenantId = null): ?string
    {
        $ref = SecretRef::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->where('environment', $environment)
            ->where('name', $name)
            ->first();
        if (! $ref) {
            return $this->fallbackEnv($name, $environment);
        }
        return $this->resolveRef($ref->vault_path, $environment);
    }

    public function resolveRef(string $vaultPath, string $environment = 'dev'): ?string
    {
        $url = env('VAULT_ADDR', 'http://vault:8200');
        $token = env('VAULT_TOKEN');
        if (! $token) {
            return $this->fallbackEnv($vaultPath, $environment);
        }
        try {
            $endpoint = rtrim($url, '/') . '/v1/' . ltrim($vaultPath, '/');
            $resp = Http::withHeaders(['X-Vault-Token' => $token])->timeout(5)->get($endpoint);
            if ($resp->successful()) {
                return $resp->json('data.data.value') ?? $resp->json('data.value');
            }
        } catch (\Throwable $e) {
            Log::warning('vault.resolve_failed', ['path' => $vaultPath, 'error' => $e->getMessage()]);
        }
        return $this->fallbackEnv($vaultPath, $environment);
    }

    /**
     * Phase 2 rule: reject promotion if any secret_ref does not point at vault for staging/prod.
     */
    public function validateForEnvironment(array $secretRefs, string $environment): array
    {
        $issues = [];
        if (in_array($environment, ['staging', 'prod'], true)) {
            foreach ($secretRefs as $key => $ref) {
                if (! is_string($ref) || ! str_starts_with($ref, 'vault:') && ! str_starts_with($ref, 'kv/')) {
                    $issues[] = "{$key} must be a vault: reference in {$environment}";
                }
            }
        }
        return $issues;
    }

    protected function fallbackEnv(string $name, string $environment): ?string
    {
        if ($environment !== 'dev') {
            return null;
        }
        $key = strtoupper(str_replace([':', '/', '-'], '_', $name));
        return env($key);
    }
}
