<?php

namespace App\Services;

use App\Models\MarketplaceSigningKey;

/**
 * Marketplace artifact signing — cosign / Sigstore–style HMAC stub.
 *
 * In production this would integrate with cosign / Sigstore using key material
 * stored in Vault. For local/dev we use a deterministic HMAC-SHA256 over the
 * canonical manifest JSON. Production deploys override the `MARKETPLACE_SIGNING_KEY`
 * env variable and may swap the implementation for a Cosign provider.
 *
 * PRD Section 21.3: "Import unsigned external template" is denied by default in
 * staging/prod environments; this service produces and verifies the signatures
 * that satisfy the OPA gate.
 */
class MarketplaceSignatureService
{
    public function activeKey(?int $tenantId = null): MarketplaceSigningKey
    {
        $query = MarketplaceSigningKey::where('is_active', true);
        if ($tenantId) {
            $query->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            });
        }
        $key = $query->orderByRaw('tenant_id IS NULL')->first();

        if (! $key) {
            $key = MarketplaceSigningKey::create([
                'tenant_id'  => null,
                'key_id'     => 'platform-default-' . substr(hash('sha256', config('app.key', 'dev')), 0, 12),
                'algorithm'  => 'HMAC-SHA256',
                'public_key' => null,
                'is_active'  => true,
            ]);
        }
        return $key;
    }

    public function sign(array $manifest, ?int $tenantId = null): array
    {
        $key       = $this->activeKey($tenantId);
        $canonical = $this->canonicalize($manifest);
        $secret    = (string) (env('MARKETPLACE_SIGNING_KEY') ?: config('app.key'));
        $sig       = hash_hmac('sha256', $canonical, $secret);

        return [
            'key_id'     => $key->key_id,
            'algorithm'  => $key->algorithm,
            'signature'  => $sig,
            'canonical_hash' => hash('sha256', $canonical),
        ];
    }

    public function verify(array $manifest, string $signature, ?string $keyId = null): bool
    {
        $canonical = $this->canonicalize($manifest);
        $secret    = (string) (env('MARKETPLACE_SIGNING_KEY') ?: config('app.key'));
        $expected  = hash_hmac('sha256', $canonical, $secret);
        return hash_equals($expected, $signature);
    }

    public function sbomHash(array $manifest): string
    {
        // SBOM proxy: sha256 of the manifest plus its dependency list when present.
        $deps = $manifest['dependencies'] ?? $manifest['required_connectors'] ?? [];
        return hash('sha256', json_encode($manifest) . '|' . json_encode($deps));
    }

    private function canonicalize(array $data): string
    {
        return $this->stableJson($data);
    }

    private function stableJson(mixed $data): string
    {
        if (is_array($data)) {
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);
            if ($isAssoc) {
                ksort($data);
                $parts = [];
                foreach ($data as $k => $v) {
                    $parts[] = json_encode((string) $k) . ':' . $this->stableJson($v);
                }
                return '{' . implode(',', $parts) . '}';
            }
            return '[' . implode(',', array_map(fn ($v) => $this->stableJson($v), $data)) . ']';
        }
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
