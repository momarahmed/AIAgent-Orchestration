<?php

namespace App\Http\Middleware;

use App\Services\RbacService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceRbac
{
    public function __construct(protected RbacService $rbac) {}

    /**
     * Usage in routes: ->middleware('rbac:agents.create')
     * The permission string is passed as parameter.
     */
    public function handle(Request $request, Closure $next, string $permission = ''): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $tenantId  = $this->resolveTenantId($request);
        $projectId = $this->resolveProjectId($request);

        if (! $tenantId) {
            return $next($request);
        }

        if (! $this->rbac->can($user, $permission, $tenantId, $projectId, $this->buildContext($request))) {
            return response()->json([
                'error'      => 'Forbidden',
                'permission' => $permission,
                'message'    => "You do not have the '{$permission}' permission in this scope.",
            ], 403);
        }

        return $next($request);
    }

    protected function resolveTenantId(Request $request): ?int
    {
        if ($request->route('tenant')) {
            return (int) $request->route('tenant');
        }
        $model = $request->route()->parameter('agent')
            ?? $request->route()->parameter('mcpServer')
            ?? $request->route()->parameter('workflow')
            ?? $request->route()->parameter('project');

        if ($model && method_exists($model, 'getAttribute')) {
            return $model->getAttribute('tenant_id');
        }

        return $request->user()?->tenants?->first()?->id;
    }

    protected function resolveProjectId(Request $request): ?int
    {
        $model = $request->route()->parameter('agent')
            ?? $request->route()->parameter('mcpServer')
            ?? $request->route()->parameter('workflow');

        if ($model && method_exists($model, 'getAttribute')) {
            return $model->getAttribute('project_id');
        }

        if ($request->route('project')) {
            return (int) $request->route('project');
        }

        return null;
    }

    protected function buildContext(Request $request): array
    {
        return [
            'ip'         => $request->ip(),
            'method'     => $request->method(),
            'path'       => $request->path(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
