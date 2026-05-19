<?php

namespace App\Contracts;

/**
 * Event Bus abstraction (Phase 4 — PRD §13).
 *
 * Keeps Kafka-specifics out of call-sites. Implementations:
 *   - KafkaEventBus    (Redpanda / MSK / Confluent — Phase 4 primary)
 *   - InProcessEventBus (Phase 2 baseline — Redis Streams emulation;
 *                        used when EVENT_BUS_DRIVER=memory or Kafka is
 *                        unreachable so dev workflows keep working).
 */
interface EventBusContract
{
    /**
     * Publish an event. Returns the event id (uuid) for tracing.
     */
    public function publish(string $topic, string $eventType, array $payload, array $headers = []): string;

    /**
     * Consume a topic with a callback. Should be called by the
     * `eventbus:consume` artisan command in a long-running worker.
     *
     * @param callable(array): void $handler
     */
    public function consume(string $topic, string $consumerGroup, callable $handler, int $maxMessages = 0): void;

    public function driver(): string;

    public function healthy(): bool;
}
