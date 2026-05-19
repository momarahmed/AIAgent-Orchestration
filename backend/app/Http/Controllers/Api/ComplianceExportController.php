<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ComplianceExport;
use App\Models\ComplianceFramework;
use App\Services\ComplianceExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceExportController extends Controller
{
    public function __construct(protected ComplianceExportService $service) {}

    public function frameworks(): JsonResponse
    {
        return response()->json(['data' => ComplianceFramework::where('is_active', true)->get()]);
    }

    public function index(Request $request): JsonResponse
    {
        $rows = ComplianceExport::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->orderByDesc('id')->paginate(20);
        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'frameworks'   => 'required|array|min:1',
            'frameworks.*' => 'string|in:SOC2,ISO27001,GDPR,HIPAA,NIST',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'format'       => 'nullable|string|in:zip,pdf,csv,jsonl',
        ]);
        $export = $this->service->generate(
            (int) $request->user()->tenant_id,
            $data['frameworks'],
            Carbon::parse($data['period_start']),
            Carbon::parse($data['period_end']),
            (int) $request->user()->id,
            $data['format'] ?? 'zip',
        );
        return response()->json($export, 201);
    }

    public function show(ComplianceExport $complianceExport): JsonResponse
    {
        return response()->json($complianceExport);
    }

    public function download(ComplianceExport $complianceExport): StreamedResponse
    {
        if ($complianceExport->status !== 'ready' || ! $complianceExport->storage_path) {
            abort(404);
        }
        $path = $complianceExport->storage_path;
        $mime = match ($complianceExport->format) {
            'zip'   => 'application/zip',
            'csv'   => 'text/csv',
            'jsonl' => 'application/x-ndjson',
            'pdf'   => 'application/pdf',
            default => 'application/octet-stream',
        };
        $name = "eamcp-compliance-{$complianceExport->id}." . ($complianceExport->format ?? 'zip');
        return Storage::disk('local')->download($path, $name, ['Content-Type' => $mime]);
    }
}
