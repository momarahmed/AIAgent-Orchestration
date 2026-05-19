"use client";

import { useEffect, useState } from "react";
import { a2aApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function A2APage() {
  const auth = useAuth();
  const [partners, setPartners] = useState<any[]>([]);
  const [messages, setMessages] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [send, setSend] = useState<{ partner_id: number | ""; to_agent: string; payload: string }>({ partner_id: "", to_agent: "", payload: '{"question":"sample"}' });

  const load = () => {
    setLoading(true);
    Promise.all([
      a2aApi.partners({ tenant_id: auth.activeTenantId }).then(setPartners),
      a2aApi.messages({ tenant_id: auth.activeTenantId, limit: 25 }).then(setMessages),
    ]).finally(() => setLoading(false));
  };
  useEffect(() => { if (auth.activeTenantId) load(); }, [auth.activeTenantId]);

  const create = async () => {
    const name = prompt("Partner display name?"); if (!name) return;
    const partner_id = prompt("Stable partner ID (slug)?") || name.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    const endpoint_url = prompt("Endpoint URL?") || "";
    await a2aApi.createPartner({ tenant_id: auth.activeTenantId, name, partner_id, endpoint_url, framework: "langgraph", auth_type: "hmac", capabilities: ["investigation"] });
    load();
  };

  const sendMessage = async () => {
    try {
      if (!send.partner_id) return alert("Pick a partner");
      const payload = JSON.parse(send.payload);
      await a2aApi.send({ partner_id: Number(send.partner_id), to_agent: send.to_agent || undefined, payload });
      load();
    } catch (e: any) { alert(e?.message || "send failed"); }
  };

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading A2A gateway…</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader
        title="A2A Gateway"
        description="Inter-agent A2A Protocol federation across frameworks (LangGraph, Dify, Flowise, SIM, CrewAI). HMAC-signed, OPA-authorized, injection-scanned."
        actions={<button onClick={create} className="rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30">+ Add Partner</button>}
      />

      {partners.length === 0 ? (
        <EmptyState title="No partners yet" description="Register an external agent partner to enable signed cross-platform A2A messaging." />
      ) : (
        <section className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
          {partners.map((p) => (
            <div key={p.id} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
              <div className="flex items-center justify-between">
                <div>
                  <div className="text-sm font-semibold text-white">{p.name}</div>
                  <div className="text-xs text-slate-500">{p.partner_id} · {p.framework}</div>
                </div>
                <StatusBadge value={p.status} />
              </div>
              <div className="mt-2 text-xs text-slate-400">{p.endpoint_url}</div>
              <div className="mt-2 flex flex-wrap gap-1">
                {(p.capabilities || []).map((c: string) => <span key={c} className="rounded-full border border-slate-700 px-2 py-0.5 text-[10px] text-slate-300">{c}</span>)}
              </div>
            </div>
          ))}
        </section>
      )}

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-white">Send A2A Message</h3>
        <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-4">
          <select className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" value={send.partner_id} onChange={(e) => setSend({ ...send, partner_id: e.target.value ? Number(e.target.value) : "" })}>
            <option value="">Pick partner…</option>
            {partners.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
          </select>
          <input className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" placeholder="to_agent (optional)" value={send.to_agent} onChange={(e) => setSend({ ...send, to_agent: e.target.value })} />
          <textarea className="md:col-span-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 font-mono text-xs" value={send.payload} onChange={(e) => setSend({ ...send, payload: e.target.value })} />
          <button onClick={sendMessage} className="rounded-xl bg-violet-500/20 px-3 py-2 text-sm font-semibold text-violet-200 hover:bg-violet-500/30 md:col-span-4">Send</button>
        </div>
      </section>

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-white">Recent messages</h3>
        <table className="mt-3 w-full text-left text-sm">
          <thead className="text-xs uppercase text-slate-500"><tr><th className="px-2 py-2">Direction</th><th>Partner</th><th>Type</th><th>Status</th><th>When</th></tr></thead>
          <tbody>
            {messages.map((m) => (
              <tr key={m.id} className="border-t border-slate-800 text-slate-300">
                <td className="px-2 py-2">{m.direction}</td>
                <td className="px-2">#{m.a2_a_partner_id ?? m.partner_id}</td>
                <td className="px-2">{m.message_type}</td>
                <td className="px-2"><StatusBadge value={m.status} /></td>
                <td className="px-2 text-xs text-slate-500">{new Date(m.created_at).toLocaleString()}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
    </div>
  );
}
