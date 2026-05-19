<?php

namespace App\Services;

use App\Models\Approval;
use App\Models\WorkflowRun;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Builder;

/**
 * Approval Queue — pause/resume gate for risky tool calls and deployments.
 */
class ApprovalService
{
    public function request(array $attrs): Approval
    {
        $approval = Approval::create(array_merge([
            'status' => 'pending',
            'expires_at' => now()->addDays((int) env('APPROVAL_TIMEOUT_DAYS', 7)),
        ], $attrs));

        Audit::record('approval', 'approval.requested', $approval->subject_type, $approval->subject_id, $approval->only(['risk_level', 'reason']));
        return $approval;
    }

    public function approve(Approval $approval, ?int $userId, ?string $comment = null): Approval
    {
        $approval->update([
            'status' => 'approved',
            'decided_by' => $userId,
            'decided_at' => now(),
            'decision_comment' => $comment,
        ]);
        Audit::record('approval', 'approval.approved', $approval->subject_type, $approval->subject_id, ['approval_id' => $approval->id]);
        $this->maybeResume($approval);
        return $approval->fresh();
    }

    public function reject(Approval $approval, ?int $userId, ?string $comment = null): Approval
    {
        $approval->update([
            'status' => 'rejected',
            'decided_by' => $userId,
            'decided_at' => now(),
            'decision_comment' => $comment,
        ]);
        Audit::record('approval', 'approval.rejected', $approval->subject_type, $approval->subject_id, ['approval_id' => $approval->id]);
        if ($approval->subject_type === 'workflow_run') {
            $run = WorkflowRun::find($approval->subject_id);
            $run?->update(['status' => 'cancelled', 'error' => 'Approval rejected', 'completed_at' => now()]);
        }
        return $approval->fresh();
    }

    public function pendingFor(string $subjectType, int $subjectId): Builder
    {
        return Approval::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('status', 'pending');
    }

    protected function maybeResume(Approval $approval): void
    {
        if ($approval->subject_type !== 'workflow_run') {
            return;
        }
        $run = WorkflowRun::find($approval->subject_id);
        if ($run && $run->status === 'awaiting_approval') {
            app(\App\Services\DurableWorkflowEngine::class)->resume($run);
        }
    }
}
