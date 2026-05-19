"use client";

import { useEffect, useState } from "react";
import { metaAgentsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function MetaAgentsPage() {
  const auth = useAuth();
  const [registry, setRegistry] = useState<any[]>([]);
  const [runs, setRuns] = useState<any[]>([]);
  const [selectedRun, setSelectedRun] = useState<any | null>(null);
  const [loading, setLoading] = useState(true);
  const [prompt, setPrompt] = useState("Build me a Saudi GIS investigation agent that queries Postgres and PostGIS.");
  const [autopilot, setAutopilot] = useState(false);

  const load = () => {
    setLoading(true);
    Promise.all([
      metaAgentsApi.registry().then(setRegistry),
      metaAgentsApi.list({ tenant_id: auth.activeTenantId, limit: 20 }).then(setRuns),
    ]).finally(() => setLoading(false));
  };
  useEffect(() => { if (auth.activeTenantId) load(); }, [auth.activeTenantId]);

  const start = async () => {
    if (autopilot) {
      const r = await metaAgentsApi.autopilot({ tenant_id: auth.activeTenantId!, prompt });
      setSelectedRun(r.run || r);
    } else {
      const intent = await metaAgentsApi.route(prompt);
      const meta = intent?.target && intent.target !== "chat" ? intent.target : "platform_architect";
      const r = await metaAgentsApi.start({ tenant_id: auth.activeTenantId!, meta_agent: meta, prompt, autonomous: false });
      setSelectedRun(r);
    }
    load();
  };

  const resume = async (id: number) => {
    await metaAgentsApi.resume(id);
    metaAgentsApi.show(id).then(setSelectedRun);
    load();
  };

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading meta-agents…</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader
        title="Meta-Agents"
        description="11 productionized meta-agents (Architect, AgentBuilder, McpBuilder, WorkflowBuilder, Template, QA, Security, DevOps, Docs, Migration, Governance) coordinated by an Intent Router."
      />

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-white">Start a build</h3>
        <textarea className="mt-3 h-24 w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-sm" value={prompt} onChange={(e) => setPrompt(e.target.value)} />
        <div className="mt-2 flex items-center gap-3">
          <label className="flex items-center gap-2 text-xs text-slate-400">
            <input type="checkbox" checked={autopilot} onChange={(e) => setAutopilot(e.target.checked)} /> Autopilot (auto-resolve approvals where policy permits)
          </label>
          <button onClick={start} className="ml-auto rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30">Run</button>
        </div>
      </section>

      <section className="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
        {registry.map((r) => (
          <div key={r.kind} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <div className="text-sm font-semibold text-white">{r.kind}</div>
            <div className="text-xs text-slate-500">{r.description}</div>
          </div>
        ))}
      </section>

      {runs.length === 0 ? (
        <EmptyState title="No meta-agent runs yet" description="Kick off a build above to see the orchestrator plan, execute and request approvals." />
      ) : (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-[300px_1fr]">
          <aside className="space-y-2">
            {runs.map((r) => (
              <button key={r.id} onClick={() => metaAgentsApi.show(r.id).then(setSelectedRun)} className={`w-full rounded-xl border px-3 py-2 text-left text-sm ${selectedRun?.id === r.id ? "border-cyan-500 bg-cyan-500/10 text-white" : "border-slate-800 bg-slate-900/40 text-slate-300 hover:bg-slate-900"}`}>
                <div className="flex items-center justify-between">
                  <div className="font-medium">{r.meta_agent}</div>
                  <StatusBadge value={r.status} />
                </div>
                <div className="text-xs text-slate-500">{new Date(r.created_at).toLocaleString()}</div>
              </button>
            ))}
          </aside>

          {selectedRun && (
            <section className="space-y-4">
              <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <div className="flex items-center justify-between">
                  <div>
                    <h2 className="text-lg font-semibold text-white">{selectedRun.meta_agent}</h2>
                    <div className="text-xs text-slate-500">run #{selectedRun.id} · {selectedRun.status}</div>
                  </div>
                  <StatusBadge value={selectedRun.status} />
                </div>
                <div className="mt-3 text-xs text-slate-400">Prompt: {selectedRun.prompt}</div>
                {(selectedRun.status === "awaiting_approval" || selectedRun.status === "paused") && (
                  <button onClick={() => resume(selectedRun.id)} className="mt-3 rounded-xl bg-amber-500/20 px-3 py-2 text-xs font-semibold text-amber-200 hover:bg-amber-500/30">Resume after approval</button>
                )}
              </div>

              <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h3 className="text-sm font-semibold text-white">Plan</h3>
                <ol className="mt-2 space-y-1 text-sm text-slate-300">
                  {(selectedRun.plan || []).map((s: any, i: number) => (
                    <li key={i} className="rounded-lg border border-slate-800 bg-slate-950/40 p-2"><span className="text-cyan-300">{i + 1}.</span> {s.label || s.title || s.action} <span className="text-xs text-slate-500">{s.summary}</span></li>
                  ))}
                </ol>
              </div>

              <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h3 className="text-sm font-semibold text-white">Actions</h3>
                <ul className="mt-2 space-y-2 text-sm">
                  {(selectedRun.actions || []).map((a: any) => (
                    <li key={a.id} className="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
                      <div className="flex items-center justify-between">
                        <div className="text-white">{a.action}</div>
                        <StatusBadge value={a.status} />
                      </div>
                      {a.target_type && <div className="text-xs text-slate-500">{a.target_type} #{a.target_id}</div>}
                      {a.output && <pre className="mt-2 max-h-40 overflow-auto rounded-lg bg-slate-950 p-2 text-[11px] text-emerald-300">{JSON.stringify(a.output, null, 2)}</pre>}
                    </li>
                  ))}
                </ul>
              </div>
            </section>
          )}
        </div>
      )}
    </div>
  );
}
