<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GitopsEnvironment;
use App\Models\GitopsSync;
use App\Services\GitOpsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GitOpsController extends Controller
{
    public function __construct(protected GitOpsService $gitops) {}

    public function environments(): JsonResponse
    {
        return response()->json(['data' => GitopsEnvironment::orderBy('environment_class')->orderBy('name')->get()]);
    }

    public function registerEnvironment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'              => 'required|string|max:120|alpha_dash',
            'engine'            => 'nullable|string|in:argocd,flux',
            'repo_url'          => 'required|string|max:500',
            'branch'            => 'nullable|string|max:120',
            'path'              => 'required|string|max:500',
            'cluster'           => 'nullable|string|max:120',
            'namespace'         => 'nullable|string|max:120',
            'environment_class' => 'nullable|string|in:dev,test,staging,prod',
            'auto_sync'         => 'nullable|boolean',
        ]);
        return response()->json($this->gitops->registerEnvironment($data), 201);
    }

    public function sync(GitopsEnvironment $environment, Request $request): JsonResponse
    {
        $sync = $this->gitops->syncEnvironment($environment, (int) $request->user()->id, 'manual');
        return response()->json($sync, 201);
    }

    public function drift(GitopsEnvironment $environment): JsonResponse
    {
        return response()->json($this->gitops->detectDrift($environment));
    }

    public function syncs(GitopsEnvironment $environment): JsonResponse
    {
        return response()->json($environment->syncs()->orderByDesc('id')->paginate(20));
    }

    public function regionStatus(): JsonResponse
    {
        return response()->json(['data' => $this->gitops->regionStatus()]);
    }

    public function failoverDrill(Request $request): JsonResponse
    {
        $data = $request->validate([
            'primary_region'   => 'required|string',
            'secondary_region' => 'required|string|different:primary_region',
        ]);
        return response()->json($this->gitops->failoverDrill($data['primary_region'], $data['secondary_region']));
    }
}
