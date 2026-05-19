"use client";

import { useEffect, useState } from "react";
import { eventBusApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge } from "@/components/shared/PageHeader";

export default function EventBusPage() {
  const auth = useAuth();
  const [status, setStatus] = useState<any>(null);
  const [subscriptions, setSubscriptions] = useState<any[]>([]);
  const [log, setLog] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [publish, setPublish] = useState({ topic: "agent.events", event_type: "manual.test", payload: '{"hello":"world"}' });

  const load = () => {
    setLoading(true);
    Promise.all([
      eventBusApi.status().then(setStatus),
      eventBusApi.subscriptions().then(setSubscriptions),
      eventBusApi.log({ limit: 50 }).then(setLog),
    ]).finally(() => setLoading(false));
  };
  useEffect(() => { load(); const t = setInterval(load, 10000); return () => clearInterval(t); }, []);

  const doPublish = async () => {
    try {
      const payload = JSON.parse(publish.payload);
      await eventBusApi.publish({ topic: publish.topic, event_type: publish.event_type, payload });
      load();
    } catch (e: any) { alert(e?.message || "publish failed"); }
  };

  const newSub = async () => {
    const topic = prompt("Topic?"); if (!topic) return;
    const handler_type = prompt("Handler type (workflow / webhook / agent)?") || "webhook";
    const config = handler_type === "webhook"
      ? { webhook_url: prompt("Webhook URL?") || "" }
      : handler_type === "workflow"
        ? { workflow_id: parseInt(prompt("Workflow ID?") || "0", 10) }
        : { agent_id: parseInt(prompt("Agent ID?") || "0", 10) };
    await eventBusApi.createSubscription({ name: `${topic} → ${handler_type}`, topic, handler_type, handler_config: config, is_active: true });
    load();
  };

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading event bus…</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader
        title="Event Bus"
        description="Scalable event backbone — Kafka/Redpanda in prod with Redis Streams fallback for dev. All events fan out to subscriptions and the immutable audit log."
        actions={<button onClick={newSub} className="rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30">+ Subscription</button>}
      />

      {status && (
        <div className="grid grid-cols-1 gap-3 md:grid-cols-4">
          <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"><div className="text-xs uppercase text-slate-500">Driver</div><div className="text-xl font-semibold text-white">{status.driver}</div></div>
          <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"><div className="text-xs uppercase text-slate-500">Brokers</div><div className="text-sm text-slate-200">{status.brokers || "—"}</div></div>
          <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"><div className="text-xs uppercase text-slate-500">Health</div><StatusBadge value={status.healthy ? "healthy" : "degraded"} /></div>
          <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"><div className="text-xs uppercase text-slate-500">Subscriptions</div><div className="text-xl font-semibold text-white">{subscriptions.length}</div></div>
        </div>
      )}

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-white">Publish</h3>
        <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
          <input className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" placeholder="topic" value={publish.topic} onChange={(e) => setPublish({ ...publish, topic: e.target.value })} />
          <input className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" placeholder="event_type" value={publish.event_type} onChange={(e) => setPublish({ ...publish, event_type: e.target.value })} />
          <textarea className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 font-mono text-xs" value={publish.payload} onChange={(e) => setPublish({ ...publish, payload: e.target.value })} />
          <button onClick={doPublish} className="rounded-xl bg-violet-500/20 px-3 py-2 text-sm font-semibold text-violet-200 hover:bg-violet-500/30 md:col-span-3">Publish</button>
        </div>
      </section>

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-white">Active subscriptions</h3>
        <ul className="mt-2 divide-y divide-slate-800 text-sm">
          {subscriptions.map((s) => (
            <li key={s.id} className="flex items-center justify-between py-2">
              <div>
                <div className="text-white">{s.name}</div>
                <div className="text-xs text-slate-500">topic: {s.topic} · handler: {s.handler_type}</div>
              </div>
              <StatusBadge value={s.is_active ? "running" : "degraded"} />
            </li>
          ))}
        </ul>
      </section>

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-white">Event log (recent)</h3>
        <table className="mt-2 w-full text-left text-sm">
          <thead className="text-xs uppercase text-slate-500"><tr><th className="px-2 py-2">When</th><th>Topic</th><th>Source</th><th>Payload</th></tr></thead>
          <tbody>
            {log.map((e) => (
              <tr key={e.id} className="border-t border-slate-800 align-top text-slate-300">
                <td className="px-2 py-2 text-xs">{new Date(e.created_at).toLocaleTimeString()}</td>
                <td className="px-2">{e.topic}</td>
                <td className="px-2 text-xs text-slate-500">{e.source ?? "—"}</td>
                <td className="px-2"><code className="text-[11px] text-cyan-200">{JSON.stringify(e.payload).slice(0, 120)}</code></td>
              </tr>
            ))}
          </tbody>
        </table>
      </section>
    </div>
  );
}
