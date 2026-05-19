"use client";

import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { runsApi } from "@/lib/api";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function RunsPage() {
  const [selected, setSelected] = useState<number | null>(null);

  const runs = useQuery({
    queryKey: ["runs"],
    queryFn: () => runsApi.list(),
    refetchInterval: 5000,
  });

  const detail = useQuery({
    queryKey: ["runs", selected],
    queryFn: () => runsApi.get(selected as number),
    enabled: !!selected,
  });

  const items = useMemo(() => runs.data?.data ?? runs.data ?? [], [runs.data]);

  return (
    <div>
      <PageHeader
        eyebrow="Operate · Phase 1"
        title="Run History"
        description="Inspect every workflow execution — node tree, durations, inputs/outputs, errors, and the underlying tool calls."
      />

      <div className="grid gap-4 px-4 py-6 lg:grid-cols-[420px_1fr] lg:px-6">
        <div className="rounded-3xl border border-slate-800 bg-slate-900/40">
          <div className="border-b border-slate-800 px-4 py-3 text-xs uppercase tracking-wider text-slate-500">Recent runs</div>
          <div className="max-h-[70vh] overflow-y-auto">
            {runs.isLoading ? (
              <div className="px-4 py-6 text-sm text-slate-400">Loading…</div>
            ) : items.length === 0 ? (
              <EmptyState title="No runs yet" description="Trigger a workflow to populate the run history." />
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
                <h3 className="mb-2 text-sm font-semibold text-slate-300">Final Output</h3>
                <pre className="max-h-96 overflow-auto rounded-2xl border border-slate-800 bg-slate-950 p-4 text-xs text-slate-300">
                  {JSON.stringify(detail.data.output ?? {}, null, 2)}
                </pre>
              </section>

              {detail.data.error && (
                <section className="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm text-rose-200">
                  <div className="mb-1 font-semibold">Error</div>
                  <pre className="text-xs">{detail.data.error}</pre>
                </section>
              )}
            </div>
          ) : (
            <EmptyState title="Run not found" description="The selected run was not retrieved." />
          )}
        </div>
      </div>
    </div>
  );
}
