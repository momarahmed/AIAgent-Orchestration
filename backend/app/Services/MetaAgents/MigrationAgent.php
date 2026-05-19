<?php

namespace App\Services\MetaAgents;

use App\Models\MetaAgentAction;
use App\Models\MetaAgentRun;
use App\Services\MigrationService;

/**
 * Migration Agent (PRD §12.2 — Phase 4).
 *
 * Front-end for MigrationService — parses n8n / Flowise / Dify / generic
 * JSON / YAML workflow files and produces best-effort native workflow
 * specs with a confidence score and a human-review checklist.
 */
class MigrationAgent extends BaseMetaAgent
{
    public function __construct(protected MigrationService $migrator) {}

    public function kind(): string { return 'migration'; }
    public function description(): string { return 'Imports external workflow formats (n8n / Flowise / Dify / JSON / YAML).'; }

    public function plan(MetaAgentRun $run): array
    {
        return [['action' => 'generate', 'subject_type' => 'migration', 'input' => $run->plan ?? []]];
    }

    public function executeStep(MetaAgentRun $run, MetaAgentAction $action): array
    {
        $format = (string) ($action->input['source_format'] ?? 'json');
        $source = $action->input['source'] ?? [];
        $import = $this->migrator->import($run->tenant_id, $run->project_id, $format, $source, $run->user_id);

        $artifacts = $run->artifacts ?? [];
        $artifacts['migration_imports'][] = $import->id;
        if ($import->workflow_id) $artifacts['workflows'][] = $import->workflow_id;
        $run->update(['artifacts' => $artifacts]);

        return [
            'migration_import_id' => $import->id,
            'workflow_id'         => $import->workflow_id,
            'confidence'          => $import->confidence,
            'checklist'           => $import->review_checklist,
        ];
    }
}
