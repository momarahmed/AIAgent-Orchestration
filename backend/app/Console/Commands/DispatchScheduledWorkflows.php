<?php

namespace App\Console\Commands;

use App\Contracts\WorkflowEngine;
use App\Models\Workflow;
use Cron\CronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DispatchScheduledWorkflows extends Command
{
    protected $signature = 'workflows:dispatch-schedules';

    protected $description = 'Run workflows whose cron schedule is due (Phase 2 schedule trigger)';

    public function handle(WorkflowEngine $engine): int
    {
        $due = 0;

        Workflow::query()
            ->where('trigger_type', 'schedule')
            ->whereNot('status', 'archived')
            ->whereNotNull('schedule_config')
            ->with('currentVersion')
            ->chunkById(50, function ($workflows) use ($engine, &$due) {
                foreach ($workflows as $workflow) {
                    if (! $workflow->currentVersion) {
                        continue;
                    }

                    $cron = $workflow->schedule_config['cron'] ?? null;
                    if (! is_string($cron) || $cron === '') {
                        continue;
                    }

                    try {
                        $expr = new CronExpression($cron);
                    } catch (\Throwable $e) {
                        Log::warning('workflow.schedule.invalid_cron', [
                            'workflow_id' => $workflow->id,
                            'cron' => $cron,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }

                    $cacheKey = "workflow_schedule_last:{$workflow->id}";
                    $lastRun = Cache::get($cacheKey);
                    $reference = $lastRun
                        ? \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $lastRun) ?: new \DateTimeImmutable($lastRun)
                        : new \DateTimeImmutable('-1 minute');

                    if (! $expr->isDue($reference)) {
                        continue;
                    }

                    $environment = $workflow->schedule_config['environment'] ?? 'dev';
                    $run = $engine->run(
                        $workflow,
                        $workflow->currentVersion,
                        ['trigger' => 'schedule', 'scheduled_at' => now()->toIso8601String()],
                        null,
                        $environment,
                    );

                    Cache::put($cacheKey, now()->toIso8601String(), now()->addDays(30));
                    $due++;

                    Log::info('workflow.schedule.dispatched', [
                        'workflow_id' => $workflow->id,
                        'run_id' => $run->id,
                        'cron' => $cron,
                    ]);
                }
            });

        $this->info("Dispatched {$due} scheduled workflow run(s).");

        return self::SUCCESS;
    }
}
