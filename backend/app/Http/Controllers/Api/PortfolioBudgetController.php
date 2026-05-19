<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChargebackReport;
use App\Models\CostRecommendation;
use App\Models\PortfolioBudget;
use App\Services\PortfolioCostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PortfolioBudgetController extends Controller
{
    public function __construct(protected PortfolioCostService $cost) {}

    public function index(Request $request): JsonResponse
    {
        $q = PortfolioBudget::query();
        if ($type = $request->query('scope_type')) {
            $q->where('scope_type', $type);
        }
        $rows = $q->orderBy('scope_type')->orderBy('scope_id')->get()
            ->map(fn ($b) => [
                'id'                  => $b->id,
                'scope_type'          => $b->scope_type,
                'scope_id'            => $b->scope_id,
                'name'                => $b->name,
                'monthly_limit_usd'   => (float) $b->monthly_limit_usd,
                'current_spend_usd'   => (float) $b->current_spend_usd,
                'utilization_percent' => $b->utilizationPercent(),
                'action_on_exceed'    => $b->action_on_exceed,
                'parent_id'           => $b->parent_id,
                'is_active'           => $b->is_active,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope_type'        => 'required|string|in:organization,tenant,project,agent',
            'scope_id'          => 'nullable|integer',
            'name'              => 'required|string|max:255',
            'monthly_limit_usd' => 'required|numeric|min:0',
            'action_on_exceed'  => 'nullable|string|in:alert,throttle,switch_to_local,block',
            'parent_id'         => 'nullable|integer|exists:portfolio_budgets,id',
            'notify'            => 'nullable|array',
        ]);
        $data['period_start'] = now()->startOfMonth();
        $data['period_end']   = now()->endOfMonth();

        return response()->json(PortfolioBudget::create($data), 201);
    }

    public function update(PortfolioBudget $portfolioBudget, Request $request): JsonResponse
    {
        $portfolioBudget->update($request->only(['name', 'monthly_limit_usd', 'action_on_exceed', 'is_active', 'notify']));
        return response()->json($portfolioBudget);
    }

    public function destroy(PortfolioBudget $portfolioBudget): JsonResponse
    {
        $portfolioBudget->delete();
        return response()->json(['deleted' => true]);
    }

    public function chargeback(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id') ?: $request->user()->tenant_id);
        $start    = $request->input('period_start') ? Carbon::parse($request->input('period_start')) : null;
        $end      = $request->input('period_end')   ? Carbon::parse($request->input('period_end'))   : null;
        return response()->json($this->cost->chargeback($tenantId, $start, $end));
    }

    public function chargebackHistory(Request $request): JsonResponse
    {
        $rows = ChargebackReport::query()
            ->when($request->user()?->tenant_id, fn ($q, $t) => $q->where('tenant_id', $t))
            ->orderByDesc('period_end')->limit(50)->get();
        return response()->json(['data' => $rows]);
    }

    public function recommendations(Request $request): JsonResponse
    {
        $tenantId = (int) ($request->input('tenant_id') ?: $request->user()->tenant_id);
        $ids = $this->cost->generateRecommendations($tenantId);
        return response()->json([
            'generated_ids' => $ids,
            'recommendations' => CostRecommendation::where('tenant_id', $tenantId)->orderByDesc('estimated_savings_usd')->get(),
        ]);
    }
}
