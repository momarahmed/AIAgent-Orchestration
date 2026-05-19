<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => Tenant::query()->withCount(['projects'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'environment' => 'sometimes|in:dev,test,staging,prod',
            'settings' => 'sometimes|array',
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(4));
        $tenant = Tenant::create($data);
        $request->user()?->tenants()->syncWithoutDetaching([$tenant->id]);

        Audit::record('create', 'tenant.create', 'tenant', $tenant->id, $data, $request, $tenant->id);
        return response()->json($tenant, 201);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        return response()->json($tenant->load('projects'));
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'environment' => 'sometimes|in:dev,test,staging,prod',
            'settings' => 'sometimes|array',
        ]);
        $tenant->update($data);
        Audit::record('update', 'tenant.update', 'tenant', $tenant->id, $data, $request, $tenant->id);
        return response()->json($tenant);
    }

    public function destroy(Request $request, Tenant $tenant): JsonResponse
    {
        $tenant->delete();
        Audit::record('delete', 'tenant.archive', 'tenant', $tenant->id, [], $request, $tenant->id);
        return response()->json(['ok' => true]);
    }
}
