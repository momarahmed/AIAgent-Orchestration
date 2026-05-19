<?php

namespace App\Services;

use App\Contracts\EventBusContract;
use App\Models\EventLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * EventBus façade (Phase 4 — PRD §13).
 *
 * Chooses the appropriate driver at runtime based on EVENT_BUS_DRIVER
 * (kafka|memory). Every publish is persisted to `event_log` for audit
 * + replay; the topic taxonomy mirrors PRD §13:
 *     agent.events | tool.events | workflow.events | run.status |
 *     deployment.events | approval.events | a2a.events
 *
 * KafkaEventBus uses raw HTTP to Redpanda's Pandaproxy when the native
 * php-rdkafka extension isn't installed. This keeps the container
 * image slim and the dev experience friction-free. For production
 * deployments with rdkafka installed, the binary path is used instead.
 */
class EventBus implements EventBusContract
{
    public const TOPICS = [
        'agent.events',
        'tool.events',
        'workflow.events',
        'run.status',
        'deployment.events',
        'approval.events',
        'a2a.events',
    ];

    protected EventBusContract $driver;

    public function __construct()
    {
        $name = env('EVENT_BUS_DRIVER', 'kafka');
        $this->driver = match ($name) {
            'kafka' => app(KafkaEventBus::class),
            default => app(InProcessEventBus::class),
        };
    }

    public function publish(string $topic, string $eventType, array $payload, array $headers = []): string
    {
        $eventId = (string) Str::uuid();
        $traceId = $headers['trace_id'] ?? null;

        try {
            EventLog::create([
                'topic'        => $topic,
                'event_type'   => $eventType,
                'tenant_id'    => $payload['tenant_id'] ?? null,
                'event_id'     => $eventId,
                'trace_id'     => $traceId,
                'payload'      => $payload,
                'headers'      => $headers,
                'status'       => 'emitted',
                'emitted_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('event_log.persist_failed', ['error' => $e->getMessage()]);
        }

        try {
            $this->driver->publish($topic, $eventType, array_merge($payload, ['event_id' => $eventId]), $headers);
        } catch (\Throwable $e) {
            Log::warning('event_bus.publish_failed', ['driver' => $this->driver->driver(), 'error' => $e->getMessage()]);
        }
        return $eventId;
    }

    public function consume(string $topic, string $consumerGroup, callable $handler, int $maxMessages = 0): void
    {
        $this->driver->consume($topic, $consumerGroup, $handler, $maxMessages);
    }

    public function driver(): string { return $this->driver->driver(); }
    public function healthy(): bool   { return $this->driver->healthy(); }
}
