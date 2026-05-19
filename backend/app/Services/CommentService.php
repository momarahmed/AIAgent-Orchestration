<?php

namespace App\Services;

use App\Models\AssetComment;
use App\Models\User;
use App\Support\Audit;

/**
 * Comments / Collaboration Service (UX-009 — Phase 4).
 *
 * Threaded comments on agents, MCP servers, workflows, templates,
 * runs, and prompts. Mentions (@user) trigger optional email / Teams /
 * Slack notifications via the Phase 3 Email MCP + Activepieces.
 */
class CommentService
{
    public function create(int $tenantId, string $assetType, int $assetId, int $userId, string $body, ?int $parentId = null): AssetComment
    {
        $mentions = $this->extractMentions($body, $tenantId);

        $comment = AssetComment::create([
            'tenant_id'  => $tenantId,
            'asset_type' => $assetType,
            'asset_id'   => $assetId,
            'user_id'    => $userId,
            'parent_id'  => $parentId,
            'body'       => $body,
            'mentions'   => $mentions,
        ]);

        if ($mentions) {
            $this->notifyMentions($comment, $mentions);
        }

        Audit::record('collaboration', 'comment_created', $assetType, $assetId, [
            'comment_id' => $comment->id,
            'mentions'   => $mentions,
        ], tenantId: $tenantId);

        return $comment->fresh();
    }

    public function resolve(AssetComment $comment, int $userId): AssetComment
    {
        $comment->update(['resolved' => true, 'resolved_at' => now()]);
        Audit::record('collaboration', 'comment_resolved', $comment->asset_type, $comment->asset_id, [
            'comment_id' => $comment->id, 'resolved_by' => $userId,
        ], tenantId: $comment->tenant_id);
        return $comment;
    }

    public function list(string $assetType, int $assetId): \Illuminate\Database\Eloquent\Collection
    {
        return AssetComment::with(['user:id,name,email', 'replies.user:id,name,email'])
            ->where('asset_type', $assetType)
            ->where('asset_id', $assetId)
            ->whereNull('parent_id')
            ->orderByDesc('created_at')
            ->get();
    }

    protected function extractMentions(string $body, int $tenantId): array
    {
        if (! preg_match_all('/@([a-zA-Z0-9_.+-]+)/', $body, $m)) return [];
        $handles = array_unique($m[1]);
        if (! $handles) return [];

        $users = User::whereIn('email', $handles)
            ->orWhereIn('name', $handles)
            ->limit(50)
            ->get(['id', 'name', 'email']);

        return $users->pluck('id')->toArray();
    }

    protected function notifyMentions(AssetComment $comment, array $userIds): void
    {
        try {
            // Phase 3 Email MCP + Activepieces will pick this event up.
            app(EventBus::class)->publish('agent.events', 'comment.mention', [
                'comment_id' => $comment->id,
                'asset_type' => $comment->asset_type,
                'asset_id'   => $comment->asset_id,
                'mentioned'  => $userIds,
                'tenant_id'  => $comment->tenant_id,
            ]);
        } catch (\Throwable) {
            // best-effort
        }
    }
}
