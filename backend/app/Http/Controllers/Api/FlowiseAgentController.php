<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowiseAgent;
use App\Models\FlowiseRun;
use App\Services\FlowiseService;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Backend surface for the AI Workflow Studio (FlowiseAI integration).
 *
 *   GET    /api/flowise/agents
 *   POST   /api/flowise/agents
 *   GET    /api/flowise/agents/{agent}
 *   PATCH  /api/flowise/agents/{agent}
 *   DELETE /api/flowise/agents/{agent}
 *   POST   /api/flowise/agents/{agent}/run
 *   POST   /api/flowise/agents/{agent}/sync
 *   POST   /api/flowise/agents/{agent}/duplicate
 *   GET    /api/flowise/agents/{agent}/runs
 *   GET    /api/flowise/agents/{agent}/embed
 *   GET    /api/flowise/chatflows           — remote list (Flowise → us)
 *   POST   /api/flowise/import              — pull a remote chatflow
 *   POST   /api/flowise/export              — push a local agent up to Flowise
 *   POST   /api/flowise/sync                — full tenant pull-sync
 *   GET    /api/flowise/health
 *
 * All routes are protected by `auth:sanctum + tenant.isolation`.
 */
class FlowiseAgentController extends Controller
{
    public function __construct(private readonly FlowiseService $flowise) {}

    // ─── Agent CRUD ───────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $userTenants = $request->user()->tenants()->pluck('tenants.id')->all();

        $query = FlowiseAgent::query()->whereIn('tenant_id', $userTenants);

        if ($id = $request->query('tenant_id'))  $query->where('tenant_id', (int) $id);
        if ($id = $request->query('project_id')) $query->where('project_id', (int) $id);
        if ($s  = $request->query('status'))     $query->where('status', $s);
        if ($s  = $request->query('q'))          $query->where('name', 'like', "%{$s}%");

        return response()->json([
            'data' => $query->with(['owner:id,name,email'])
                ->orderByDesc('updated_at')
                ->paginate(50),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'       => 'required|integer|exists:tenants,id',
            'project_id'      => 'nullable|integer|exists:projects,id',
            'name'            => 'required|string|max:160',
            'description'     => 'nullable|string|max:2000',
            'status'          => 'sometimes|in:draft,active,paused,error',
            'workflow_config' => 'sometimes|array',
            'tools_config'    => 'sometimes|array',
            'model_config'    => 'sometimes|array',
            'push_to_flowise' => 'sometimes|boolean',
        ]);

        $this->assertMembership($request, (int) $data['tenant_id']);

        return DB::transaction(function () use ($data, $request) {
            $agent = FlowiseAgent::create([
                'tenant_id'       => $data['tenant_id'],
                'project_id'      => $data['project_id'] ?? null,
                'owner_id'        => $request->user()?->id,
                'name'            => $data['name'],
                'slug'            => Str::slug($data['name']) . '-' . Str::lower(Str::random(4)),
                'description'     => $data['description'] ?? null,
                'status'          => $data['status'] ?? 'draft',
                'workflow_config' => $data['workflow_config'] ?? null,
                'tools_config'    => $data['tools_config'] ?? null,
                'model_config'    => $data['model_config'] ?? null,
            ]);

            if (($data['push_to_flowise'] ?? true) && $this->flowise->isConfigured()) {
                try {
                    $this->flowise->pushAgent($agent);
                } catch (\Throwable $e) {
                    $this->flowise->recordSync($agent, 'push', 'failed', null, $e->getMessage());
                    $agent->update(['status' => 'error']);
                }
            }

            Audit::record('create', 'flowise.agent.create', 'flowise_agent', $agent->id,
                ['name' => $agent->name], $request, $agent->tenant_id, $agent->project_id);

            return response()->json($agent->fresh(), 201);
        });
    }

    public function show(Request $request, FlowiseAgent $agent): JsonResponse
    {
        $this->authorizeAgent($request, $agent);
        return response()->json($agent->load(['owner:id,name,email', 'syncs' => fn ($q) => $q->latest()->limit(10)]));
    }

    public function update(Request $request, FlowiseAgent $agent): JsonResponse
    {
        $this->authorizeAgent($request, $agent);

        $data = $request->validate([
            'name'            => 'sometimes|string|max:160',
            'description'     => 'nullable|string|max:2000',
            'status'          => 'sometimes|in:draft,active,paused,error,archived',
            'workflow_config' => 'sometimes|array',
            'tools_config'    => 'sometimes|array',
            'model_config'    => 'sometimes|array',
            'project_id'      => 'sometimes|nullable|integer|exists:projects,id',
            'push_to_flowise' => 'sometimes|boolean',
        ]);

        $agent->fill(collect($data)->except('push_to_flowise')->all())->save();

        if (($data['push_to_flowise'] ?? true) && $this->flowise->isConfigured()) {
            try {
                $this->flowise->pushAgent($agent);
            } catch (\Throwable $e) {
                $this->flowise->recordSync($agent, 'push', 'failed', null, $e->getMessage());
            }
        }

        Audit::record('update', 'flowise.agent.update', 'flowise_agent', $agent->id,
            collect($data)->except(['workflow_config'])->all(),
            $request, $agent->tenant_id, $agent->project_id);

        return response()->json($agent->fresh());
    }

    public function destroy(Request $request, FlowiseAgent $agent): JsonResponse
    {
        $this->authorizeAgent($request, $agent);

        if ($this->flowise->isConfigured()) {
            $this->flowise->deleteAgent($agent);
        }

        $agent->update(['status' => 'archived']);
        $agent->delete();

        Audit::record('delete', 'flowise.agent.delete', 'flowise_agent', $agent->id, [],
            $request, $agent->tenant_id, $agent->project_id);

        return response()->json(['ok' => true]);
    }

    public function duplicate(Request $request, FlowiseAgent $agent): JsonResponse
    {
        $this->authorizeAgent($request, $agent);

        $copy = $agent->replicate(['flowise_chatflow_id', 'flowise_deployed_url', 'flowise_api_endpoint', 'last_run_at', 'last_run_status', 'last_synced_at']);
        $copy->name = $agent->name . ' (copy)';
        $copy->slug = Str::slug($copy->name) . '-' . Str::lower(Str::random(4));
        $copy->status = 'draft';
        $copy->owner_id = $request->user()?->id;
        $copy->save();

        if ($this->flowise->isConfigured()) {
            try { $this->flowise->pushAgent($copy); } catch (\Throwable) {}
        }

        Audit::record('create', 'flowise.agent.duplicate', 'flowise_agent', $copy->id,
            ['source_id' => $agent->id], $request, $copy->tenant_id, $copy->project_id);

        return response()->json($copy->fresh(), 201);
    }

    // ─── Run / sync / runs / embed ────────────────────────────────────

    public function run(Request $request, FlowiseAgent $agent): JsonResponse
    {
        $this->authorizeAgent($request, $agent);

        $data = $request->validate([
            'question'   => 'sometimes|string|max:8000',
            'prompt'     => 'sometimes|string|max:8000',
            'history'    => 'sometimes|array',
            'config'     => 'sometimes|array',
            'session_id' => 'sometimes|string|max:64',
        ]);

        if (! ($data['question'] ?? $data['prompt'] ?? null)) {
            throw ValidationException::withMessages(['question' => 'Provide question or prompt.']);
        }

        if ($agent->status === 'paused' || $agent->status === 'archived') {
            return response()->json(['message' => "Agent is {$agent->status}; resume it first."], 422);
        }

        $run = $this->flowise->runAgent($agent, $data, $request->user()?->id);

        Audit::record('execute', 'flowise.agent.run', 'flowise_agent', $agent->id,
            ['run_id' => $run->id, 'status' => $run->status],
            $request, $agent->tenant_id, $agent->project_id);

        return response()->json($run, $run->status === 'failed' ? 500 : 200);
    }

    public function sync(Request $request, FlowiseAgent $agent): JsonResponse
    {
        $this->authorizeAgent($request, $agent);

        if (! $this->flowise->isConfigured()) {
            return response()->json(['message' => 'Flowise is not configured.'], 503);
        }

        try {
            $data = $this->flowise->pushAgent($agent);
            return response()->json(['ok' => true, 'remote' => $data, 'agent' => $agent->fresh()]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
        }
    }

    public function runs(Request $request, FlowiseAgent $agent): JsonResponse
    {
        $this->authorizeAgent($request, $agent);

        return response()->json([
            'data' => FlowiseRun::where('flowise_agent_id', $agent->id)
                ->orderByDesc('id')
                ->paginate(50),
        ]);
    }

    /**
     * Returns the iframe URL the frontend should embed for this agent.
     * Resolved server-side so we can swap embed strategies (canvas/chat
     * widget) without redeploying the frontend.
     */
    public function embed(Request $request, FlowiseAgent $agent): JsonResponse
    {
        $this->authorizeAgent($request, $agent);

        $base = $this->flowise->embedUrl();
        if ($base === '') {
            return response()->json(['message' => 'FLOWISE_EMBED_URL not configured.'], 503);
        }

        $canvas = $agent->flowise_chatflow_id
            ? "{$base}/canvas/{$agent->flowise_chatflow_id}"
            : "{$base}/chatflows";

        return response()->json([
            'canvas_url' => $canvas,
            'chat_url'   => $agent->flowise_chatflow_id ? "{$base}/chatbot/{$agent->flowise_chatflow_id}" : null,
        ]);
    }

    // ─── Tenant-level: chatflows / import / export / sync / health ────

    public function chatflows(Request $request): JsonResponse
    {
        if (! $this->flowise->isConfigured()) {
            return response()->json(['data' => [], 'message' => 'Flowise not configured.'], 200);
        }
        try {
            return response()->json(['data' => $this->flowise->listRemoteChatflows()]);
        } catch (\Throwable $e) {
            return response()->json(['data' => [], 'message' => $e->getMessage()], 502);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'    => 'required|integer|exists:tenants,id',
            'project_id'   => 'nullable|integer|exists:projects,id',
            'chatflow_id'  => 'required|string|max:64',
        ]);
        $this->assertMembership($request, (int) $data['tenant_id']);

        try {
            $agent = $this->flowise->importChatflow(
                (int) $data['tenant_id'],
                isset($data['project_id']) ? (int) $data['project_id'] : null,
                $data['chatflow_id'],
                $request->user()?->id,
            );
            Audit::record('import', 'flowise.import', 'flowise_agent', $agent->id,
                ['chatflow_id' => $data['chatflow_id']], $request, $agent->tenant_id, $agent->project_id);
            return response()->json($agent, 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }
    }

    public function export(Request $request): JsonResponse
    {
        $data = $request->validate([
            'agent_id' => 'required|integer|exists:flowise_agents,id',
        ]);
        $agent = FlowiseAgent::findOrFail($data['agent_id']);
        $this->authorizeAgent($request, $agent);

        try {
            $remote = $this->flowise->pushAgent($agent);
            return response()->json(['ok' => true, 'remote' => $remote, 'agent' => $agent->fresh()]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
        }
    }

    public function syncTenant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'  => 'required|integer|exists:tenants,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);
        $this->assertMembership($request, (int) $data['tenant_id']);

        $result = $this->flowise->syncTenant(
            (int) $data['tenant_id'],
            isset($data['project_id']) ? (int) $data['project_id'] : null,
            $request->user()?->id,
        );

        Audit::record('sync', 'flowise.sync', 'tenant', (int) $data['tenant_id'],
            $result, $request, (int) $data['tenant_id']);

        $status = empty($result['errors']) ? 200 : 207; // 207 = multi-status
        return response()->json($result, $status);
    }

    public function health(): JsonResponse
    {
        return response()->json(array_merge(
            $this->flowise->health(),
            ['embed_url' => $this->flowise->embedUrl(), 'configured' => $this->flowise->isConfigured()],
        ));
    }

    // ─── Auth helpers ────────────────────────────────────────────────

    protected function assertMembership(Request $request, int $tenantId): void
    {
        $userTenants = $request->user()->tenants()->pluck('tenants.id')->all();
        abort_unless(in_array($tenantId, $userTenants, true), 403,
            'You do not have access to this tenant.');
    }

    protected function authorizeAgent(Request $request, FlowiseAgent $agent): void
    {
        $this->assertMembership($request, $agent->tenant_id);
    }
}
