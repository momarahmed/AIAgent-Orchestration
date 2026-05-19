"use client";

import { useEffect, useState } from "react";
import { securityScansApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

const SEVERITY_COLORS: Record<string, string> = {
  critical: "text-rose-400 bg-rose-500/10",
  high: "text-orange-400 bg-orange-500/10",
  medium: "text-amber-400 bg-amber-500/10",
  low: "text-slate-400 bg-slate-800",
};

export default function SecurityScansPage() {
  const auth = useAuth();
  const [scans, setScans] = useState<any[]>([]);
  const [summary, setSummary] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [selectedScan, setSelectedScan] = useState<any>(null);

  useEffect(() => {
    setLoading(true);
    Promise.all([
      securityScansApi.list({ tenant_id: auth.activeTenantId }).then((r: any) => setScans(r.data || [])),
      securityScansApi.summary({ tenant_id: auth.activeTenantId }).then(setSummary),
    ]).finally(() => setLoading(false));
  }, [auth.activeTenantId]);

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading scans...</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader title="Security Scanner" description="Container, dependency, and secret scanning pipeline. High-severity findings block staging/prod promotion." />

      {summary && (
        <div className="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-7">
          {[
            { label: "Total Scans", value: summary.total_scans, color: "text-cyan-400" },
            { label: "Blocking", value: summary.blocking_scans, color: "text-rose-400" },
            { label: "Critical", value: summary.total_critical, color: "text-rose-400" },
            { label: "High", value: summary.total_high, color: "text-orange-400" },
            { label: "Medium", value: summary.total_medium, color: "text-amber-400" },
            { label: "Low", value: summary.total_low, color: "text-slate-400" },
          ].map((s, i) => (
            <div key={i} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 text-center">
              <div className={`text-2xl font-bold ${s.color}`}>{s.value}</div>
              <div className="mt-1 text-xs text-slate-500">{s.label}</div>
            </div>
          ))}
        </div>
      )}

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div className="space-y-3">
          {scans.map((scan: any) => (
            <div key={scan.id} onClick={() => setSelectedScan(scan)}
              className={`cursor-pointer rounded-2xl border p-4 transition ${selectedScan?.id === scan.id ? "border-cyan-500/40 bg-cyan-500/5" : "border-slate-800 bg-slate-900/60 hover:border-slate-700"}`}>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className="text-sm font-semibold text-white">{scan.scan_type}</span>
                  <span className="rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-400">{scan.target_type}</span>
                  {scan.blocks_promotion && <span className="rounded bg-rose-500/10 px-2 py-0.5 text-xs text-rose-400">BLOCKS</span>}
                </div>
                <span className={`rounded px-2 py-0.5 text-xs ${scan.status === "completed" ? "bg-emerald-500/10 text-emerald-400" : "bg-amber-500/10 text-amber-400"}`}>{scan.status}</span>
              </div>
              {scan.severity_summary && <div className="mt-1 text-xs text-slate-400">{scan.severity_summary}</div>}
              <div className="mt-2 flex gap-2">
                {scan.critical_count > 0 && <span className="rounded bg-rose-500/10 px-2 py-0.5 text-xs text-rose-400">{scan.critical_count} critical</span>}
                {scan.high_count > 0 && <span className="rounded bg-orange-500/10 px-2 py-0.5 text-xs text-orange-400">{scan.high_count} high</span>}
                {scan.medium_count > 0 && <span className="rounded bg-amber-500/10 px-2 py-0.5 text-xs text-amber-400">{scan.medium_count} medium</span>}
              </div>
              <div className="mt-2 text-[10px] text-slate-600">{scan.target_ref} | {new Date(scan.created_at).toLocaleString()}</div>
            </div>
          ))}
          {scans.length === 0 && <div className="py-8 text-center text-sm text-slate-500">No security scans yet.</div>}
        </div>

        {selectedScan && (
          <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <div className="text-sm font-semibold text-white">Scan #{selectedScan.id} Findings</div>
            <div className="mt-4 max-h-[500px] space-y-2 overflow-auto">
              {(selectedScan.findings || []).map((f: any, i: number) => (
                <div key={i} className="rounded-xl border border-slate-800 bg-slate-950 p-3">
                  <div className="flex items-center gap-2">
                    <span className={`rounded px-2 py-0.5 text-xs ${SEVERITY_COLORS[f.severity] || ""}`}>{f.severity}</span>
                    <span className="text-xs font-medium text-white">{f.id || f.rule || f.type}</span>
                  </div>
                  <div className="mt-1 text-xs text-slate-400">{f.message}</div>
                  {f.package && <div className="mt-1 text-[10px] text-slate-500">{f.package} {f.version} {f.fixed_in ? `→ Fix: ${f.fixed_in}` : ""}</div>}
                </div>
              ))}
              {(!selectedScan.findings || selectedScan.findings.length === 0) && (
                <div className="py-4 text-center text-sm text-slate-500">No findings.</div>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
