"use client";

import { useQuery } from "@tanstack/react-query";
import { analyticsApi, gitopsApi, continuousSecurityApi, costGovernanceApi, marketplaceApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";

export default function OperationsDashboardPage() {
  const usage = useQuery({ queryKey: ["ops-usage"], queryFn: () => analyticsApi.usage(7) });
  const reliability = useQuery({ queryKey: ["ops-rel"], queryFn: () => analyticsApi.reliability(7) });
  const cost = useQuery({ queryKey: ["ops-cost"], queryFn: () => analyticsApi.cost(7) });
  const regions = useQuery({ queryKey: ["ops-regions"], queryFn: () => gitopsApi.regions() });
  const due = useQuery({ queryKey: ["ops-due"], queryFn: () => continuousSecurityApi.dueDates() });
  const budgets = useQuery({ queryKey: ["ops-budgets"], queryFn: () => costGovernanceApi.budgets() });
  const adoption = useQuery({ queryKey: ["ops-adoption"], queryFn: () => marketplaceApi.list({ per_page: 5 }) });

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 5 · Platform Ops"
        title="Operational dashboard"
        description="Live SLO, region status, queue depth, security, and budget posture for platform owners."
      />

      <section className="grid grid-cols-1 gap-4 px-1 md:grid-cols-2 xl:grid-cols-4">
        <Kpi label="Runs (7d)" value={((usage.data as any)?.daily ?? []).reduce((a: number, r: any) => a + Number(r.runs ?? 0), 0)} />
        <Kpi label="Success rate" value={`${(reliability.data as any)?.success_rate ?? 0}%`} tone="emerald" />
        <Kpi label="Spend (7d)" value={`$${Number((cost.data as any)?.total_usd ?? 0).toFixed(2)}`} />
        <Kpi label="Open vulns" value={(due.data as any)?.open ?? 0} tone={((due.data as any)?.overdue ?? 0) > 0 ? "rose" : "amber"} />
      </section>

      <section className="grid gap-4 px-1 lg:grid-cols-2">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">Regional posture</h3>
          <ul className="mt-3 space-y-2 text-sm">
            {((regions.data as any)?.data ?? []).map((r: any) => (
              <li key={r.id} className="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-950/40 p-3">
                <div>
                  <div className="text-slate-100">{r.region}</div>
                  <div className="text-xs text-slate-500">{r.role}</div>
                </div>
                <div className="text-right">
                  <div className={`text-xs ${r.status === "healthy" ? "text-emerald-300" : "text-amber-300"}`}>{r.status}</div>
                  <div className="text-[10px] text-slate-500">lag {Number(r.replication_lag_seconds).toFixed(1)}s</div>
                </div>
              </li>
            ))}
          </ul>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">Budget posture</h3>
          <ul className="mt-3 space-y-3">
            {((budgets.data as any)?.data ?? []).slice(0, 5).map((b: any) => (
              <li key={b.id}>
                <div className="flex justify-between text-xs text-slate-400">
                  <span className="truncate">{b.name}</span>
                  <span>{b.utilization_percent}%</span>
                </div>
                <div className="mt-1 h-2 rounded-full bg-slate-800">
                  <div
                    className={`h-full rounded-full ${b.utilization_percent > 90 ? "bg-rose-400" : b.utilization_percent > 70 ? "bg-amber-400" : "bg-emerald-400"}`}
                    style={{ width: `${Math.min(100, b.utilization_percent)}%` }}
                  />
                </div>
              </li>
            ))}
          </ul>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 lg:col-span-2">
          <h3 className="text-sm font-semibold text-white">Top marketplace templates</h3>
          <table className="mt-3 min-w-full divide-y divide-slate-800 text-sm">
            <thead className="text-xs uppercase tracking-wider text-slate-400">
              <tr>
                <th className="px-3 py-2 text-left">Template</th>
                <th className="px-3 py-2 text-left">Category</th>
                <th className="px-3 py-2 text-left">Installs</th>
                <th className="px-3 py-2 text-left">Rating</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-900 text-slate-300">
              {((adoption.data as any)?.data ?? []).map((l: any) => (
                <tr key={l.id}>
                  <td className="px-3 py-2">{l.title}</td>
                  <td className="px-3 py-2 text-xs uppercase">{l.category}</td>
                  <td className="px-3 py-2">{l.install_count}</td>
                  <td className="px-3 py-2">{Number(l.rating_avg ?? 0).toFixed(1)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}

function Kpi({ label, value, tone = "cyan" }: { label: string; value: any; tone?: "cyan" | "emerald" | "rose" | "amber" }) {
  const cls = {
    cyan:    "border-cyan-500/20 bg-cyan-500/5 text-cyan-200",
    emerald: "border-emerald-500/30 bg-emerald-500/5 text-emerald-200",
    rose:    "border-rose-500/30 bg-rose-500/5 text-rose-200",
    amber:   "border-amber-500/30 bg-amber-500/5 text-amber-200",
  }[tone];
  return (
    <div className={`rounded-2xl border ${cls} p-4`}>
      <div className="text-[10px] uppercase tracking-wider opacity-80">{label}</div>
      <div className="mt-2 text-2xl font-semibold">{value}</div>
    </div>
  );
}
