<?php

namespace App\Contracts;

use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\Models\WorkflowVersion;

/**
 * WorkflowEngine — abstract contract that hides the underlying engine
 * (Phase 1: in-process, Phase 2: durable queue, future: Temporal).
 */
interface WorkflowEngine
{
    public function run(Workflow $workflow, WorkflowVersion $version, array $input, ?int $userId, string $environment = 'dev'): WorkflowRun;

    public function resume(WorkflowRun $run, array $resumeInput = []): WorkflowRun;

    public function cancel(WorkflowRun $run, ?string $reason = null): WorkflowRun;

    public function replay(WorkflowRun $run, ?string $fromNodeId = null, array $overrideInputs = []): WorkflowRun;

    public function debugNode(WorkflowRun $run, string $nodeId, array $overrideInput = []): array;
}
