<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Services\TemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateLifecycleController extends Controller
{
    public function __construct(protected TemplateService $templates) {}

    public function fromAsset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_type' => 'required|in:agent,mcp_server,workflow',
            'asset_id' => 'required|integer',
            'name' => 'required|string|max:160',
            'description' => 'nullable|string',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'visibility' => 'sometimes|in:private,tenant,public',
        ]);
        $template = $this->templates->fromAsset($data['asset_type'], $data['asset_id'], $data);
        return response()->json($template, 201);
    }

    public function exportJson(Template $template): JsonResponse
    {
        return response()->json($this->templates->exportJson($template));
    }

    public function exportZip(Template $template): BinaryFileResponse
    {
        $result = $this->templates->exportZip($template);
        return response()->download($result['path'], $result['filename'])->deleteFileAfterSend();
    }

    public function importJson(Request $request): JsonResponse
    {
        $data = $request->validate([
            'manifest' => 'required|array',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ]);
        $result = $this->templates->importJson($data['manifest'], $data['tenant_id'] ?? null, $request->user()?->id);
        return response()->json($result, 201);
    }

    public function importZip(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file', 'tenant_id' => 'nullable|integer|exists:tenants,id']);
        $path = $request->file('file')->getRealPath();
        $result = $this->templates->importZip($path, $request->input('tenant_id'), $request->user()?->id);
        return response()->json($result, 201);
    }

    public function instantiate(Request $request, Template $template): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
            'project_id' => 'required|integer|exists:projects,id',
            'parameters' => 'sometimes|array',
        ]);
        $result = $this->templates->instantiate($template, $data['parameters'] ?? [], $data['tenant_id'], $data['project_id'], $request->user()?->id);
        return response()->json($result, 201);
    }
}
