<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies that the authenticated user belongs to the tenant referenced
 * by the current request. Prevents cross-tenant data access.
 */
class EnsureTenantIsolation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $tenantId = $this->resolveTenantId($request);
        if (! $tenantId) {
            return $next($request);
        }

        $userTenantIds = $user->tenants()->pluck('tenants.id')->toArray();
        if (! in_array((int) $tenantId, $userTenantIds, true)) {
            return response()->json([
                'error'   => 'Forbidden',
                'message' => 'Cross-tenant access denied.',
            ], 403);
        }

        return $next($request);
    }

    protected function resolveTenantId(Request $request): ?int
    {
        if ($request->route('tenant')) {
            return (int) $request->route('tenant');
        }

        foreach (['agent', 'mcpServer', 'workflow', 'project', 'deployment', 'approval'] as $param) {
            $model = $request->route()->parameter($param);
            if ($model && method_exists($model, 'getAttribute') && $model->getAttribute('tenant_id')) {
                return (int) $model->getAttribute('tenant_id');
            }
        }

        return null;
    }
}
