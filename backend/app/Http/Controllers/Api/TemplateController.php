<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Phase-1 template stub — full lifecycle in Phase 2.
 */
class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Template::query();
        if ($id = $request->query('tenant_id')) {
            $q->where(fn ($w) => $w->where('tenant_id', $id)->orWhereNull('tenant_id'));
        }
        return response()->json(['data' => $q->orderBy('name')->paginate(50)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'nullable|exists:tenants,id',
            'name' => 'required|string|max:160',
            'asset_type' => 'required|in:agent,mcp_server,workflow',
            'description' => 'nullable|string',
            'payload' => 'nullable|array',
            'visibility' => 'sometimes|in:private,tenant,public',
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::lower(Str::random(3));
        $template = Template::create($data);
        return response()->json($template, 201);
    }

    public function show(Template $template): JsonResponse
    {
        return response()->json($template);
    }
}
