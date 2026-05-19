<?php

namespace App\Services;

use App\Models\AuditEvent;
use App\Models\AuditExport;
use App\Models\AuditReportTemplate;
use App\Support\Audit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Audit report generation, export (CSV/JSON/PDF), and tamper-evident hash chain.
 *
 * PRD Section 22.1: every create/update/delete/deploy/tool-call/approval/
 * import/export is auditable and exportable as evidence.
 */
class AuditReportService
{
    public function generateExport(
        int $tenantId,
        string $format,
        array $filters = [],
        ?int $templateId = null,
        ?int $requestedBy = null,
    ): AuditExport {
        $export = AuditExport::create([
            'tenant_id'               => $tenantId,
            'audit_report_template_id'=> $templateId,
            'format'                  => $format,
            'status'                  => 'generating',
            'filters'                 => $filters,
            'period_start'            => $filters['period_start'] ?? now()->subDays(30),
            'period_end'              => $filters['period_end'] ?? now(),
            'requested_by'            => $requestedBy,
        ]);

        try {
            $query = AuditEvent::where('tenant_id', $tenantId);

            if (! empty($filters['period_start'])) {
                $query->where('created_at', '>=', $filters['period_start']);
            }
            if (! empty($filters['period_end'])) {
                $query->where('created_at', '<=', $filters['period_end']);
            }
            if (! empty($filters['event_type'])) {
                $query->where('event_type', $filters['event_type']);
            }
            if (! empty($filters['subject_type'])) {
                $query->where('subject_type', $filters['subject_type']);
            }
            if (! empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            $events = $query->orderBy('created_at', 'desc')->get();

            $filename = "audit-exports/{$tenantId}/" . Str::uuid() . ".{$format}";
            $content  = match ($format) {
                'csv'  => $this->toCsv($events),
                'json' => $this->toJson($events),
                'pdf'  => $this->toPdf($events),
                default => $this->toJson($events),
            };

            Storage::put($filename, $content);

            $export->update([
                'status'       => 'completed',
                'file_path'    => $filename,
                'file_size'    => strlen($content),
                'record_count' => $events->count(),
                'completed_at' => now(),
            ]);

            Audit::record('export', 'audit_report_exported', 'audit_export', $export->id, [
                'format' => $format, 'record_count' => $events->count(),
            ], tenantId: $tenantId);
        } catch (\Throwable $e) {
            $export->update(['status' => 'failed']);
            throw $e;
        }

        return $export->fresh();
    }

    public function getTemplates(): \Illuminate\Database\Eloquent\Collection
    {
        return AuditReportTemplate::all();
    }

    /**
     * Build a tamper-evident hash for a new audit event (append-only chain).
     */
    public function computeHash(array $eventData, ?string $prevHash = null): string
    {
        $canonical = json_encode([
            'tenant_id'    => $eventData['tenant_id'] ?? null,
            'user_id'      => $eventData['user_id'] ?? null,
            'event_type'   => $eventData['event_type'] ?? '',
            'action'       => $eventData['action'] ?? '',
            'subject_type' => $eventData['subject_type'] ?? null,
            'subject_id'   => $eventData['subject_id'] ?? null,
            'created_at'   => $eventData['created_at'] ?? now()->toIso8601String(),
            'prev_hash'    => $prevHash,
        ], JSON_UNESCAPED_SLASHES);

        return hash('sha256', $canonical);
    }

    protected function toCsv($events): string
    {
        $lines = [];
        $lines[] = implode(',', [
            'id', 'tenant_id', 'project_id', 'user_id', 'event_type',
            'subject_type', 'subject_id', 'action', 'ip_address', 'created_at',
        ]);

        foreach ($events as $e) {
            $lines[] = implode(',', [
                $e->id, $e->tenant_id, $e->project_id, $e->user_id,
                '"' . str_replace('"', '""', $e->event_type) . '"',
                $e->subject_type, $e->subject_id,
                '"' . str_replace('"', '""', $e->action) . '"',
                $e->ip_address, $e->created_at,
            ]);
        }

        return implode("\n", $lines);
    }

    protected function toJson($events): string
    {
        return $events->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * PDF generation stub — in production, use a library like DomPDF or wkhtmltopdf.
     * Phase 3 delivers the HTML-to-PDF pipeline; the exact renderer is configurable.
     */
    protected function toPdf($events): string
    {
        $html = '<html><head><title>Audit Report</title><style>';
        $html .= 'body{font-family:sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}';
        $html .= 'th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f0f0f0}';
        $html .= '</style></head><body>';
        $html .= '<h1>Audit Report</h1>';
        $html .= '<p>Generated: ' . now()->toIso8601String() . ' | Records: ' . $events->count() . '</p>';
        $html .= '<table><tr><th>ID</th><th>Event</th><th>Action</th><th>Subject</th><th>User</th><th>Time</th></tr>';

        foreach ($events as $e) {
            $html .= '<tr>';
            $html .= "<td>{$e->id}</td><td>{$e->event_type}</td><td>{$e->action}</td>";
            $html .= "<td>{$e->subject_type}#{$e->subject_id}</td>";
            $html .= "<td>{$e->user_id}</td><td>{$e->created_at}</td></tr>";
        }

        $html .= '</table></body></html>';

        return $html;
    }
}
