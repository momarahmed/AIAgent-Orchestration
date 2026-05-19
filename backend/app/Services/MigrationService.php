<?php

namespace App\Services;

use App\Models\MigrationImport;
use App\Models\Workflow;
use App\Models\WorkflowVersion;
use App\Support\Audit;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

/**
 * Migration Service (PRD §12.2 / §24.5 — Phase 4).
 *
 * Parsers for n8n, Flowise, Dify, generic JSON, and generic YAML
 * workflow formats. Each parser returns a normalized React-Flow-style
 * graph that can be persisted as a native workflow and edited in the
 * platform's Workflow Builder.
 *
 * Confidence scores are emitted alongside a human-review checklist so
 * the imported workflow is never blindly activated — it lands as
 * `status=draft` and the user has to review before promoting.
 */
class MigrationService
{
    public const SUPPORTED = ['n8n', 'flowise', 'dify', 'json', 'yaml'];

    public function import(int $tenantId, ?int $projectId, string $format, $source, ?int $userId = null, ?string $filename = null): MigrationImport
    {
        $format = strtolower($format);
        $raw    = is_array($source) ? $source : $this->decode($source, $format);

        [$graph, $confidence, $checklist] = match ($format) {
            'n8n'     => $this->parseN8n($raw),
            'flowise' => $this->parseFlowise($raw),
            'dify'    => $this->parseDify($raw),
            'json'    => $this->parseGenericJson($raw),
            'yaml'    => $this->parseGenericYaml($raw),
            default   => [['nodes' => [], 'edges' => []], 0.0, ['unsupported_format']],
        };

        $import = MigrationImport::create([
            'tenant_id'    => $tenantId,
            'project_id'   => $projectId,
            'source_format'=> $format,
            'filename'     => $filename,
            'raw_source'   => $raw,
            'translated'   => $graph,
            'confidence'   => $confidence,
            'review_checklist' => $checklist,
            'status'       => 'parsed',
            'imported_by'  => $userId,
        ]);

        if ($confidence >= 0.4 && ! empty($graph['nodes']) && $projectId !== null) {
            $wf = Workflow::create([
                'tenant_id'   => $tenantId,
                'project_id'  => $projectId,
                'name'        => "Imported ({$format}) " . ($filename ?: now()->toDateString()),
                'slug'        => "import-{$format}-" . Str::random(6),
                'status'      => 'draft',
                'risk_level'  => 'L1',
                'trigger_type'=> 'manual',
            ]);
            $v = WorkflowVersion::create([
                'workflow_id' => $wf->id,
                'version'     => 1,
                'graph_json'  => $graph,
                'metadata'    => [
                    'imported_from' => $format,
                    'confidence'    => $confidence,
                    'review_required' => true,
                ],
            ]);
            $wf->update(['current_version_id' => $v->id]);
            $import->update(['status' => 'imported', 'workflow_id' => $wf->id]);
        }

        Audit::record('migration', 'workflow_imported', 'migration_import', $import->id, [
            'format' => $format,
            'confidence' => $confidence,
        ], tenantId: $tenantId);

        return $import->fresh();
    }

    // ─── Parsers ─────────────────────────────────────────────────────

    /**
     * n8n workflow JSON → React Flow graph. Maps `n8n-nodes-base.*` to
     * generic platform node types and warns on unknown integrations.
     */
    protected function parseN8n(array $raw): array
    {
        $nodes = []; $edges = []; $unknown = 0;
        $checklist = [];

        foreach ($raw['nodes'] ?? [] as $n) {
            $type = $this->mapN8nType($n['type'] ?? 'unknown');
            if ($type === 'unknown') {
                $unknown++;
                $checklist[] = "Review unknown n8n node type: " . ($n['type'] ?? '?');
            }
            $nodes[] = [
                'id'   => $n['id'] ?? $n['name'] ?? Str::random(6),
                'type' => $type,
                'data' => [
                    'label'    => $n['name'] ?? 'Untitled',
                    'original' => $n['type'] ?? null,
                    'config'   => $n['parameters'] ?? [],
                ],
                'position' => $n['position'] ?? null,
            ];
        }

        // n8n `connections` format → React Flow edges
        foreach ($raw['connections'] ?? [] as $from => $outs) {
            foreach ($outs['main'] ?? [] as $branchIdx => $branch) {
                foreach ($branch as $conn) {
                    $edges[] = [
                        'id'     => "e_{$from}_" . ($conn['node'] ?? ''),
                        'source' => $from,
                        'target' => $conn['node'] ?? '',
                        'data'   => ['branch' => $branchIdx],
                    ];
                }
            }
        }

        $confidence = $this->confidence(count($nodes), $unknown);
        return [['nodes' => $nodes, 'edges' => $edges], $confidence, $checklist];
    }

    /**
     * Flowise flow JSON → React Flow graph. Flowise already uses
     * `nodes`/`edges` so it's a near-1:1 mapping with field renaming.
     */
    protected function parseFlowise(array $raw): array
    {
        $nodes = [];
        $unknown = 0;
        $checklist = [];

        foreach ($raw['nodes'] ?? [] as $n) {
            $type = $this->mapFlowiseType($n['data']['name'] ?? $n['type'] ?? 'unknown');
            if ($type === 'unknown') {
                $unknown++;
                $checklist[] = 'Review unknown Flowise node: ' . ($n['data']['name'] ?? '?');
            }
            $nodes[] = [
                'id'   => $n['id'] ?? Str::random(6),
                'type' => $type,
                'data' => [
                    'label'    => $n['data']['label'] ?? ($n['data']['name'] ?? 'Untitled'),
                    'original' => $n['data']['name'] ?? null,
                    'config'   => $n['data']['inputs'] ?? [],
                ],
                'position' => $n['position'] ?? null,
            ];
        }

        $edges = [];
        foreach ($raw['edges'] ?? [] as $e) {
            $edges[] = [
                'id'     => $e['id'] ?? Str::random(6),
                'source' => $e['source'] ?? '',
                'target' => $e['target'] ?? '',
            ];
        }

        return [['nodes' => $nodes, 'edges' => $edges], $this->confidence(count($nodes), $unknown), $checklist];
    }

    /**
     * Dify app/workflow YAML → React Flow graph. Dify uses graph nodes
     * with `type` like start/end/llm/tool/code/template-transform.
     */
    protected function parseDify(array $raw): array
    {
        $workflow = $raw['workflow'] ?? $raw;
        $graph = $workflow['graph'] ?? $workflow;
        $nodes = []; $edges = []; $unknown = 0;
        $checklist = [];

        foreach ($graph['nodes'] ?? [] as $n) {
            $type = $this->mapDifyType($n['data']['type'] ?? $n['type'] ?? 'unknown');
            if ($type === 'unknown') {
                $unknown++;
                $checklist[] = 'Review unknown Dify node: ' . ($n['data']['type'] ?? '?');
            }
            $nodes[] = [
                'id'   => $n['id'] ?? Str::random(6),
                'type' => $type,
                'data' => [
                    'label'    => $n['data']['title'] ?? 'Untitled',
                    'original' => $n['data']['type'] ?? null,
                    'config'   => $n['data'] ?? [],
                ],
                'position' => $n['position'] ?? null,
            ];
        }
        foreach ($graph['edges'] ?? [] as $e) {
            $edges[] = [
                'id'     => $e['id'] ?? Str::random(6),
                'source' => $e['source'] ?? '',
                'target' => $e['target'] ?? '',
            ];
        }
        return [['nodes' => $nodes, 'edges' => $edges], $this->confidence(count($nodes), $unknown), $checklist];
    }

    protected function parseGenericJson(array $raw): array
    {
        if (isset($raw['nodes']) && isset($raw['edges'])) {
            return [$raw, 0.7, []];
        }
        return [['nodes' => [], 'edges' => []], 0.1, ['Unrecognised JSON structure']];
    }

    protected function parseGenericYaml(array $raw): array
    {
        return $this->parseGenericJson($raw);
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    protected function decode($source, string $format): array
    {
        if (is_array($source)) return $source;
        $s = (string) $source;
        if ($format === 'yaml' || $format === 'dify') {
            try { return Yaml::parse($s) ?? []; } catch (\Throwable) { /* fall through */ }
        }
        $j = json_decode($s, true);
        return is_array($j) ? $j : [];
    }

    protected function confidence(int $nodeCount, int $unknownCount): float
    {
        if ($nodeCount === 0) return 0.0;
        $score = 1.0 - ($unknownCount / max($nodeCount, 1));
        return round(max(0.0, min(1.0, $score)) * 100, 2);
    }

    protected function mapN8nType(string $t): string
    {
        return match (true) {
            str_contains($t, 'webhook')   => 'trigger',
            str_contains($t, 'cron')      => 'schedule',
            str_contains($t, 'function')  => 'transform',
            str_contains($t, 'if')        => 'decision',
            str_contains($t, 'http')      => 'http_call',
            str_contains($t, 'set')       => 'transform',
            str_contains($t, 'openai')    => 'agent',
            default => 'unknown',
        };
    }

    protected function mapFlowiseType(string $t): string
    {
        $t = strtolower($t);
        return match (true) {
            str_contains($t, 'llm') || str_contains($t, 'chatmodel') => 'agent',
            str_contains($t, 'tool')      => 'tool',
            str_contains($t, 'agent')     => 'agent',
            str_contains($t, 'memory')    => 'memory',
            str_contains($t, 'retriever') => 'rag',
            str_contains($t, 'chain')     => 'workflow_node',
            default => 'unknown',
        };
    }

    protected function mapDifyType(string $t): string
    {
        return match ($t) {
            'start'                => 'trigger',
            'end'                  => 'output',
            'llm', 'agent'         => 'agent',
            'tool'                 => 'tool',
            'code'                 => 'transform',
            'template-transform'   => 'transform',
            'knowledge-retrieval'  => 'rag',
            'if-else'              => 'decision',
            'http-request'         => 'http_call',
            default                => 'unknown',
        };
    }
}
