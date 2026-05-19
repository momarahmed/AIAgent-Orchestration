<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecretRef;
use App\Services\SecretService;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecretController extends Controller
{
    public function __construct(protected SecretService $secrets) {}

    public function index(Request $request): JsonResponse
    {
        $q = SecretRef::query();
        foreach (['tenant_id', 'project_id', 'environment'] as $f) {
            if ($v = $request->query($f)) $q->where($f, $v);
        }
        return response()->json(['data' => $q->orderByDesc('id')->paginate(100)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'project_id' => 'nullable|exists:projects,id',
            'name' => 'required|string|max:160',
            'vault_path' => 'required|string|max:255',
            'environment' => 'required|in:dev,test,staging,prod',
            'provider' => 'sometimes|in:vault,aws_sm,azure_kv,gcp_sm,env',
        ]);
        $ref = SecretRef::create(array_merge($data, ['created_by' => $request->user()?->id]));
        Audit::record('create', 'secret_ref.create', 'secret_ref', $ref->id, ['name' => $ref->name, 'environment' => $ref->environment]);
        return response()->json($ref, 201);
    }

    public function destroy(SecretRef $secret): JsonResponse
    {
        $secret->delete();
        return response()->json(['ok' => true]);
    }
}
