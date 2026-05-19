<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ComplianceExportController;
use App\Http\Controllers\Api\ContinuousScannerController;
use App\Http\Controllers\Api\GitOpsController;
use App\Http\Controllers\Api\LegacyImportController;
use App\Http\Controllers\Api\LocaleController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\PortfolioBudgetController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuditReportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CodegenController;
use App\Http\Controllers\Api\CopyController;
use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\McpServerController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\NetworkPolicyController;
use App\Http\Controllers\Api\OpaPolicyController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProviderBudgetController;
use App\Http\Controllers\Api\RbacController;
use App\Http\Controllers\Api\RunController;
use App\Http\Controllers\Api\SecretController;
use App\Http\Controllers\Api\SecurityScanController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\TemplateLifecycleController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\ToolController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\Api\WorkflowController;
use App\Http\Controllers\Api\A2AController;
use App\Http\Controllers\Api\BridgeController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\EventBusController;
use App\Http\Controllers\Api\FlowiseAgentController;
use App\Http\Controllers\Api\MemoryController;
use App\Http\Controllers\Api\MetaAgentController;
use App\Http\Controllers\Api\MigrationController;
use App\Http\Controllers\Api\ModelRegistryController;
use App\Http\Controllers\Api\ObservabilityController;
use App\Http\Controllers\Api\PromptController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [MetricsController::class, 'health']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// ─── Phase 4: Unauthenticated observability + A2A inbound ───────────
// Prometheus scrapes these so they must be reachable without bearer auth.
Route::get('/observability/metrics', [ObservabilityController::class, 'metrics']);
Route::post('/a2a/inbound/{partnerId}', [A2AController::class, 'inbound']);

Route::middleware(['auth:sanctum', 'tenant.isolation'])->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/chat/execute', [ChatController::class, 'execute'])->middleware('prompt.injection');

    Route::apiResource('tenants', TenantController::class);
    Route::apiResource('projects', ProjectController::class);

    // Agents
    Route::apiResource('agents', AgentController::class);
    Route::post('/agents/{agent}/duplicate', [AgentController::class, 'duplicate']);
    Route::get('/agents/{agent}/runs', [AgentController::class, 'runs']);
    Route::post('/agents/{agent}/copy', [CopyController::class, 'copyAgent']);
    Route::get('/agents/{agent}/versions', [VersionController::class, 'agentVersions']);
    Route::get('/agents/{agent}/versions/{a}/diff/{b}', [VersionController::class, 'diffAgent']);
    Route::post('/agents/{agent}/versions/{version}/rollback', [VersionController::class, 'rollbackAgent']);
    Route::post('/agents/{agent}/debug', fn (\Illuminate\Http\Request $r, \App\Models\Agent $agent) => app(DebugController::class)->debugAgent($r, $agent->id));

    // MCP Servers
    Route::apiResource('mcp-servers', McpServerController::class)->parameters(['mcp-servers' => 'mcpServer']);
    Route::post('/mcp-servers/{mcpServer}/health', [McpServerController::class, 'healthCheck']);
    Route::get('/mcp-servers/{mcpServer}/tools', [ToolController::class, 'index']);
    Route::post('/mcp-servers/{mcpServer}/tools', [ToolController::class, 'store']);
    Route::put('/mcp-servers/{mcpServer}/tools/{tool}', [ToolController::class, 'update']);
    Route::delete('/mcp-servers/{mcpServer}/tools/{tool}', [ToolController::class, 'destroy']);
    Route::post('/mcp-servers/{mcpServer}/copy', [CopyController::class, 'copyMcp']);
    Route::get('/mcp-servers/{mcpServer}/versions', [VersionController::class, 'mcpVersions']);
    Route::post('/mcp-servers/{mcpServer}/versions/{version}/rollback', [VersionController::class, 'rollbackMcp']);
    Route::post('/tools/{tool}/debug', [DebugController::class, 'debugTool']);

    // Workflows
    Route::apiResource('workflows', WorkflowController::class);
    Route::post('/workflows/{workflow}/run', [WorkflowController::class, 'run'])->middleware('prompt.injection');
    Route::post('/workflows/{workflow}/copy', [CopyController::class, 'copyWorkflow']);
    Route::get('/workflows/{workflow}/versions', [VersionController::class, 'workflowVersions']);
    Route::get('/workflows/{workflow}/versions/{a}/diff/{b}', [VersionController::class, 'diffWorkflow']);
    Route::post('/workflows/{workflow}/versions/{version}/rollback', [VersionController::class, 'rollbackWorkflow']);

    // Runs (Phase 2 debug/replay/resume/cancel)
    Route::get('/runs', [RunController::class, 'index']);
    Route::get('/runs/{run}', [RunController::class, 'show']);
    Route::post('/runs/{run}/debug-node', [DebugController::class, 'debugWorkflowNode']);
    Route::post('/runs/{run}/replay', [DebugController::class, 'replayRun']);
    Route::post('/runs/{run}/resume', [DebugController::class, 'resumeRun']);
    Route::post('/runs/{run}/cancel', [DebugController::class, 'cancelRun']);

    // Approvals
    Route::get('/approvals', [ApprovalController::class, 'index']);
    Route::get('/approvals/{approval}', [ApprovalController::class, 'show']);
    Route::post('/approvals/{approval}/approve', [ApprovalController::class, 'approve']);
    Route::post('/approvals/{approval}/reject', [ApprovalController::class, 'reject']);

    // Deployments
    Route::get('/deployments', [DeploymentController::class, 'index']);
    Route::post('/deployments', [DeploymentController::class, 'store']);
    Route::get('/deployments/{deployment}', [DeploymentController::class, 'show']);
    Route::post('/deployments/{deployment}/approve', [DeploymentController::class, 'approve']);
    Route::post('/deployments/{deployment}/rollback', [DeploymentController::class, 'rollback']);

    // Templates lifecycle
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::get('/templates/{template}', [TemplateController::class, 'show']);
    Route::post('/templates/from-asset', [TemplateLifecycleController::class, 'fromAsset']);
    Route::get('/templates/{template}/export.json', [TemplateLifecycleController::class, 'exportJson']);
    Route::get('/templates/{template}/export.zip', [TemplateLifecycleController::class, 'exportZip']);
    Route::post('/templates/import', [TemplateLifecycleController::class, 'importJson']);
    Route::post('/templates/import-zip', [TemplateLifecycleController::class, 'importZip']);
    Route::post('/templates/{template}/instantiate', [TemplateLifecycleController::class, 'instantiate']);

    // Tests
    Route::get('/tests', [TestController::class, 'index']);
    Route::post('/tests', [TestController::class, 'store']);
    Route::get('/tests/{testSuite}', [TestController::class, 'show']);
    Route::post('/tests/{testSuite}/run', [TestController::class, 'run']);
    Route::get('/test-executions/{execution}', [TestController::class, 'execution']);

    // Codegen (OpenHands SDK)
    Route::get('/codegen', [CodegenController::class, 'index']);
    Route::post('/codegen/mcp', [CodegenController::class, 'generateMcp']);
    Route::get('/codegen/{codegen}', [CodegenController::class, 'show']);

    // Secrets
    Route::get('/secrets', [SecretController::class, 'index']);
    Route::post('/secrets', [SecretController::class, 'store']);
    Route::delete('/secrets/{secret}', [SecretController::class, 'destroy']);

    // Audit + Metrics (Phase 1/2 baseline)
    Route::get('/audit', [AuditController::class, 'index']);
    Route::get('/metrics/overview', [MetricsController::class, 'overview']);

    // ─── Phase 3: RBAC/ABAC ─────────────────────────────────────────
    Route::prefix('rbac')->group(function () {
        Route::get('/roles', [RbacController::class, 'roles']);
        Route::post('/roles', [RbacController::class, 'createRole']);
        Route::put('/roles/{role}', [RbacController::class, 'updateRole']);
        Route::get('/permissions', [RbacController::class, 'permissions']);
        Route::get('/my-permissions', [RbacController::class, 'userPermissions']);
        Route::post('/assign-tenant-role', [RbacController::class, 'assignTenantRole']);
        Route::post('/assign-project-role', [RbacController::class, 'assignProjectRole']);
        Route::get('/abac-policies', [RbacController::class, 'abacPolicies']);
        Route::post('/abac-policies', [RbacController::class, 'createAbacPolicy']);
        Route::put('/abac-policies/{abacPolicy}', [RbacController::class, 'updateAbacPolicy']);
        Route::delete('/abac-policies/{abacPolicy}', [RbacController::class, 'deleteAbacPolicy']);
    });

    // ─── Phase 3: OPA Policy Management ─────────────────────────────
    Route::prefix('opa-policies')->group(function () {
        Route::get('/', [OpaPolicyController::class, 'index']);
        Route::post('/', [OpaPolicyController::class, 'store']);
        Route::get('/library', [OpaPolicyController::class, 'library']);
        Route::post('/lint', [OpaPolicyController::class, 'lint']);
        Route::get('/{opaPolicy}', [OpaPolicyController::class, 'show']);
        Route::put('/{opaPolicy}', [OpaPolicyController::class, 'update']);
        Route::delete('/{opaPolicy}', [OpaPolicyController::class, 'destroy']);
        Route::post('/{opaPolicy}/activate', [OpaPolicyController::class, 'activate']);
        Route::post('/{opaPolicy}/disable', [OpaPolicyController::class, 'disable']);
        Route::post('/{opaPolicy}/dry-run', [OpaPolicyController::class, 'dryRun']);
    });

    // ─── Phase 3: Audit Reports & Exports ───────────────────────────
    Route::prefix('audit-reports')->group(function () {
        Route::get('/events', [AuditReportController::class, 'events']);
        Route::get('/asset-history', [AuditReportController::class, 'assetHistory']);
        Route::get('/templates', [AuditReportController::class, 'templates']);
        Route::post('/export', [AuditReportController::class, 'export']);
        Route::get('/exports', [AuditReportController::class, 'exports']);
        Route::get('/exports/{auditExport}/download', [AuditReportController::class, 'download']);
    });

    // ─── Phase 3: Security Scanner ──────────────────────────────────
    Route::prefix('security-scans')->group(function () {
        Route::get('/', [SecurityScanController::class, 'index']);
        Route::get('/summary', [SecurityScanController::class, 'summary']);
        Route::post('/trigger', [SecurityScanController::class, 'triggerScan']);
        Route::post('/deployment/{deploymentId}', [SecurityScanController::class, 'scanDeployment']);
        Route::get('/deployment/{deploymentId}/gate', [SecurityScanController::class, 'promotionGate']);
        Route::get('/{securityScan}', [SecurityScanController::class, 'show']);
    });

    // ─── Phase 3: Network Policies ──────────────────────────────────
    Route::prefix('network-policies')->group(function () {
        Route::get('/', [NetworkPolicyController::class, 'index']);
        Route::post('/', [NetworkPolicyController::class, 'store']);
        Route::post('/check', [NetworkPolicyController::class, 'check']);
        Route::put('/{networkAllowlist}', [NetworkPolicyController::class, 'update']);
        Route::delete('/{networkAllowlist}', [NetworkPolicyController::class, 'destroy']);
        Route::get('/mcp-servers/{mcpServer}/k8s-policy', [NetworkPolicyController::class, 'generateK8sPolicy']);
    });

    // ─── Phase 3: Provider Budgets ──────────────────────────────────
    Route::prefix('provider-budgets')->group(function () {
        Route::get('/', [ProviderBudgetController::class, 'index']);
        Route::post('/', [ProviderBudgetController::class, 'store']);
        Route::post('/check', [ProviderBudgetController::class, 'check']);
        Route::get('/summary', [ProviderBudgetController::class, 'summary']);
        Route::put('/{providerBudget}', [ProviderBudgetController::class, 'update']);
        Route::delete('/{providerBudget}', [ProviderBudgetController::class, 'destroy']);
    });

    // ═════════════════════════════════════════════════════════════════
    // PHASE 4 — Advanced Multi-Agent Platform (PRD §9, §12, §13, §22)
    // ═════════════════════════════════════════════════════════════════

    // ─── Phase 4: Model Control Plane ───────────────────────────────
    Route::prefix('models')->group(function () {
        Route::get('/',                 [ModelRegistryController::class, 'models']);
        Route::post('/',                [ModelRegistryController::class, 'storeModel']);
        Route::get('/{slug}',           [ModelRegistryController::class, 'showModel']);
        Route::post('/route/plan',      [ModelRegistryController::class, 'plan']);
        Route::post('/route/complete',  [ModelRegistryController::class, 'complete']);
        Route::get('/routing/rules',    [ModelRegistryController::class, 'rules']);
        Route::post('/routing/rules',   [ModelRegistryController::class, 'storeRule']);
    });

    // ─── Phase 4: Prompt Registry ───────────────────────────────────
    Route::prefix('prompts')->group(function () {
        Route::get('/',                          [PromptController::class, 'index']);
        Route::post('/',                         [PromptController::class, 'store']);
        Route::get('/{prompt}',                  [PromptController::class, 'show']);
        Route::post('/{prompt}/versions',        [PromptController::class, 'newVersion']);
        Route::post('/{prompt}/versions/{version}/activate', [PromptController::class, 'activate']);
        Route::get('/{prompt}/versions/{a}/diff/{b}', [PromptController::class, 'diff']);
        Route::post('/{prompt}/render',          [PromptController::class, 'render']);
        Route::post('/{prompt}/evaluate',        [PromptController::class, 'evaluate']);
        Route::get('/{prompt}/leaderboard',      [PromptController::class, 'leaderboard']);
    });

    // ─── Phase 4: Memory / RAG ──────────────────────────────────────
    Route::prefix('memory')->group(function () {
        Route::get('/collections',                       [MemoryController::class, 'collections']);
        Route::post('/collections',                      [MemoryController::class, 'createCollection']);
        Route::get('/collections/{collection}/items',    [MemoryController::class, 'items']);
        Route::post('/collections/{collection}/remember',[MemoryController::class, 'remember']);
        Route::post('/collections/{collection}/retrieve',[MemoryController::class, 'retrieve']);
        Route::delete('/items/{item}',                   [MemoryController::class, 'forget']);
        Route::get('/short-term/{sessionId}',            [MemoryController::class, 'shortTerm']);
    });

    // ─── Phase 4: A2A Gateway ───────────────────────────────────────
    Route::prefix('a2a')->group(function () {
        Route::get('/partners',              [A2AController::class, 'partners']);
        Route::post('/partners',             [A2AController::class, 'storePartner']);
        Route::put('/partners/{partner}',    [A2AController::class, 'updatePartner']);
        Route::post('/send',                 [A2AController::class, 'send']);
        Route::get('/messages',              [A2AController::class, 'messages']);
    });

    // ─── Phase 4: Meta-Agents ───────────────────────────────────────
    Route::prefix('meta-agents')->group(function () {
        Route::get('/',                  [MetaAgentController::class, 'index']);
        Route::get('/registry',          [MetaAgentController::class, 'registry']);
        Route::post('/route',            [MetaAgentController::class, 'route']);
        Route::post('/run',              [MetaAgentController::class, 'start']);
        Route::post('/autopilot',        [MetaAgentController::class, 'autopilot']);
        Route::get('/{run}',             [MetaAgentController::class, 'show']);
        Route::post('/{run}/resume',     [MetaAgentController::class, 'resume']);
    });

    // ─── Phase 4: Migration (n8n / Flowise / Dify / JSON / YAML) ───
    Route::prefix('migrations')->group(function () {
        Route::get('/',           [MigrationController::class, 'index']);
        Route::post('/import',    [MigrationController::class, 'import']);
        Route::get('/{import}',   [MigrationController::class, 'show']);
    });

    // ─── Phase 4: Cross-framework Bridges ───────────────────────────
    Route::prefix('bridges')->group(function () {
        Route::get('/frameworks',                       [BridgeController::class, 'frameworks']);
        Route::get('/connections',                      [BridgeController::class, 'connections']);
        Route::post('/connections',                     [BridgeController::class, 'storeConnection']);
        Route::post('/connections/{connection}/call',   [BridgeController::class, 'call']);
        Route::post('/connections/{connection}/toggle', [BridgeController::class, 'toggle']);
    });

    // ─── Phase 4: Event Bus ─────────────────────────────────────────
    Route::prefix('event-bus')->group(function () {
        Route::get('/status',         [EventBusController::class, 'status']);
        Route::post('/publish',       [EventBusController::class, 'publish']);
        Route::get('/subscriptions',  [EventBusController::class, 'subscriptions']);
        Route::post('/subscriptions', [EventBusController::class, 'storeSubscription']);
        Route::get('/log',            [EventBusController::class, 'log']);
    });

    // ─── Phase 4: Advanced Observability ───────────────────────────
    Route::prefix('observability')->group(function () {
        Route::get('/metrics.json',         [ObservabilityController::class, 'metricsJson']);
        Route::get('/timeline/{run}',       [ObservabilityController::class, 'timeline']);
        Route::post('/runs/{run}/replay',   [ObservabilityController::class, 'replay']);
        Route::get('/knowledge-graph',      [ObservabilityController::class, 'knowledgeGraph']);
    });

    // ─── Phase 4: Comments / Collaboration ──────────────────────────
    Route::prefix('comments')->group(function () {
        Route::get('/',                       [CommentController::class, 'index']);
        Route::post('/',                      [CommentController::class, 'store']);
        Route::post('/{comment}/resolve',     [CommentController::class, 'resolve']);
        Route::delete('/{comment}',           [CommentController::class, 'destroy']);
    });

    // ─── Phase 5: Template Marketplace ──────────────────────────────
    Route::prefix('marketplace')->group(function () {
        Route::get('/listings', [MarketplaceController::class, 'index']);
        Route::get('/search', [MarketplaceController::class, 'search']);
        Route::get('/installs', [MarketplaceController::class, 'installs']);
        Route::get('/publisher-summary', [MarketplaceController::class, 'publisherSummary']);
        Route::post('/publish', [MarketplaceController::class, 'publish']);
        Route::get('/listings/{listing}', [MarketplaceController::class, 'show']);
        Route::post('/listings/{listing}/install', [MarketplaceController::class, 'install']);
        Route::post('/listings/{listing}/rate', [MarketplaceController::class, 'rate']);
    });

    // ─── Phase 5: Advanced Analytics ────────────────────────────────
    Route::prefix('analytics')->group(function () {
        Route::get('/usage', [AnalyticsController::class, 'usage']);
        Route::get('/cost', [AnalyticsController::class, 'cost']);
        Route::get('/reliability', [AnalyticsController::class, 'reliability']);
        Route::get('/template-adoption', [AnalyticsController::class, 'templateAdoption']);
        Route::get('/quality-scores', [AnalyticsController::class, 'qualityScores']);
        Route::post('/quality-scores/recompute', [AnalyticsController::class, 'recompute']);
    });

    // ─── Phase 5: Portfolio Cost Governance ─────────────────────────
    Route::prefix('cost-governance')->group(function () {
        Route::get('/budgets', [PortfolioBudgetController::class, 'index']);
        Route::post('/budgets', [PortfolioBudgetController::class, 'store']);
        Route::put('/budgets/{portfolioBudget}', [PortfolioBudgetController::class, 'update']);
        Route::delete('/budgets/{portfolioBudget}', [PortfolioBudgetController::class, 'destroy']);
        Route::post('/chargeback', [PortfolioBudgetController::class, 'chargeback']);
        Route::get('/chargeback', [PortfolioBudgetController::class, 'chargebackHistory']);
        Route::post('/recommendations', [PortfolioBudgetController::class, 'recommendations']);
    });

    // ─── Phase 5: Multi-Compliance Audit Exports ────────────────────
    Route::prefix('compliance')->group(function () {
        Route::get('/frameworks', [ComplianceExportController::class, 'frameworks']);
        Route::get('/exports', [ComplianceExportController::class, 'index']);
        Route::post('/exports', [ComplianceExportController::class, 'store']);
        Route::get('/exports/{complianceExport}', [ComplianceExportController::class, 'show']);
        Route::get('/exports/{complianceExport}/download', [ComplianceExportController::class, 'download']);
    });

    // ─── Phase 5: Continuous Security Scanning ──────────────────────
    Route::prefix('continuous-security')->group(function () {
        Route::get('/snapshots', [ContinuousScannerController::class, 'snapshots']);
        Route::post('/snapshots', [ContinuousScannerController::class, 'takeSnapshot']);
        Route::get('/diffs', [ContinuousScannerController::class, 'diffs']);
        Route::post('/diffs/latest', [ContinuousScannerController::class, 'diffLatest']);
        Route::get('/findings', [ContinuousScannerController::class, 'findings']);
        Route::post('/findings', [ContinuousScannerController::class, 'reportFinding']);
        Route::post('/findings/{finding}/transition', [ContinuousScannerController::class, 'transitionFinding']);
        Route::get('/due-dates', [ContinuousScannerController::class, 'dueDates']);
    });

    // ─── Phase 5: GitOps + HA/DR ────────────────────────────────────
    Route::prefix('gitops')->group(function () {
        Route::get('/environments', [GitOpsController::class, 'environments']);
        Route::post('/environments', [GitOpsController::class, 'registerEnvironment']);
        Route::post('/environments/{environment}/sync', [GitOpsController::class, 'sync']);
        Route::get('/environments/{environment}/drift', [GitOpsController::class, 'drift']);
        Route::get('/environments/{environment}/syncs', [GitOpsController::class, 'syncs']);
        Route::get('/regions', [GitOpsController::class, 'regionStatus']);
        Route::post('/failover-drill', [GitOpsController::class, 'failoverDrill']);
    });

    // ─── Phase 5: Legacy Imports (AutoGen / Tesslate / n8n / Flowise / Dify / CrewAI)
    Route::prefix('legacy-imports')->group(function () {
        Route::get('/', [LegacyImportController::class, 'index']);
        Route::post('/', [LegacyImportController::class, 'store']);
        Route::get('/{legacyImport}', [LegacyImportController::class, 'show']);
    });

    // ─── AI Workflow Studio — FlowiseAI integration (UI: /workflow-studio)
    Route::prefix('flowise')->group(function () {
        Route::get('/health',                [FlowiseAgentController::class, 'health']);
        Route::get('/chatflows',             [FlowiseAgentController::class, 'chatflows']);
        Route::post('/import',               [FlowiseAgentController::class, 'import']);
        Route::post('/export',               [FlowiseAgentController::class, 'export']);
        Route::post('/sync',                 [FlowiseAgentController::class, 'syncTenant']);

        Route::get('/agents',                [FlowiseAgentController::class, 'index']);
        Route::post('/agents',               [FlowiseAgentController::class, 'store']);
        Route::get('/agents/{agent}',        [FlowiseAgentController::class, 'show']);
        Route::patch('/agents/{agent}',      [FlowiseAgentController::class, 'update']);
        Route::put('/agents/{agent}',        [FlowiseAgentController::class, 'update']);
        Route::delete('/agents/{agent}',     [FlowiseAgentController::class, 'destroy']);
        Route::post('/agents/{agent}/run',     [FlowiseAgentController::class, 'run'])->middleware('prompt.injection');
        Route::post('/agents/{agent}/sync',    [FlowiseAgentController::class, 'sync']);
        Route::post('/agents/{agent}/duplicate',[FlowiseAgentController::class, 'duplicate']);
        Route::get('/agents/{agent}/runs',     [FlowiseAgentController::class, 'runs']);
        Route::get('/agents/{agent}/embed',    [FlowiseAgentController::class, 'embed']);
    });
});

// ─── Phase 5: Localization (public — used at app boot) ──────────────
Route::get('/locales', [LocaleController::class, 'index']);
Route::get('/locales/{code}', [LocaleController::class, 'dictionary']);
Route::middleware(['auth:sanctum', 'tenant.isolation'])->post('/locales/{code}', [LocaleController::class, 'upsert']);
