<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LocalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __construct(protected LocalizationService $svc) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->svc->activeLocales()]);
    }

    public function dictionary(string $code, Request $request): JsonResponse
    {
        return response()->json([
            'code'        => $code,
            'namespace'   => $request->query('namespace'),
            'translations'=> $this->svc->dictionary($code, $request->query('namespace')),
        ]);
    }

    public function upsert(string $code, Request $request): JsonResponse
    {
        $data = $request->validate([
            'namespace' => 'required|string|max:120',
            'key'       => 'required|string|max:255',
            'value'     => 'required|string',
        ]);
        return response()->json($this->svc->upsert($code, $data['namespace'], $data['key'], $data['value']));
    }
}
