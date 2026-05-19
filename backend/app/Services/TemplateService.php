<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\McpServer;
use App\Models\Template;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use App\Models\AgentVersion;
use App\Support\Audit;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Template Manager — create, export (JSON/YAML/ZIP), import (manifest+secret-scan),
 * instantiate from template. Per PRD §16.
 */
class TemplateService
{
    public function fromAsset(string $type, int $assetId, array $meta = []): Template
    {
        [$payload, $params] = match ($type) {
            'agent'      => $this->extractAgent($assetId),
            'mcp_server' => $this->extractMcp($assetId),
            'workflow'   => $this->extractWorkflow($assetId),
            default      => throw new \InvalidArgumentException("Unknown asset_type {$type}"),
        };

        $name = $meta['name'] ?? ($payload['name'] ?? 'Template');
        $template = Template::create([
            'tenant_id' => $meta['tenant_id'] ?? null,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::lower(Str::random(4)),
            'asset_type' => $type,
            'description' => $meta['description'] ?? null,
            'payload' => $payload,
            'parameters_schema' => $params,
            'visibility' => $meta['visibility'] ?? 'private',
        ]);

        Audit::record('create', 'template.create', 'template', $template->id, ['from' => "{$type}:{$assetId}"]);
        return $template;
    }

    public function exportZip(Template $template): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'eamcp-tpl');
        $zipPath = $tmp . '.zip';
        @rename($tmp, $zipPath);
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $manifest = [
            'manifest_version' => '1.0',
            'name' => $template->name,
            'slug' => $template->slug,
            'asset_type' => $template->asset_type,
            'description' => $template->description,
            'parameters' => $template->parameters_schema ?? [],
            'created_at' => optional($template->created_at)->toIso8601String(),
        ];
        $zip->addFromString('manifest.yaml', $this->toYaml($manifest));
        $zip->addFromString('payload.json', json_encode($template->payload, JSON_PRETTY_PRINT));
        $zip->addFromString('README.md', "# {$template->name}\n\n{$template->description}\n");

        $zip->close();
        return ['path' => $zipPath, 'filename' => "{$template->slug}.eamcp-template.zip"];
    }

    public function exportJson(Template $template): array
    {
        return [
            'manifest_version' => '1.0',
            'name' => $template->name,
            'slug' => $template->slug,
            'asset_type' => $template->asset_type,
            'description' => $template->description,
            'parameters_schema' => $template->parameters_schema,
            'payload' => $template->payload,
        ];
    }

    public function importJson(array $payload, ?int $tenantId, ?int $userId): array
    {
        $issues = $this->validateManifest($payload);
        $secretIssues = $this->scanForSecrets($payload['payload'] ?? []);
        if ($secretIssues) {
            $issues = array_merge($issues, $secretIssues);
        }

        $template = Template::create([
            'tenant_id' => $tenantId,
            'name' => $payload['name'] ?? 'Imported Template',
            'slug' => Str::slug($payload['name'] ?? 'imported') . '-' . Str::lower(Str::random(4)),
            'asset_type' => $payload['asset_type'] ?? 'workflow',
            'description' => $payload['description'] ?? null,
            'payload' => $payload['payload'] ?? [],
            'parameters_schema' => $payload['parameters_schema'] ?? [],
            'visibility' => 'private',
        ]);

        Audit::record('create', 'template.import', 'template', $template->id, ['issues' => $issues]);
        return ['template' => $template, 'issues' => $issues];
    }

    public function importZip(string $zipPath, ?int $tenantId, ?int $userId): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['issues' => ['Could not open ZIP'], 'template' => null];
        }
        $manifest = $zip->getFromName('manifest.yaml');
        $payload = $zip->getFromName('payload.json');
        $zip->close();

        if (! $manifest || ! $payload) {
            return ['issues' => ['Missing manifest.yaml or payload.json'], 'template' => null];
        }

        $manifestData = $this->fromYaml($manifest);
        $payloadData = json_decode($payload, true) ?: [];

        return $this->importJson(array_merge($manifestData, ['payload' => $payloadData]), $tenantId, $userId);
    }

    /**
     * Instantiate concrete asset from a template, parameter values applied.
     */
    public function instantiate(Template $template, array $params, int $tenantId, int $projectId, ?int $userId): array
    {
        $payload = $this->applyParameters($template->payload ?? [], $params);

        return match ($template->asset_type) {
            'agent'      => $this->createAgentFromPayload($payload, $tenantId, $projectId, $userId),
            'mcp_server' => $this->createMcpFromPayload($payload, $tenantId, $projectId, $userId),
            'workflow'   => $this->createWorkflowFromPayload($payload, $tenantId, $projectId, $userId),
            default      => ['error' => 'Unsupported asset_type'],
        };
    }

    protected function validateManifest(array $payload): array
    {
        $issues = [];
        if (empty($payload['name'])) $issues[] = 'manifest: missing name';
        if (empty($payload['asset_type'])) $issues[] = 'manifest: missing asset_type';
        if (! in_array($payload['asset_type'] ?? '', ['agent', 'mcp_server', 'workflow'], true)) {
            $issues[] = 'manifest: invalid asset_type';
        }
        return $issues;
    }

    protected function scanForSecrets($payload): array
    {
        $json = is_array($payload) ? json_encode($payload) : (string) $payload;
        $issues = [];
        $patterns = [
            '/sk-[A-Za-z0-9]{20,}/' => 'OpenAI-style API key',
            '/aws_secret_access_key\s*[:=]/i' => 'AWS secret access key',
            '/-----BEGIN [A-Z ]*PRIVATE KEY-----/' => 'PEM private key',
            '/eyJ[A-Za-z0-9_\-\.]{30,}/' => 'JWT-like token',
        ];
        foreach ($patterns as $regex => $label) {
            if (preg_match($regex, $json)) $issues[] = "secret-scan: {$label} detected";
        }
        return $issues;
    }

    protected function extractAgent(int $id): array
    {
        $agent = Agent::with('currentVersion')->findOrFail($id);
        $payload = [
            'name' => $agent->name,
            'description' => $agent->description,
            'risk_level' => $agent->risk_level,
            'max_risk_level_without_approval' => $agent->max_risk_level_without_approval,
            'version' => $agent->currentVersion?->only(['role', 'system_instructions', 'model_config', 'memory_scope']),
            // secrets stripped — only refs remain
        ];
        $params = [
            'name' => ['type' => 'string', 'required' => true, 'description' => 'Agent name'],
            'model' => ['type' => 'string', 'required' => false, 'description' => 'Override model'],
        ];
        return [$payload, $params];
    }

    protected function extractMcp(int $id): array
    {
        $mcp = McpServer::with('tools')->findOrFail($id);
        $payload = [
            'name' => $mcp->name,
            'transport' => $mcp->transport,
            'runtime' => $mcp->runtime,
            'auth_method' => $mcp->auth_method,
            'tools' => $mcp->tools->map(fn ($t) => $t->only(['name', 'description', 'risk_level', 'input_schema', 'output_schema']))->all(),
        ];
        $params = [
            'endpoint' => ['type' => 'string', 'required' => true, 'description' => 'MCP server endpoint URL'],
            'api_key_ref' => ['type' => 'string', 'required' => false, 'description' => 'Vault path for API key'],
        ];
        return [$payload, $params];
    }

    protected function extractWorkflow(int $id): array
    {
        $wf = Workflow::with('currentVersion')->findOrFail($id);
        $payload = [
            'name' => $wf->name,
            'description' => $wf->description,
            'trigger_type' => $wf->trigger_type,
            'graph_json' => $wf->currentVersion?->graph_json ?? ['nodes' => [], 'edges' => []],
            'variables' => $wf->currentVersion?->variables ?? [],
        ];
        $params = collect($wf->currentVersion?->variables ?? [])->mapWithKeys(fn ($v, $k) => [
            $k => ['type' => 'string', 'required' => false],
        ])->toArray();
        return [$payload, $params];
    }

    protected function applyParameters(array $payload, array $params): array
    {
        $json = json_encode($payload);
        foreach ($params as $key => $value) {
            $json = str_replace('{{' . $key . '}}', is_string($value) ? $value : json_encode($value), $json);
        }
        return json_decode($json, true) ?: $payload;
    }

    protected function createAgentFromPayload(array $payload, int $tenantId, int $projectId, ?int $userId): array
    {
        $agent = Agent::create([
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
            'name' => $payload['name'] ?? 'Templated Agent',
            'slug' => Str::slug($payload['name'] ?? 'agent') . '-' . Str::lower(Str::random(3)),
            'description' => $payload['description'] ?? null,
            'risk_level' => $payload['risk_level'] ?? 'L1',
            'max_risk_level_without_approval' => $payload['max_risk_level_without_approval'] ?? 'L1',
            'status' => 'draft',
        ]);
        $version = AgentVersion::create(array_merge([
            'agent_id' => $agent->id,
            'version' => 1,
            'created_by' => $userId,
        ], $payload['version'] ?? []));
        $agent->update(['current_version_id' => $version->id]);
        return ['type' => 'agent', 'id' => $agent->id];
    }

    protected function createMcpFromPayload(array $payload, int $tenantId, int $projectId, ?int $userId): array
    {
        $mcp = McpServer::create([
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
            'name' => $payload['name'] ?? 'Templated MCP',
            'slug' => Str::slug($payload['name'] ?? 'mcp') . '-' . Str::lower(Str::random(3)),
            'transport' => $payload['transport'] ?? 'http',
            'runtime' => $payload['runtime'] ?? 'python',
            'auth_method' => $payload['auth_method'] ?? 'none',
            'endpoint' => $payload['endpoint'] ?? null,
            'status' => 'draft',
        ]);
        foreach ($payload['tools'] ?? [] as $tool) {
            \App\Models\Tool::create([
                'mcp_server_id' => $mcp->id,
                'name' => $tool['name'] ?? 'tool',
                'description' => $tool['description'] ?? null,
                'risk_level' => $tool['risk_level'] ?? 'L1',
                'input_schema' => $tool['input_schema'] ?? null,
                'output_schema' => $tool['output_schema'] ?? null,
                'is_enabled' => true,
            ]);
        }
        return ['type' => 'mcp_server', 'id' => $mcp->id];
    }

    protected function createWorkflowFromPayload(array $payload, int $tenantId, int $projectId, ?int $userId): array
    {
        $wf = Workflow::create([
            'tenant_id' => $tenantId,
            'project_id' => $projectId,
            'name' => $payload['name'] ?? 'Templated Workflow',
            'slug' => Str::slug($payload['name'] ?? 'workflow') . '-' . Str::lower(Str::random(3)),
            'description' => $payload['description'] ?? null,
            'trigger_type' => $payload['trigger_type'] ?? 'manual',
            'status' => 'draft',
        ]);
        $version = WorkflowVersion::create([
            'workflow_id' => $wf->id,
            'version' => 1,
            'graph_json' => $payload['graph_json'] ?? ['nodes' => [], 'edges' => []],
            'variables' => $payload['variables'] ?? [],
            'created_by' => $userId,
        ]);
        $wf->update(['current_version_id' => $version->id]);
        return ['type' => 'workflow', 'id' => $wf->id];
    }

    protected function toYaml(array $data, int $level = 0): string
    {
        $out = '';
        $pad = str_repeat('  ', $level);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $out .= "{$pad}{$key}:\n" . $this->toYaml($value, $level + 1);
            } else {
                $val = is_string($value) ? '"' . addslashes($value) . '"' : json_encode($value);
                $out .= "{$pad}{$key}: {$val}\n";
            }
        }
        return $out;
    }

    protected function fromYaml(string $yaml): array
    {
        if (function_exists('yaml_parse')) {
            return yaml_parse($yaml) ?: [];
        }
        // Tolerant minimal parser for our manifest.yaml format
        $data = [];
        foreach (preg_split('/\r?\n/', $yaml) as $line) {
            if (! str_contains($line, ':')) continue;
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            if ($v === '') continue;
            $data[$k] = trim($v, "\"' ");
        }
        return $data;
    }
}
