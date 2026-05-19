<?php

namespace Database\Seeders;

use App\Models\AuditReportTemplate;
use App\Models\McpServer;
use App\Models\NetworkAllowlist;
use App\Models\OpaPolicy;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProviderBudget;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Seeder;

class Phase3Seeder extends Seeder
{
    public function run(): void
    {
        // ─── Full RBAC Roles (PRD Section 21.2) ─────────────────────

        $roles = [
            ['name' => 'viewer',            'label' => 'Viewer',            'level' => 10, 'is_system' => true, 'description' => 'Read-only access to all assets'],
            ['name' => 'runner',            'label' => 'Runner',            'level' => 20, 'is_system' => true, 'description' => 'Can execute agents and workflows'],
            ['name' => 'builder',           'label' => 'Builder',           'level' => 30, 'is_system' => true, 'description' => 'Can create/edit agents, MCP servers, workflows'],
            ['name' => 'publisher',         'label' => 'Publisher',         'level' => 40, 'is_system' => true, 'description' => 'Can promote assets to staging/production'],
            ['name' => 'admin',             'label' => 'Admin',             'level' => 50, 'is_system' => true, 'description' => 'Full tenant administration'],
            ['name' => 'security_approver', 'label' => 'Security Approver', 'level' => 60, 'is_system' => true, 'description' => 'Can approve L3+ actions and security policies'],
            ['name' => 'platform_owner',    'label' => 'Platform Owner',    'level' => 100,'is_system' => true, 'description' => 'Super-admin with cross-tenant access'],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(['name' => $r['name']], $r);
        }

        // ─── Permissions ────────────────────────────────────────────

        $permGroups = [
            'agents' => ['agents.read', 'agents.create', 'agents.update', 'agents.delete', 'agents.execute', 'agents.deploy'],
            'mcp_servers' => ['mcp_servers.read', 'mcp_servers.create', 'mcp_servers.update', 'mcp_servers.delete', 'mcp_servers.deploy'],
            'workflows' => ['workflows.read', 'workflows.create', 'workflows.update', 'workflows.delete', 'workflows.execute', 'workflows.deploy'],
            'tools' => ['tools.read', 'tools.create', 'tools.update', 'tools.delete', 'tools.execute'],
            'deployments' => ['deployments.read', 'deployments.create', 'deployments.approve', 'deployments.rollback'],
            'approvals' => ['approvals.read', 'approvals.approve', 'approvals.reject'],
            'templates' => ['templates.read', 'templates.create', 'templates.import', 'templates.export'],
            'secrets' => ['secrets.read', 'secrets.create', 'secrets.delete', 'secrets.rotate'],
            'audit' => ['audit.read', 'audit.export'],
            'policies' => ['policies.read', 'policies.create', 'policies.update', 'policies.activate'],
            'security' => ['security.scan', 'security.read'],
            'admin' => ['admin.roles', 'admin.users', 'admin.tenants', 'admin.settings'],
        ];

        foreach ($permGroups as $group => $perms) {
            foreach ($perms as $perm) {
                Permission::firstOrCreate(['name' => $perm], [
                    'label' => ucwords(str_replace(['.', '_'], ' ', $perm)),
                    'group' => $group,
                ]);
            }
        }

        // Wire role → permissions
        $rolePerms = [
            'viewer' => array_merge($permGroups['agents'], $permGroups['workflows'], $permGroups['tools'], $permGroups['audit']),
            'runner' => ['agents.execute', 'workflows.execute', 'tools.execute', 'approvals.read'],
            'builder' => array_merge(
                $permGroups['agents'], $permGroups['mcp_servers'], $permGroups['workflows'],
                $permGroups['tools'], $permGroups['templates'],
            ),
            'publisher' => array_merge($permGroups['deployments'], $permGroups['templates']),
            'security_approver' => array_merge($permGroups['approvals'], $permGroups['security'], $permGroups['policies']),
            'admin' => ['*'],
            'platform_owner' => ['*'],
        ];

        foreach ($rolePerms as $roleName => $perms) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) continue;

            if (in_array('*', $perms, true)) {
                $role->update(['permissions' => ['*']]);
            } else {
                $permIds = Permission::whereIn('name', $perms)->pluck('id');
                $role->permissions()->syncWithoutDetaching($permIds);
            }
        }

        // ─── Assign Platform Owner to admin user ────────────────────

        $admin = User::where('email', 'admin@enterprise-ai-mcp.local')->first();
        $platformOwnerRole = Role::where('name', 'platform_owner')->first();
        $securityRole = Role::where('name', 'security_approver')->first();

        if ($admin && $platformOwnerRole) {
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                $admin->tenants()->syncWithoutDetaching([$tenant->id => ['role_id' => $platformOwnerRole->id]]);
            }
        }

        // Create security approver user
        $secUser = User::firstOrCreate(
            ['email' => 'security@enterprise-ai-mcp.local'],
            ['name' => 'Security Approver', 'password' => \Illuminate\Support\Facades\Hash::make('Security@12345')]
        );
        if ($securityRole) {
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                $secUser->tenants()->syncWithoutDetaching([$tenant->id => ['role_id' => $securityRole->id]]);
            }
        }

        // ─── Demo MCP Servers (real enterprise servers) ─────────────

        $primary = Tenant::where('slug', 'esri-saudi')->first();
        $project = Project::where('slug', 'gis-ops')->first();
        if (! $primary || ! $project) return;

        $dbMcp = McpServer::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'database-mcp'],
            [
                'tenant_id' => $primary->id, 'name' => 'Database MCP Server',
                'description' => 'Safe SQL execution with static analysis — PostgreSQL, SQL Server, Oracle.',
                'transport' => 'http', 'runtime' => 'python', 'endpoint' => 'http://127.0.0.1:9101/mcp',
                'auth_method' => 'bearer', 'status' => 'draft', 'health' => 'unknown',
            ]
        );
        foreach ([
            ['name' => 'check_db_health', 'risk_level' => 'L0', 'description' => 'Check database connectivity and health'],
            ['name' => 'inspect_schema', 'risk_level' => 'L0', 'description' => 'List tables, columns, indexes'],
            ['name' => 'run_safe_query', 'risk_level' => 'L1', 'description' => 'Execute read-only SQL with safety analysis'],
            ['name' => 'analyze_slow_queries', 'risk_level' => 'L1', 'description' => 'Analyze slow query log'],
            ['name' => 'explain_plan', 'risk_level' => 'L0', 'description' => 'Generate execution plan for a query'],
        ] as $t) {
            Tool::firstOrCreate(['mcp_server_id' => $dbMcp->id, 'name' => $t['name']], $t);
        }

        $fileMcp = McpServer::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'file-pdf-mcp'],
            [
                'tenant_id' => $primary->id, 'name' => 'File/PDF MCP Server',
                'description' => 'Read PDFs, DOCX, XLSX. Extract tables. Generate reports. Qdrant RAG backend.',
                'transport' => 'http', 'runtime' => 'python', 'endpoint' => 'http://127.0.0.1:9102/mcp',
                'auth_method' => 'none', 'status' => 'draft', 'health' => 'unknown',
            ]
        );
        foreach ([
            ['name' => 'read_pdf', 'risk_level' => 'L0', 'description' => 'Extract text from PDF document'],
            ['name' => 'read_docx', 'risk_level' => 'L0', 'description' => 'Extract text from DOCX document'],
            ['name' => 'extract_tables', 'risk_level' => 'L0', 'description' => 'Extract structured tables from document'],
            ['name' => 'create_report', 'risk_level' => 'L1', 'description' => 'Generate report in Markdown/HTML/PDF/DOCX'],
            ['name' => 'export_pdf', 'risk_level' => 'L1', 'description' => 'Export content as PDF'],
        ] as $t) {
            Tool::firstOrCreate(['mcp_server_id' => $fileMcp->id, 'name' => $t['name']], $t);
        }

        $emailMcp = McpServer::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'email-mcp'],
            [
                'tenant_id' => $primary->id, 'name' => 'Email/Notification MCP Server',
                'description' => 'Send emails and notifications. External recipients require approval.',
                'transport' => 'http', 'runtime' => 'python', 'endpoint' => 'http://127.0.0.1:9103/mcp',
                'auth_method' => 'oauth2', 'status' => 'draft', 'health' => 'unknown',
            ]
        );
        foreach ([
            ['name' => 'create_draft', 'risk_level' => 'L1', 'description' => 'Create email draft'],
            ['name' => 'send_email', 'risk_level' => 'L2', 'description' => 'Send email (L2 external recipients)'],
            ['name' => 'notify_user', 'risk_level' => 'L0', 'description' => 'Send in-platform notification'],
        ] as $t) {
            Tool::firstOrCreate(['mcp_server_id' => $emailMcp->id, 'name' => $t['name']], $t);
        }

        $monitoringMcp = McpServer::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'monitoring-mcp'],
            [
                'tenant_id' => $primary->id, 'name' => 'Monitoring MCP Server',
                'description' => 'Query Loki logs, Prometheus metrics, and analyze alerts.',
                'transport' => 'http', 'runtime' => 'python', 'endpoint' => 'http://127.0.0.1:9104/mcp',
                'auth_method' => 'bearer', 'status' => 'draft', 'health' => 'unknown',
            ]
        );
        foreach ([
            ['name' => 'query_logs', 'risk_level' => 'L0', 'description' => 'Query Loki log streams'],
            ['name' => 'get_metrics', 'risk_level' => 'L0', 'description' => 'Query Prometheus metrics'],
            ['name' => 'analyze_alerts', 'risk_level' => 'L1', 'description' => 'Analyze active alerts'],
        ] as $t) {
            Tool::firstOrCreate(['mcp_server_id' => $monitoringMcp->id, 'name' => $t['name']], $t);
        }

        $itsmMcp = McpServer::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'itsm-mcp'],
            [
                'tenant_id' => $primary->id, 'name' => 'ITSM MCP Server',
                'description' => 'Create/update Jira and ServiceNow tickets.',
                'transport' => 'http', 'runtime' => 'python', 'endpoint' => 'http://127.0.0.1:9105/mcp',
                'auth_method' => 'oauth2', 'status' => 'draft', 'health' => 'unknown',
            ]
        );
        foreach ([
            ['name' => 'create_ticket', 'risk_level' => 'L1', 'description' => 'Create a new ticket in Jira/ServiceNow'],
            ['name' => 'update_ticket', 'risk_level' => 'L1', 'description' => 'Update an existing ticket'],
            ['name' => 'attach_report', 'risk_level' => 'L1', 'description' => 'Attach a file/report to a ticket'],
        ] as $t) {
            Tool::firstOrCreate(['mcp_server_id' => $itsmMcp->id, 'name' => $t['name']], $t);
        }

        $browserMcp = McpServer::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'browser-cua-mcp'],
            [
                'tenant_id' => $primary->id, 'name' => 'Browser/CUA MCP Server',
                'description' => 'Browser automation with Playwright in sandboxed workers.',
                'transport' => 'http', 'runtime' => 'python', 'endpoint' => 'http://127.0.0.1:9106/mcp',
                'auth_method' => 'none', 'status' => 'draft', 'health' => 'unknown',
                'requires_sandbox' => true,
                'sandbox_config' => ['read_only_root' => true, 'no_host_mount' => true, 'no_privileged' => true, 'cpu_limit' => '1000m', 'memory_limit' => '1Gi', 'timeout_seconds' => 600],
            ]
        );
        foreach ([
            ['name' => 'open_page', 'risk_level' => 'L3', 'description' => 'Navigate to a URL in sandbox browser'],
            ['name' => 'click_button', 'risk_level' => 'L3', 'description' => 'Click an element on the page'],
            ['name' => 'fill_form', 'risk_level' => 'L3', 'description' => 'Fill form fields'],
            ['name' => 'capture_screen', 'risk_level' => 'L3', 'description' => 'Take a screenshot'],
            ['name' => 'read_ui_state', 'risk_level' => 'L3', 'description' => 'Extract visible UI state'],
        ] as $t) {
            Tool::firstOrCreate(['mcp_server_id' => $browserMcp->id, 'name' => $t['name']], $t);
        }

        // ─── Network Allowlists ─────────────────────────────────────

        $arcgisMcp = McpServer::where('slug', 'arcgis-mcp')->first();
        if ($arcgisMcp) {
            foreach (['dev', 'staging', 'prod'] as $env) {
                NetworkAllowlist::firstOrCreate(
                    ['mcp_server_id' => $arcgisMcp->id, 'environment' => $env, 'host' => 'portal.arcgis.local'],
                    ['tenant_id' => $primary->id, 'port' => 443, 'protocol' => 'tcp', 'description' => 'ArcGIS Portal']
                );
                NetworkAllowlist::firstOrCreate(
                    ['mcp_server_id' => $arcgisMcp->id, 'environment' => $env, 'host' => 'server.arcgis.local'],
                    ['tenant_id' => $primary->id, 'port' => 6443, 'protocol' => 'tcp', 'description' => 'ArcGIS Server']
                );
            }
        }

        // ─── OPA Demo Policy ────────────────────────────────────────

        OpaPolicy::firstOrCreate(
            ['slug' => 'prod-arcgis-dual-approval'],
            [
                'tenant_id' => $primary->id,
                'name' => 'Production ArcGIS Dual Approval',
                'category' => 'deployment',
                'description' => 'Production deployments to ArcGIS MCP require both Security Approver and Platform Owner sign-off.',
                'rego_code' => $this->loadRegoFile('deployment.rego'),
                'package_path' => 'eamcp.deployment',
                'status' => 'active',
                'version' => 1,
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]
        );

        // ─── Audit Report Templates ─────────────────────────────────

        $templates = [
            ['slug' => 'asset-change-log', 'name' => 'Asset Change Log', 'category' => 'asset_change',
             'description' => 'All asset modifications (create/update/delete) in the period.',
             'filters' => ['event_type' => ['create', 'update', 'delete']],
             'columns' => ['id', 'event_type', 'subject_type', 'subject_id', 'action', 'user_id', 'created_at']],
            ['slug' => 'deployment-log', 'name' => 'Deployment Log', 'category' => 'deployment',
             'description' => 'All deployment promotions and rollbacks.',
             'filters' => ['subject_type' => 'deployment'],
             'columns' => ['id', 'action', 'subject_id', 'user_id', 'payload', 'created_at']],
            ['slug' => 'approval-log', 'name' => 'Approval Decision Log', 'category' => 'approval',
             'description' => 'All approval decisions (approved/rejected).',
             'filters' => ['event_type' => ['approve', 'reject']],
             'columns' => ['id', 'event_type', 'action', 'subject_type', 'subject_id', 'user_id', 'created_at']],
            ['slug' => 'high-risk-tool-calls', 'name' => 'High-Risk Tool Call Log', 'category' => 'tool_call',
             'description' => 'All tool calls at risk level L2 and above.',
             'filters' => ['event_type' => 'tool_call'],
             'columns' => ['id', 'action', 'payload', 'user_id', 'created_at']],
            ['slug' => 'template-io-log', 'name' => 'Template Import/Export Log', 'category' => 'template_io',
             'description' => 'All template imports and exports.',
             'filters' => ['event_type' => ['import', 'export']],
             'columns' => ['id', 'event_type', 'action', 'subject_type', 'subject_id', 'user_id', 'created_at']],
            ['slug' => 'security-policy-changes', 'name' => 'Security Policy Change Log', 'category' => 'security_policy',
             'description' => 'All OPA policy and RBAC role modifications.',
             'filters' => ['subject_type' => ['opa_policy', 'role', 'abac_policy']],
             'columns' => ['id', 'event_type', 'action', 'subject_type', 'subject_id', 'user_id', 'created_at']],
        ];

        foreach ($templates as $tpl) {
            AuditReportTemplate::firstOrCreate(['slug' => $tpl['slug']], array_merge($tpl, ['is_system' => true]));
        }

        // ─── Provider Budgets ───────────────────────────────────────

        ProviderBudget::firstOrCreate(
            ['tenant_id' => $primary->id, 'provider' => 'openai', 'period_start' => now()->startOfMonth()],
            [
                'model' => null,
                'monthly_limit_usd' => 500.00,
                'current_spend_usd' => 0,
                'period_end' => now()->endOfMonth(),
                'action_on_exceed' => 'warn',
                'is_active' => true,
            ]
        );
        ProviderBudget::firstOrCreate(
            ['tenant_id' => $primary->id, 'provider' => 'claude', 'period_start' => now()->startOfMonth()],
            [
                'model' => null,
                'monthly_limit_usd' => 300.00,
                'current_spend_usd' => 0,
                'period_end' => now()->endOfMonth(),
                'action_on_exceed' => 'reject',
                'is_active' => true,
            ]
        );
        ProviderBudget::firstOrCreate(
            ['tenant_id' => $primary->id, 'provider' => 'google_adk', 'period_start' => now()->startOfMonth()],
            [
                'model' => null,
                'monthly_limit_usd' => 200.00,
                'current_spend_usd' => 0,
                'period_end' => now()->endOfMonth(),
                'action_on_exceed' => 'warn',
                'is_active' => true,
            ]
        );
    }

    protected function loadRegoFile(string $filename): string
    {
        $paths = [
            base_path('../infra/opa/' . $filename),
            base_path('/../infra/opa/' . $filename),
            dirname(base_path()) . '/infra/opa/' . $filename,
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return file_get_contents($path);
            }
        }

        return "package eamcp.deployment\ndefault allow := false\nallow { input.environment == \"dev\" }\n";
    }
}
