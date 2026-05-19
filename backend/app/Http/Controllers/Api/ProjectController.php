<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Tenant;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::query()->withCount(['agents', 'mcpServers', 'workflows']);
        if ($tenantId = $request->query('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }
        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'metadata' => 'sometimes|array',
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(3));
        $project = Project::create($data);
        Audit::record('create', 'project.create', 'project', $project->id, $data, $request, $project->tenant_id, $project->id);
        return response()->json($project, 201);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json($project->loadCount(['agents', 'mcpServers', 'workflows']));
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'description' => 'nullable|string',
            'metadata' => 'sometimes|array',
        ]);
        $project->update($data);
        Audit::record('update', 'project.update', 'project', $project->id, $data, $request, $project->tenant_id, $project->id);
        return response()->json($project);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $project->delete();
        Audit::record('delete', 'project.archive', 'project', $project->id, [], $request, $project->tenant_id, $project->id);
        return response()->json(['ok' => true]);
    }
}
