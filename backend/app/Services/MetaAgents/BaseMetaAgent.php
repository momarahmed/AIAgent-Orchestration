<?php

namespace App\Services\MetaAgents;

use App\Models\MetaAgentAction;
use App\Models\MetaAgentRun;

/**
 * Base contract for all Phase 4 meta-agents.
 *
 * Each meta-agent inherits from BaseMetaAgent and implements `plan()` +
 * `executeStep()`. The orchestrator (MetaAgentOrchestrator) instantiates
 * them, walks their plan, persists every action, requests approvals for
 * risky steps and writes the resulting artifacts back to the run row.
 */
abstract class BaseMetaAgent
{
    abstract public function kind(): string;
    abstract public function description(): string;

    /**
     * Decompose the natural-language prompt into a sequence of steps.
     *
     * @return array<int, array{action:string,subject_type?:string,subject_id?:int,input?:array,approval_required?:bool,reasoning?:string}>
     */
    abstract public function plan(MetaAgentRun $run): array;

    /**
     * Execute a single step. Returns the action output (persisted on
     * the MetaAgentAction row by the orchestrator).
     */
    abstract public function executeStep(MetaAgentRun $run, MetaAgentAction $action): array;
}
