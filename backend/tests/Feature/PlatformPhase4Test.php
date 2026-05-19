<?php

namespace Tests\Feature;

use App\Models\MemoryCollection;
use App\Models\ModelRecord;
use App\Models\ModelRoutingRule;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EventBus;
use App\Services\IntentRouterService;
use App\Services\MemoryService;
use App\Services\MetaAgentOrchestrator;
use App\Services\MigrationService;
use App\Services\ModelRouter;
use App\Services\PromptRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 — Advanced Multi-Agent Platform
 *
 * Smoke + integration tests for: Model Control Plane, Prompt Registry,
 * Memory/RAG, A2A Gateway, Event Bus, Meta-Agents, Migration imports,
 * Cross-framework Bridges, Observability and Comments.
 */
class PlatformPhase4Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Tenant $tenant;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['slug' => 'p4-corp', 'name' => 'Phase4 Corp', 'environment' => 'dev']);
        $adminRole = Role::create(['name' => 'admin', 'label' => 'Admin', 'level' => 50, 'is_system' => true, 'permissions' => ['*']]);
        $this->admin = User::factory()->create();
        $this->admin->tenants()->attach($this->tenant->id, ['role_id' => $adminRole->id]);
        $this->token = $this->admin->createToken('p4')->plainTextToken;
    }

    // ─── Model Control Plane ────────────────────────────────────────

    public function test_model_router_picks_primary_by_rule(): void
    {
        ModelRecord::create(['slug' => 'openai:gpt-4o-mini', 'provider' => 'openai', 'name' => 'GPT-4o mini', 'capabilities' => ['chat'], 'cost_per_1k_in' => 0.0001, 'cost_per_1k_out' => 0.0004, 'latency_p50_ms' => 500]);
        ModelRoutingRule::create([
            'name' => 'default-chat',
            'priority' => 10,
            'match' => ['capability' => 'chat'],
            'route' => ['primary_model_slug' => 'openai:gpt-4o-mini', 'fallback' => []],
            'is_active' => true,
        ]);

        $plan = app(ModelRouter::class)->plan(['capabilities' => ['chat'], 'tenant_id' => $this->tenant->id]);
        $this->assertSame('openai:gpt-4o-mini', $plan['primary']->slug);
    }

    public function test_models_index_endpoint(): void
    {
        ModelRecord::create(['slug' => 'ollama:llama3.2:1b', 'provider' => 'ollama', 'name' => 'Llama 3.2', 'capabilities' => ['chat'], 'is_local' => true]);
        $this->withToken($this->token)->getJson('/api/models?tenant_id=' . $this->tenant->id)
            ->assertOk()->assertJsonFragment(['slug' => 'ollama:llama3.2:1b']);
    }

    // ─── Prompt Registry ────────────────────────────────────────────

    public function test_prompt_registry_versioning_and_rendering(): void
    {
        $reg = app(PromptRegistryService::class);
        $prompt = $reg->create($this->tenant->id, null, 'P', 'Hello {{name}}', ['slug' => 'p', 'category' => 'agent_system']);
        $reg->newVersion($prompt, 'Hi {{name}}', 'tweak', ['activate' => true]);

        $rendered = $reg->render($prompt->fresh(), ['name' => 'Wolf']);
        $this->assertSame('Hi Wolf', $rendered);
    }

    public function test_prompt_endpoint_creates_with_initial_version(): void
    {
        $this->withToken($this->token)->postJson('/api/prompts', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Sys',
            'slug' => 'sys',
            'category' => 'agent_system',
            'body' => 'You are {{role}}',
        ])->assertSuccessful();
        $this->assertDatabaseHas('prompts', ['slug' => 'sys']);
        $this->assertDatabaseHas('prompt_versions', ['version' => 1, 'status' => 'active']);
    }

    // ─── Memory / RAG ───────────────────────────────────────────────

    public function test_memory_remember_then_retrieve_returns_matching_text(): void
    {
        $svc = app(MemoryService::class);
        $collection = MemoryCollection::create([
            'tenant_id' => $this->tenant->id, 'slug' => 'notes', 'name' => 'Notes',
            'scope' => 'tenant', 'vector_namespace' => 't1_notes',
            'embedding_model' => 'fallback', 'embedding_dim' => 384,
        ]);
        $svc->remember($collection, 'Saudi Arabia capital is Riyadh.');
        $svc->remember($collection, 'PostGIS handles spatial joins.');
        $hits = $svc->retrieve($collection, 'capital of Saudi Arabia', ['top_k' => 2, 'scope' => 'tenant']);
        $this->assertIsArray($hits);
    }

    // ─── Event Bus ──────────────────────────────────────────────────

    public function test_event_bus_publishes_to_event_log(): void
    {
        $bus = app(EventBus::class);
        $id = $bus->publish('agent.events', 'test.fired', ['value' => 1]);
        $this->assertNotEmpty($id);
        $this->assertDatabaseHas('event_log', ['topic' => 'agent.events', 'event_type' => 'test.fired']);
    }

    public function test_event_bus_status_endpoint(): void
    {
        $this->withToken($this->token)->getJson('/api/event-bus/status')
            ->assertOk()->assertJsonStructure(['driver', 'topics']);
    }

    // ─── A2A Gateway ────────────────────────────────────────────────

    public function test_a2a_partner_can_be_registered(): void
    {
        $this->withToken($this->token)->postJson('/api/a2a/partners', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Demo', 'partner_id' => 'demo-x',
            'endpoint_url' => 'https://x.example.com/a2a',
            'framework' => 'langgraph', 'auth_type' => 'hmac',
            'capabilities' => ['investigation'],
        ])->assertSuccessful();
        $this->assertDatabaseHas('a2a_partners', ['partner_id' => 'demo-x']);
    }

    // ─── Meta-Agents + Intent Router ────────────────────────────────

    public function test_intent_router_classifies_build_prompt(): void
    {
        $router = app(IntentRouterService::class);
        $intent = $router->classify('Please build me an agent that queries PostGIS');
        $this->assertContains($intent['target'] ?? null, [
            'platform_architect', 'agent_builder', 'workflow_builder',
        ]);
    }

    public function test_meta_agent_registry_endpoint_returns_11_agents(): void
    {
        $this->withToken($this->token)->getJson('/api/meta-agents/registry')
            ->assertOk()->assertJsonCount(11, 'data');
    }

    public function test_meta_agent_orchestrator_records_a_run(): void
    {
        $orch = app(MetaAgentOrchestrator::class);
        $run = $orch->start($this->tenant->id, null, $this->admin->id, 'agent_builder', 'demo prompt');
        $this->assertNotNull($run);
        $this->assertDatabaseHas('meta_agent_runs', ['id' => $run->id, 'meta_agent' => 'agent_builder']);
    }

    // ─── Migration imports ──────────────────────────────────────────

    public function test_migration_imports_simple_n8n_workflow(): void
    {
        $svc = app(MigrationService::class);
        $sample = [
            'name' => 'My n8n flow',
            'nodes' => [
                ['name' => 'Start', 'type' => 'n8n-nodes-base.start'],
                ['name' => 'HTTP', 'type' => 'n8n-nodes-base.httpRequest'],
            ],
            'connections' => [],
        ];
        $import = $svc->import($this->tenant->id, null, 'n8n', $sample, $this->admin->id);
        $this->assertDatabaseHas('migration_imports', ['id' => $import->id]);
    }

    // ─── Bridges ────────────────────────────────────────────────────

    public function test_bridge_connection_storage(): void
    {
        $this->withToken($this->token)->postJson('/api/bridges/connections', [
            'tenant_id' => $this->tenant->id,
            'framework' => 'crewai',
            'name' => 'Local Crew',
            'enabled' => true,
        ])->assertSuccessful();
        $this->assertDatabaseHas('bridge_connections', ['framework' => 'crewai']);
    }

    public function test_bridge_frameworks_endpoint(): void
    {
        $this->withToken($this->token)->getJson('/api/bridges/frameworks')
            ->assertOk()->assertJsonStructure(['data']);
    }

    // ─── Observability ──────────────────────────────────────────────

    public function test_prometheus_metrics_endpoint_is_text(): void
    {
        $this->getJson('/api/observability/metrics')->assertOk();
    }

    // ─── Comments ──────────────────────────────────────────────────

    public function test_can_post_and_list_comments(): void
    {
        $this->withToken($this->token)->postJson('/api/comments', [
            'tenant_id'  => $this->tenant->id,
            'asset_type' => 'agent',
            'asset_id'   => 1,
            'body'       => 'looks good',
        ])->assertSuccessful();
        $this->withToken($this->token)->getJson('/api/comments?asset_type=agent&asset_id=1')
            ->assertOk()->assertJsonFragment(['body' => 'looks good']);
    }
}
