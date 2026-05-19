<?php

namespace Tests\Feature;

use App\Models\ComplianceFramework;
use App\Models\Locale;
use App\Models\LocaleTranslation;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceVersion;
use App\Models\PortfolioBudget;
use App\Models\Project;
use App\Models\Role;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VulnerabilityFinding;
use App\Services\AutogenImporterService;
use App\Services\ComplianceExportService;
use App\Services\ContinuousScannerService;
use App\Services\GitOpsService;
use App\Services\MarketplaceService;
use App\Services\PortfolioCostService;
use App\Services\RagSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PlatformPhase5Test extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Tenant $tenant;
    protected Project $project;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->tenant = Tenant::create(['slug' => 'phase5', 'name' => 'Phase Five Corp', 'environment' => 'dev']);
        $this->project = Project::create(['tenant_id' => $this->tenant->id, 'slug' => 'demo', 'name' => 'Demo']);

        $adminRole = Role::create(['name' => 'admin', 'label' => 'Admin', 'level' => 50, 'is_system' => true, 'permissions' => ['*']]);
        $this->admin = User::factory()->create(['email' => 'admin@phase5.test']);
        $this->admin->tenants()->attach($this->tenant->id, ['role_id' => $adminRole->id]);

        $this->token = $this->admin->createToken('test')->plainTextToken;
    }

    private function makeTemplate(string $name = 'Demo Workflow', string $assetType = 'workflow'): Template
    {
        return Template::create([
            'tenant_id'         => $this->tenant->id,
            'name'              => $name,
            'slug'              => str()->slug($name) . '-' . uniqid(),
            'asset_type'        => $assetType,
            'description'       => 'A demo template',
            'payload'           => ['name' => $name, 'model' => 'gpt-4o', 'definition' => ['nodes' => [['id' => 'n1', 'type' => 'agent']], 'edges' => []]],
            'parameters_schema' => ['type' => 'object', 'properties' => ['email' => ['type' => 'string']]],
        ]);
    }

    public function test_marketplace_publish_signs_and_publishes_listing(): void
    {
        $template = $this->makeTemplate();
        $svc = app(MarketplaceService::class);

        $listing = $svc->publish($template, $this->admin->id, [
            'visibility' => 'internal',
            'tags'       => ['demo', 'workflow'],
            'readme'     => ['content' => 'Demo template README'],
        ]);

        $this->assertEquals('published', $listing->status);
        $this->assertTrue((bool) $listing->signed);
        $this->assertNotNull($listing->latest_signature);
        $this->assertEquals(1, $listing->versions()->count());
    }

    public function test_marketplace_install_in_dev_succeeds_and_creates_workflow(): void
    {
        $template = $this->makeTemplate('Daily KPI');
        $svc = app(MarketplaceService::class);

        $listing = $svc->publish($template, $this->admin->id, ['visibility' => 'internal']);

        $install = $svc->install($listing, [
            'tenant_id'   => $this->tenant->id,
            'project_id'  => $this->project->id,
            'user_id'     => $this->admin->id,
            'environment' => 'dev',
            'parameters'  => ['email' => 'ops@example.com'],
        ]);

        $this->assertEquals('installed', $install->status);
        $this->assertArrayHasKey('workflow_id', $install->created_assets);
        $this->assertEquals(1, $listing->fresh()->install_count);
    }

    public function test_marketplace_install_unsigned_in_prod_is_blocked(): void
    {
        $listing = MarketplaceListing::create([
            'tenant_id'      => $this->tenant->id,
            'template_id'    => $this->makeTemplate()->id,
            'publisher_id'   => $this->admin->id,
            'slug'           => 'unsigned-listing',
            'title'          => 'Unsigned Listing',
            'description'    => 'no signature',
            'category'       => 'workflow',
            'visibility'     => 'external',
            'status'         => 'published',
            'latest_version' => '0.1.0',
            'signed'         => false,
        ]);
        MarketplaceVersion::create([
            'listing_id' => $listing->id,
            'version'    => '0.1.0',
            'manifest'   => ['asset_type' => 'workflow', 'payload' => ['name' => 'x']],
            'status'     => 'approved',
        ]);

        $this->expectException(\RuntimeException::class);
        app(MarketplaceService::class)->install($listing, [
            'tenant_id'   => $this->tenant->id,
            'environment' => 'prod',
            'user_id'     => $this->admin->id,
        ]);
    }

    public function test_marketplace_rag_search_returns_relevant_results(): void
    {
        $tpl = $this->makeTemplate('SQL Server Health Monitor');
        app(MarketplaceService::class)->publish($tpl, $this->admin->id, [
            'visibility' => 'internal',
            'tags'       => ['sqlserver', 'monitoring', 'daily'],
            'readme'     => ['content' => 'Monitors SQL Server health with hourly checks.'],
        ]);

        $results = app(RagSearchService::class)->search('monitor sql server health', $this->tenant->id);
        $this->assertNotEmpty($results);
        $this->assertEquals('SQL Server Health Monitor', $results[0]['title']);
    }

    public function test_compliance_export_creates_ready_zip(): void
    {
        ComplianceFramework::create([
            'code'      => 'SOC2',
            'name'      => 'SOC 2 Type II',
            'controls'  => ['CC7.2' => 'Anomaly detection', 'CC8.1' => 'Change management'],
            'is_active' => true,
        ]);

        $export = app(ComplianceExportService::class)->generate(
            $this->tenant->id,
            ['SOC2'],
            Carbon::now()->subDays(30),
            Carbon::now(),
            $this->admin->id,
            'zip',
        );

        $this->assertEquals('ready', $export->status);
        $this->assertNotNull($export->storage_path);
        $this->assertGreaterThan(0, $export->file_size);
        $this->assertArrayHasKey('SOC2', $export->control_mappings);
    }

    public function test_continuous_scanner_records_snapshot_diff_and_finding(): void
    {
        $svc = app(ContinuousScannerService::class);

        $svc->snapshot('ghcr.io/eamcp/test:1');
        sleep(0); // ensure ordering
        $svc->snapshot('ghcr.io/eamcp/test:1');
        $diff = $svc->diffLatest('ghcr.io/eamcp/test:1');

        $this->assertNotNull($diff);
        $this->assertContains($diff->risk_level, ['low', 'medium', 'high', 'critical']);

        $finding = $svc->recordFinding([
            'cve'              => 'CVE-2026-0001',
            'image_ref'        => 'ghcr.io/eamcp/test:1',
            'package'          => 'curl',
            'installed_version'=> '7.81',
            'fixed_version'    => '7.88',
            'severity'         => 'high',
        ]);

        $svc->transitionFinding($finding->id, 'accepted_risk', $this->admin->id, 'risk accepted');
        $this->assertEquals('accepted_risk', VulnerabilityFinding::find($finding->id)->state);
    }

    public function test_gitops_register_sync_and_failover_drill(): void
    {
        $gitops = app(GitOpsService::class);

        $env = $gitops->registerEnvironment([
            'name'              => 'eamcp-test',
            'engine'            => 'argocd',
            'repo_url'          => 'https://github.com/example/eamcp.git',
            'path'              => 'envs/test',
            'environment_class' => 'dev',
            'auto_sync'         => true,
        ]);
        $sync = $gitops->syncEnvironment($env, $this->admin->id);
        $this->assertEquals('succeeded', $sync->status);

        $drill = $gitops->failoverDrill('us-east-1', 'eu-west-1');
        $this->assertEquals('completed', $drill['status']);
        $this->assertLessThanOrEqual(30, $drill['rto_minutes']);
    }

    public function test_autogen_importer_converts_to_workflow_with_confidence(): void
    {
        $job = app(AutogenImporterService::class)->import('autogen', [
            'agents' => [
                ['name' => 'Researcher', 'system_message' => 'Research.', 'llm_config' => ['model' => 'gpt-4o']],
                ['name' => 'Writer',     'system_message' => 'Write.',    'llm_config' => ['model' => 'gpt-4o']],
            ],
            'messages' => [],
        ], ['tenant_id' => $this->tenant->id, 'user_id' => $this->admin->id]);

        $this->assertNotEquals('failed', $job->status);
        $this->assertGreaterThanOrEqual(50, (float) $job->confidence_score);
        $this->assertNotEmpty($job->output_payload['nodes'] ?? []);
    }

    public function test_locales_endpoint_returns_dictionary(): void
    {
        $en = Locale::create(['code' => 'en', 'name' => 'English', 'rtl' => false, 'is_active' => true]);
        LocaleTranslation::create(['locale_id' => $en->id, 'namespace' => 'common', 'key' => 'welcome', 'value' => 'Welcome']);

        $response = $this->getJson('/api/locales/en');
        $response->assertOk()->assertJsonPath('translations.common.welcome', 'Welcome');
    }

    public function test_portfolio_budget_records_spend_and_marks_exceeded(): void
    {
        $budget = PortfolioBudget::create([
            'scope_type'        => 'tenant',
            'scope_id'          => $this->tenant->id,
            'name'              => 'Tenant cap',
            'monthly_limit_usd' => 100,
            'current_spend_usd' => 0,
            'action_on_exceed'  => 'alert',
            'is_active'         => true,
            'period_start'      => now()->startOfMonth(),
            'period_end'        => now()->endOfMonth(),
        ]);

        app(PortfolioCostService::class)->recordSpend('tenant', $this->tenant->id, 150.0, [
            'tenant_id' => $this->tenant->id,
            'metric'    => 'model_spend',
            'provider'  => 'openai',
        ]);

        $budget->refresh();
        $this->assertTrue($budget->isExceeded());
        $this->assertEquals(150.0, (float) $budget->current_spend_usd);
    }

    public function test_analytics_endpoints_return_json(): void
    {
        foreach (['/api/analytics/usage', '/api/analytics/cost', '/api/analytics/reliability', '/api/analytics/template-adoption'] as $path) {
            $this->withToken($this->token)->getJson($path)->assertOk();
        }
    }

    public function test_marketplace_publish_and_install_via_http(): void
    {
        $template = $this->makeTemplate('Email Summarizer');

        $resp = $this->withToken($this->token)->postJson('/api/marketplace/publish', [
            'template_id' => $template->id,
            'visibility'  => 'internal',
            'tags'        => ['email', 'daily'],
        ]);
        $resp->assertCreated();
        $listingId = $resp->json('id');
        $this->assertNotNull($listingId);

        $this->withToken($this->token)
            ->postJson("/api/marketplace/listings/{$listingId}/install", [
                'environment' => 'dev',
                'project_id'  => $this->project->id,
                'parameters'  => [],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'installed');
    }
}
