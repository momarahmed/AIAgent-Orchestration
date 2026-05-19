"use client";

import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { flowiseApi } from "@/lib/api";
import { PageHeader, StatusBadge } from "@/components/shared/PageHeader";
import { FlowiseEmbed } from "@/components/workflow-studio/FlowiseEmbed";
import { RunPanel } from "@/components/workflow-studio/RunPanel";

type Tab = "builder" | "runs" | "settings" | "logs";

/**
 * /workflow-studio/[id] — detail page.
 *  - Builder tab: embedded Flowise canvas.
 *  - Runs tab:    execution history with token usage / duration / errors.
 *  - Settings:    rename, description, archive.
 *  - Logs:        sync error history.
 */
export default function WorkflowStudioAgentPage() {
  const params = useParams();
  const router = useRouter();
  const qc = useQueryClient();
  const id = Number(params.id);

  const [tab, setTab] = useState<Tab>("builder");

  const agentQ = useQuery({
    queryKey: ["flowise", "agent", id],
    queryFn: () => flowiseApi.get(id),
    enabled: !!id,
    refetchInterval: tab === "runs" ? 5_000 : false,
  });

  const embedQ = useQuery({
    queryKey: ["flowise", "embed", id],
    queryFn: () => flowiseApi.embed(id),
    enabled: !!id,
  });

  const runsQ = useQuery({
    queryKey: ["flowise", "runs", id],
    queryFn: () => flowiseApi.runs(id),
    enabled: !!id && tab === "runs",
    refetchInterval: 5_000,
  });

  const sync = useMutation({
    mutationFn: () => flowiseApi.sync(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["flowise", "agent", id] }),
  });

  const remove = useMutation({
    mutationFn: () => flowiseApi.remove(id),
    onSuccess: () => router.push("/workflow-studio"),
  });

  const agent = agentQ.data;

  if (agentQ.isLoading || !agent) {
    return <div className="px-6 py-10 text-sm text-slate-400">Loading agent…</div>;
  }

  return (
    <div>
      <div className="px-4 pt-4 text-xs text-slate-400 lg:px-6">
        <Link href="/workflow-studio" className="text-cyan-300 hover:underline">Workflow Studio</Link>
        <span className="mx-2 text-slate-600">/</span>
        <span>{agent.slug}</span>
      </div>
      <PageHeader
        eyebrow="Build · Workflow"
        title={agent.name}
        description={agent.description ?? "Visual AI Agent powered by FlowiseAI."}
        actions={
          <>
            <span><StatusBadge value={agent.status} /></span>
            <button
              onClick={() => sync.mutate()}
              disabled={sync.isPending}
              className="rounded-2xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-slate-200 hover:border-cyan-400/40"
            >
              {sync.isPending ? "Syncing…" : "Sync to Flowise"}
            </button>
            <button
              onClick={() => { if (confirm("Delete this agent? This will also delete it on the Flowise side.")) remove.mutate(); }}
              className="rounded-2xl border border-rose-700/60 px-3 py-2 text-sm text-rose-300 hover:bg-rose-500/10"
            >
              Delete
            </button>
          </>
        }
      />

      <Tabs tab={tab} onChange={setTab} />

      {tab === "builder" && (
        <div className="grid grid-cols-1 gap-6 px-4 py-6 lg:grid-cols-[1fr_360px] lg:px-6">
          <FlowiseEmbed
            url={embedQ.data?.canvas_url ?? null}
            fallbackMessage={embedQ.error ? "Could not resolve Flowise embed URL — set FLOWISE_EMBED_URL." : undefined}
          />
          <RunPanel agent={agent} />
        </div>
      )}

      {tab === "runs" && <RunsTab runs={(runsQ.data as any)?.data ?? []} loading={runsQ.isLoading} />}

      {tab === "settings" && <SettingsTab agentId={id} agent={agent} />}

      {tab === "logs" && <LogsTab syncs={(agent as any).syncs ?? []} />}
    </div>
  );
}

function Tabs({ tab, onChange }: { tab: Tab; onChange: (t: Tab) => void }) {
  const tabs: { id: Tab; label: string }[] = [
    { id: "builder",  label: "Builder" },
    { id: "runs",     label: "Run history" },
    { id: "settings", label: "Settings" },
    { id: "logs",     label: "Sync logs" },
  ];
  return (
    <div className="border-b border-slate-800 px-4 lg:px-6">
      <div className="flex gap-1">
        {tabs.map((t) => (
          <button
            key={t.id}
            onClick={() => onChange(t.id)}
            className={`rounded-t-2xl px-4 py-2 text-sm font-medium ${
              tab === t.id
                ? "bg-slate-900 text-white shadow-[inset_0_-2px_0_0_rgba(34,211,238,0.6)]"
                : "text-slate-400 hover:text-white"
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>
    </div>
  );
}

function RunsTab({ runs, loading }: { runs: any[]; loading: boolean }) {
  if (loading) return <div className="px-6 py-10 text-sm text-slate-400">Loading runs…</div>;
  if (!runs.length) return <div className="px-6 py-10 text-sm text-slate-400">No runs yet. Trigger one from the Builder tab.</div>;
  return (
    <div className="overflow-x-auto px-4 py-6 lg:px-6">
      <table className="w-full overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/40 text-sm">
        <thead className="bg-slate-900/80 text-left text-xs uppercase tracking-wider text-slate-400">
          <tr>
            <th className="px-4 py-3">When</th>
            <th className="px-4 py-3">Status</th>
            <th className="px-4 py-3">Duration</th>
            <th className="px-4 py-3">Tokens</th>
            <th className="px-4 py-3">Input</th>
            <th className="px-4 py-3">Output / Error</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-800">
          {runs.map((r) => (
            <tr key={r.id} className="align-top">
              <td className="px-4 py-3 text-slate-400">{new Date(r.created_at).toLocaleString()}</td>
              <td className="px-4 py-3"><StatusBadge value={r.status} /></td>
              <td className="px-4 py-3 text-slate-300">{r.duration_ms ?? "—"} ms</td>
              <td className="px-4 py-3 text-slate-300">{r.total_tokens ?? "—"}</td>
              <td className="px-4 py-3 max-w-xs">
                <pre className="max-h-32 overflow-auto whitespace-pre-wrap text-[11px] text-slate-300">
                  {JSON.stringify(r.input, null, 2)}
                </pre>
              </td>
              <td className="px-4 py-3 max-w-md">
                <pre className="max-h-40 overflow-auto whitespace-pre-wrap text-[11px] text-slate-200">
                  {r.error_message ?? JSON.stringify(r.output, null, 2)}
                </pre>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function SettingsTab({ agentId, agent }: { agentId: number; agent: any }) {
  const qc = useQueryClient();
  const [name, setName] = useState(agent.name);
  const [description, setDescription] = useState(agent.description ?? "");

  const save = useMutation({
    mutationFn: () => flowiseApi.update(agentId, { name, description, push_to_flowise: true }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["flowise", "agent", agentId] }),
  });

  return (
    <div className="grid grid-cols-1 gap-6 px-4 py-6 lg:max-w-3xl lg:px-6">
      <Field label="Name">
        <input value={name} onChange={(e) => setName(e.target.value)} className="w-full rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white" />
      </Field>
      <Field label="Description">
        <textarea value={description} onChange={(e) => setDescription(e.target.value)} rows={4} className="w-full resize-y rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white" />
      </Field>
      <Field label="Flowise chatflow ID">
        <code className="block rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 font-mono text-xs text-slate-300">
          {agent.flowise_chatflow_id ?? "(not pushed)"}
        </code>
      </Field>
      <Field label="Owner">
        <span className="text-sm text-slate-300">{agent.owner?.name ?? "—"} · {agent.owner?.email ?? "—"}</span>
      </Field>
      <Field label="Last synced">
        <span className="text-sm text-slate-300">{agent.last_synced_at ? new Date(agent.last_synced_at).toLocaleString() : "never"}</span>
      </Field>
      <div>
        <button
          onClick={() => save.mutate()}
          disabled={save.isPending}
          className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 disabled:opacity-50"
        >
          {save.isPending ? "Saving…" : "Save & sync to Flowise"}
        </button>
      </div>
    </div>
  );
}

function LogsTab({ syncs }: { syncs: any[] }) {
  if (!syncs.length) return <div className="px-6 py-10 text-sm text-slate-400">No sync events yet.</div>;
  return (
    <div className="px-4 py-6 lg:px-6">
      <table className="w-full overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/40 text-sm">
        <thead className="bg-slate-900/80 text-left text-xs uppercase tracking-wider text-slate-400">
          <tr>
            <th className="px-4 py-3">When</th>
            <th className="px-4 py-3">Direction</th>
            <th className="px-4 py-3">Status</th>
            <th className="px-4 py-3">Detail</th>
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-800">
          {syncs.map((s) => (
            <tr key={s.id}>
              <td className="px-4 py-3 text-slate-400">{new Date(s.created_at).toLocaleString()}</td>
              <td className="px-4 py-3 text-slate-300">{s.direction}</td>
              <td className="px-4 py-3"><StatusBadge value={s.sync_status} /></td>
              <td className="px-4 py-3 text-slate-300">
                <pre className="max-h-32 overflow-auto whitespace-pre-wrap text-[11px]">
                  {s.sync_error ?? JSON.stringify(s.payload, null, 2)}
                </pre>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function Field({ label, children }: { label: string; children: any }) {
  return (
    <label className="block">
      <div className="mb-1 text-xs uppercase tracking-wider text-slate-500">{label}</div>
      {children}
    </label>
  );
}
