<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentVersion;
use App\Models\RunReplay;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;
use App\Support\Audit;

/**
 * Replay Service (PRD §22 / OBS-006 — Phase 4).
 *
 * Re-runs a previous workflow run with optional version pinning:
 *   - agent versions
 *   - mcp server versions
 *   - prompt versions
 *   - model slug
 *
 * The replay produces a new WorkflowRun and a RunReplay link row that
 * the Run Detail UI can use to render a diff against the source run.
 */
class ReplayService
{
    public function __construct(protected DurableWorkflowEngine $engine) {}

    public function replay(WorkflowRun $source, array $pinnedVersions = [], ?int $userId = null, ?string $reason = null): RunReplay
    {
        $version = WorkflowVersion::find($pinnedVersions['workflow_version_id'] ?? $source->workflow_version_id);
        if (! $version) {
            $version = $source->workflow->currentVersion;
        }

        $link = RunReplay::create([
            'source_run_id'    => $source->id,
            'replayed_by'      => $userId,
            'pinned_versions'  => $pinnedVersions,
            'status'           => 'running',
            'reason'           => $reason,
        ]);

        try {
            $input = $source->input ?? [];
            $replayRun = $this->engine->run($source->workflow, $version, $input, $userId, $source->environment ?? 'dev');
            $link->update([
                'replay_run_id' => $replayRun->id,
                'status'        => 'completed',
                'diff'          => $this->summarizeDiff($source, $replayRun),
            ]);
            Audit::record('observability', 'run_replayed', 'workflow_run', $source->id, [
                'replay_run_id' => $replayRun->id,
                'pinned'        => $pinnedVersions,
            ], tenantId: $source->workflow->tenant_id);
        } catch (\Throwable $e) {
            $link->update(['status' => 'failed', 'diff' => ['error' => $e->getMessage()]]);
        }
        return $link->fresh();
    }

    protected function summarizeDiff(WorkflowRun $a, WorkflowRun $b): array
    {
        return [
            'status'      => ['source' => $a->status, 'replay' => $b->status],
            'duration_ms' => [
                'source'  => $a->started_at && $a->completed_at ? $a->started_at->diffInMilliseconds($a->completed_at) : null,
                'replay'  => $b->started_at && $b->completed_at ? $b->started_at->diffInMilliseconds($b->completed_at) : null,
            ],
            'task_count'  => ['source' => $a->tasks()->count(), 'replay' => $b->tasks()->count()],
        ];
    }
}
