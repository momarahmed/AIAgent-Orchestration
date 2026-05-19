"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { continuousSecurityApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";

const RISK_TONE: Record<string, string> = {
  critical: "border-rose-500/50 bg-rose-500/15 text-rose-200",
  high:     "border-orange-500/50 bg-orange-500/15 text-orange-200",
  medium:   "border-amber-500/50 bg-amber-500/15 text-amber-200",
  low:      "border-emerald-500/40 bg-emerald-500/10 text-emerald-200",
};

const STATE_TONE: Record<string, string> = {
  open:           "border-rose-500/40 bg-rose-500/10 text-rose-200",
  accepted_risk:  "border-amber-500/40 bg-amber-500/10 text-amber-200",
  fixed:          "border-emerald-500/40 bg-emerald-500/10 text-emerald-200",
  false_positive: "border-slate-600 bg-slate-700 text-slate-200",
};

export default function ContinuousSecurityPage() {
  const qc = useQueryClient();
  const [image, setImage] = useState("ghcr.io/eamcp/backend:latest");

  const snapshots = useQuery({ queryKey: ["sbom-snapshots", image], queryFn: () => continuousSecurityApi.snapshots({ image_ref: image }) });
  const diffs = useQuery({ queryKey: ["sbom-diffs", image], queryFn: () => continuousSecurityApi.diffs({ image_ref: image }) });
  const findings = useQuery({ queryKey: ["vuln-findings"], queryFn: () => continuousSecurityApi.findings() });
  const dueDates = useQuery({ queryKey: ["vuln-due"], queryFn: () => continuousSecurityApi.dueDates() });

  const takeSnap = useMutation({
    mutationFn: () => continuousSecurityApi.takeSnapshot(image),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["sbom-snapshots"] }),
  });

  const transition = useMutation({
    mutationFn: ({ id, state }: { id: number; state: string }) =>
      continuousSecurityApi.transitionFinding(id, { state }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["vuln-findings"] }),
  });

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 5 · Security"
        title="Continuous Security Scanning"
        description="SBOM snapshots, diff alerts, and vulnerability lifecycle tracking."
        actions={
          <div className="flex items-center gap-2">
            <input value={image} onChange={(e) => setImage(e.target.value)} className="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-white" />
            <button onClick={() => takeSnap.mutate()} className="rounded-lg bg-cyan-500/30 px-3 py-2 text-sm text-cyan-100 hover:bg-cyan-500/40">
              Snapshot
            </button>
          </div>
        }
      />

      <section className="grid grid-cols-1 gap-4 px-1 md:grid-cols-2 xl:grid-cols-5">
        {Object.entries((dueDates.data as any) ?? {}).map(([k, v]) => (
          <div key={k} className="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
            <div className="text-[10px] uppercase tracking-wider text-slate-400">{k}</div>
            <div className="mt-1 text-2xl font-semibold text-white">{String(v)}</div>
          </div>
        ))}
      </section>

      <section className="grid gap-4 px-1 lg:grid-cols-2">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">SBOM snapshots</h3>
          <ul className="mt-3 divide-y divide-slate-800 text-sm">
            {((snapshots.data as any)?.data ?? []).map((s: any) => (
              <li key={s.id} className="flex items-center justify-between py-2">
                <span className="font-mono text-xs text-slate-400 truncate max-w-[60%]">{s.sbom_hash.slice(0, 16)}…</span>
                <span className="text-slate-300">{s.total_packages} pkgs</span>
              </li>
            ))}
          </ul>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">SBOM diffs</h3>
          <ul className="mt-3 space-y-2 text-sm">
            {((diffs.data as any)?.data ?? []).map((d: any) => (
              <li key={d.id} className={`rounded-xl border p-3 ${RISK_TONE[d.risk_level] ?? RISK_TONE.low}`}>
                <div className="flex items-center justify-between">
                  <span className="font-semibold uppercase tracking-wider text-xs">{d.risk_level}</span>
                  <span className="text-xs opacity-80">{new Date(d.created_at).toLocaleString()}</span>
                </div>
                <div className="mt-2 text-xs">+{d.diff?.added?.length ?? 0} added · ↻{d.diff?.changed?.length ?? 0} changed · −{d.diff?.removed?.length ?? 0} removed</div>
              </li>
            ))}
          </ul>
        </div>
      </section>

      <section className="px-1">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">Vulnerability findings</h3>
          <div className="mt-3 overflow-hidden rounded-xl border border-slate-800">
            <table className="min-w-full divide-y divide-slate-800 text-sm">
              <thead className="bg-slate-900/60 text-xs uppercase tracking-wider text-slate-400">
                <tr>
                  <th className="px-3 py-2 text-left">CVE</th>
                  <th className="px-3 py-2 text-left">Package</th>
                  <th className="px-3 py-2 text-left">Severity</th>
                  <th className="px-3 py-2 text-left">State</th>
                  <th className="px-3 py-2 text-left">Deadline</th>
                  <th className="px-3 py-2 text-left"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-900 bg-slate-950/30 text-slate-300">
                {((findings.data as any)?.data ?? []).map((f: any) => (
                  <tr key={f.id}>
                    <td className="px-3 py-2 font-mono text-xs">{f.cve}</td>
                    <td className="px-3 py-2">{f.package} <span className="text-xs text-slate-500">{f.installed_version} → {f.fixed_version ?? "—"}</span></td>
                    <td className="px-3 py-2">
                      <span className={`rounded-full border px-2 py-0.5 text-[10px] uppercase ${RISK_TONE[f.severity] ?? RISK_TONE.low}`}>{f.severity}</span>
                    </td>
                    <td className="px-3 py-2">
                      <span className={`rounded-full border px-2 py-0.5 text-[10px] uppercase ${STATE_TONE[f.state] ?? STATE_TONE.open}`}>{f.state}</span>
                    </td>
                    <td className="px-3 py-2 text-xs text-slate-400">{f.deadline}</td>
                    <td className="px-3 py-2">
                      {f.state === "open" && (
                        <div className="flex gap-2 text-[10px]">
                          <button onClick={() => transition.mutate({ id: f.id, state: "accepted_risk" })} className="rounded border border-amber-500/40 bg-amber-500/10 px-2 py-1 text-amber-200">Accept risk</button>
                          <button onClick={() => transition.mutate({ id: f.id, state: "fixed" })} className="rounded border border-emerald-500/40 bg-emerald-500/10 px-2 py-1 text-emerald-200">Fixed</button>
                          <button onClick={() => transition.mutate({ id: f.id, state: "false_positive" })} className="rounded border border-slate-600 bg-slate-800 px-2 py-1 text-slate-200">FP</button>
                        </div>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  );
}
