<?php

namespace Database\Seeders;

use App\Models\AgentQualityScore;
use App\Models\AnalyticsSnapshot;
use App\Models\ComplianceFramework;
use App\Models\GitopsEnvironment;
use App\Models\Locale;
use App\Models\LocaleTranslation;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceVersion;
use App\Models\PortfolioBudget;
use App\Models\RegionHealth;
use App\Models\SbomDiff;
use App\Models\SbomSnapshot;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VulnerabilityFinding;
use App\Services\MarketplaceSignatureService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Phase 5 demo data — Marketplace + Analytics + Compliance + GitOps + Locales.
 *
 * Idempotent: uses updateOrCreate / firstOrCreate everywhere.
 *
 * Ships 25 marketplace listings (PRD Phase 5 Acceptance Criterion 1).
 */
class Phase5Seeder extends Seeder
{
    public function run(): void
    {
        $tenant    = Tenant::first() ?? Tenant::create(['name' => 'Default', 'slug' => 'default']);
        $publisher = User::first();
        $sigSvc    = app(MarketplaceSignatureService::class);

        $this->seedLocales();
        $this->seedComplianceFrameworks();
        $this->seedRegions();
        $this->seedGitops();
        $this->seedPortfolioBudgets($tenant);
        $this->seedMarketplace($tenant, $publisher, $sigSvc);
        $this->seedAnalyticsSnapshots($tenant);
        $this->seedQualityScores();
        $this->seedContinuousScanner();
    }

    private function seedLocales(): void
    {
        $en = Locale::updateOrCreate(['code' => 'en'], ['name' => 'English', 'rtl' => false, 'is_active' => true]);
        $ar = Locale::updateOrCreate(['code' => 'ar'], ['name' => 'العربية',  'rtl' => true,  'is_active' => true]);

        $strings = [
            'common.welcome'              => ['en' => 'Welcome',                  'ar' => 'مرحبا'],
            'common.dashboard'            => ['en' => 'Dashboard',                'ar' => 'لوحة التحكم'],
            'common.agents'               => ['en' => 'Agents',                   'ar' => 'الوكلاء'],
            'common.mcp_servers'          => ['en' => 'MCP Servers',              'ar' => 'خوادم MCP'],
            'common.workflows'            => ['en' => 'Workflows',                'ar' => 'مسارات العمل'],
            'common.runs'                 => ['en' => 'Runs',                     'ar' => 'التشغيلات'],
            'common.approvals'            => ['en' => 'Approvals',                'ar' => 'الموافقات'],
            'common.deployments'          => ['en' => 'Deployments',              'ar' => 'النشر'],
            'common.templates'            => ['en' => 'Templates',                'ar' => 'القوالب'],
            'common.marketplace'          => ['en' => 'Marketplace',              'ar' => 'السوق'],
            'common.analytics'            => ['en' => 'Analytics',                'ar' => 'التحليلات'],
            'common.cost_governance'      => ['en' => 'Cost Governance',          'ar' => 'حوكمة التكلفة'],
            'common.compliance'           => ['en' => 'Compliance',               'ar' => 'الامتثال'],
            'common.continuous_security'  => ['en' => 'Continuous Security',      'ar' => 'الأمان المستمر'],
            'common.gitops'               => ['en' => 'GitOps',                   'ar' => 'GitOps'],
            'common.operations'           => ['en' => 'Operations',               'ar' => 'العمليات'],
            'common.search'               => ['en' => 'Search',                   'ar' => 'بحث'],
            'common.install'              => ['en' => 'Install',                  'ar' => 'تثبيت'],
            'common.publish'              => ['en' => 'Publish',                  'ar' => 'نشر'],
            'common.rate'                 => ['en' => 'Rate',                     'ar' => 'تقييم'],
            'common.save'                 => ['en' => 'Save',                     'ar' => 'حفظ'],
            'common.cancel'               => ['en' => 'Cancel',                   'ar' => 'إلغاء'],
            'common.export'               => ['en' => 'Export',                   'ar' => 'تصدير'],
            'marketplace.title'           => ['en' => 'Template Marketplace',     'ar' => 'سوق القوالب'],
            'marketplace.search_placeholder' => ['en' => 'Find templates with natural language…', 'ar' => 'ابحث عن القوالب بلغة طبيعية…'],
            'analytics.usage'             => ['en' => 'Usage',                    'ar' => 'الاستخدام'],
            'analytics.cost'              => ['en' => 'Cost',                     'ar' => 'التكلفة'],
            'analytics.reliability'       => ['en' => 'Reliability',              'ar' => 'الموثوقية'],
        ];

        foreach ($strings as $key => $values) {
            [$ns, $k] = explode('.', $key, 2);
            foreach (['en', 'ar'] as $locale) {
                LocaleTranslation::updateOrCreate(
                    ['locale_id' => $locale === 'en' ? $en->id : $ar->id, 'namespace' => $ns, 'key' => $k],
                    ['value' => $values[$locale]],
                );
            }
        }
    }

    private function seedComplianceFrameworks(): void
    {
        $frameworks = [
            ['code' => 'SOC2', 'name' => 'SOC 2 Type II', 'controls' => [
                'CC1.1' => 'Commitment to integrity and ethical values',
                'CC2.1' => 'Internal communication of security policies',
                'CC5.1' => 'Logical and physical access controls',
                'CC6.1' => 'Logical access security software',
                'CC6.7' => 'Restrict transmission of sensitive information',
                'CC7.1' => 'Security event monitoring',
                'CC7.2' => 'Anomaly detection',
                'CC8.1' => 'Change management',
                'CC9.1' => 'Risk mitigation',
            ]],
            ['code' => 'ISO27001', 'name' => 'ISO/IEC 27001:2022', 'controls' => [
                'A.5.1'  => 'Information security policies',
                'A.5.15' => 'Access control',
                'A.8.2'  => 'Information classification',
                'A.8.16' => 'Monitoring activities',
                'A.8.28' => 'Secure coding',
                'A.8.32' => 'Change management',
            ]],
            ['code' => 'GDPR', 'name' => 'EU General Data Protection Regulation', 'controls' => [
                'GDPR.5'   => 'Principles relating to processing of personal data',
                'GDPR.25'  => 'Data protection by design and by default',
                'GDPR.30'  => 'Records of processing activities',
                'GDPR.32'  => 'Security of processing',
                'GDPR.33'  => 'Breach notification (audit log)',
                'GDPR.35'  => 'Data protection impact assessment',
            ]],
        ];
        foreach ($frameworks as $f) {
            ComplianceFramework::updateOrCreate(
                ['code' => $f['code']],
                ['name' => $f['name'], 'controls' => $f['controls'], 'is_active' => true, 'description' => $f['name']],
            );
        }
    }

    private function seedRegions(): void
    {
        RegionHealth::updateOrCreate(['region' => 'us-east-1'], [
            'role' => 'primary', 'status' => 'healthy', 'replication_lag_seconds' => 0.5, 'last_checked_at' => now(),
        ]);
        RegionHealth::updateOrCreate(['region' => 'eu-west-1'], [
            'role' => 'secondary', 'status' => 'healthy', 'replication_lag_seconds' => 1.4, 'last_checked_at' => now(),
        ]);
    }

    private function seedGitops(): void
    {
        foreach ([
            ['name' => 'eamcp-dev',     'environment_class' => 'dev',     'auto_sync' => true,  'namespace' => 'eamcp-dev'],
            ['name' => 'eamcp-staging', 'environment_class' => 'staging', 'auto_sync' => false, 'namespace' => 'eamcp-stg'],
            ['name' => 'eamcp-prod',    'environment_class' => 'prod',    'auto_sync' => false, 'namespace' => 'eamcp-prd'],
        ] as $env) {
            GitopsEnvironment::updateOrCreate(
                ['name' => $env['name']],
                array_merge([
                    'engine'      => 'argocd',
                    'repo_url'    => 'https://github.com/example/eamcp-platform.git',
                    'branch'      => 'main',
                    'path'        => "envs/{$env['name']}",
                    'cluster'     => 'in-cluster',
                    'drift_state' => 'synced',
                    'last_sync_at'=> now()->subMinutes(rand(2, 30)),
                    'last_commit_sha' => Str::random(12),
                ], $env),
            );
        }
    }

    private function seedPortfolioBudgets(Tenant $tenant): void
    {
        $org = PortfolioBudget::updateOrCreate(
            ['scope_type' => 'organization', 'scope_id' => null, 'name' => 'Org-wide Model Spend Cap'],
            ['monthly_limit_usd' => 50000, 'current_spend_usd' => 12500, 'action_on_exceed' => 'alert', 'is_active' => true, 'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth()],
        );
        PortfolioBudget::updateOrCreate(
            ['scope_type' => 'tenant', 'scope_id' => $tenant->id, 'name' => "Tenant {$tenant->name} Cap"],
            ['monthly_limit_usd' => 5000, 'current_spend_usd' => 1240, 'action_on_exceed' => 'throttle', 'parent_id' => $org->id, 'is_active' => true, 'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth()],
        );
    }

    private function seedMarketplace(Tenant $tenant, ?User $publisher, MarketplaceSignatureService $sig): void
    {
        $catalog = [
            ['Daily GIS Health Report',     'workflow', 'GIS monitoring',          ['monitoring','gis','daily','report'],     'Monitors ArcGIS service health and emails a daily report.'],
            ['SQL Server Health Monitor',   'workflow', 'database',                ['database','sqlserver','monitoring'],     'Hourly checks on SQL Server health metrics.'],
            ['Incident Triage Agent',       'agent',    'ITSM',                    ['itsm','triage','jira'],                  'LLM agent that triages incoming ServiceNow incidents.'],
            ['Email Summarizer Workflow',   'workflow', 'productivity',            ['email','summarizer','daily'],            'Summarizes inbox into a digest each morning.'],
            ['ArcGIS MCP Connector',        'mcp',      'GIS connector',           ['gis','arcgis','connector','mcp'],        'MCP server exposing read-only ArcGIS REST endpoints.'],
            ['ServiceNow MCP Connector',    'mcp',      'ITSM connector',          ['itsm','servicenow','mcp'],               'MCP server exposing governed ServiceNow operations.'],
            ['Jira MCP Connector',          'mcp',      'ITSM connector',          ['itsm','jira','mcp'],                     'MCP server for Jira issue ops and SLA tracking.'],
            ['Slack Notifier MCP',          'mcp',      'communication',           ['slack','notification','mcp'],            'MCP server for posting governed Slack messages.'],
            ['Confluence Reader MCP',       'mcp',      'documents',               ['confluence','docs','mcp'],               'MCP server for reading Confluence pages.'],
            ['SharePoint Reader MCP',       'mcp',      'documents',               ['sharepoint','docs','mcp'],               'MCP server for reading SharePoint documents.'],
            ['n8n Bridge MCP',              'mcp',      'workflow bridge',         ['n8n','bridge','mcp'],                    'MCP server to trigger n8n workflows.'],
            ['Activepieces Bridge MCP',     'mcp',      'workflow bridge',         ['activepieces','bridge','mcp'],           'MCP server to trigger Activepieces flows.'],
            ['Postgres Read MCP',           'mcp',      'database connector',      ['postgres','database','mcp'],             'Read-only Postgres MCP with column-level allowlists.'],
            ['Snowflake MCP',               'mcp',      'data warehouse',          ['snowflake','warehouse','mcp'],           'Snowflake MCP with cost-aware query routing.'],
            ['Customer Onboarding Agent',   'agent',    'CRM',                     ['crm','salesforce','onboarding'],         'Agent that onboards a new Salesforce account.'],
            ['Compliance Evidence Agent',   'agent',    'compliance',              ['compliance','soc2','evidence'],          'Agent that gathers SOC 2 evidence weekly.'],
            ['Code Review Meta-Agent',      'agent',    'engineering',             ['code','review','meta-agent'],            'Meta-agent that reviews PRs against guidelines.'],
            ['Cost Anomaly Watcher',        'workflow', 'finops',                  ['finops','anomaly','daily'],              'Detects daily anomalies in model spend.'],
            ['Trivy Continuous Scan',       'workflow', 'security',                ['security','sbom','trivy'],               'Continuous Trivy scans + SBOM diff alerts.'],
            ['Failover Drill Runbook',      'workflow', 'DR',                      ['dr','failover','runbook'],               'Runbook for quarterly regional failover drills.'],
            ['Daily KPI Email',             'workflow', 'reporting',               ['kpi','email','daily'],                   'Sends daily KPI snapshot to leadership.'],
            ['New Hire IT Onboarding',      'workflow', 'IT operations',           ['it','onboarding','user'],                'Provisions a new hire across IDP + ticketing.'],
            ['Approval Escalation Watcher', 'agent',    'governance',              ['approval','escalation'],                 'Escalates approvals exceeding SLA.'],
            ['Prompt Injection Audit',      'workflow', 'security',                ['security','prompt-injection'],           'Periodic prompt-injection corpus audit.'],
            ['Tenant Health Dashboard',     'workflow', 'platform-ops',            ['platform','tenant','health'],            'Refreshes the per-tenant health dashboard.'],
        ];

        foreach ($catalog as $i => [$title, $category, $assetTypeLabel, $tags, $description]) {
            $slug   = Str::slug($title) . '-' . ($i + 1);
            $assetType = match ($category) {
                'mcp'      => 'mcp',
                'agent'    => 'agent',
                'workflow' => 'workflow',
                default    => 'workflow',
            };

            $template = Template::firstOrCreate(
                ['slug' => $slug],
                [
                    'tenant_id'         => $tenant->id,
                    'name'              => $title,
                    'asset_type'        => $assetType,
                    'description'       => $description,
                    'payload'           => [
                        'name'  => $title,
                        'model' => 'gpt-4o-mini',
                        'instructions' => "You are the {$title} agent. " . $description,
                        'definition'   => ['nodes' => [['id' => 'start', 'type' => 'agent']], 'edges' => []],
                    ],
                    'parameters_schema' => [
                        'type' => 'object',
                        'properties' => [
                            'recipient_email' => ['type' => 'string', 'description' => 'Where to send results'],
                            'schedule_cron'   => ['type' => 'string', 'default' => '0 7 * * *'],
                        ],
                    ],
                    'visibility'        => 'internal',
                ],
            );

            $listing = MarketplaceListing::updateOrCreate(
                ['slug' => $slug],
                [
                    'tenant_id'          => $tenant->id,
                    'template_id'        => $template->id,
                    'publisher_id'       => $publisher?->id,
                    'title'              => $title,
                    'description'        => $description,
                    'category'           => $category,
                    'visibility'         => $i % 5 === 0 ? 'external' : 'internal',
                    'status'             => 'published',
                    'screenshots'        => [],
                    'tags'               => $tags,
                    'parameters_schema'  => $template->parameters_schema,
                    'required_connectors'=> [],
                    'readme'             => ['content' => $description . "\n\n## Usage\n\n" . "1. Install\n2. Configure parameters\n3. Run"],
                    'latest_version'     => '1.0.0',
                    'rating_avg'         => round(3.8 + ($i % 10) / 10, 2),
                    'rating_count'       => 5 + $i,
                    'install_count'      => 10 + ($i * 3),
                    'sbom_hash'          => hash('sha256', $title . '1.0.0'),
                    'signed'             => true,
                    'quality_review'     => [
                        'verdict' => 'approved',
                        'score'   => 88,
                    ],
                ],
            );

            $manifest = [
                'slug'              => $listing->slug,
                'title'             => $listing->title,
                'description'       => $listing->description,
                'category'          => $listing->category,
                'asset_type'        => $assetType,
                'payload'           => $template->payload,
                'parameters_schema' => $template->parameters_schema,
                'tags'              => $tags,
            ];
            $sigBundle = $sig->sign($manifest, $tenant->id);

            MarketplaceVersion::updateOrCreate(
                ['listing_id' => $listing->id, 'version' => '1.0.0'],
                [
                    'manifest'          => $manifest,
                    'parameters_schema' => $template->parameters_schema,
                    'signature'         => $sigBundle['signature'],
                    'sbom_hash'         => $sig->sbomHash($manifest),
                    'status'            => 'approved',
                    'review_log'        => ['meta_agents' => ['verdict' => 'approved', 'score' => 88]],
                    'published_by'      => $publisher?->id,
                    'published_at'      => now()->subDays(rand(1, 60)),
                ],
            );

            $listing->update([
                'latest_signature' => $sigBundle['signature'],
                'sbom_hash'        => $sig->sbomHash($manifest),
            ]);
        }
    }

    private function seedAnalyticsSnapshots(Tenant $tenant): void
    {
        for ($d = 29; $d >= 0; $d--) {
            $day = now()->subDays($d);
            AnalyticsSnapshot::updateOrCreate(
                ['tenant_id' => $tenant->id, 'day' => $day->toDateString(), 'kind' => 'cost', 'metric' => 'model_spend'],
                ['dimensions' => ['provider' => 'openai'], 'value' => round(80 + sin($d / 3) * 20 + ($d % 5) * 5, 2)],
            );
            AnalyticsSnapshot::updateOrCreate(
                ['tenant_id' => $tenant->id, 'day' => $day->toDateString(), 'kind' => 'usage', 'metric' => 'runs'],
                ['dimensions' => [], 'value' => 120 + ($d % 7) * 12],
            );
            AnalyticsSnapshot::updateOrCreate(
                ['tenant_id' => $tenant->id, 'day' => $day->toDateString(), 'kind' => 'reliability', 'metric' => 'success_rate'],
                ['dimensions' => [], 'value' => round(95 + (sin($d / 2) * 3), 2)],
            );
        }
    }

    private function seedQualityScores(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            AgentQualityScore::updateOrCreate(
                ['subject_type' => 'workflow', 'subject_id' => $i],
                [
                    'success_rate'     => round(85 + (rand(0, 1500) / 100), 2),
                    'latency_ms_p95'   => round(1200 + rand(0, 2500), 2),
                    'cost_per_run_usd' => round(0.05 + (rand(0, 50) / 100), 4),
                    'user_rating'      => round(3.5 + (rand(0, 150) / 100), 2),
                    'composite_score'  => round(70 + rand(0, 25), 2),
                    'computed_at'      => now(),
                ],
            );
        }
    }

    private function seedContinuousScanner(): void
    {
        $base = SbomSnapshot::firstOrCreate(
            ['sbom_hash' => hash('sha256', 'baseline')],
            [
                'image_ref' => 'ghcr.io/eamcp/backend:latest',
                'components' => [['name' => 'curl', 'version' => '7.81', 'type' => 'deb'], ['name' => 'openssl', 'version' => '3.0.2', 'type' => 'deb']],
                'total_packages' => 2,
            ],
        );
        $newer = SbomSnapshot::firstOrCreate(
            ['sbom_hash' => hash('sha256', 'newer')],
            [
                'image_ref' => 'ghcr.io/eamcp/backend:latest',
                'components' => [['name' => 'curl', 'version' => '7.88', 'type' => 'deb'], ['name' => 'openssl', 'version' => '3.0.13', 'type' => 'deb'], ['name' => 'libxml2', 'version' => '2.11.5', 'type' => 'deb']],
                'total_packages' => 3,
            ],
        );

        SbomDiff::updateOrCreate(
            ['current_snapshot_id' => $newer->id],
            [
                'image_ref' => 'ghcr.io/eamcp/backend:latest',
                'previous_snapshot_id' => $base->id,
                'diff' => [
                    'added'   => [['name' => 'libxml2', 'version' => '2.11.5']],
                    'changed' => [['name' => 'curl', 'from' => '7.81', 'to' => '7.88'], ['name' => 'openssl', 'from' => '3.0.2', 'to' => '3.0.13']],
                    'removed' => [],
                ],
                'risk_level' => 'medium',
                'alert_sent' => false,
            ],
        );

        VulnerabilityFinding::updateOrCreate(
            ['cve' => 'CVE-2024-12345', 'image_ref' => 'ghcr.io/eamcp/backend:latest', 'package' => 'openssl'],
            ['installed_version' => '3.0.2', 'fixed_version' => '3.0.13', 'severity' => 'high', 'state' => 'open', 'deadline' => now()->addDays(14)->toDateString()],
        );
        VulnerabilityFinding::updateOrCreate(
            ['cve' => 'CVE-2024-23456', 'image_ref' => 'ghcr.io/eamcp/backend:latest', 'package' => 'curl'],
            ['installed_version' => '7.81', 'fixed_version' => '7.88', 'severity' => 'medium', 'state' => 'fixed', 'deadline' => now()->subDays(2)->toDateString()],
        );
    }
}
