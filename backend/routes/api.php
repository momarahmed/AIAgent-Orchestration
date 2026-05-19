<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\McpServerController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\RunController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\ToolController;
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

    Route::apiResource('agents', AgentController::class);
    Route::post('/agents/{agent}/duplicate', [AgentController::class, 'duplicate']);
    Route::get('/agents/{agent}/runs', [AgentController::class, 'runs']);

    Route::apiResource('mcp-servers', McpServerController::class)->parameters(['mcp-servers' => 'mcpServer']);
    Route::post('/mcp-servers/{mcpServer}/health', [McpServerController::class, 'healthCheck']);
    Route::get('/mcp-servers/{mcpServer}/tools', [ToolController::class, 'index']);
    Route::post('/mcp-servers/{mcpServer}/tools', [ToolController::class, 'store']);
    Route::put('/mcp-servers/{mcpServer}/tools/{tool}', [ToolController::class, 'update']);
    Route::delete('/mcp-servers/{mcpServer}/tools/{tool}', [ToolController::class, 'destroy']);

    Route::apiResource('workflows', WorkflowController::class);
    Route::post('/workflows/{workflow}/run', [WorkflowController::class, 'run']);

    Route::get('/runs', [RunController::class, 'index']);
    Route::get('/runs/{run}', [RunController::class, 'show']);

    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::get('/templates/{template}', [TemplateController::class, 'show']);

    Route::get('/audit', [AuditController::class, 'index']);
    Route::get('/metrics/overview', [MetricsController::class, 'overview']);
});
