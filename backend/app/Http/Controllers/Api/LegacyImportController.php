<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegacyImport;
use App\Services\AutogenImporterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegacyImportController extends Controller
{
    public function __construct(protected AutogenImporterService $importer) {}

    public function index(Request $request): JsonResponse
    {
        $q = LegacyImport::query()->where('tenant_id', $request->user()->tenant_id);
        if ($format = $request->query('source_format')) {
            $q->where('source_format', $format);
        }
        return response()->json($q->latest()->paginate(20));
    }

    public function show(LegacyImport $legacyImport): JsonResponse
    {
        return response()->json($legacyImport);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'source_format' => 'required|string|in:autogen,tesslate,n8n,flowise,dify,crewai',
            'payload'       => 'required|array',
            'project_id'    => 'nullable|integer|exists:projects,id',
        ]);
        $job = $this->importer->import($data['source_format'], $data['payload'], [
            'tenant_id'  => $request->user()->tenant_id,
            'project_id' => $data['project_id'] ?? null,
            'user_id'    => $request->user()->id,
        ]);
        return response()->json($job, 201);
    }
}
