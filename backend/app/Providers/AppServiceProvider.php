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

        // Phase 4 — Advanced Multi-Agent Platform
        $this->app->singleton(\App\Services\ModelRegistryService::class);
        $this->app->singleton(\App\Services\ModelRouter::class);
        $this->app->singleton(\App\Services\PromptRegistryService::class);
        $this->app->singleton(\App\Services\PromptEvaluationService::class);
        $this->app->singleton(\App\Services\QdrantClient::class);
        $this->app->singleton(\App\Services\EmbeddingService::class);
        $this->app->singleton(\App\Services\MemoryService::class);
        $this->app->singleton(\App\Services\KnowledgeGraphService::class);
        $this->app->singleton(\App\Services\A2AGatewayService::class);
        $this->app->singleton(\App\Services\EventBus::class);
        $this->app->singleton(\App\Services\KafkaEventBus::class);
        $this->app->singleton(\App\Services\InProcessEventBus::class);
        $this->app->singleton(\App\Services\MetaAgentOrchestrator::class);
        $this->app->singleton(\App\Services\IntentRouterService::class);
        $this->app->singleton(\App\Services\MigrationService::class);
        $this->app->singleton(\App\Services\BridgeRegistry::class);
        $this->app->singleton(\App\Services\ObservabilityService::class);
        $this->app->singleton(\App\Services\ReplayService::class);
        $this->app->singleton(\App\Services\CommentService::class);

        // Phase 5
        $this->app->singleton(\App\Services\MarketplaceSignatureService::class);
        $this->app->singleton(\App\Services\MetaAgentReviewService::class);
        $this->app->singleton(\App\Services\RagSearchService::class);
        $this->app->singleton(\App\Services\MarketplaceService::class);
        $this->app->singleton(\App\Services\AnalyticsService::class);
        $this->app->singleton(\App\Services\PortfolioCostService::class);
        $this->app->singleton(\App\Services\ComplianceExportService::class);
        $this->app->singleton(\App\Services\ContinuousScannerService::class);
        $this->app->singleton(\App\Services\GitOpsService::class);
        $this->app->singleton(\App\Services\AutogenImporterService::class);
        $this->app->singleton(\App\Services\LocalizationService::class);
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
