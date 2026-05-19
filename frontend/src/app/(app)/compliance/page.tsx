"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { complianceApi, api } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";

export default function CompliancePage() {
  const qc = useQueryClient();
  const frameworks = useQuery({ queryKey: ["compliance-frameworks"], queryFn: () => complianceApi.frameworks() });
  const exports = useQuery({ queryKey: ["compliance-exports"], queryFn: () => complianceApi.exports() });

  const [selected, setSelected] = useState<string[]>(["SOC2"]);
  const [start, setStart] = useState(new Date(Date.now() - 90 * 24 * 3600 * 1000).toISOString().slice(0, 10));
  const [end, setEnd] = useState(new Date().toISOString().slice(0, 10));
  const [format, setFormat] = useState<"zip" | "pdf" | "csv" | "jsonl">("zip");

  const create = useMutation({
    mutationFn: () =>
      complianceApi.create({ frameworks: selected, period_start: start, period_end: end, format }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["compliance-exports"] }),
  });

  const toggle = (code: string) => setSelected((s) => (s.includes(code) ? s.filter((x) => x !== code) : [...s, code]));

  const download = async (id: number) => {
    const blob = await complianceApi.download(id);
    const url = URL.createObjectURL(blob.data);
    const a = document.createElement("a");
    a.href = url;
    a.download = `eamcp-compliance-${id}.zip`;
    a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 5 · Compliance"
        title="Audit evidence packs"
        description="One-click SOC 2, ISO 27001, GDPR evidence — exportable in PDF, CSV, JSON Lines, ZIP."
      />

      <section className="grid gap-4 px-1 lg:grid-cols-[1fr_2fr]">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">New evidence pack</h3>
          <div className="mt-3 space-y-3 text-sm">
            <div className="flex flex-wrap gap-2">
              {((frameworks.data as any)?.data ?? []).map((f: any) => (
                <button
                  key={f.code}
                  onClick={() => toggle(f.code)}
                  className={`rounded-full border px-3 py-1 text-xs ${
                    selected.includes(f.code)
                      ? "border-cyan-500/50 bg-cyan-500/20 text-cyan-100"
                      : "border-slate-700 bg-slate-800 text-slate-300"
                  }`}
                >
                  {f.code} · {f.name}
                </button>
              ))}
            </div>
            <div className="grid grid-cols-2 gap-2">
              <label className="text-xs text-slate-400">
                Period start
                <input type="date" value={start} onChange={(e) => setStart(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-white" />
              </label>
              <label className="text-xs text-slate-400">
                Period end
                <input type="date" value={end} onChange={(e) => setEnd(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-white" />
              </label>
            </div>
            <select value={format} onChange={(e) => setFormat(e.target.value as any)} className="w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-white">
              <option value="zip">ZIP (all files)</option>
              <option value="pdf">PDF (auditor-ready summary)</option>
              <option value="csv">CSV (audit events)</option>
              <option value="jsonl">JSON Lines (audit events)</option>
            </select>
            <button
              onClick={() => create.mutate()}
              disabled={create.isPending || selected.length === 0}
              className="w-full rounded-lg bg-emerald-500/30 px-3 py-2 text-sm text-emerald-100 hover:bg-emerald-500/40 disabled:opacity-60"
            >
              {create.isPending ? "Generating…" : "Generate evidence pack"}
            </button>
          </div>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">Recent exports</h3>
          <div className="mt-3 overflow-hidden rounded-xl border border-slate-800">
            <table className="min-w-full divide-y divide-slate-800 text-sm">
              <thead className="bg-slate-900/60 text-xs uppercase tracking-wider text-slate-400">
                <tr>
                  <th className="px-3 py-2 text-left">ID</th>
                  <th className="px-3 py-2 text-left">Frameworks</th>
                  <th className="px-3 py-2 text-left">Period</th>
                  <th className="px-3 py-2 text-left">Status</th>
                  <th className="px-3 py-2 text-left">Format</th>
                  <th className="px-3 py-2 text-left"></th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-900 bg-slate-950/30 text-slate-300">
                {((exports.data as any)?.data ?? []).map((e: any) => (
                  <tr key={e.id}>
                    <td className="px-3 py-2">#{e.id}</td>
                    <td className="px-3 py-2 text-xs">{(e.frameworks ?? []).join(", ")}</td>
                    <td className="px-3 py-2 text-xs">{e.period_start} → {e.period_end}</td>
                    <td className="px-3 py-2">
                      <span className={`rounded-full border px-2 py-0.5 text-[10px] ${e.status === "ready" ? "border-emerald-500/40 bg-emerald-500/10 text-emerald-200" : "border-amber-500/40 bg-amber-500/10 text-amber-200"}`}>{e.status}</span>
                    </td>
                    <td className="px-3 py-2 text-xs">{e.format}</td>
                    <td className="px-3 py-2 text-right">
                      {e.status === "ready" && (
                        <button onClick={() => download(e.id)} className="text-xs text-cyan-300 hover:text-cyan-100">
                          download
                        </button>
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
