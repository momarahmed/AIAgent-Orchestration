"use client";

import { useEffect, useState } from "react";
import { modelsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

export default function ModelsPage() {
  const auth = useAuth();
  const [models, setModels] = useState<any[]>([]);
  const [rules, setRules] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [planResult, setPlanResult] = useState<any>(null);
  const [planQuery, setPlanQuery] = useState({ capability: "chat", tag: "", residency: "" });

  const load = () =>
    Promise.all([
      modelsApi.list(auth.activeTenantId || undefined).then(setModels),
      modelsApi.rules().then(setRules),
    ]).finally(() => setLoading(false));

  useEffect(() => {
    setLoading(true);
    load();
  }, [auth.activeTenantId]);

  const runPlan = async () => {
    const req: Record<string, any> = {
      capabilities: planQuery.capability.split(",").map((c) => c.trim()).filter(Boolean),
      tenant_id: auth.activeTenantId,
    };
    if (planQuery.tag) req.tag = planQuery.tag;
    if (planQuery.residency) req.residency = planQuery.residency;
    const r = await modelsApi.plan(req);
    setPlanResult(r);
  };

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading model catalog…</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader title="Model Control Plane" description="Unified catalog of cloud + local LLMs with deterministic routing rules, cost estimates and fallbacks." />

      <section className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {models.map((m) => (
          <div key={m.id} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <div className="flex items-center justify-between">
              <div className="text-sm font-semibold text-white">{m.name}</div>
              <span className="rounded bg-slate-800 px-2 py-0.5 text-[10px] uppercase text-slate-400">{m.provider}</span>
            </div>
            <div className="mt-1 text-xs text-slate-500">{m.slug}</div>
            <div className="mt-3 flex flex-wrap gap-1">
              {(m.capabilities || []).map((c: string) => (
                <span key={c} className="rounded-full border border-slate-700 px-2 py-0.5 text-[10px] text-slate-300">{c}</span>
              ))}
            </div>
            <div className="mt-4 grid grid-cols-3 gap-2 text-[11px] text-slate-400">
              <div><span className="block text-slate-500">In/1k</span>${Number(m.cost_per_1k_in).toFixed(4)}</div>
              <div><span className="block text-slate-500">Out/1k</span>${Number(m.cost_per_1k_out).toFixed(4)}</div>
              <div><span className="block text-slate-500">p50</span>{m.latency_p50_ms}ms</div>
            </div>
            {m.is_local && <div className="mt-3 inline-flex rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] text-emerald-300">Local · Data-residency safe</div>}
          </div>
        ))}
      </section>

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h2 className="text-sm font-semibold text-white">Routing Rules</h2>
        <p className="text-xs text-slate-500">Highest priority wins. Rules can match capability, tag, residency or tenant.</p>
        <div className="mt-3 space-y-2">
          {rules.map((r) => (
            <div key={r.id} className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-800 bg-slate-950/40 p-3">
              <div>
                <div className="text-sm font-medium text-white">{r.name}</div>
                <div className="text-xs text-slate-500">priority {r.priority} · match {JSON.stringify(r.match)}</div>
              </div>
              <div className="text-xs text-cyan-300">→ {r.route?.primary_model_slug}</div>
            </div>
          ))}
        </div>
      </section>

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h2 className="text-sm font-semibold text-white">Plan a route</h2>
        <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-4">
          <input className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" value={planQuery.capability} onChange={(e) => setPlanQuery({ ...planQuery, capability: e.target.value })} placeholder="capability (chat,tools)" />
          <input className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" value={planQuery.tag} onChange={(e) => setPlanQuery({ ...planQuery, tag: e.target.value })} placeholder="tag (e.g. reasoning)" />
          <input className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" value={planQuery.residency} onChange={(e) => setPlanQuery({ ...planQuery, residency: e.target.value })} placeholder="residency (local-only)" />
          <button onClick={runPlan} className="rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30">Plan</button>
        </div>
        {planResult && (
          <pre className="mt-3 overflow-auto rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-emerald-300">
            {JSON.stringify(planResult, null, 2)}
          </pre>
        )}
      </section>
    </div>
  );
}
