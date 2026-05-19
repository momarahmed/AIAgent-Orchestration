"use client";

import { useEffect, useState } from "react";
import { observabilityApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

export default function ObservabilityPage() {
  const auth = useAuth();
  const [metrics, setMetrics] = useState<Record<string, any>>({});
  const [kg, setKg] = useState<{ nodes: any[]; edges: any[] }>({ nodes: [], edges: [] });
  const [runId, setRunId] = useState<number | null>(null);
  const [timeline, setTimeline] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [replayResult, setReplayResult] = useState<any>(null);

  const load = () => {
    Promise.all([
      observabilityApi.metrics().then((r) => setMetrics(r.metrics || r)).catch(() => null),
      auth.activeTenantId
        ? observabilityApi.knowledgeGraph(auth.activeTenantId).then(setKg).catch(() => null)
        : Promise.resolve(),
    ]).finally(() => setLoading(false));
  };
  useEffect(() => { setLoading(true); load(); const t = setInterval(load, 15000); return () => clearInterval(t); }, [auth.activeTenantId]);

  const loadTimeline = async (id: number) => {
    setRunId(id);
    const r = await observabilityApi.timeline(id).catch(() => []);
    setTimeline(r);
  };
  const doReplay = async () => {
    if (!runId) return;
    const r = await observabilityApi.replay(runId, { pin_versions: true });
    setReplayResult(r);
  };

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading observability…</div>;

  const metricEntries = Object.entries(metrics || {}).slice(0, 16);

  return (
    <div className="space-y-6 p-6">
      <PageHeader
        title="Advanced Observability"
        description="OpenTelemetry traces, Prometheus metrics, Loki logs, knowledge-graph topology and run replay — all stitched into the platform."
      />

      <section className="grid grid-cols-2 gap-3 md:grid-cols-4">
        {metricEntries.length === 0 && (
          <div className="md:col-span-4 rounded-2xl border border-slate-800 bg-slate-900/40 p-5 text-sm text-slate-400">
            Prometheus metrics will populate as workflows run. The scrape endpoint is at <code className="text-cyan-300">/api/observability/metrics</code> (text format).
          </div>
        )}
        {metricEntries.map(([k, v]) => (
          <div key={k} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <div className="text-[11px] uppercase tracking-wider text-slate-500">{k}</div>
            <div className="mt-1 text-xl font-semibold text-white">{typeof v === "number" ? v.toFixed(2) : String(v)}</div>
          </div>
        ))}
      </section>

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-white">Run replay</h3>
        <div className="mt-3 flex flex-wrap items-center gap-3">
          <input type="number" placeholder="Run ID" className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" onChange={(e) => loadTimeline(parseInt(e.target.value, 10))} />
          <button onClick={doReplay} className="rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30" disabled={!runId}>Replay (pin versions)</button>
        </div>

        {timeline.length > 0 && (
          <ul className="mt-3 space-y-1 text-sm">
            {timeline.map((s: any, i: number) => (
              <li key={i} className="rounded-lg border border-slate-800 bg-slate-950/40 p-2 text-slate-300">
                <span className="text-cyan-300">{s.t_ms}ms</span> · {s.event} <span className="text-xs text-slate-500">{s.detail}</span>
              </li>
            ))}
          </ul>
        )}

        {replayResult && <pre className="mt-3 overflow-auto rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-emerald-300">{JSON.stringify(replayResult, null, 2)}</pre>}
      </section>

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-white">Knowledge graph ({kg.nodes?.length || 0} nodes · {kg.edges?.length || 0} edges)</h3>
        <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <div className="text-xs uppercase text-slate-500">Nodes</div>
            <ul className="mt-2 max-h-72 space-y-1 overflow-auto text-sm">
              {(kg.nodes || []).map((n: any) => (
                <li key={n.id} className="rounded-lg border border-slate-800 bg-slate-950/40 p-2 text-slate-300">
                  <span className="text-cyan-300">{n.node_type}</span> · {n.label} {n.ref_type && <span className="text-xs text-slate-500"> ({n.ref_type}#{n.ref_id})</span>}
                </li>
              ))}
            </ul>
          </div>
          <div>
            <div className="text-xs uppercase text-slate-500">Edges</div>
            <ul className="mt-2 max-h-72 space-y-1 overflow-auto text-sm">
              {(kg.edges || []).map((e: any) => (
                <li key={e.id} className="rounded-lg border border-slate-800 bg-slate-950/40 p-2 text-slate-300">{e.from_node_id} —<span className="text-cyan-300"> {e.relation} </span>→ {e.to_node_id}</li>
              ))}
            </ul>
          </div>
        </div>
      </section>
    </div>
  );
}
