<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformPhase1Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@enterprise-ai-mcp.local')->firstOrFail();
    }

    protected function token(): string
    {
        return $this->admin->createToken('test')->plainTextToken;
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')->assertOk();
    }

    public function test_login_and_me(): void
    {
        $login = $this->postJson('/api/auth/login', [
            'email' => 'admin@enterprise-ai-mcp.local',
            'password' => 'Admin@12345',
        ]);
        $login->assertOk()->assertJsonStructure(['token', 'user', 'tenants']);
        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@enterprise-ai-mcp.local');
    }

    public function test_agent_crud_creates_version_and_audit(): void
    {
        $project = \App\Models\Project::first();
        $response = $this->withToken($this->token())->postJson('/api/agents', [
            'tenant_id' => $project->tenant_id,
            'project_id' => $project->id,
            'name' => 'Test Agent',
            'system_instructions' => 'Test',
            'model_config' => ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
            'memory_scope' => 'session',
        ]);
        $response->assertCreated();
        $agentId = $response->json('id');

        $this->withToken($this->token())->putJson("/api/agents/{$agentId}", [
            'system_instructions' => 'Updated instructions',
        ])->assertOk();

        $this->assertDatabaseHas('agent_versions', [
            'agent_id' => $agentId,
            'version' => 2,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'subject_type' => 'agent',
            'subject_id' => $agentId,
        ]);
    }

    public function test_mcp_health_check(): void
    {
        $mcp = \App\Models\McpServer::first();
        $this->withToken($this->token())
            ->postJson("/api/mcp-servers/{$mcp->id}/health")
            ->assertOk()
            ->assertJsonStructure(['health', 'checked_at']);
    }

    public function test_workflow_run_smoke(): void
    {
        $wf = \App\Models\Workflow::where('slug', 'hello-world')->first();
        $run = $this->withToken($this->token())
            ->postJson("/api/workflows/{$wf->id}/run", ['input' => ['prompt' => 'smoke test']]);
        $run->assertOk()->assertJsonStructure(['id', 'status', 'output']);
        $runId = $run->json('id');

        $detail = $this->withToken($this->token())->getJson("/api/runs/{$runId}");
        $detail->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonStructure(['tasks']);
    }

    public function test_chat_execute(): void
    {
        $this->withToken($this->token())
            ->postJson('/api/chat/execute', ['prompt' => 'Hello GIS agent'])
            ->assertOk()
            ->assertJsonStructure(['run_id', 'response']);
    }
}
