<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityScan;
use App\Services\SecurityScannerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SecurityScanController extends Controller
{
    public function __construct(protected SecurityScannerService $scanner) {}

    public function index(Request $request): JsonResponse
    {
        $query = SecurityScan::query();

        if ($request->query('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }
        if ($request->query('deployment_id')) {
            $query->where('deployment_id', $request->query('deployment_id'));
        }
        if ($request->query('scan_type')) {
            $query->where('scan_type', $request->query('scan_type'));
        }
        if ($request->query('blocks_promotion')) {
            $query->where('blocks_promotion', true);
        }

        return response()->json($query->orderByDesc('created_at')->paginate(50));
    }

    public function show(SecurityScan $securityScan): JsonResponse
    {
        return response()->json($securityScan->load('deployment'));
    }

    public function triggerScan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scan_type'     => 'required|in:trivy,syft,grype,secret_scan,code_scan',
            'target_type'   => 'required|in:container,template,code,dependency',
            'target_ref'    => 'required|string',
            'deployment_id' => 'nullable|exists:deployments,id',
            'tenant_id'     => 'nullable|exists:tenants,id',
        ]);

        $scan = $this->scanner->runScan(
            $data['scan_type'],
            $data['target_type'],
            $data['target_ref'],
            $data['deployment_id'] ?? null,
            $data['tenant_id'] ?? null,
            $request->user()->id,
        );

        return response()->json($scan, 201);
    }

    public function scanDeployment(Request $request, int $deploymentId): JsonResponse
    {
        $scans = $this->scanner->scanDeployment(
            $deploymentId,
            $request->input('target_ref', 'latest'),
            $request->input('tenant_id'),
            $request->user()->id,
        );

        return response()->json(['scans' => $scans]);
    }

    public function promotionGate(int $deploymentId): JsonResponse
    {
        $result = $this->scanner->deploymentPassesGate($deploymentId);
        return response()->json($result);
    }

    public function summary(Request $request): JsonResponse
    {
        $tenantId = $request->query('tenant_id');
        $query = SecurityScan::where('status', 'completed');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $recent = $query->orderByDesc('created_at')->limit(100)->get();

        return response()->json([
            'total_scans'     => $recent->count(),
            'blocking_scans'  => $recent->where('blocks_promotion', true)->count(),
            'total_critical'  => $recent->sum('critical_count'),
            'total_high'      => $recent->sum('high_count'),
            'total_medium'    => $recent->sum('medium_count'),
            'total_low'       => $recent->sum('low_count'),
            'scan_types'      => $recent->groupBy('scan_type')->map->count(),
        ]);
    }
}
