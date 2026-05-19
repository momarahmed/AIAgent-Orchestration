<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProviderBudget;
use App\Services\ProviderBudgetService;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderBudgetController extends Controller
{
    public function __construct(protected ProviderBudgetService $budgetSvc) {}

    public function index(Request $request): JsonResponse
    {
        $query = ProviderBudget::query();
        if ($request->query('tenant_id')) {
            $query->where('tenant_id', $request->query('tenant_id'));
        }
        if ($request->query('active_only')) {
            $query->where('is_active', true)
                ->where('period_start', '<=', now())
                ->where('period_end', '>=', now());
        }
        return response()->json($query->orderBy('provider')->paginate(50));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id'        => 'required|exists:tenants,id',
            'provider'         => 'required|in:openai,claude,google_adk',
            'model'            => 'nullable|string',
            'monthly_limit_usd'=> 'required|numeric|min:0',
            'period_start'     => 'required|date',
            'period_end'       => 'required|date|after:period_start',
            'action_on_exceed' => 'in:reject,warn,throttle',
        ]);

        $budget = ProviderBudget::create($data);

        Audit::record('create', 'provider_budget_created', 'provider_budget', $budget->id, [
            'provider' => $data['provider'], 'limit' => $data['monthly_limit_usd'],
        ]);

        return response()->json($budget, 201);
    }

    public function update(Request $request, ProviderBudget $providerBudget): JsonResponse
    {
        $data = $request->validate([
            'monthly_limit_usd'=> 'numeric|min:0',
            'action_on_exceed' => 'in:reject,warn,throttle',
            'is_active'        => 'boolean',
        ]);

        $providerBudget->update($data);
        return response()->json($providerBudget);
    }

    public function destroy(ProviderBudget $providerBudget): JsonResponse
    {
        $providerBudget->delete();
        return response()->json(null, 204);
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'provider'  => 'required|string',
            'model'     => 'nullable|string',
        ]);

        $result = $this->budgetSvc->checkBudget(
            $data['tenant_id'],
            $data['provider'],
            $data['model'] ?? null,
        );

        return response()->json($result);
    }

    public function summary(Request $request): JsonResponse
    {
        $tenantId = $request->validate(['tenant_id' => 'required|exists:tenants,id'])['tenant_id'];
        return response()->json($this->budgetSvc->getTenantSpendSummary($tenantId));
    }
}
