"use client";

import { useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { analyticsApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";
import { useI18n } from "@/lib/i18n-context";

export default function AnalyticsPage() {
  const { t } = useI18n();
  const usage = useQuery({ queryKey: ["analytics-usage"], queryFn: () => analyticsApi.usage(30) });
  const cost = useQuery({ queryKey: ["analytics-cost"], queryFn: () => analyticsApi.cost(30) });
  const reliability = useQuery({ queryKey: ["analytics-reliability"], queryFn: () => analyticsApi.reliability(30) });
  const adoption = useQuery({ queryKey: ["analytics-adoption"], queryFn: () => analyticsApi.templateAdoption(90) });
  const quality = useQuery({ queryKey: ["analytics-quality"], queryFn: () => analyticsApi.qualityScores() });

  const dailySpark = useMemo(() => {
    const daily = (cost.data as any)?.daily ?? {};
    return Object.entries(daily).map(([day, v]: [string, any]) => ({ day, value: Number(v) }));
  }, [cost.data]);

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 5 · Analytics"
        title={t("common.analytics", "Advanced Analytics")}
        description="Usage, cost, reliability, and template adoption — refreshed daily."
      />

      <section className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 px-1">
        <Kpi label={t("analytics.usage", "Runs (30d)")} value={(usage.data as any)?.daily?.reduce((a: number, r: any) => a + Number(r.runs ?? 0), 0) ?? 0} />
        <Kpi label={t("analytics.cost", "Spend (30d)")} value={`$${Number((cost.data as any)?.total_usd ?? 0).toFixed(2)}`} tone={(cost.data as any)?.anomaly?.anomaly ? "rose" : "cyan"} />
        <Kpi label={t("analytics.reliability", "Success rate")} value={`${(reliability.data as any)?.success_rate ?? 0}%`} />
        <Kpi label="Quality scores" value={(quality.data as any)?.length ?? 0} />
      </section>

      <section className="grid gap-4 px-1 lg:grid-cols-2">
        <Card title="Cost trend (30d)" subtitle={(cost.data as any)?.anomaly?.anomaly ? `⚠ Anomaly z=${(cost.data as any).anomaly.z_score}` : "stable"}>
          <Sparkline points={dailySpark} />
        </Card>
        <Card title="Top workflows by runs">
          <ul className="divide-y divide-slate-800">
            {((usage.data as any)?.top_workflows ?? []).map((w: any) => (
              <li key={w.workflow_id} className="flex items-center justify-between py-2 text-sm text-slate-300">
                <span className="truncate">{w.workflow}</span>
                <span className="text-xs text-slate-500">{w.runs}</span>
              </li>
            ))}
          </ul>
        </Card>
        <Card title="Reliability">
          <ul className="space-y-2 text-sm text-slate-300">
            <li>Success rate: <b>{(reliability.data as any)?.success_rate ?? 0}%</b></li>
            <li>Failure rate: <b>{(reliability.data as any)?.failure_rate ?? 0}%</b></li>
            <li>Avg duration: <b>{(reliability.data as any)?.avg_duration_seconds ?? 0}s</b></li>
            <li>Awaiting approval: <b>{(reliability.data as any)?.awaiting_approval ?? 0}</b></li>
            <li>Tool failure rate: <b>{(reliability.data as any)?.task_failure_rate ?? 0}%</b></li>
          </ul>
        </Card>
        <Card title="Template adoption (90d)">
          <ul className="divide-y divide-slate-800">
            {((adoption.data as any)?.top_templates ?? []).map((tpl: any) => (
              <li key={tpl.listing_id} className="flex items-center justify-between py-2 text-sm text-slate-300">
                <span className="truncate">{tpl.title}</span>
                <span className="text-xs text-slate-500">{tpl.installs}</span>
              </li>
            ))}
          </ul>
        </Card>
      </section>

      <section className="px-1">
        <Card title="Agent / Workflow quality scores">
          <div className="overflow-hidden rounded-xl border border-slate-800">
            <table className="min-w-full divide-y divide-slate-800 text-sm">
              <thead className="bg-slate-900/60 text-xs uppercase tracking-wider text-slate-400">
                <tr>
                  <th className="px-3 py-2 text-left">Subject</th>
                  <th className="px-3 py-2 text-left">Success rate</th>
                  <th className="px-3 py-2 text-left">Latency p95</th>
                  <th className="px-3 py-2 text-left">Cost/run</th>
                  <th className="px-3 py-2 text-left">Composite</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-900 bg-slate-950/30 text-slate-300">
                {(quality.data as any[] ?? []).map((q: any) => (
                  <tr key={q.id}>
                    <td className="px-3 py-2">{q.subject_type} #{q.subject_id}</td>
                    <td className="px-3 py-2">{Number(q.success_rate).toFixed(1)}%</td>
                    <td className="px-3 py-2">{Number(q.latency_ms_p95).toFixed(0)}ms</td>
                    <td className="px-3 py-2">${Number(q.cost_per_run_usd).toFixed(4)}</td>
                    <td className="px-3 py-2 font-semibold text-cyan-200">{Number(q.composite_score).toFixed(1)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      </section>
    </div>
  );
}

function Kpi({ label, value, tone = "cyan" }: { label: string; value: any; tone?: "cyan" | "rose" | "emerald" }) {
  const cls = tone === "rose" ? "border-rose-500/30 bg-rose-500/5 text-rose-200" : tone === "emerald" ? "border-emerald-500/30 bg-emerald-500/5 text-emerald-200" : "border-cyan-500/20 bg-cyan-500/5 text-cyan-200";
  return (
    <div className={`rounded-2xl border ${cls} p-4`}>
      <div className="text-[10px] uppercase tracking-wider opacity-80">{label}</div>
      <div className="mt-2 text-2xl font-semibold">{value}</div>
    </div>
  );
}

function Card({ title, subtitle, children }: { title: string; subtitle?: string; children: any }) {
  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold text-white">{title}</h3>
        {subtitle && <span className="text-xs text-slate-400">{subtitle}</span>}
      </div>
      <div className="mt-3">{children}</div>
    </div>
  );
}

function Sparkline({ points }: { points: { day: string; value: number }[] }) {
  if (!points.length) return <div className="text-xs text-slate-500">no data</div>;
  const max = Math.max(...points.map((p) => p.value)) || 1;
  const w = 320, h = 80;
  const step = w / Math.max(1, points.length - 1);
  const d = points
    .map((p, i) => `${i === 0 ? "M" : "L"}${(i * step).toFixed(1)},${(h - (p.value / max) * h).toFixed(1)}`)
    .join(" ");
  return (
    <svg viewBox={`0 0 ${w} ${h}`} className="h-24 w-full">
      <path d={d} fill="none" stroke="rgba(34,211,238,0.9)" strokeWidth="2" />
    </svg>
  );
}
