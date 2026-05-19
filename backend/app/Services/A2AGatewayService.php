<?php

namespace App\Services;

use App\Models\A2AMessage;
use App\Models\A2APartner;
use App\Models\Agent;
use App\Support\Audit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * A2A Gateway Service (PRD §13.2 / §13.3 — Phase 4)
 *
 * Implements the message-passing surface of the A2A Protocol
 * (https://a2a-protocol.org/latest/). Three responsibilities:
 *
 *   1. Outbound — internal agents calling external partner agents.
 *   2. Inbound  — external partners invoking internal agents.
 *   3. Audit    — every message logged with the PRD §13.3 schema.
 *
 * Authorization runs through OpaPolicyService against the eamcp.a2a
 * package (allowlists + injection guards). Failed authz writes an audit
 * event and the message is stored with status=blocked for the audit log.
 */
class A2AGatewayService
{
    public function __construct(
        protected OpaPolicyService $opa,
        protected PromptInjectionService $injection,
        protected EventBus $bus,
    ) {}

    // ─── Outbound (internal → external) ──────────────────────────────

    public function send(A2APartner $partner, array $payload, array $opts = []): A2AMessage
    {
        $message = $this->logOutbound($partner, $payload, $opts);

        $authz = $this->authorize(direction: 'outbound', partner: $partner, payload: $payload);
        if (! $authz['allowed']) {
            $message->update(['status' => 'blocked', 'error' => $authz['reason']]);
            Audit::record('a2a', 'outbound_blocked', 'a2a_partner', $partner->id, [
                'reason'   => $authz['reason'],
                'message_id' => $message->message_id,
            ], tenantId: $partner->tenant_id);
            return $message;
        }

        try {
            $start = microtime(true);
            $resp = Http::timeout(30)
                ->withHeaders($this->authHeaders($partner))
                ->post($partner->endpoint_url, [
                    'message_id'      => $message->message_id,
                    'conversation_id' => $message->conversation_id,
                    'from_agent'      => $message->from_agent,
                    'to_agent'        => $message->to_agent,
                    'message_type'    => $message->message_type,
                    'priority'        => $message->priority,
                    'payload'         => $payload,
                    'created_at'      => $message->created_at?->toIso8601String(),
                ]);
            $latency = (int) ((microtime(true) - $start) * 1000);

            $message->update([
                'status'     => $resp->successful() ? 'delivered' : 'failed',
                'error'      => $resp->successful() ? null : "HTTP {$resp->status()}: " . mb_substr($resp->body(), 0, 500),
                'latency_ms' => $latency,
                'headers'    => $resp->headers(),
            ]);

            $this->bus->publish('agent.events', 'a2a.outbound.delivered', [
                'message_id'  => $message->message_id,
                'partner_id'  => $partner->partner_id,
                'tenant_id'   => $partner->tenant_id,
                'status'      => $message->status,
                'latency_ms'  => $latency,
            ]);
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed', 'error' => $e->getMessage()]);
            Log::warning('a2a.outbound_failed', ['error' => $e->getMessage(), 'partner_id' => $partner->partner_id]);
        }

        return $message;
    }

    // ─── Inbound (external → internal) ───────────────────────────────

    /**
     * @param array $envelope  raw A2A envelope as received from the partner
     */
    public function receive(A2APartner $partner, array $envelope): array
    {
        $payload = $envelope['payload'] ?? [];

        $message = A2AMessage::create([
            'message_id'      => $envelope['message_id'] ?? (string) Str::uuid(),
            'conversation_id' => $envelope['conversation_id'] ?? (string) Str::uuid(),
            'tenant_id'       => $partner->tenant_id,
            'from_agent'      => $envelope['from_agent'] ?? "{$partner->partner_id}:unknown",
            'to_agent'        => $envelope['to_agent'] ?? 'internal:unknown',
            'from_scope'      => 'external',
            'to_scope'        => 'internal',
            'message_type'    => $envelope['message_type'] ?? 'request',
            'priority'        => $envelope['priority'] ?? 'normal',
            'direction'       => 'inbound',
            'status'          => 'pending',
            'payload'         => $payload,
            'headers'         => $envelope['headers'] ?? null,
        ]);

        $authz = $this->authorize(direction: 'inbound', partner: $partner, payload: $payload);
        if (! $authz['allowed']) {
            $message->update(['status' => 'blocked', 'error' => $authz['reason']]);
            Audit::record('a2a', 'inbound_blocked', 'a2a_partner', $partner->id, [
                'reason'   => $authz['reason'],
                'message_id' => $message->message_id,
            ], tenantId: $partner->tenant_id);
            return ['accepted' => false, 'reason' => $authz['reason'], 'message_id' => $message->message_id];
        }

        // Dispatch on the event bus so workflow triggers can react.
        $this->bus->publish('agent.events', 'a2a.inbound.received', [
            'message_id' => $message->message_id,
            'partner_id' => $partner->partner_id,
            'tenant_id'  => $partner->tenant_id,
            'to_agent'   => $message->to_agent,
            'payload'    => $payload,
        ]);

        $message->update(['status' => 'delivered']);
        Audit::record('a2a', 'inbound_received', 'a2a_partner', $partner->id, [
            'message_id' => $message->message_id,
            'to_agent'   => $message->to_agent,
        ], tenantId: $partner->tenant_id);

        return ['accepted' => true, 'message_id' => $message->message_id];
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    protected function logOutbound(A2APartner $partner, array $payload, array $opts): A2AMessage
    {
        $fromAgent = $opts['from_agent'] ?? 'internal:platform';
        if (! empty($opts['from_agent_id'])) {
            $a = Agent::find($opts['from_agent_id']);
            if ($a) $fromAgent = "internal:{$a->slug}";
        }
        return A2AMessage::create([
            'message_id'      => $opts['message_id'] ?? (string) Str::uuid(),
            'conversation_id' => $opts['conversation_id'] ?? (string) Str::uuid(),
            'tenant_id'       => $partner->tenant_id,
            'workflow_run_id' => $opts['workflow_run_id'] ?? null,
            'from_agent'      => $fromAgent,
            'to_agent'        => "{$partner->partner_id}:" . ($opts['to_agent'] ?? 'default'),
            'from_scope'      => 'internal',
            'to_scope'        => 'external',
            'message_type'    => $opts['message_type'] ?? 'request',
            'priority'        => $opts['priority'] ?? 'normal',
            'direction'       => 'outbound',
            'status'          => 'pending',
            'payload'         => $payload,
        ]);
    }

    protected function authorize(string $direction, A2APartner $partner, array $payload): array
    {
        if ($partner->status !== 'active' || $partner->quarantined) {
            return ['allowed' => false, 'reason' => "partner_status_{$partner->status}"];
        }

        $scan = $this->injection->scan(json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '');
        $flags = [];
        if (! empty($scan['reasons'])) $flags[] = 'critical';
        if (! empty($scan['warnings'])) $flags[] = 'warning';

        $input = [
            'direction'  => $direction,
            'from_agent' => [
                'scope'      => $direction === 'outbound' ? 'internal' : 'external',
                'tenant_id'  => $partner->tenant_id,
                'partner_id' => $partner->partner_id,
            ],
            'to_agent' => [
                'scope'      => $direction === 'outbound' ? 'external' : 'internal',
                'tenant_id'  => $partner->tenant_id,
                'partner_id' => $partner->partner_id,
            ],
            'injection_flags' => $flags,
        ];

        $decision = $this->opa->evaluate('eamcp/a2a/allow', $input);
        if (! ($decision['allowed'] ?? false)) {
            return ['allowed' => false, 'reason' => $decision['reason'] ?? 'a2a_policy_denied'];
        }
        return ['allowed' => true];
    }

    protected function authHeaders(A2APartner $partner): array
    {
        $headers = ['Content-Type' => 'application/json', 'X-A2A-Protocol' => '1.0'];
        if ($partner->auth_type === 'bearer' && $partner->secret_ref) {
            $token = app(SecretService::class)->resolveRef($partner->secret_ref);
            if ($token) $headers['Authorization'] = "Bearer {$token}";
        }
        if ($partner->auth_type === 'hmac') {
            $secret = $partner->secret_ref
                ? app(SecretService::class)->resolveRef($partner->secret_ref)
                : env('A2A_GATEWAY_SHARED_SECRET', 'eamcp-a2a-dev');
            $sig    = hash_hmac('sha256', $partner->partner_id . '|' . now()->timestamp, $secret ?? '');
            $headers['X-A2A-Signature'] = $sig;
            $headers['X-A2A-Timestamp'] = (string) now()->timestamp;
        }
        return $headers;
    }
}
