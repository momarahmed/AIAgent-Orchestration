"use client";

import { useMemo, useState, useEffect } from "react";
import { useSearchParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { runsApi } from "@/lib/api";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function RunsPage() {
  const searchParams = useSearchParams();
  const [selected, setSelected] = useState<number | null>(null);
  const [status, setStatus] = useState("");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");

  useEffect(() => {
    const runParam = searchParams.get("run");
    if (runParam) setSelected(Number(runParam));
  }, [searchParams]);

  const runs = useQuery({
    queryKey: ["runs", status, from, to],
    queryFn: () =>
      runsApi.list({
        status: status || undefined,
        from: from || undefined,
        to: to || undefined,
      }),
    refetchInterval: 5000,
  });

  const detail = useQuery({
    queryKey: ["runs", selected],
    queryFn: () => runsApi.get(selected as number),
    enabled: !!selected,
  });

  const items = useMemo(() => {
    const raw = runs.data;
    if (Array.isArray(raw)) return raw;
    return raw?.data ?? [];
  }, [runs.data]);

  const toolCalls = useMemo(() => {
    const tasks = (detail.data as any)?.tasks ?? [];
    return tasks.flatMap((t: any) =>
      (t.tool_calls ?? []).map((tc: any) => ({ ...tc, node_id: t.node_id })),
    );
  }, [detail.data]);

  return (
    <div>
      <PageHeader
        eyebrow="UX-004 · Phase 1"
        title="Run History"
        description="Inspect every workflow execution — node tree, durations, inputs/outputs, errors, and MCP tool calls."
      />

      <div className="flex flex-wrap gap-2 px-4 pb-2 lg:px-6">
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-sm text-white"
        >
          <option value="">All statuses</option>
          {["running", "completed", "failed"].map((s) => (
            <option key={s} value={s}>{s}</option>
          ))}
        </select>
        <input
          type="date"
          value={from}
          onChange={(e) => setFrom(e.target.value)}
          className="rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-sm text-white"
        />
        <input
          type="date"
          value={to}
          onChange={(e) => setTo(e.target.value)}
          className="rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-sm text-white"
        />
      </div>

      <div className="grid gap-4 px-4 py-6 lg:grid-cols-[420px_1fr] lg:px-6">
        <div className="rounded-3xl border border-slate-800 bg-slate-900/40">
          <div className="border-b border-slate-800 px-4 py-3 text-xs uppercase tracking-wider text-slate-500">Recent runs</div>
          <div className="max-h-[70vh] overflow-y-auto">
            {runs.isLoading ? (
              <div className="px-4 py-6 text-sm text-slate-400">Loading…</div>
            ) : items.length === 0 ? (
              <EmptyState title="No runs yet" description="Trigger a workflow or use Chat to populate run history." />
            ) : (
              <ul className="divide-y divide-slate-800">
                {items.map((r: any) => (
                  <li key={r.id}>
                    <button
                      onClick={() => setSelected(r.id)}
                      className={`w-full px-4 py-3 text-left transition ${selected === r.id ? "bg-cyan-500/10" : "hover:bg-slate-900/80"}`}
                    >
                      <div className="flex items-center justify-between gap-2">
                        <div className="font-semibold text-white">Run #{r.id}</div>
                        <StatusBadge value={r.status} />
                      </div>
                      <div className="mt-1 text-xs text-slate-500">{r.workflow?.name ?? "Workflow"}</div>
                      <div className="mt-1 text-[11px] text-slate-500">
                        {r.started_at ? new Date(r.started_at).toLocaleString() : new Date(r.created_at).toLocaleString()}
                      </div>
                    </button>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>

        <div className="rounded-3xl border border-slate-800 bg-slate-900/40">
          {!selected ? (
            <EmptyState title="Select a run" description="Choose a run on the left to inspect node-by-node execution and tool calls." />
          ) : detail.isLoading ? (
            <div className="px-4 py-6 text-sm text-slate-400">Loading run…</div>
          ) : detail.data ? (
            <div className="space-y-4 p-5">
              <div>
                <div className="text-xs uppercase tracking-wider text-slate-500">Run #{detail.data.id}</div>
                <h2 className="mt-1 text-xl font-semibold text-white">{(detail.data as any).workflow?.name ?? "Workflow"}</h2>
                <div className="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                  <StatusBadge value={detail.data.status} />
                  {detail.data.started_at && <span>started {new Date(detail.data.started_at).toLocaleString()}</span>}
                  {detail.data.completed_at && <span>· finished {new Date(detail.data.completed_at).toLocaleString()}</span>}
                </div>
              </div>

              {(detail.data as any).output && (
                <section>
                  <h3 className="mb-2 text-sm font-semibold text-slate-300">Output</h3>
                  <pre className="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-950 p-3 text-xs text-slate-300">
                    {JSON.stringify((detail.data as any).output, null, 2)}
                  </pre>
                </section>
              )}

              <section>
                <h3 className="mb-2 text-sm font-semibold text-slate-300">Tasks</h3>
                <div className="overflow-hidden rounded-2xl border border-slate-800">
                  <table className="w-full text-sm">
                    <thead className="bg-slate-900 text-left text-xs uppercase tracking-wider text-slate-400">
                      <tr>
                        <th className="px-3 py-2">Node</th>
                        <th className="px-3 py-2">Type</th>
                        <th className="px-3 py-2">Status</th>
                        <th className="px-3 py-2">Duration</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-800">
                      {(detail.data.tasks ?? []).map((t: any) => (
                        <tr key={t.id}>
                          <td className="px-3 py-2 font-mono text-xs text-cyan-300">{t.node_id}</td>
                          <td className="px-3 py-2 text-slate-300">{t.node_type}</td>
                          <td className="px-3 py-2"><StatusBadge value={t.status} /></td>
                          <td className="px-3 py-2 text-slate-400">{t.duration_ms} ms</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </section>

              <section>
                <h3 className="mb-2 text-sm font-semibold text-slate-300">Tool calls</h3>
                {toolCalls.length === 0 ? (
                  <p className="text-sm text-slate-500">No MCP tool calls recorded for this run.</p>
                ) : (
                  <div className="space-y-2">
                    {toolCalls.map((tc: any) => (
                      <div key={tc.id} className="rounded-2xl border border-slate-800 bg-slate-950 p-3 text-xs">
                        <div className="font-semibold text-white">{tc.tool_name ?? tc.tool?.name ?? "tool"}</div>
                        <div className="text-slate-500">node {tc.node_id} · {tc.status}</div>
                        <pre className="mt-2 overflow-x-auto text-slate-400">{JSON.stringify(tc.output ?? tc.input, null, 2)}</pre>
                      </div>
                    ))}
                  </div>
                )}
              </section>
            </div>
          ) : null}
        </div>
      </div>
    </div>
  );
}
