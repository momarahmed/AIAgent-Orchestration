<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetComment;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(protected CommentService $comments) {}

    public function index(Request $r): JsonResponse
    {
        $r->validate(['asset_type' => 'required|string', 'asset_id' => 'required|integer']);
        return response()->json(['data' => $this->comments->list($r->query('asset_type'), (int) $r->query('asset_id'))]);
    }

    public function store(Request $r): JsonResponse
    {
        $data = $r->validate([
            'tenant_id'  => 'required|integer|exists:tenants,id',
            'asset_type' => 'required|string',
            'asset_id'   => 'required|integer',
            'body'       => 'required|string',
            'parent_id'  => 'nullable|integer|exists:asset_comments,id',
        ]);
        $c = $this->comments->create(
            $data['tenant_id'], $data['asset_type'], $data['asset_id'],
            $r->user()?->id ?? 0, $data['body'], $data['parent_id'] ?? null
        );
        return response()->json($c, 201);
    }

    public function resolve(Request $r, AssetComment $comment): JsonResponse
    {
        return response()->json($this->comments->resolve($comment, $r->user()?->id ?? 0));
    }

    public function destroy(AssetComment $comment): JsonResponse
    {
        $comment->delete();
        return response()->json(['ok' => true]);
    }
}
