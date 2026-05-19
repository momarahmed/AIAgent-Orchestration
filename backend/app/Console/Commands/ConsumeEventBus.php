<?php

namespace App\Console\Commands;

use App\Models\EventSubscription;
use App\Services\DurableWorkflowEngine;
use App\Services\EventBus;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EventBus consumer worker (Phase 4 — PRD §13, RT-* requirements).
 *
 * Subscribes to all platform topics and dispatches each event according
 * to the matching `event_subscriptions` row:
 *   - workflow: triggers a Temporal/Durable workflow run.
 *   - webhook : POSTs the event payload to an HTTP endpoint.
 *   - agent  : executes an agent against the payload as input.
 *
 * Run via:
 *     php artisan eventbus:consume --all
 *     php artisan eventbus:consume --topic=workflow.events
 */
class ConsumeEventBus extends Command
{
    protected $signature = 'eventbus:consume
        {--topic=*}
        {--all}
        {--group=eamcp-default}
        {--max=0 : maximum messages to consume (0 = infinite)}';

    protected $description = 'Drain Kafka/Redis-Streams topics and route events to subscriptions';

    public function handle(EventBus $bus, DurableWorkflowEngine $engine): int
    {
        $topics = $this->option('all') ? EventBus::TOPICS : (array) $this->option('topic');
        $topics = array_filter($topics ?: []);
        if (empty($topics)) {
            $this->error('Specify --topic=name or --all');
            return self::FAILURE;
        }
        $group = (string) $this->option('group');
        $max   = (int) $this->option('max');

        $this->info(sprintf('[event-bus:%s] consumer-group=%s topics=%s', $bus->driver(), $group, implode(',', $topics)));

        // For dev simplicity we round-robin across topics in a single
        // process — production would spawn one worker per topic.
        $perTopicMax = $max > 0 ? max(1, (int) ($max / count($topics))) : 0;
        foreach ($topics as $topic) {
            $bus->consume($topic, $group, function (array $event) use ($engine, $topic) {
                $this->dispatchEvent($topic, $event, $engine);
            }, $perTopicMax);
        }

        return self::SUCCESS;
    }

    protected function dispatchEvent(string $topic, array $event, DurableWorkflowEngine $engine): void
    {
        $eventType = $event['_event_type'] ?? $event['event_type'] ?? 'unknown';
        $subs = EventSubscription::query()->where('topic', $topic)->where('is_active', true)->get();

        foreach ($subs as $sub) {
            if (! $this->matchesFilter($sub->filter ?? [], $event)) continue;

            try {
                match ($sub->handler_type) {
                    'workflow' => $this->triggerWorkflow($sub, $event, $engine),
                    'webhook'  => $this->postWebhook($sub, $event),
                    'agent'    => $this->runAgent($sub, $event),
                    default    => null,
                };
                Audit::record('event_bus', 'dispatched', 'event_subscription', $sub->id, [
                    'topic' => $topic, 'event_type' => $eventType, 'handler' => $sub->handler_type,
                ], tenantId: $sub->tenant_id);
            } catch (\Throwable $e) {
                Log::warning('event_subscription.dispatch_failed', [
                    'sub' => $sub->id, 'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function matchesFilter(array $filter, array $event): bool
    {
        foreach ($filter as $k => $v) {
            if (! array_key_exists($k, $event)) return false;
            if (is_array($v)) {
                if (! in_array($event[$k], $v, true)) return false;
            } elseif ($event[$k] != $v) {
                return false;
            }
        }
        return true;
    }

    protected function triggerWorkflow($sub, array $event, DurableWorkflowEngine $engine): void
    {
        $workflowId = (int) ($sub->handler_config['workflow_id'] ?? 0);
        if (! $workflowId) return;
        $workflow = \App\Models\Workflow::with('currentVersion')->find($workflowId);
        if (! $workflow || ! $workflow->currentVersion) return;
        $engine->run($workflow, $workflow->currentVersion, ['event' => $event], null, 'event-bus');
    }

    protected function postWebhook($sub, array $event): void
    {
        $url = (string) ($sub->handler_config['webhook_url'] ?? '');
        if (! $url) return;
        Http::timeout(10)->post($url, $event);
    }

    protected function runAgent($sub, array $event): void
    {
        $agentId = (int) ($sub->handler_config['agent_id'] ?? 0);
        if (! $agentId) return;
        app(\App\Services\AgentRuntime::class)->execute($agentId, json_encode($event), []);
    }
}
