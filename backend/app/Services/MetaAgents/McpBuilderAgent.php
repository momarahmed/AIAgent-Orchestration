<?php

namespace App\Services\MetaAgents;

use App\Models\CodegenJob;
use App\Models\McpServer;
use App\Models\McpServerVersion;
use App\Models\MetaAgentAction;
use App\Models\MetaAgentRun;
use App\Models\Tool;
use App\Services\OpenHandsClient;
use App\Support\Audit;
use Illuminate\Support\Str;

/**
 * MCP Builder Agent (PRD §12.2 — Phase 4).
 *
 * Uses OpenHandsClient (Phase 2/3) to scaffold an MCP server, runs the
 * Phase 3 security scanners, and registers the server + tools in the
 * platform. The generated code lives in `infra/openhands/<slug>/` and a
 * stub Dockerfile is shipped so DevOps Deployment can build it.
 */
class McpBuilderAgent extends BaseMetaAgent
{
    public function __construct(protected OpenHandsClient $openhands) {}

    public function kind(): string { return 'mcp_builder'; }
    public function description(): string { return 'Scaffolds and registers MCP servers using OpenHands SDK.'; }

    public function plan(MetaAgentRun $run): array
    {
        $specs = $run->plan['mcp_servers'] ?? [];
        $steps = [];
        foreach ($specs as $spec) {
            $steps[] = ['action' => 'generate', 'subject_type' => 'mcp_server', 'input' => $spec];
        }
        $steps[] = ['action' => 'scan', 'subject_type' => 'security', 'input' => ['scope' => 'mcp_servers']];
        return $steps;
    }

    public function executeStep(MetaAgentRun $run, MetaAgentAction $action): array
    {
        if ($action->action !== 'generate' || $action->subject_type !== 'mcp_server') {
            return ['skipped' => true];
        }
        $spec = $action->input ?? [];
        $name = (string) ($spec['name'] ?? 'Generated MCP Server');
        $slug = Str::slug($name) . '-' . Str::random(4);

        $job = CodegenJob::create([
            'tenant_id'   => $run->tenant_id,
            'project_id'  => $run->project_id,
            'kind'        => 'mcp_server',
            'prompt'      => $spec['prompt'] ?? "Build an MCP server named {$name}",
            'inputs'      => $spec,
            'status'      => 'queued',
            'triggered_by'=> $run->user_id,
        ]);
        try {
            $job = $this->openhands->generateMcpServer($job);
        } catch (\Throwable $e) {
            $job->update(['status' => 'failed', 'outputs' => ['error' => $e->getMessage()]]);
        }
        $codegen = ['codegen_job_id' => $job->id, 'status' => $job->status, 'outputs' => $job->outputs];

        $server = McpServer::create([
            'tenant_id'  => $run->tenant_id,
            'project_id' => $run->project_id,
            'name'       => $name,
            'slug'       => $slug,
            'transport'  => $spec['transport'] ?? 'stdio',
            'runtime'    => $spec['runtime'] ?? 'python',
            'endpoint'   => $spec['endpoint'] ?? null,
            'status'     => 'draft',
            'health'     => 'unknown',
            'sandbox_config' => $spec['sandbox_config'] ?? null,
            'requires_sandbox' => $spec['requires_sandbox'] ?? false,
        ]);

        McpServerVersion::create([
            'mcp_server_id' => $server->id,
            'version' => 1,
            'config' => array_merge($spec, ['codegen' => $codegen]),
        ]);

        foreach ($spec['tools'] ?? [] as $t) {
            Tool::create([
                'mcp_server_id' => $server->id,
                'name'          => $t['name'] ?? 'unnamed_tool',
                'description'   => $t['description'] ?? null,
                'risk_level'    => $t['risk_level'] ?? 'L1',
                'is_enabled'    => true,
                'input_schema'  => $t['input_schema'] ?? null,
                'output_schema' => $t['output_schema'] ?? null,
            ]);
        }

        $artifacts = $run->artifacts ?? [];
        $artifacts['mcp_servers'][] = $server->id;
        $run->update(['artifacts' => $artifacts]);

        Audit::record('meta_agent', 'mcp_server_created', 'mcp_server', $server->id, [
            'meta_run' => $run->id, 'codegen' => $codegen,
        ], tenantId: $run->tenant_id);

        return ['mcp_server_id' => $server->id, 'codegen' => $codegen];
    }
}
