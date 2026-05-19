"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { gitopsApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";

const DRIFT_TONE: Record<string, string> = {
  synced:          "border-emerald-500/40 bg-emerald-500/10 text-emerald-200",
  drift_detected:  "border-amber-500/40 bg-amber-500/10 text-amber-200",
  out_of_sync:     "border-rose-500/40 bg-rose-500/10 text-rose-200",
};

export default function GitOpsPage() {
  const qc = useQueryClient();
  const envs = useQuery({ queryKey: ["gitops-envs"], queryFn: () => gitopsApi.environments() });
  const regions = useQuery({ queryKey: ["gitops-regions"], queryFn: () => gitopsApi.regions() });

  const [primary, setPrimary] = useState("us-east-1");
  const [secondary, setSecondary] = useState("eu-west-1");

  const syncMut = useMutation({
    mutationFn: (id: number) => gitopsApi.sync(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["gitops-envs"] }),
  });
  const driftMut = useMutation({
    mutationFn: (id: number) => gitopsApi.drift(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["gitops-envs"] }),
  });
  const drillMut = useMutation({
    mutationFn: () => gitopsApi.failoverDrill({ primary_region: primary, secondary_region: secondary }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["gitops-regions"] }),
  });

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 5 · GitOps + HA/DR"
        title="GitOps environments"
        description="Argo CD / Flux–reconciled environments, drift detection, regional failover."
      />

      <section className="grid gap-4 px-1 lg:grid-cols-3">
        {((envs.data as any)?.data ?? []).map((env: any) => (
          <div key={env.id} className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
            <div className="flex items-center justify-between">
              <div>
                <div className="text-sm font-semibold text-white">{env.name}</div>
                <div className="text-xs text-slate-500">{env.engine} · {env.environment_class}</div>
              </div>
              <span className={`rounded-full border px-2 py-0.5 text-[10px] uppercase ${DRIFT_TONE[env.drift_state] ?? DRIFT_TONE.synced}`}>
                {env.drift_state}
              </span>
            </div>
            <div className="mt-3 grid grid-cols-2 gap-2 text-xs text-slate-400">
              <div>Branch: <span className="text-slate-200">{env.branch}</span></div>
              <div>Auto-sync: <span className="text-slate-200">{env.auto_sync ? "on" : "off"}</span></div>
              <div>NS: <span className="text-slate-200">{env.namespace ?? "—"}</span></div>
              <div>Commit: <span className="font-mono text-slate-200">{env.last_commit_sha ?? "—"}</span></div>
            </div>
            <div className="mt-4 flex gap-2 text-xs">
              <button onClick={() => syncMut.mutate(env.id)} className="rounded-lg bg-cyan-500/30 px-3 py-1 text-cyan-100 hover:bg-cyan-500/40">
                Sync
              </button>
              <button onClick={() => driftMut.mutate(env.id)} className="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1 text-slate-200 hover:bg-slate-700">
                Detect drift
              </button>
            </div>
          </div>
        ))}
      </section>

      <section className="grid gap-4 px-1 lg:grid-cols-[2fr_1fr]">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">Region health</h3>
          <table className="mt-3 min-w-full divide-y divide-slate-800 text-sm">
            <thead className="text-xs uppercase tracking-wider text-slate-400">
              <tr>
                <th className="px-3 py-2 text-left">Region</th>
                <th className="px-3 py-2 text-left">Role</th>
                <th className="px-3 py-2 text-left">Status</th>
                <th className="px-3 py-2 text-left">Repl lag (s)</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-900 text-slate-300">
              {((regions.data as any)?.data ?? []).map((r: any) => (
                <tr key={r.id}>
                  <td className="px-3 py-2">{r.region}</td>
                  <td className="px-3 py-2 capitalize">{r.role}</td>
                  <td className="px-3 py-2">
                    <span className={`rounded-full border px-2 py-0.5 text-[10px] uppercase ${r.status === "healthy" ? "border-emerald-500/40 bg-emerald-500/10 text-emerald-200" : "border-amber-500/40 bg-amber-500/10 text-amber-200"}`}>
                      {r.status}
                    </span>
                  </td>
                  <td className="px-3 py-2 text-xs">{Number(r.replication_lag_seconds).toFixed(1)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <div className="rounded-2xl border border-cyan-500/30 bg-cyan-500/5 p-5">
          <h3 className="text-sm font-semibold text-cyan-200">Failover drill</h3>
          <div className="mt-3 space-y-2 text-sm text-cyan-100">
            <input value={primary} onChange={(e) => setPrimary(e.target.value)} className="w-full rounded-lg border border-cyan-400/30 bg-slate-900/70 px-3 py-2 text-white" />
            <input value={secondary} onChange={(e) => setSecondary(e.target.value)} className="w-full rounded-lg border border-cyan-400/30 bg-slate-900/70 px-3 py-2 text-white" />
            <button onClick={() => drillMut.mutate()} className="w-full rounded-lg bg-cyan-500/30 px-3 py-2 hover:bg-cyan-500/40">
              {drillMut.isPending ? "Running drill…" : "Run failover drill"}
            </button>
            {drillMut.data && (
              <pre className="mt-2 overflow-x-auto rounded-lg bg-slate-950/70 p-3 text-[10px] text-cyan-100">
                {JSON.stringify(drillMut.data, null, 2)}
              </pre>
            )}
          </div>
        </div>
      </section>
    </div>
  );
}
