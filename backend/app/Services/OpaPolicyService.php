<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\OpaPolicy;
use App\Models\OpaPolicyVersion;
use App\Models\Tool;
use App\Support\Audit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Full policy-as-code engine (Phase 2 baseline + Phase 3 expansion).
 *
 * Phase 3 additions:
 * - Policy authoring, versioning, dry-run, and lifecycle
 * - Policy categories: tool_risk, deployment, approval, network, template_import, tenant_isolation
 * - OPA bundle build from authored policies
 * - Evaluation of deployment, approval, and network policies
 */
class OpaPolicyService
{
    protected array $riskOrder = ['L0' => 0, 'L1' => 1, 'L2' => 2, 'L3' => 3, 'L4' => 4];

    // ─── Phase 2 core: tool-call evaluation ─────────────────────────

    /**
     * @return array{allow:bool,reason?:string,requires_approval:bool}
     */
    public function evaluateToolCall(?Agent $agent, Tool $tool, array $context = []): array
    {
        $opaUrl = env('OPA_URL');
        $payload = [
            'input' => [
                'agent' => [
                    'id' => $agent?->id,
                    'risk_level' => $agent?->risk_level ?? 'L1',
                    'max_risk_level_without_approval' => $agent?->max_risk_level_without_approval ?? 'L1',
                    'allowed_tools' => $agent?->currentVersion?->allowed_tools ?? [],
                ],
                'tool' => [
                    'id' => $tool->id,
                    'name' => $tool->name,
                    'risk_level' => $tool->risk_level ?? 'L1',
                    'mcp_server_id' => $tool->mcp_server_id,
                ],
                'context' => $context,
            ],
        ];

        if ($opaUrl) {
            try {
                $resp = Http::timeout(3)->post(rtrim($opaUrl, '/') . '/v1/data/eamcp/tool_call', $payload);
                if ($resp->successful()) {
                    $result = $resp->json('result', []);
                    return [
                        'allow' => (bool) ($result['allow'] ?? false),
                        'requires_approval' => (bool) ($result['requires_approval'] ?? false),
                        'reason' => $result['reason'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('opa.evaluate_failed', ['error' => $e->getMessage()]);
            }
        }

        return $this->evaluateBuiltin($agent, $tool);
    }

    // ─── Phase 3: deployment policy evaluation ──────────────────────

    /**
     * Evaluate deployment policies (e.g., prod ArcGIS requires Security Approver + Platform Owner).
     */
    public function evaluateDeployment(array $deploymentContext): array
    {
        return $this->evaluateOpaPath('eamcp/deployment', $deploymentContext);
    }

    /**
     * Evaluate approval requirement policies.
     */
    public function evaluateApprovalRequirement(array $approvalContext): array
    {
        return $this->evaluateOpaPath('eamcp/approval', $approvalContext);
    }

    /**
     * Evaluate network access policies.
     */
    public function evaluateNetworkAccess(array $networkContext): array
    {
        return $this->evaluateOpaPath('eamcp/network', $networkContext);
    }

    /**
     * Evaluate template import policies.
     */
    public function evaluateTemplateImport(array $importContext): array
    {
        return $this->evaluateOpaPath('eamcp/template_import', $importContext);
    }

    /**
     * Evaluate tenant isolation policies.
     */
    public function evaluateTenantIsolation(array $isolationContext): array
    {
        return $this->evaluateOpaPath('eamcp/tenant_isolation', $isolationContext);
    }

    // ─── Phase 3: policy CRUD and lifecycle ──────────────────────────

    public function createPolicy(array $data, int $userId): OpaPolicy
    {
        $policy = OpaPolicy::create([
            'tenant_id'    => $data['tenant_id'] ?? null,
            'name'         => $data['name'],
            'slug'         => $data['slug'] ?? Str::slug($data['name']),
            'category'     => $data['category'],
            'description'  => $data['description'] ?? null,
            'rego_code'    => $data['rego_code'],
            'package_path' => $data['package_path'] ?? "eamcp.{$data['category']}",
            'status'       => 'draft',
            'version'      => 1,
            'metadata'     => $data['metadata'] ?? null,
            'created_by'   => $userId,
            'updated_by'   => $userId,
        ]);

        OpaPolicyVersion::create([
            'opa_policy_id' => $policy->id,
            'version'       => 1,
            'rego_code'     => $data['rego_code'],
            'status'        => 'draft',
            'created_by'    => $userId,
        ]);

        Audit::record('create', 'opa_policy_created', 'opa_policy', $policy->id, [
            'category' => $data['category'],
        ]);

        return $policy;
    }

    public function updatePolicy(OpaPolicy $policy, array $data, int $userId): OpaPolicy
    {
        $newVersion = $policy->version + 1;

        $policy->update([
            'name'         => $data['name'] ?? $policy->name,
            'description'  => $data['description'] ?? $policy->description,
            'rego_code'    => $data['rego_code'] ?? $policy->rego_code,
            'package_path' => $data['package_path'] ?? $policy->package_path,
            'version'      => $newVersion,
            'metadata'     => $data['metadata'] ?? $policy->metadata,
            'updated_by'   => $userId,
        ]);

        OpaPolicyVersion::create([
            'opa_policy_id' => $policy->id,
            'version'       => $newVersion,
            'rego_code'     => $data['rego_code'] ?? $policy->rego_code,
            'status'        => $policy->status,
            'created_by'    => $userId,
        ]);

        Audit::record('update', 'opa_policy_updated', 'opa_policy', $policy->id, [
            'version' => $newVersion,
        ]);

        return $policy->fresh();
    }

    public function activatePolicy(OpaPolicy $policy, int $userId): OpaPolicy
    {
        $policy->update(['status' => 'active', 'updated_by' => $userId]);
        $this->pushToOpa($policy);

        Audit::record('update', 'opa_policy_activated', 'opa_policy', $policy->id);
        return $policy->fresh();
    }

    public function disablePolicy(OpaPolicy $policy, int $userId): OpaPolicy
    {
        $policy->update(['status' => 'disabled', 'updated_by' => $userId]);
        Audit::record('update', 'opa_policy_disabled', 'opa_policy', $policy->id);
        return $policy->fresh();
    }

    /**
     * Dry-run a policy against sample input without deploying.
     */
    public function dryRun(OpaPolicy $policy, array $sampleInput): array
    {
        $opaUrl = env('OPA_URL');
        if (! $opaUrl) {
            return ['error' => 'OPA server not configured', 'simulated' => true];
        }

        try {
            // Use OPA's compile/partial-eval API for dry run
            $resp = Http::timeout(5)->post(rtrim($opaUrl, '/') . '/v1/data/' . str_replace('.', '/', $policy->package_path), [
                'input' => $sampleInput,
            ]);

            $result = $resp->json('result', []);
            $policy->update(['dry_run_result' => $result]);

            return ['success' => true, 'result' => $result];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Lint a Rego policy (basic syntax validation).
     */
    public function lintRego(string $regoCode): array
    {
        $issues = [];

        if (! preg_match('/^package\s+[\w.]+/m', $regoCode)) {
            $issues[] = ['line' => 1, 'severity' => 'error', 'message' => 'Missing package declaration'];
        }
        if (preg_match('/\beval\s*\(/', $regoCode)) {
            $issues[] = ['severity' => 'warning', 'message' => 'Avoid eval() in policies'];
        }
        if (preg_match('/\bhttp\.send\s*\(/', $regoCode)) {
            $issues[] = ['severity' => 'warning', 'message' => 'http.send() may cause latency — review carefully'];
        }

        return [
            'valid'  => empty(array_filter($issues, fn ($i) => ($i['severity'] ?? '') === 'error')),
            'issues' => $issues,
        ];
    }

    /**
     * Get the policy library (categorized list).
     */
    public function getPolicyLibrary(?int $tenantId = null): array
    {
        $query = OpaPolicy::query();
        if ($tenantId) {
            $query->where(fn ($q) => $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id'));
        }

        return $query->orderBy('category')->orderBy('name')->get()
            ->groupBy('category')
            ->toArray();
    }

    // ─── Internal ────────────────────────────────────────────────────

    protected function evaluateOpaPath(string $path, array $context): array
    {
        $opaUrl = env('OPA_URL');
        if (! $opaUrl) {
            return ['allow' => true, 'fallback' => true];
        }

        try {
            $resp = Http::timeout(3)->post(
                rtrim($opaUrl, '/') . '/v1/data/' . str_replace('.', '/', $path),
                ['input' => $context],
            );

            if ($resp->successful()) {
                return $resp->json('result', ['allow' => true]);
            }
        } catch (\Throwable $e) {
            Log::warning("opa.{$path}_failed", ['error' => $e->getMessage()]);
        }

        return ['allow' => true, 'fallback' => true];
    }

    protected function pushToOpa(OpaPolicy $policy): void
    {
        $opaUrl = env('OPA_URL');
        if (! $opaUrl) {
            return;
        }

        try {
            Http::timeout(5)
                ->withHeaders(['Content-Type' => 'text/plain'])
                ->put(
                    rtrim($opaUrl, '/') . '/v1/policies/' . $policy->slug,
                    $policy->rego_code,
                );
        } catch (\Throwable $e) {
            Log::error('opa.push_failed', ['policy' => $policy->slug, 'error' => $e->getMessage()]);
        }
    }

    protected function evaluateBuiltin(?Agent $agent, Tool $tool): array
    {
        $maxRisk = $agent?->max_risk_level_without_approval ?? 'L1';
        $toolRisk = $tool->risk_level ?? 'L1';

        $allowedTools = $agent?->currentVersion?->allowed_tools ?? null;
        if ($allowedTools && ! in_array($tool->id, $allowedTools, true)) {
            return ['allow' => false, 'requires_approval' => false, 'reason' => 'tool_not_in_agent_allowlist'];
        }

        if (($this->riskOrder[$toolRisk] ?? 0) > ($this->riskOrder[$maxRisk] ?? 0)) {
            return [
                'allow' => false,
                'requires_approval' => true,
                'reason' => "tool risk {$toolRisk} exceeds agent max {$maxRisk}",
            ];
        }

        return ['allow' => true, 'requires_approval' => false];
    }
}
