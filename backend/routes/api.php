<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\McpServerController;
use App\Http\Controllers\Api\MetricsController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\RunController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\WorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Enterprise AI MCP Platform — API Routes
|--------------------------------------------------------------------------
| All routes are namespaced under /api by RouteServiceProvider in Laravel 12.
| Public endpoints are limited to auth + health. Everything else is gated
| by Sanctum bearer tokens.
*/

Route::get('/health', [MetricsController::class, 'health']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('tenants', TenantController::class);
    Route::apiResource('projects', ProjectController::class);

    Route::apiResource('agents', AgentController::class);
    Route::post('/agents/{agent}/duplicate', [AgentController::class, 'duplicate']);

    Route::apiResource('mcp-servers', McpServerController::class)->parameters(['mcp-servers' => 'mcpServer']);
    Route::post('/mcp-servers/{mcpServer}/health', [McpServerController::class, 'healthCheck']);

    Route::apiResource('workflows', WorkflowController::class);
    Route::post('/workflows/{workflow}/run', [WorkflowController::class, 'run']);

    Route::get('/runs', [RunController::class, 'index']);
    Route::get('/runs/{run}', [RunController::class, 'show']);

    Route::get('/audit', [AuditController::class, 'index']);
    Route::get('/metrics/overview', [MetricsController::class, 'overview']);
});
