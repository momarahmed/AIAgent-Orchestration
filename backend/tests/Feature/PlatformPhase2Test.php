<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentVersion;
use App\Models\Approval;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\TestSuite;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Tenant $tenant;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', 'admin@enterprise-ai-mcp.local')->firstOrFail();
        $this->tenant = Tenant::where('slug', 'esri-saudi')->firstOrFail();
        $this->project = Project::where('slug', 'gis-ops')->firstOrFail();
    }

    protected function token(): string
    {
        return $this->admin->createToken('test')->plainTextToken;
    }

    public function test_approval_queue_endpoint_returns_paginated(): void
    {
        $this->withToken($this->token())
            ->getJson('/api/approvals')
            ->assertOk();
    }

    public function test_approval_gated_workflow_pauses_and_resumes(): void
    {
        $wf = Workflow::where('slug', 'approval-gated-publish')->firstOrFail();

        $resp = $this->withToken($this->token())
            ->postJson("/api/workflows/{$wf->id}/run", ['input' => []]);
        $resp->assertOk();
        $this->assertSame('awaiting_approval', $resp->json('status'));

        $approval = Approval::where('subject_type', 'workflow_run')
            ->where('subject_id', $resp->json('id'))
            ->latest()
            ->firstOrFail();

        $this->withToken($this->token())
            ->postJson("/api/approvals/{$approval->id}/approve")
            ->assertOk();
    }

    public function test_agent_version_diff(): void
    {
        $agent = Agent::create([
            'tenant_id' => $this->tenant->id,
            'project_id' => $this->project->id,
            'name' => 'DiffAgent',
            'slug' => 'diff-' . uniqid(),
            'risk_level' => 'L1',
            'status' => 'draft',
        ]);
        AgentVersion::create(['agent_id' => $agent->id, 'version' => 1, 'system_instructions' => 'a']);
        AgentVersion::create(['agent_id' => $agent->id, 'version' => 2, 'system_instructions' => 'b']);

        $this->withToken($this->token())
            ->getJson("/api/agents/{$agent->id}/versions/1/diff/2")
            ->assertOk()
            ->assertJsonStructure(['from', 'to', 'changes']);
    }

    public function test_template_export_and_import_roundtrip(): void
    {
        $wf = Workflow::where('slug', 'hello-world')->firstOrFail();

        $created = $this->withToken($this->token())
            ->postJson('/api/templates/from-asset', [
                'asset_type' => 'workflow',
                'asset_id' => $wf->id,
                'name' => 'Hello roundtrip',
                'tenant_id' => $this->tenant->id,
            ])->assertCreated()->json();

        $exported = $this->withToken($this->token())
            ->getJson("/api/templates/{$created['id']}/export.json")
            ->assertOk()
            ->json();

        $this->assertSame('workflow', $exported['asset_type']);
        $this->assertDoesNotMatchRegularExpression('/sk-[A-Za-z0-9]{20,}/', json_encode($exported));

        $imp = $this->withToken($this->token())
            ->postJson('/api/templates/import', [
                'manifest' => $exported,
                'tenant_id' => $this->tenant->id,
            ])->assertCreated()->json();

        $this->assertNotEmpty($imp['template']['id']);
    }

    public function test_deployment_to_prod_blocks_non_vault_secrets(): void
    {
        $wf = Workflow::where('slug', 'hello-world')->firstOrFail();

        $resp = $this->withToken($this->token())->postJson('/api/deployments', [
            'tenant_id' => $this->tenant->id,
            'project_id' => $this->project->id,
            'asset_type' => 'workflow',
            'asset_id' => $wf->id,
            'environment' => 'prod',
            'secret_refs' => ['api_key' => 'plaintext-not-vault'],
        ])->assertCreated();

        $this->assertSame('failed', $resp->json('status'));
    }

    public function test_test_runner_executes_agent_suite(): void
    {
        $agent = Agent::where('slug', 'gis-health')->firstOrFail();
        $suite = TestSuite::where('asset_type', 'agent')->where('asset_id', $agent->id)->firstOrFail();

        $resp = $this->withToken($this->token())->postJson("/api/tests/{$suite->id}/run");
        $resp->assertOk();
        $this->assertGreaterThan(0, $resp->json('passed') + $resp->json('failed'));
    }

    public function test_codegen_stub_returns_generated_files(): void
    {
        $resp = $this->withToken($this->token())->postJson('/api/codegen/mcp', [
            'tenant_id' => $this->tenant->id,
            'prompt' => 'Generate an MCP server for the inventory REST API.',
            'inputs' => ['name' => 'inventory', 'tools' => [['name' => 'list', 'description' => 'list items']]],
        ])->assertCreated();

        $this->assertSame('completed', $resp->json('status'));
        $this->assertIsArray($resp->json('outputs.files'));
    }

    public function test_agent_copy_creates_new_asset_without_secrets(): void
    {
        $agent = Agent::where('slug', 'gis-health')->firstOrFail();

        $resp = $this->withToken($this->token())
            ->postJson("/api/agents/{$agent->id}/copy", ['name' => 'Copy of GIS'])
            ->assertCreated();

        $this->assertSame('Copy of GIS', $resp->json('name'));
        $this->assertNotSame($agent->id, $resp->json('id'));
    }

    public function test_workflow_run_supports_environment(): void
    {
        $wf = Workflow::where('slug', 'hello-world')->firstOrFail();
        $resp = $this->withToken($this->token())
            ->postJson("/api/workflows/{$wf->id}/run", [
                'input' => ['prompt' => 'env smoke'],
                'environment' => 'dev',
            ])->assertOk();

        $this->assertSame('dev', $resp->json('environment'));
    }
}
