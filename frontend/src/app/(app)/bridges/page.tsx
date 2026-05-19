"use client";

import { useEffect, useState } from "react";
import { bridgesApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge } from "@/components/shared/PageHeader";

export default function BridgesPage() {
  const auth = useAuth();
  const [frameworks, setFrameworks] = useState<string[]>([]);
  const [connections, setConnections] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [call, setCall] = useState({ id: 0, action: "invoke", payload: '{"input":"hello"}' });
  const [result, setResult] = useState<any>(null);

  const load = () => {
    setLoading(true);
    Promise.all([
      bridgesApi.frameworks().then(setFrameworks),
      bridgesApi.connections({ tenant_id: auth.activeTenantId }).then(setConnections),
    ]).finally(() => setLoading(false));
  };
  useEffect(() => { if (auth.activeTenantId) load(); }, [auth.activeTenantId]);

  const create = async () => {
    const framework = prompt("Framework (dify, flowise, sim, crewai)?"); if (!framework) return;
    const name = prompt("Name?") || `${framework} bridge`;
    const endpoint_url = prompt("Endpoint URL?") || "";
    await bridgesApi.createConnection({ tenant_id: auth.activeTenantId, framework, name, endpoint_url, enabled: true });
    load();
  };

  const toggle = async (c: any) => { await bridgesApi.toggle(c.id, !c.enabled); load(); };

  const send = async () => {
    try {
      const input = JSON.parse(call.payload);
      const r = await bridgesApi.call(call.id, { action: call.action, input });
      setResult(r);
    } catch (e: any) { setResult({ error: e?.message }); }
  };

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading bridges…</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader
        title="Cross-Framework Bridges"
        description="First-class adapters to call existing Dify, Flowise, SIM and CrewAI workflows from the platform."
        actions={<button onClick={create} className="rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30">+ Connection</button>}
      />

      <section className="flex flex-wrap gap-2">
        {frameworks.map((f) => (
          <span key={f} className="rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-xs uppercase tracking-wider text-slate-300">{f}</span>
        ))}
      </section>

      <section className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
        {connections.map((c) => (
          <div key={c.id} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <div className="flex items-center justify-between">
              <div>
                <div className="text-sm font-semibold text-white">{c.name}</div>
                <div className="text-xs text-slate-500">{c.framework} · {c.endpoint_url || "no endpoint"}</div>
              </div>
              <StatusBadge value={c.enabled ? "healthy" : "degraded"} />
            </div>
            <div className="mt-3 flex gap-2">
              <button onClick={() => toggle(c)} className="rounded-lg bg-slate-800 px-3 py-1 text-xs text-slate-200 hover:bg-slate-700">{c.enabled ? "Disable" : "Enable"}</button>
              <button onClick={() => setCall({ ...call, id: c.id })} className="rounded-lg bg-violet-500/20 px-3 py-1 text-xs text-violet-200">Use</button>
            </div>
          </div>
        ))}
      </section>

      {call.id > 0 && (
        <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
          <h3 className="text-sm font-semibold text-white">Call bridge #{call.id}</h3>
          <input className="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" placeholder="action (e.g. invoke, run, complete)" value={call.action} onChange={(e) => setCall({ ...call, action: e.target.value })} />
          <textarea className="mt-2 h-32 w-full rounded-xl border border-slate-800 bg-slate-950 p-3 font-mono text-xs" value={call.payload} onChange={(e) => setCall({ ...call, payload: e.target.value })} />
          <button onClick={send} className="mt-2 rounded-xl bg-emerald-500/20 px-3 py-2 text-xs font-semibold text-emerald-200">Send</button>
          {result && <pre className="mt-3 overflow-auto rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-cyan-200">{JSON.stringify(result, null, 2)}</pre>}
        </section>
      )}
    </div>
  );
}
