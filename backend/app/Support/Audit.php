<?php

namespace App\Support;

use App\Models\AuditEvent;
use Illuminate\Http\Request;

/**
 * Tiny helper to record audit events. Phase 1 baseline; full export
 * + reporting lands in Phase 3.
 */
class Audit
{
    public static function record(
        string $eventType,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $payload = [],
        ?Request $request = null,
        ?int $tenantId = null,
        ?int $projectId = null,
    ): void {
        $request = $request ?: request();
        AuditEvent::create([
            'tenant_id'    => $tenantId,
            'project_id'   => $projectId,
            'user_id'      => optional($request->user())->id,
            'event_type'   => $eventType,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'action'       => $action,
            'payload'      => $payload,
            'ip_address'   => $request?->ip(),
            'user_agent'   => $request?->userAgent(),
        ]);
    }
}
