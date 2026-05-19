<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentVersion;
use App\Models\McpServer;
use App\Models\Project;
use App\Models\Role;
use App\Models\Template;
use App\Models\Tenant;
use App\Models\Tool;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Admin', 'permissions' => ['*']]);
        $builderRole = Role::firstOrCreate(['name' => 'builder'], ['label' => 'Builder', 'permissions' => ['agents', 'mcp', 'workflows', 'runs']]);
        $viewerRole = Role::firstOrCreate(['name' => 'viewer'], ['label' => 'Viewer', 'permissions' => ['read']]);

        // ---- Demo users ----
        $admin = User::firstOrCreate(
            ['email' => 'admin@enterprise-ai-mcp.local'],
            ['name' => 'Platform Admin', 'password' => Hash::make('Admin@12345')]
        );
        $builder = User::firstOrCreate(
            ['email' => 'builder@enterprise-ai-mcp.local'],
            ['name' => 'Agent Builder', 'password' => Hash::make('Builder@12345')]
        );
        $viewer = User::firstOrCreate(
            ['email' => 'viewer@enterprise-ai-mcp.local'],
            ['name' => 'Business Viewer', 'password' => Hash::make('Viewer@12345')]
        );

        // ---- Tenants ----
        $tenants = [
            ['slug' => 'esri-saudi',   'name' => 'ESRI Saudi Enterprise',     'environment' => 'prod'],
            ['slug' => 'smart-city',   'name' => 'Smart City Innovation',     'environment' => 'staging'],
            ['slug' => 'platform-lab', 'name' => 'Platform Engineering Lab',  'environment' => 'dev'],
            ['slug' => 'demo',         'name' => 'Demo Workspace',            'environment' => 'dev'],
        ];

        foreach ($tenants as $t) {
            $tenant = Tenant::firstOrCreate(['slug' => $t['slug']], $t);
            $admin->tenants()->syncWithoutDetaching([$tenant->id => ['role_id' => $adminRole->id]]);
            $builder->tenants()->syncWithoutDetaching([$tenant->id => ['role_id' => $builderRole->id]]);
            $viewer->tenants()->syncWithoutDetaching([$tenant->id => ['role_id' => $viewerRole->id]]);
        }

        $primary = Tenant::where('slug', 'esri-saudi')->first();

        $project = Project::firstOrCreate(
            ['tenant_id' => $primary->id, 'slug' => 'gis-ops'],
            ['name' => 'GIS Operations', 'description' => 'Geospatial intelligence + automation workspace.']
        );

        // ---- MCP Server ----
        $mcp = McpServer::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'arcgis-mcp'],
            [
                'tenant_id' => $primary->id,
                'name' => 'ArcGIS MCP Server',
                'description' => 'Reference ArcGIS MCP server (stub — real adapter ships Phase 3).',
                'transport' => 'http',
                'runtime' => 'python',
                'endpoint' => 'http://127.0.0.1:9100/mcp',
                'auth_method' => 'api_key',
                'status' => 'draft',
                'health' => 'unknown',
            ]
        );

        Tool::firstOrCreate(
            ['mcp_server_id' => $mcp->id, 'name' => 'find_features'],
            [
                'description' => 'Search ArcGIS feature layers by attribute or geometry.',
                'risk_level' => 'L1',
                'input_schema' => ['type' => 'object', 'properties' => ['layer' => ['type' => 'string'], 'where' => ['type' => 'string']]],
                'output_schema' => ['type' => 'object'],
            ]
        );
        Tool::firstOrCreate(
            ['mcp_server_id' => $mcp->id, 'name' => 'create_feature'],
            [
                'description' => 'Insert a new feature into a layer (write-back).',
                'risk_level' => 'L3',
                'input_schema' => ['type' => 'object'],
                'output_schema' => ['type' => 'object'],
            ]
        );

        // ---- Agent ----
        $agent = Agent::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'gis-health'],
            [
                'tenant_id' => $primary->id,
                'name' => 'GIS Health Agent',
                'description' => 'Diagnoses ArcGIS layer/service health and reports issues.',
                'status' => 'draft',
                'risk_level' => 'L2',
            ]
        );
        $agentVersion = AgentVersion::firstOrCreate(
            ['agent_id' => $agent->id, 'version' => 1],
            [
                'role' => 'Geospatial Operations Specialist',
                'system_instructions' => 'You are a GIS health agent. Diagnose ArcGIS service status, surface anomalies, and recommend fixes. Be concise, factual, and cite the tool calls you used.',
                'model_config' => ['provider' => 'openai', 'model' => 'gpt-4o-mini', 'temperature' => 0.2, 'fallback_model' => 'gpt-4o'],
                'allowed_mcp_servers' => [$mcp->id],
                'allowed_tools' => Tool::where('mcp_server_id', $mcp->id)->pluck('id')->toArray(),
                'memory_scope' => 'project',
            ]
        );
        $agent->update(['current_version_id' => $agentVersion->id]);

        // ---- Workflow (Trigger -> Agent -> MCP Tool) ----
        $workflow = Workflow::firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'hello-world'],
            [
                'tenant_id' => $primary->id,
                'name' => 'Hello World — Anchor Scenario',
                'description' => 'Trigger → GIS Health Agent → ArcGIS find_features tool. Proves Phase 1 end-to-end pipe.',
                'trigger_type' => 'manual',
                'status' => 'draft',
            ]
        );
        $tool = Tool::where('mcp_server_id', $mcp->id)->where('name', 'find_features')->first();
        $graph = [
            'nodes' => [
                ['id' => 't1', 'type' => 'trigger',  'position' => ['x' => 80,  'y' => 200], 'data' => ['label' => 'Manual']],
                ['id' => 'a1', 'type' => 'agent',    'position' => ['x' => 320, 'y' => 200], 'data' => ['label' => 'GIS Health', 'agent_id' => $agent->id, 'prompt' => 'Diagnose the ArcGIS Roads layer. Use find_features to confirm coverage.']],
                ['id' => 'm1', 'type' => 'mcp_tool', 'position' => ['x' => 600, 'y' => 200], 'data' => ['label' => 'find_features', 'tool_id' => $tool?->id, 'inputs' => ['layer' => 'roads', 'where' => '1=1']]],
            ],
            'edges' => [
                ['id' => 'e1', 'source' => 't1', 'target' => 'a1'],
                ['id' => 'e2', 'source' => 'a1', 'target' => 'm1'],
            ],
        ];
        $wfVersion = WorkflowVersion::firstOrCreate(
            ['workflow_id' => $workflow->id, 'version' => 1],
            ['graph_json' => $graph, 'variables' => []]
        );
        $workflow->update(['current_version_id' => $wfVersion->id]);

        Template::firstOrCreate(
            ['slug' => 'hello-world-template'],
            [
                'tenant_id' => $primary->id,
                'name' => 'Hello World Workflow',
                'asset_type' => 'workflow',
                'description' => 'Anchor scenario template — Trigger → Agent → MCP Tool.',
                'payload' => ['workflow_slug' => 'hello-world'],
            ]
        );
    }
}
