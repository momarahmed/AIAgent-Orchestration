<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MigrationImport;
use App\Services\MigrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MigrationController extends Controller
{
    public function __construct(protected MigrationService $service) {}

    public function index(Request $r): JsonResponse
    {
        $q = MigrationImport::query();
        if ($r->query('tenant_id')) $q->where('tenant_id', $r->query('tenant_id'));
        return response()->json(['data' => $q->orderByDesc('created_at')->paginate(50)->items()]);
    }

    public function show(MigrationImport $import): JsonResponse
    {
        return response()->json($import);
    }

    public function import(Request $r): JsonResponse
    {
        $data = $r->validate([
            'tenant_id'    => 'required|integer|exists:tenants,id',
            'project_id'   => 'nullable|integer|exists:projects,id',
            'source_format'=> 'required|in:n8n,flowise,dify,json,yaml',
            'source'       => 'required',
            'filename'     => 'nullable|string',
        ]);
        $import = $this->service->import(
            $data['tenant_id'],
            $data['project_id'] ?? null,
            $data['source_format'],
            $data['source'],
            $r->user()?->id,
            $data['filename'] ?? null,
        );
        return response()->json($import, 201);
    }
}
