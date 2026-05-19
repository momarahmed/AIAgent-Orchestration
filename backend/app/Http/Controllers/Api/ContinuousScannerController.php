<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SbomDiff;
use App\Models\SbomSnapshot;
use App\Models\VulnerabilityFinding;
use App\Services\ContinuousScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContinuousScannerController extends Controller
{
    public function __construct(protected ContinuousScannerService $scanner) {}

    public function snapshots(Request $request): JsonResponse
    {
        $q = SbomSnapshot::query()->orderByDesc('id');
        if ($img = $request->query('image_ref')) {
            $q->where('image_ref', $img);
        }
        return response()->json($q->paginate(20));
    }

    public function takeSnapshot(Request $request): JsonResponse
    {
        $data = $request->validate(['image_ref' => 'required|string']);
        return response()->json($this->scanner->snapshot($data['image_ref']), 201);
    }

    public function diffs(Request $request): JsonResponse
    {
        $q = SbomDiff::query()->orderByDesc('id');
        if ($img = $request->query('image_ref')) {
            $q->where('image_ref', $img);
        }
        if ($risk = $request->query('risk_level')) {
            $q->where('risk_level', $risk);
        }
        return response()->json($q->paginate(20));
    }

    public function diffLatest(Request $request): JsonResponse
    {
        $data = $request->validate(['image_ref' => 'required|string']);
        $diff = $this->scanner->diffLatest($data['image_ref']);
        if (! $diff) {
            return response()->json(['error' => 'no_snapshot_yet'], 404);
        }
        return response()->json($diff);
    }

    public function findings(Request $request): JsonResponse
    {
        $q = VulnerabilityFinding::query();
        foreach (['severity', 'state', 'image_ref'] as $field) {
            if ($val = $request->query($field)) {
                $q->where($field, $val);
            }
        }
        return response()->json($q->orderByDesc('id')->paginate(50));
    }

    public function reportFinding(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cve'               => 'required|string|max:50',
            'image_ref'         => 'required|string',
            'package'           => 'required|string',
            'installed_version' => 'nullable|string',
            'fixed_version'     => 'nullable|string',
            'severity'          => 'nullable|string|in:critical,high,medium,low,info',
            'description'       => 'nullable|string',
        ]);
        return response()->json($this->scanner->recordFinding($data), 201);
    }

    public function transitionFinding(VulnerabilityFinding $finding, Request $request): JsonResponse
    {
        $data = $request->validate([
            'state' => 'required|string|in:open,accepted_risk,fixed,false_positive',
            'note'  => 'nullable|string',
        ]);
        return response()->json($this->scanner->transitionFinding(
            $finding->id, $data['state'], (int) $request->user()->id, $data['note'] ?? null,
        ));
    }

    public function dueDates(): JsonResponse
    {
        return response()->json($this->scanner->dueDates());
    }
}
