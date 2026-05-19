<?php

namespace App\Services;

use App\Contracts\EventBusContract;
use App\Models\EventLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kafka / Redpanda event bus implementation (Phase 4).
 *
 * Publish path:
 *   1. Use php-rdkafka extension if loaded.
 *   2. Otherwise POST to Redpanda's HTTP proxy (Pandaproxy) at :8082.
 *
 * Consume path:
 *   - Pandaproxy consumer groups are used so the same code-path works
 *     even when rdkafka isn't compiled in. Designed for the
 *     `event-consumer` worker container.
 *
 * The implementation degrades gracefully — if neither rdkafka nor the
 * Pandaproxy is available the publish silently no-ops (event_log row is
 * still written so the call is traceable).
 */
class KafkaEventBus implements EventBusContract
{
    protected string $brokers;
    protected string $clientId;
    protected ?string $proxy;
    protected bool $useRdkafka;

    public function __construct()
    {
        $this->brokers   = env('KAFKA_BROKERS', 'redpanda:9092');
        $this->clientId  = env('KAFKA_CLIENT_ID', 'eamcp-backend');
        $this->proxy     = env('KAFKA_HTTP_PROXY', 'http://redpanda:8082');
        $this->useRdkafka = extension_loaded('rdkafka');
    }

    public function driver(): string { return 'kafka'; }

    public function healthy(): bool
    {
        if (! $this->proxy) return $this->useRdkafka;
        try {
            return Http::timeout(2)->get(rtrim($this->proxy, '/') . '/topics')->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function publish(string $topic, string $eventType, array $payload, array $headers = []): string
    {
        $msg = json_encode(array_merge($payload, ['_event_type' => $eventType, '_headers' => $headers]));

        if ($this->useRdkafka) {
            try {
                $conf = new \RdKafka\Conf();
                $conf->set('bootstrap.servers', $this->brokers);
                $conf->set('client.id', $this->clientId);
                $producer = new \RdKafka\Producer($conf);
                $producerTopic = $producer->newTopic($topic);
                $producerTopic->produce(RD_KAFKA_PARTITION_UA, 0, $msg);
                $producer->poll(0);
                $producer->flush(2000);
                return $payload['event_id'] ?? '';
            } catch (\Throwable $e) {
                Log::warning('kafka.rdkafka_failed', ['error' => $e->getMessage()]);
            }
        }

        // HTTP proxy fallback.
        if ($this->proxy) {
            try {
                Http::timeout(3)->withHeaders(['Content-Type' => 'application/vnd.kafka.json.v2+json'])
                    ->post(rtrim($this->proxy, '/') . "/topics/{$topic}", [
                        'records' => [['value' => array_merge($payload, ['_event_type' => $eventType, '_headers' => $headers])]],
                    ]);
            } catch (\Throwable $e) {
                Log::warning('kafka.http_proxy_failed', ['error' => $e->getMessage()]);
            }
        }
        return $payload['event_id'] ?? '';
    }

    public function consume(string $topic, string $consumerGroup, callable $handler, int $maxMessages = 0): void
    {
        if ($this->useRdkafka) {
            $this->consumeRdkafka($topic, $consumerGroup, $handler, $maxMessages);
            return;
        }
        $this->consumeHttpProxy($topic, $consumerGroup, $handler, $maxMessages);
    }

    protected function consumeRdkafka(string $topic, string $group, callable $handler, int $max): void
    {
        $conf = new \RdKafka\Conf();
        $conf->set('group.id', $group);
        $conf->set('bootstrap.servers', $this->brokers);
        $conf->set('auto.offset.reset', 'earliest');
        $consumer = new \RdKafka\KafkaConsumer($conf);
        $consumer->subscribe([$topic]);

        $count = 0;
        while ($max === 0 || $count < $max) {
            $message = $consumer->consume(1000);
            if (! $message) continue;
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $data = json_decode($message->payload, true) ?? [];
                    $handler($data);
                    $this->markConsumed($data['event_id'] ?? null);
                    $count++;
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;
                default:
                    Log::warning('kafka.consume_error', ['msg' => $message->errstr()]);
            }
        }
    }

    protected function consumeHttpProxy(string $topic, string $group, callable $handler, int $max): void
    {
        if (! $this->proxy) {
            sleep(1);
            return;
        }

        $base = rtrim($this->proxy, '/');
        $instance = $this->clientId . '-' . substr(md5($group . $topic), 0, 8);

        // Create consumer instance (best-effort).
        try {
            Http::timeout(5)->withHeaders(['Content-Type' => 'application/vnd.kafka.v2+json'])
                ->post("{$base}/consumers/{$group}", [
                    'name' => $instance,
                    'format' => 'json',
                    'auto.offset.reset' => 'earliest',
                ]);
            Http::timeout(5)->withHeaders(['Content-Type' => 'application/vnd.kafka.v2+json'])
                ->post("{$base}/consumers/{$group}/instances/{$instance}/subscription", [
                    'topics' => [$topic],
                ]);
        } catch (\Throwable $e) {
            Log::warning('kafka.proxy_subscribe_failed', ['error' => $e->getMessage()]);
        }

        $count = 0;
        while ($max === 0 || $count < $max) {
            try {
                $r = Http::timeout(15)
                    ->withHeaders(['Accept' => 'application/vnd.kafka.json.v2+json'])
                    ->get("{$base}/consumers/{$group}/instances/{$instance}/records?timeout=5000");
                if ($r->successful()) {
                    foreach (($r->json() ?? []) as $record) {
                        $handler($record['value'] ?? []);
                        $this->markConsumed($record['value']['event_id'] ?? null);
                        $count++;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('kafka.proxy_poll_failed', ['error' => $e->getMessage()]);
                sleep(2);
            }
        }
    }

    protected function markConsumed(?string $eventId): void
    {
        if (! $eventId) return;
        try {
            EventLog::where('event_id', $eventId)->update(['status' => 'consumed', 'consumed_at' => now()]);
        } catch (\Throwable) {}
    }
}
