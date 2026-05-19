<?php

use App\Http\Controllers\Api\AgentController;
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
use Illuminate\Support\Facades\Route;

Route::get('/health', [MetricsController::class, 'health']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

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
});
