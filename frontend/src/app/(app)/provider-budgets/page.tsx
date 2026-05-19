"use client";

import { useEffect, useState } from "react";
import { providerBudgetsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

const PROVIDER_COLORS: Record<string, string> = {
  openai: "from-emerald-500 to-teal-600",
  claude: "from-orange-500 to-amber-600",
  google_adk: "from-blue-500 to-indigo-600",
};

export default function ProviderBudgetsPage() {
  const auth = useAuth();
  const [budgets, setBudgets] = useState<any[]>([]);
  const [summary, setSummary] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!auth.activeTenantId) return;
    setLoading(true);
    Promise.all([
      providerBudgetsApi.list({ tenant_id: auth.activeTenantId, active_only: true }).then((r: any) => setBudgets(r.data || [])),
      providerBudgetsApi.summary(auth.activeTenantId).then(setSummary),
    ]).finally(() => setLoading(false));
  }, [auth.activeTenantId]);

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading budgets...</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader title="Provider Budgets" description="Per-tenant model cost caps. Track token consumption and enforce monthly limits." />

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
        {summary.map((s: any, i: number) => {
          const pct = s.utilization || 0;
          const gradient = PROVIDER_COLORS[s.provider] || "from-slate-500 to-slate-600";
          return (
            <div key={i} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
              <div className="flex items-center justify-between">
                <div className="text-sm font-semibold capitalize text-white">{s.provider.replace(/_/g, " ")}</div>
                {s.model && <span className="rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-400">{s.model}</span>}
              </div>
              <div className="mt-4 flex items-end gap-2">
                <span className="text-3xl font-bold text-white">${s.spent.toFixed(2)}</span>
                <span className="mb-1 text-sm text-slate-500">/ ${s.limit.toFixed(2)}</span>
              </div>
              <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
                <div className={`h-full rounded-full bg-gradient-to-r ${gradient} transition-all`}
                  style={{ width: `${Math.min(pct, 100)}%` }} />
              </div>
              <div className="mt-2 flex items-center justify-between text-xs">
                <span className={pct >= 100 ? "text-rose-400" : pct >= 80 ? "text-amber-400" : "text-slate-500"}>
                  {pct.toFixed(1)}% used
                </span>
                <span className="text-slate-500">${s.remaining.toFixed(2)} remaining</span>
              </div>
            </div>
          );
        })}
        {summary.length === 0 && (
          <div className="col-span-3 py-8 text-center text-sm text-slate-500">No active budgets configured.</div>
        )}
      </div>

      <div className="mt-8">
        <div className="mb-3 text-sm font-semibold text-white">All Budget Rules</div>
        <div className="overflow-x-auto rounded-2xl border border-slate-800">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-slate-800 bg-slate-900/50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3">Provider</th>
                <th className="px-4 py-3">Model</th>
                <th className="px-4 py-3">Limit</th>
                <th className="px-4 py-3">Spent</th>
                <th className="px-4 py-3">Period</th>
                <th className="px-4 py-3">On Exceed</th>
                <th className="px-4 py-3">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/50">
              {budgets.map((b: any) => (
                <tr key={b.id} className="hover:bg-slate-900/30">
                  <td className="px-4 py-3 capitalize text-white">{b.provider.replace(/_/g, " ")}</td>
                  <td className="px-4 py-3 text-slate-400">{b.model || "All"}</td>
                  <td className="px-4 py-3 text-white">${parseFloat(b.monthly_limit_usd).toFixed(2)}</td>
                  <td className="px-4 py-3 text-slate-300">${parseFloat(b.current_spend_usd).toFixed(2)}</td>
                  <td className="px-4 py-3 text-xs text-slate-500">{b.period_start} → {b.period_end}</td>
                  <td className="px-4 py-3">
                    <span className={`rounded px-2 py-0.5 text-xs ${b.action_on_exceed === "reject" ? "bg-rose-500/10 text-rose-400" : b.action_on_exceed === "warn" ? "bg-amber-500/10 text-amber-400" : "bg-slate-800 text-slate-400"}`}>
                      {b.action_on_exceed}
                    </span>
                  </td>
                  <td className="px-4 py-3"><span className={`text-xs ${b.is_active ? "text-emerald-400" : "text-slate-500"}`}>{b.is_active ? "Active" : "Inactive"}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
