<?php

namespace App\Services;

use App\Contracts\EventBusContract;
use App\Models\EventLog;
use Illuminate\Support\Facades\Redis;

/**
 * Redis-Streams / in-process EventBus (Phase 2 baseline, Phase 4 fallback).
 *
 * Used when EVENT_BUS_DRIVER=memory or when Kafka is unreachable. Backed
 * by Redis Streams so multi-process consumers still see events; consumer
 * groups are stored under the same EventLog audit trail.
 */
class InProcessEventBus implements EventBusContract
{
    public function driver(): string { return 'redis-streams'; }

    public function healthy(): bool
    {
        try { Redis::ping(); return true; } catch (\Throwable) { return false; }
    }

    public function publish(string $topic, string $eventType, array $payload, array $headers = []): string
    {
        try {
            Redis::xadd("events:{$topic}", '*', [
                'event_type' => $eventType,
                'payload'    => json_encode($payload),
                'headers'    => json_encode($headers),
            ]);
        } catch (\Throwable) {
            // already logged to event_log by EventBus façade
        }
        return $payload['event_id'] ?? '';
    }

    public function consume(string $topic, string $consumerGroup, callable $handler, int $maxMessages = 0): void
    {
        $stream = "events:{$topic}";
        try {
            Redis::xgroup('CREATE', $stream, $consumerGroup, '$', 'MKSTREAM');
        } catch (\Throwable) {
            // group probably exists
        }

        $count = 0;
        $consumerName = gethostname() . ':' . getmypid();

        while ($maxMessages === 0 || $count < $maxMessages) {
            try {
                $messages = Redis::xreadgroup($consumerGroup, $consumerName, [$stream => '>'], 10, 5000);
                if (! $messages) continue;
                foreach ($messages as $streamName => $entries) {
                    foreach ($entries as $id => $fields) {
                        $payload = json_decode($fields['payload'] ?? '{}', true) ?? [];
                        $handler(array_merge($payload, ['_event_type' => $fields['event_type'] ?? null]));
                        Redis::xack($stream, $consumerGroup, [$id]);
                        $count++;
                        if (! empty($payload['event_id'])) {
                            EventLog::where('event_id', $payload['event_id'])
                                ->update(['status' => 'consumed', 'consumed_at' => now()]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                sleep(1);
            }
        }
    }
}
