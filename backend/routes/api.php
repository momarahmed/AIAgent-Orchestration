<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CodegenController;
use App\Http\Controllers\Api\CopyController;
use App\Http\Controllers\Api\DebugController;
use App\Http\Controllers\Api\DeploymentController;
use App\Http\Controllers\Api\McpServerController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\RunController;
use App\Http\Controllers\Api\SecretController;
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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/chat/execute', [ChatController::class, 'execute']);

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
    Route::post('/workflows/{workflow}/run', [WorkflowController::class, 'run']);
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

    // Audit + Metrics
    Route::get('/audit', [AuditController::class, 'index']);
    Route::get('/metrics/overview', [MetricsController::class, 'overview']);
});
