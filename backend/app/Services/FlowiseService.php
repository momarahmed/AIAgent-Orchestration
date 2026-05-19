<?php

namespace App\Services;

use App\Models\FlowiseAgent;
use App\Models\FlowiseRun;
use App\Models\FlowiseSync;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * FlowiseService — central HTTP client + two-way sync engine for the
 * /workflow-studio surface.
 *
 * The Laravel app is the only thing that talks to Flowise's REST API
 * directly: the browser never sees FLOWISE_API_KEY. Embedded canvases
 * use a public URL (FLOWISE_EMBED_URL) plus per-tenant auth on the
 * Flowise side.
 *
 * If Flowise is unreachable (dev mode without the container) we degrade
 * to a "local-only" mode: writes still persist locally and are flagged
 * in flowise_syncs as failed/pending so the user can retry from the UI.
 */
class FlowiseService
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.flowise.url'));
    }

    public function embedUrl(): string
    {
        return rtrim((string) config('services.flowise.embed_url', ''), '/');
    }

    /**
     * Health check — returns ['ok' => bool, 'detail' => string].
     * Used by the dashboard "Sync" button to surface clear errors.
     */
    public function health(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'detail' => 'FLOWISE_API_URL is not configured'];
        }

        try {
            $resp = $this->http()->get($this->base() . '/api/v1/ping');
            return [
                'ok'     => $resp->successful(),
                'detail' => $resp->successful() ? 'Flowise reachable' : "HTTP {$resp->status()}",
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'detail' => 'Flowise unreachable: ' . $e->getMessage()];
        }
    }

    // ─── Push: local → Flowise ────────────────────────────────────────

    /**
     * Create or update the chatflow on the Flowise side. Returns the
     * Flowise payload (containing the chatflowid) on success.
     *
     * @throws \RuntimeException when Flowise rejects the call. The caller
     *         should catch and persist a flowise_syncs row with the error.
     */
    public function pushAgent(FlowiseAgent $agent): array
    {
        $payload = [
            'name'         => $agent->name,
            'flowData'     => json_encode($agent->workflow_config ?? $this->blankFlow()),
            'deployed'     => $agent->status === 'active',
            'isPublic'     => false,
            'category'     => 'eamcp',
            'type'         => 'CHATFLOW',
        ];

        $http = $this->http();

        // Update existing chatflow
        if ($agent->flowise_chatflow_id) {
            $resp = $http->put(
                $this->base() . "/api/v1/chatflows/{$agent->flowise_chatflow_id}",
                $payload,
            );
        } else {
            $resp = $http->post($this->base() . '/api/v1/chatflows', $payload);
        }

        if (! $resp->successful()) {
            throw new \RuntimeException(
                "Flowise push failed (HTTP {$resp->status()}): " . $resp->body(),
            );
        }

        $data = $resp->json();
        $chatflowId = $data['id'] ?? $data['chatflowid'] ?? $agent->flowise_chatflow_id;

        $agent->update([
            'flowise_chatflow_id'  => $chatflowId,
            'flowise_api_endpoint' => $this->base() . "/api/v1/prediction/{$chatflowId}",
            'last_synced_at'       => now(),
        ]);

        $this->recordSync($agent, 'push', 'success', $data);

        return $data;
    }

    public function deleteAgent(FlowiseAgent $agent): void
    {
        if (! $agent->flowise_chatflow_id) {
            return;
        }

        try {
            $resp = $this->http()->delete(
                $this->base() . "/api/v1/chatflows/{$agent->flowise_chatflow_id}",
            );
            if (! $resp->successful() && $resp->status() !== 404) {
                throw new \RuntimeException("HTTP {$resp->status()}: {$resp->body()}");
            }
            $this->recordSync($agent, 'push', 'success', ['action' => 'delete']);
        } catch (\Throwable $e) {
            Log::warning('flowise.delete_failed', ['agent_id' => $agent->id, 'msg' => $e->getMessage()]);
            $this->recordSync($agent, 'push', 'failed', ['action' => 'delete'], $e->getMessage());
        }
    }

    // ─── Pull: Flowise → local ────────────────────────────────────────

    /**
     * List all chatflows on the Flowise side. Used by import dialog and
     * by syncTenant() below.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listRemoteChatflows(): array
    {
        $resp = $this->http()->get($this->base() . '/api/v1/chatflows');
        if (! $resp->successful()) {
            throw new \RuntimeException("Flowise list failed (HTTP {$resp->status()}): " . $resp->body());
        }
        return $resp->json() ?: [];
    }

    /**
     * Pull a single chatflow into the local DB (creating if missing,
     * updating in place if already mirrored).
     */
    public function importChatflow(int $tenantId, ?int $projectId, string $chatflowId, ?int $ownerId = null): FlowiseAgent
    {
        $resp = $this->http()->get($this->base() . "/api/v1/chatflows/{$chatflowId}");
        if (! $resp->successful()) {
            throw new \RuntimeException("Flowise fetch failed (HTTP {$resp->status()}): " . $resp->body());
        }
        $remote = $resp->json();

        $name     = $remote['name'] ?? "Imported chatflow {$chatflowId}";
        $flowData = $this->decodeFlowData($remote['flowData'] ?? null);

        $agent = FlowiseAgent::firstOrNew(['flowise_chatflow_id' => $chatflowId]);
        $agent->fill([
            'tenant_id'            => $tenantId,
            'project_id'           => $projectId,
            'owner_id'             => $ownerId,
            'name'                 => $name,
            'slug'                 => $agent->slug ?: Str::slug($name) . '-' . Str::lower(Str::random(4)),
            'description'          => $remote['description'] ?? null,
            'status'               => ($remote['deployed'] ?? false) ? 'active' : 'draft',
            'flowise_chatflow_id'  => $chatflowId,
            'flowise_api_endpoint' => $this->base() . "/api/v1/prediction/{$chatflowId}",
            'workflow_config'      => $flowData,
            'tools_config'         => $this->extractTools($flowData),
            'model_config'         => $this->extractModel($flowData),
            'last_synced_at'       => now(),
        ]);
        $agent->save();

        $this->recordSync($agent, 'pull', 'success', ['chatflow_id' => $chatflowId]);

        return $agent->fresh();
    }

    /**
     * Pull-sync every chatflow visible to Flowise into the given tenant.
     * Returns ['imported' => int, 'updated' => int, 'errors' => array].
     */
    public function syncTenant(int $tenantId, ?int $projectId = null, ?int $ownerId = null): array
    {
        $imported = 0; $updated = 0; $errors = [];
        try {
            $remote = $this->listRemoteChatflows();
        } catch (\Throwable $e) {
            return ['imported' => 0, 'updated' => 0, 'errors' => [$e->getMessage()]];
        }

        foreach ($remote as $cf) {
            $chatflowId = $cf['id'] ?? $cf['chatflowid'] ?? null;
            if (! $chatflowId) continue;
            try {
                $existed = FlowiseAgent::where('flowise_chatflow_id', $chatflowId)->exists();
                $this->importChatflow($tenantId, $projectId, $chatflowId, $ownerId);
                $existed ? $updated++ : $imported++;
            } catch (\Throwable $e) {
                $errors[] = "{$chatflowId}: " . $e->getMessage();
            }
        }
        return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
    }

    // ─── Execution ────────────────────────────────────────────────────

    /**
     * Run an agent. Persists a FlowiseRun row with timing/tokens/output.
     */
    public function runAgent(FlowiseAgent $agent, array $input, ?int $userId = null): FlowiseRun
    {
        $run = FlowiseRun::create([
            'flowise_agent_id' => $agent->id,
            'tenant_id'        => $agent->tenant_id,
            'user_id'          => $userId,
            'status'           => 'running',
            'input'            => $input,
            'session_id'       => $input['session_id'] ?? Str::uuid()->toString(),
            'started_at'       => now(),
        ]);

        if (! $agent->flowise_chatflow_id) {
            $run->update([
                'status'        => 'failed',
                'error_message' => 'Agent has no flowise_chatflow_id — push it to Flowise first.',
                'completed_at'  => now(),
                'duration_ms'   => 0,
            ]);
            $agent->update(['last_run_status' => 'failed', 'last_run_at' => now()]);
            return $run->fresh();
        }

        $start = microtime(true);
        try {
            $resp = $this->http()->timeout(120)->post(
                $this->base() . "/api/v1/prediction/{$agent->flowise_chatflow_id}",
                [
                    'question'       => $input['question'] ?? $input['prompt'] ?? '',
                    'overrideConfig' => $input['config'] ?? new \stdClass(),
                    'history'        => $input['history'] ?? [],
                    'sessionId'      => $run->session_id,
                ],
            );
            $duration = (int) ((microtime(true) - $start) * 1000);

            if (! $resp->successful()) {
                throw new \RuntimeException("HTTP {$resp->status()}: " . $resp->body());
            }
            $body = $resp->json();
            $usage = $body['tokenUsage'] ?? $body['usage'] ?? [];

            $run->update([
                'status'            => 'succeeded',
                'output'            => $body,
                'tool_calls'        => $body['usedTools'] ?? $body['tool_calls'] ?? null,
                'conversation'      => $body['chatHistory'] ?? null,
                'prompt_tokens'     => $usage['prompt_tokens']     ?? $usage['promptTokens']     ?? null,
                'completion_tokens' => $usage['completion_tokens'] ?? $usage['completionTokens'] ?? null,
                'total_tokens'      => $usage['total_tokens']      ?? $usage['totalTokens']      ?? null,
                'duration_ms'       => $duration,
                'completed_at'      => now(),
            ]);
            $agent->update(['last_run_status' => 'succeeded', 'last_run_at' => now()]);
        } catch (\Throwable $e) {
            $duration = (int) ((microtime(true) - $start) * 1000);
            $run->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'duration_ms'   => $duration,
                'completed_at'  => now(),
            ]);
            $agent->update(['last_run_status' => 'failed', 'last_run_at' => now()]);
            Log::warning('flowise.run_failed', ['agent_id' => $agent->id, 'msg' => $e->getMessage()]);
        }

        return $run->fresh();
    }

    // ─── Internals ────────────────────────────────────────────────────

    public function recordSync(?FlowiseAgent $agent, string $direction, string $status, ?array $payload = null, ?string $error = null): FlowiseSync
    {
        return FlowiseSync::create([
            'flowise_agent_id'    => $agent?->id,
            'tenant_id'           => $agent?->tenant_id ?? 0,
            'direction'           => $direction,
            'sync_status'         => $status,
            'flowise_chatflow_id' => $agent?->flowise_chatflow_id,
            'payload'             => $payload,
            'sync_error'          => $error,
            'last_synced_at'      => $status === 'success' ? now() : null,
        ]);
    }

    protected function http(): PendingRequest
    {
        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        $key = (string) config('services.flowise.api_key', '');
        if ($key !== '') {
            $headers['Authorization'] = 'Bearer ' . $key;
        }
        return Http::timeout(30)->withHeaders($headers)->acceptJson();
    }

    protected function base(): string
    {
        return rtrim((string) config('services.flowise.url', ''), '/');
    }

    protected function blankFlow(): array
    {
        return ['nodes' => [], 'edges' => [], 'viewport' => ['x' => 0, 'y' => 0, 'zoom' => 1]];
    }

    protected function decodeFlowData(mixed $raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw) && $raw !== '') {
            try { return json_decode($raw, true, 512, JSON_THROW_ON_ERROR) ?: $this->blankFlow(); }
            catch (\Throwable) { return $this->blankFlow(); }
        }
        return $this->blankFlow();
    }

    /** Best-effort: surface tool node names so the UI can show them as chips. */
    protected function extractTools(array $flow): array
    {
        $tools = [];
        foreach ($flow['nodes'] ?? [] as $n) {
            $cat = strtolower((string) ($n['data']['category'] ?? $n['data']['type'] ?? ''));
            if (str_contains($cat, 'tool') || str_contains($cat, 'agent')) {
                $tools[] = $n['data']['label'] ?? $n['data']['name'] ?? $n['id'] ?? 'tool';
            }
        }
        return array_values(array_unique($tools));
    }

    protected function extractModel(array $flow): array
    {
        foreach ($flow['nodes'] ?? [] as $n) {
            $cat = strtolower((string) ($n['data']['category'] ?? ''));
            if (str_contains($cat, 'chat model') || str_contains($cat, 'llm')) {
                return [
                    'name'     => $n['data']['name']     ?? null,
                    'provider' => $n['data']['provider'] ?? null,
                    'inputs'   => $n['data']['inputs']   ?? null,
                ];
            }
        }
        return [];
    }
}
