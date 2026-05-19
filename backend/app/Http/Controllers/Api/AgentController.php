<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentVersion;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Agent::query()->with('currentVersion');
        if ($id = $request->query('project_id')) $query->where('project_id', $id);
        if ($id = $request->query('tenant_id'))  $query->where('tenant_id', $id);
        if ($s = $request->query('q')) $query->where('name', 'like', "%{$s}%");
        if ($s = $request->query('status')) $query->where('status', $s);
        return response()->json(['data' => $query->orderByDesc('updated_at')->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:160',
            'description' => 'nullable|string',
            'risk_level' => 'sometimes|in:L0,L1,L2,L3,L4',
            'role' => 'nullable|string',
            'system_instructions' => 'nullable|string',
            'model_config' => 'sometimes|array',
            'allowed_mcp_servers' => 'sometimes|array',
            'allowed_tools' => 'sometimes|array',
            'memory_scope' => 'sometimes|in:none,session,project,tenant',
        ]);

        return DB::transaction(function () use ($data, $request) {
            $agent = Agent::create([
                'tenant_id' => $data['tenant_id'],
                'project_id' => $data['project_id'],
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(3)),
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'risk_level' => $data['risk_level'] ?? 'L1',
            ]);
            $version = AgentVersion::create([
                'agent_id' => $agent->id,
                'version' => 1,
                'role' => $data['role'] ?? null,
                'system_instructions' => $data['system_instructions'] ?? null,
                'model_config' => $data['model_config'] ?? ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
                'allowed_mcp_servers' => $data['allowed_mcp_servers'] ?? [],
                'allowed_tools' => $data['allowed_tools'] ?? [],
                'memory_scope' => $data['memory_scope'] ?? 'session',
                'created_by' => $request->user()?->id,
            ]);
            $agent->update(['current_version_id' => $version->id]);
            Audit::record('create', 'agent.create', 'agent', $agent->id, $data, $request, $agent->tenant_id, $agent->project_id);
            return response()->json($agent->load('currentVersion'), 201);
        });
    }

    public function show(Agent $agent): JsonResponse
    {
        return response()->json($agent->load(['currentVersion', 'versions']));
    }

    public function update(Request $request, Agent $agent): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:160',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,staging,production,archived',
            'risk_level' => 'sometimes|in:L0,L1,L2,L3,L4',
            'role' => 'nullable|string',
            'system_instructions' => 'nullable|string',
            'model_config' => 'sometimes|array',
            'allowed_mcp_servers' => 'sometimes|array',
            'allowed_tools' => 'sometimes|array',
            'memory_scope' => 'sometimes|in:none,session,project,tenant',
        ]);

        return DB::transaction(function () use ($agent, $data, $request) {
            $agent->update(array_intersect_key($data, array_flip(['name', 'description', 'status', 'risk_level'])));
            $versionableKeys = ['role', 'system_instructions', 'model_config', 'allowed_mcp_servers', 'allowed_tools', 'memory_scope'];
            $versionData = array_intersect_key($data, array_flip($versionableKeys));
            if (!empty($versionData)) {
                $latest = $agent->versions()->max('version') ?? 0;
                $base = $agent->currentVersion?->only($versionableKeys) ?? [];
                $version = AgentVersion::create(array_merge($base, $versionData, [
                    'agent_id' => $agent->id,
                    'version' => $latest + 1,
                    'created_by' => $request->user()?->id,
                ]));
                $agent->update(['current_version_id' => $version->id]);
            }
            Audit::record('update', 'agent.update', 'agent', $agent->id, $data, $request, $agent->tenant_id, $agent->project_id);
            return response()->json($agent->fresh()->load(['currentVersion', 'versions']));
        });
    }

    public function destroy(Request $request, Agent $agent): JsonResponse
    {
        $agent->update(['status' => 'archived']);
        $agent->delete();
        Audit::record('delete', 'agent.archive', 'agent', $agent->id, [], $request, $agent->tenant_id, $agent->project_id);
        return response()->json(['ok' => true]);
    }

    public function duplicate(Request $request, Agent $agent): JsonResponse
    {
        $copy = $agent->replicate(['current_version_id']);
        $copy->name = $agent->name . ' (copy)';
        $copy->slug = Str::slug($copy->name) . '-' . Str::lower(Str::random(3));
        $copy->status = 'draft';
        $copy->save();
        if ($cv = $agent->currentVersion) {
            $v = $cv->replicate(['version']);
            $v->agent_id = $copy->id;
            $v->version = 1;
            $v->created_by = $request->user()?->id;
            $v->save();
            $copy->update(['current_version_id' => $v->id]);
        }
        Audit::record('create', 'agent.duplicate', 'agent', $copy->id, ['source_id' => $agent->id], $request, $copy->tenant_id, $copy->project_id);
        return response()->json($copy->load('currentVersion'), 201);
    }
}
