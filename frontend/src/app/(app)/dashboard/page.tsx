"use client";

import dynamic from "next/dynamic";

// Mock-data dashboard sourced from /UI reference. Live KPIs are layered on
// top via the strip below using the real backend metrics endpoint.
const DashboardUI = dynamic(() => import("@/components/ui/EnterpriseAIMCPDashboard"), { ssr: false });

import { useQuery } from "@tanstack/react-query";
import { metricsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";

function LiveKpiStrip() {
  const auth = useAuth();
  const { data, isLoading } = useQuery({
    queryKey: ["metrics", "overview", auth.activeTenantId],
    queryFn: () => metricsApi.overview(auth.activeTenantId ?? undefined),
  });

  const k = data?.kpis ?? {};
  const items = [
    { label: "Agents",       value: k.agents ?? 0,         tone: "cyan"   },
    { label: "MCP Servers",  value: k.mcp_servers ?? 0,    tone: "violet" },
    { label: "Workflows",    value: k.workflows ?? 0,      tone: "blue"   },
    { label: "Runs (total)", value: k.runs_total ?? 0,     tone: "emerald" },
    { label: "Success rate", value: `${k.success_rate ?? 0}%`, tone: "lime" },
    { label: "Failures",     value: k.runs_failed ?? 0,    tone: "rose"   },
  ];

  return (
    <section className="border-b border-slate-800 bg-slate-950/80 px-4 py-4 lg:px-6">
      <div className="mb-2 flex items-center justify-between">
        <div>
          <div className="text-xs uppercase tracking-wider text-slate-500">Live Platform Metrics</div>
          <div className="text-sm font-semibold text-white">From the Enterprise AI MCP Backend</div>
        </div>
        {isLoading && <div className="text-xs text-slate-500">Refreshing…</div>}
      </div>
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        {items.map((it) => (
          <div key={it.label} className="rounded-2xl border border-slate-800 bg-slate-900/70 p-3">
            <div className="text-[11px] uppercase tracking-wider text-slate-500">{it.label}</div>
            <div className="mt-1 text-xl font-semibold text-white">{it.value}</div>
          </div>
        ))}
      </div>
    </section>
  );
}

export default function DashboardPage() {
  return (
    <div className="min-h-full">
      <LiveKpiStrip />
      <DashboardUI />
    </div>
  );
}
