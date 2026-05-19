<?php

namespace App\Providers;

use App\Contracts\WorkflowEngine;
use App\Services\DurableWorkflowEngine;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Phase 2: bind the durable engine behind the WorkflowEngine contract.
        // A future Temporal-backed engine will swap this single binding.
        $this->app->bind(WorkflowEngine::class, DurableWorkflowEngine::class);
    }

    public function boot(): void
    {
        //
    }
}
