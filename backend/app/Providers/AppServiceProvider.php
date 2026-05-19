<?php

namespace App\Providers;

use App\Contracts\WorkflowEngine;
use App\Services\DurableWorkflowEngine;
use App\Services\ProviderRegistry;
use App\Services\Providers\GoogleAdkProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WorkflowEngine::class, DurableWorkflowEngine::class);

        $this->app->singleton(\App\Services\RbacService::class);
        $this->app->singleton(\App\Services\OpaPolicyService::class);
        $this->app->singleton(\App\Services\SecretService::class);
        $this->app->singleton(\App\Services\PromptInjectionService::class);
        $this->app->singleton(\App\Services\SecurityScannerService::class);
        $this->app->singleton(\App\Services\NetworkPolicyService::class);
        $this->app->singleton(\App\Services\ToolSandboxService::class);
        $this->app->singleton(\App\Services\ProviderBudgetService::class);
        $this->app->singleton(\App\Services\AuditReportService::class);
        $this->app->singleton(\App\Services\ActivepiecesBridge::class);
    }

    public function boot(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('rbac', \App\Http\Middleware\EnforceRbac::class);
        $router->aliasMiddleware('tenant.isolation', \App\Http\Middleware\EnsureTenantIsolation::class);
        $router->aliasMiddleware('prompt.injection', \App\Http\Middleware\PromptInjectionFilter::class);

        // Phase 3: register Google ADK provider adapter
        try {
            $registry = $this->app->make(ProviderRegistry::class);
            $registry->register(new GoogleAdkProvider());
        } catch (\Throwable) {
            // ProviderRegistry may not be instantiated yet during migrations
        }
    }
}
