<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Services\WorkflowRuntime;
use App\Support\Audit;
use App\Support\WorkflowGraphValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WorkflowController extends Controller
{
    public function __construct(protected WorkflowRuntime $runtime) {}

    public function index(Request $request): JsonResponse
    {
        $query = Workflow::query()->with('currentVersion');
        if ($id = $request->query('project_id')) $query->where('project_id', $id);
        if ($id = $request->query('tenant_id'))  $query->where('tenant_id', $id);
        if ($s = $request->query('q')) $query->where('name', 'like', "%{$s}%");
        return response()->json(['data' => $query->orderByDesc('updated_at')->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:160',
            'description' => 'nullable|string',
            'trigger_type' => 'sometimes|in:manual,schedule,webhook,event,chat',
            'graph_json' => 'sometimes|array',
            'variables' => 'sometimes|array',
        ]);

        if (isset($data['graph_json'])) {
            WorkflowGraphValidator::validate($data['graph_json']);
        }

        return DB::transaction(function () use ($data, $request) {
            $wf = Workflow::create([
                'tenant_id' => $data['tenant_id'],
                'project_id' => $data['project_id'],
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(3)),
                'description' => $data['description'] ?? null,
                'trigger_type' => $data['trigger_type'] ?? 'manual',
                'status' => 'draft',
            ]);
            $v = $wf->versions()->create([
                'version' => 1,
                'graph_json' => $data['graph_json'] ?? ['nodes' => [], 'edges' => []],
                'variables' => $data['variables'] ?? [],
                'created_by' => $request->user()?->id,
            ]);
            $wf->update(['current_version_id' => $v->id]);
            Audit::record('create', 'workflow.create', 'workflow', $wf->id, $data, $request, $wf->tenant_id, $wf->project_id);
            return response()->json($wf->load('currentVersion'), 201);
        });
    }

    public function show(Workflow $workflow): JsonResponse
    {
        return response()->json($workflow->load(['currentVersion', 'versions']));
    }

    public function update(Request $request, Workflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:160',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,staging,production,archived',
            'trigger_type' => 'sometimes|in:manual,schedule,webhook,event,chat',
            'graph_json' => 'sometimes|array',
            'variables' => 'sometimes|array',
        ]);

        if (isset($data['graph_json'])) {
            WorkflowGraphValidator::validate($data['graph_json']);
        }

        return DB::transaction(function () use ($workflow, $data, $request) {
            $workflow->update(array_intersect_key($data, array_flip(['name', 'description', 'status', 'trigger_type'])));
            if (array_key_exists('graph_json', $data) || array_key_exists('variables', $data)) {
                $latest = $workflow->versions()->max('version') ?? 0;
                $base = $workflow->currentVersion?->only(['graph_json', 'variables']) ?? [];
                $version = $workflow->versions()->create(array_merge($base, [
                    'version' => $latest + 1,
                    'graph_json' => $data['graph_json'] ?? ($base['graph_json'] ?? ['nodes' => [], 'edges' => []]),
                    'variables' => $data['variables'] ?? ($base['variables'] ?? []),
                    'created_by' => $request->user()?->id,
                ]));
                $workflow->update(['current_version_id' => $version->id]);
            }
            Audit::record('update', 'workflow.update', 'workflow', $workflow->id, $data, $request, $workflow->tenant_id, $workflow->project_id);
            return response()->json($workflow->fresh()->load(['currentVersion', 'versions']));
        });
    }

    public function destroy(Request $request, Workflow $workflow): JsonResponse
    {
        $workflow->delete();
        Audit::record('delete', 'workflow.archive', 'workflow', $workflow->id, [], $request, $workflow->tenant_id, $workflow->project_id);
        return response()->json(['ok' => true]);
    }

    public function run(Request $request, Workflow $workflow): JsonResponse
    {
        $payload = $request->validate(['input' => 'sometimes|array']);
        $version = $workflow->currentVersion;
        if (! $version) {
            return response()->json(['error' => 'Workflow has no version to execute.'], 422);
        }

        $run = $this->runtime->run($workflow, $version, $payload['input'] ?? [], $request->user()?->id);
        Audit::record('run', 'workflow.run', 'workflow', $workflow->id, ['run_id' => $run->id, 'status' => $run->status], $request, $workflow->tenant_id, $workflow->project_id);
        return response()->json($run);
    }
}
