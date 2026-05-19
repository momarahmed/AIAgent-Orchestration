<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemoryCollection;
use App\Models\MemoryItem;
use App\Services\MemoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemoryController extends Controller
{
    public function __construct(protected MemoryService $memory) {}

    public function collections(Request $r): JsonResponse
    {
        $q = MemoryCollection::query();
        if ($r->query('tenant_id')) $q->where('tenant_id', $r->query('tenant_id'));
        if ($r->query('project_id')) $q->where('project_id', $r->query('project_id'));
        return response()->json(['data' => $q->orderBy('name')->get()]);
    }

    public function createCollection(Request $r): JsonResponse
    {
        $data = $r->validate([
            'tenant_id'        => 'required|integer|exists:tenants,id',
            'project_id'       => 'nullable|integer|exists:projects,id',
            'slug'             => 'required|string',
            'scope'            => 'in:none,session,project,tenant',
            'embedding_model'  => 'nullable|string',
        ]);
        $col = $this->memory->ensureCollection(
            $data['tenant_id'],
            $data['project_id'] ?? null,
            $data['slug'],
            $data['scope'] ?? 'project',
            $data['embedding_model'] ?? 'text-embedding-3-small',
        );
        return response()->json($col, 201);
    }

    public function items(MemoryCollection $collection, Request $r): JsonResponse
    {
        return response()->json(['data' => MemoryItem::where('memory_collection_id', $collection->id)
            ->latest()->paginate(50)->items()]);
    }

    public function remember(Request $r, MemoryCollection $collection): JsonResponse
    {
        $data = $r->validate([
            'content'    => 'required|string',
            'session_id' => 'nullable|string',
            'agent_id'   => 'nullable|integer|exists:agents,id',
            'source_type'=> 'nullable|string',
            'metadata'   => 'nullable|array',
        ]);
        $item = $this->memory->remember($collection, $data['content'], $data);
        return response()->json($item, 201);
    }

    public function retrieve(Request $r, MemoryCollection $collection): JsonResponse
    {
        $data = $r->validate([
            'query'      => 'required|string',
            'top_k'      => 'nullable|integer',
            'session_id' => 'nullable|string',
            'agent_id'   => 'nullable|integer|exists:agents,id',
            'tenant_id'  => 'nullable|integer',
        ]);
        $agent = isset($data['agent_id']) ? \App\Models\Agent::find($data['agent_id']) : null;
        $start = microtime(true);
        $results = $this->memory->retrieve($collection, $data['query'], $data, $agent);
        $latency = (int) ((microtime(true) - $start) * 1000);
        return response()->json([
            'data'       => $results,
            'latency_ms' => $latency,
        ]);
    }

    public function forget(MemoryItem $item): JsonResponse
    {
        $this->memory->forget($item);
        return response()->json(['ok' => true]);
    }

    public function shortTerm(string $sessionId): JsonResponse
    {
        return response()->json(['data' => $this->memory->loadShortTerm($sessionId)]);
    }
}
