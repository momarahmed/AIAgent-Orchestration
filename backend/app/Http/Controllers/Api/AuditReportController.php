<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\AuditExport;
use App\Services\AuditReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuditReportController extends Controller
{
    public function __construct(protected AuditReportService $reportSvc) {}

    /**
     * Paginated audit events with filtering.
     */
    public function events(Request $request): JsonResponse
    {
        $query = AuditEvent::query();

        if ($request->query('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }
        if ($request->query('event_type')) {
            $query->where('event_type', $request->query('event_type'));
        }
        if ($request->query('subject_type')) {
            $query->where('subject_type', $request->query('subject_type'));
        }
        if ($request->query('subject_id')) {
            $query->where('subject_id', $request->query('subject_id'));
        }
        if ($request->query('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }
        if ($request->query('from')) {
            $query->where('created_at', '>=', $request->query('from'));
        }
        if ($request->query('to')) {
            $query->where('created_at', '<=', $request->query('to'));
        }

        return response()->json(
            $query->orderByDesc('created_at')->paginate((int) $request->query('per_page', 50))
        );
    }

    /**
     * Audit history for a specific asset.
     */
    public function assetHistory(Request $request): JsonResponse
    {
        $request->validate([
            'subject_type' => 'required|string',
            'subject_id'   => 'required|integer',
        ]);

        $events = AuditEvent::where('subject_type', $request->query('subject_type'))
            ->where('subject_id', $request->query('subject_id'))
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($events);
    }

    /**
     * List available report templates.
     */
    public function templates(): JsonResponse
    {
        return response()->json($this->reportSvc->getTemplates());
    }

    /**
     * Generate and export an audit report.
     */
    public function export(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'    => 'required|exists:tenants,id',
            'format'       => 'required|in:csv,json,pdf',
            'template_id'  => 'nullable|exists:audit_report_templates,id',
            'period_start' => 'nullable|date',
            'period_end'   => 'nullable|date',
            'event_type'   => 'nullable|string',
            'subject_type' => 'nullable|string',
            'user_id'      => 'nullable|integer',
        ]);

        $filters = array_filter([
            'period_start' => $data['period_start'] ?? null,
            'period_end'   => $data['period_end'] ?? null,
            'event_type'   => $data['event_type'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'user_id'      => $data['user_id'] ?? null,
        ]);

        $export = $this->reportSvc->generateExport(
            $data['tenant_id'],
            $data['format'],
            $filters,
            $data['template_id'] ?? null,
            $request->user()->id,
        );

        return response()->json($export, 201);
    }

    /**
     * List previous exports.
     */
    public function exports(Request $request): JsonResponse
    {
        $query = AuditExport::with('template');
        if ($request->query('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }
        return response()->json($query->orderByDesc('created_at')->paginate(25));
    }

    /**
     * Download an export file.
     */
    public function download(AuditExport $auditExport): \Symfony\Component\HttpFoundation\Response
    {
        if (! $auditExport->file_path || ! Storage::exists($auditExport->file_path)) {
            return response()->json(['error' => 'Export file not found'], 404);
        }

        return Storage::download($auditExport->file_path);
    }
}
