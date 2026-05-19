<?php

namespace App\Services;

use App\Models\SecretRef;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Vault-backed secret management (Phase 2 baseline + Phase 3 hardening).
 *
 * Phase 3 additions:
 * - Credential rotation support (Vault dynamic secrets)
 * - Automatic secret refresh inside long-running MCP server pods
 * - Export scanner blocks template export containing non-vault secret-like strings
 * - Short-lived token cache with grace window for Vault outages
 */
class SecretService
{
    protected const CACHE_TTL = 300; // 5 minutes — short-lived secret cache
    protected const GRACE_TTL = 60;  // 1 minute grace window on vault outage

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
        $cacheKey = "vault_secret:" . md5($vaultPath . $environment);

        // Check short-lived cache first (grace window for vault outages)
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $url = env('VAULT_ADDR', 'http://vault:8200');
        $token = env('VAULT_TOKEN');
        if (! $token) {
            return $this->fallbackEnv($vaultPath, $environment);
        }

        try {
            $endpoint = rtrim($url, '/') . '/v1/' . ltrim($vaultPath, '/');
            $resp = Http::withHeaders(['X-Vault-Token' => $token])->timeout(5)->get($endpoint);
            if ($resp->successful()) {
                $value = $resp->json('data.data.value') ?? $resp->json('data.value');
                if ($value !== null) {
                    Cache::put($cacheKey, $value, self::CACHE_TTL);
                }
                return $value;
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

    /**
     * Phase 3: Rotate a secret in Vault (write new value, invalidate cache).
     */
    public function rotateSecret(string $vaultPath, string $newValue): bool
    {
        $url = env('VAULT_ADDR', 'http://vault:8200');
        $token = env('VAULT_TOKEN');
        if (! $token) {
            return false;
        }

        try {
            $endpoint = rtrim($url, '/') . '/v1/' . ltrim($vaultPath, '/');
            $resp = Http::withHeaders(['X-Vault-Token' => $token])
                ->timeout(5)
                ->post($endpoint, ['data' => ['value' => $newValue]]);

            if ($resp->successful()) {
                Cache::forget("vault_secret:" . md5($vaultPath . 'dev'));
                Cache::forget("vault_secret:" . md5($vaultPath . 'staging'));
                Cache::forget("vault_secret:" . md5($vaultPath . 'prod'));

                Log::info('vault.secret_rotated', ['path' => $vaultPath]);
                return true;
            }
        } catch (\Throwable $e) {
            Log::error('vault.rotation_failed', ['path' => $vaultPath, 'error' => $e->getMessage()]);
        }

        return false;
    }

    /**
     * Phase 3: Request dynamic secret from Vault (e.g., database credentials).
     */
    public function requestDynamicSecret(string $role, string $backend = 'database'): ?array
    {
        $url = env('VAULT_ADDR', 'http://vault:8200');
        $token = env('VAULT_TOKEN');
        if (! $token) {
            return null;
        }

        try {
            $endpoint = rtrim($url, '/') . "/v1/{$backend}/creds/{$role}";
            $resp = Http::withHeaders(['X-Vault-Token' => $token])->timeout(10)->get($endpoint);

            if ($resp->successful()) {
                $data = $resp->json('data', []);
                return [
                    'username'   => $data['username'] ?? null,
                    'password'   => $data['password'] ?? null,
                    'lease_id'   => $resp->json('lease_id'),
                    'lease_ttl'  => $resp->json('lease_duration'),
                ];
            }
        } catch (\Throwable $e) {
            Log::error('vault.dynamic_secret_failed', ['role' => $role, 'error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Phase 3: Refresh all cached secrets (called by long-running MCP server pods).
     */
    public function refreshAllSecrets(?int $tenantId = null): int
    {
        $refs = SecretRef::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->get();

        $refreshed = 0;
        foreach ($refs as $ref) {
            $cacheKey = "vault_secret:" . md5($ref->vault_path . $ref->environment);
            Cache::forget($cacheKey);

            $value = $this->resolveRef($ref->vault_path, $ref->environment);
            if ($value !== null) {
                $refreshed++;
            }
        }

        return $refreshed;
    }

    /**
     * Phase 3: Scan content for secret-like strings.
     * Used by export scanner to block template exports containing secrets.
     */
    public function scanForSecrets(string $content): array
    {
        $patterns = [
            'aws_key'       => '/AKIA[0-9A-Z]{16}/',
            'private_key'   => '/-----BEGIN (RSA |EC |DSA )?PRIVATE KEY-----/',
            'vault_token'   => '/hvs\.[a-zA-Z0-9]{24,}/',
            'github_token'  => '/gh[ps]_[a-zA-Z0-9]{36,}/',
            'generic_password' => '/(password|secret|api_key|token)\s*[=:]\s*["\']?[a-zA-Z0-9\/\+]{20,}/i',
            'connection_string' => '/(?:mysql|postgres|mongodb|redis):\/\/[^:]+:[^@]+@/i',
        ];

        $found = [];
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $found[] = [
                    'type'       => $name,
                    'redacted'   => mb_substr($matches[0], 0, 8) . '***REDACTED***',
                    'message'    => "Potential {$name} found in content",
                ];
            }
        }

        return $found;
    }

    /**
     * Check Vault health status.
     */
    public function healthCheck(): array
    {
        $url = env('VAULT_ADDR', 'http://vault:8200');
        try {
            $resp = Http::timeout(3)->get(rtrim($url, '/') . '/v1/sys/health');
            return [
                'healthy'     => $resp->successful(),
                'initialized' => $resp->json('initialized', false),
                'sealed'      => $resp->json('sealed', true),
                'version'     => $resp->json('version'),
            ];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'error' => $e->getMessage()];
        }
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
