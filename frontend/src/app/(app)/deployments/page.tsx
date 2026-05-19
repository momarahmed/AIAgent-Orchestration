"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { agentsApi, deploymentsApi, mcpServersApi, workflowsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

const ENVIRONMENTS = ["dev", "test", "staging", "prod"] as const;
type Env = typeof ENVIRONMENTS[number];

const STATUS_BADGE: Record<string, string> = {
  pending: "bg-slate-500/10 text-slate-300 border-slate-500/30",
  validating: "bg-cyan-500/10 text-cyan-300 border-cyan-500/30",
  testing: "bg-cyan-500/10 text-cyan-300 border-cyan-500/30",
  scanning: "bg-cyan-500/10 text-cyan-300 border-cyan-500/30",
  building: "bg-cyan-500/10 text-cyan-300 border-cyan-500/30",
  deploying: "bg-cyan-500/10 text-cyan-300 border-cyan-500/30",
  awaiting_approval: "bg-amber-500/10 text-amber-300 border-amber-500/30",
  deployed: "bg-emerald-500/10 text-emerald-300 border-emerald-500/30",
  failed: "bg-rose-500/10 text-rose-300 border-rose-500/30",
  rolled_back: "bg-violet-500/10 text-violet-300 border-violet-500/30",
};

export default function DeploymentsPage() {
  const auth = useAuth();
  const qc = useQueryClient();
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState<{ asset_type: "agent" | "mcp_server" | "workflow"; asset_id: number | ""; environment: Env; secret_refs: Record<string, string>; notes: string }>({
    asset_type: "workflow", asset_id: "", environment: "dev", secret_refs: {}, notes: "",
  });

  const { data: deployments = [] } = useQuery({
    queryKey: ["deployments"],
    queryFn: () => deploymentsApi.list({}),
  });

  const { data: agents = [] } = useQuery({ queryKey: ["agents", "deploy"], queryFn: () => agentsApi.list() });
  const { data: mcps = [] } = useQuery({ queryKey: ["mcps", "deploy"], queryFn: () => mcpServersApi.list() });
  const { data: wfs = [] } = useQuery({ queryKey: ["workflows", "deploy"], queryFn: () => workflowsApi.list() });

  const createMut = useMutation({
    mutationFn: () => deploymentsApi.create({
      tenant_id: auth.activeTenantId,
      project_id: auth.activeProjectId,
      asset_type: form.asset_type,
      asset_id: Number(form.asset_id),
      environment: form.environment,
      secret_refs: form.secret_refs,
      notes: form.notes,
    }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ["deployments"] }); setOpen(false); },
  });

  const approveMut = useMutation({
    mutationFn: (id: number) => deploymentsApi.approve(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["deployments"] }),
  });
  const rollbackMut = useMutation({
    mutationFn: ({ id, reason }: { id: number; reason?: string }) => deploymentsApi.rollback(id, reason),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["deployments"] }),
  });

  const assetOptions = form.asset_type === "agent" ? agents : form.asset_type === "mcp_server" ? mcps : wfs;

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 2 · Operate"
        title="Deployments"
        description="Promote agents, MCP servers, and workflows across dev → test → staging → prod with validation, tests, scans, and approval gates."
        actions={
          <button onClick={() => setOpen(true)} className="rounded-xl bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 hover:bg-cyan-400">
            New deployment
          </button>
        }
      />

      <div className="grid gap-4 md:grid-cols-4">
        {ENVIRONMENTS.map((env) => {
          const inEnv = deployments.filter((d: any) => d.environment === env);
          return (
            <div key={env} className="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
              <div className="flex items-center justify-between text-xs uppercase tracking-wider text-slate-500">
                <span>{env}</span>
                <span>{inEnv.length}</span>
              </div>
              <div className="mt-3 space-y-2 text-sm">
                {inEnv.slice(0, 5).map((d: any) => (
                  <div key={d.id} className="rounded-xl border border-slate-800 bg-slate-900/40 p-3">
                    <div className="flex items-center justify-between text-xs text-slate-400">
                      <span>{d.asset_type} #{d.asset_id}</span>
                      <span className={`rounded-full border px-1.5 py-0.5 text-[10px] ${STATUS_BADGE[d.status] ?? "border-slate-700 text-slate-300"}`}>{d.status}</span>
                    </div>
                    <div className="mt-2 flex gap-2">
                      {d.status === "awaiting_approval" && (
                        <button onClick={() => approveMut.mutate(d.id)} className="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-2 py-1 text-[11px] text-emerald-300 hover:bg-emerald-500/20">Approve</button>
                      )}
                      {d.status === "deployed" && (
                        <button onClick={() => rollbackMut.mutate({ id: d.id, reason: "Manual rollback" })} className="rounded-lg border border-violet-500/40 bg-violet-500/10 px-2 py-1 text-[11px] text-violet-300 hover:bg-violet-500/20">Rollback</button>
                      )}
                    </div>
                  </div>
                ))}
                {inEnv.length === 0 && <div className="rounded-xl border border-dashed border-slate-800 p-3 text-xs text-slate-500">No deployments</div>}
              </div>
            </div>
          );
        })}
      </div>

      <div className="rounded-2xl border border-slate-800 bg-slate-950/60">
        <div className="border-b border-slate-800 px-4 py-3 text-sm font-semibold text-white">Pipeline detail</div>
        <table className="w-full text-sm">
          <thead className="border-b border-slate-800 bg-slate-900/40 text-xs uppercase tracking-wider text-slate-500">
            <tr>
              <th className="px-4 py-3 text-left">ID</th>
              <th className="px-4 py-3 text-left">Asset</th>
              <th className="px-4 py-3 text-left">Env</th>
              <th className="px-4 py-3 text-left">Status</th>
              <th className="px-4 py-3 text-left">Steps</th>
              <th className="px-4 py-3 text-left">Updated</th>
            </tr>
          </thead>
          <tbody>
            {deployments.map((d: any) => (
              <tr key={d.id} className="border-b border-slate-900">
                <td className="px-4 py-3 font-mono text-slate-400">#{d.id}</td>
                <td className="px-4 py-3 text-slate-300">{d.asset_type} #{d.asset_id}</td>
                <td className="px-4 py-3 text-slate-300">{d.environment}</td>
                <td className="px-4 py-3">
                  <span className={`inline-flex rounded-full border px-2 py-0.5 text-[11px] ${STATUS_BADGE[d.status] ?? "border-slate-700 text-slate-300"}`}>{d.status}</span>
                </td>
                <td className="px-4 py-3">
                  <div className="flex flex-wrap gap-1">
                    {(d.pipeline ?? []).map((s: any, idx: number) => (
                      <span key={idx} className={`rounded-full px-2 py-0.5 text-[10px] ${s.ok === false ? "bg-rose-500/10 text-rose-300" : "bg-slate-800/60 text-slate-400"}`} title={s.error || s.note}>
                        {s.step}
                      </span>
                    ))}
                  </div>
                </td>
                <td className="px-4 py-3 text-xs text-slate-500">{d.updated_at?.slice(0, 19).replace("T", " ")}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {open && (
        <div className="fixed inset-0 z-30 flex items-center justify-center bg-slate-950/80 px-4">
          <div className="w-full max-w-xl rounded-2xl border border-slate-800 bg-slate-950 p-6">
            <div className="text-lg font-semibold text-white">New deployment</div>
            <div className="mt-4 space-y-4 text-sm">
              <div>
                <label className="text-xs uppercase tracking-wider text-slate-500">Asset type</label>
                <select value={form.asset_type} onChange={(e) => setForm({ ...form, asset_type: e.target.value as any, asset_id: "" })} className="mt-1 w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white">
                  <option value="agent">Agent</option>
                  <option value="mcp_server">MCP Server</option>
                  <option value="workflow">Workflow</option>
                </select>
              </div>
              <div>
                <label className="text-xs uppercase tracking-wider text-slate-500">Asset</label>
                <select value={form.asset_id} onChange={(e) => setForm({ ...form, asset_id: e.target.value as any })} className="mt-1 w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white">
                  <option value="">Select…</option>
                  {assetOptions.map((a: any) => <option key={a.id} value={a.id}>{a.name}</option>)}
                </select>
              </div>
              <div>
                <label className="text-xs uppercase tracking-wider text-slate-500">Environment</label>
                <select value={form.environment} onChange={(e) => setForm({ ...form, environment: e.target.value as Env })} className="mt-1 w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white">
                  {ENVIRONMENTS.map((e) => <option key={e} value={e}>{e}</option>)}
                </select>
                {(form.environment === "staging" || form.environment === "prod") && (
                  <div className="mt-1 text-[11px] text-amber-300">Promotion to {form.environment} requires vault-backed secrets and approver sign-off.</div>
                )}
              </div>
              <div>
                <label className="text-xs uppercase tracking-wider text-slate-500">Notes</label>
                <textarea value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} rows={3} className="mt-1 w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white" />
              </div>
            </div>
            <div className="mt-6 flex justify-end gap-2">
              <button onClick={() => setOpen(false)} className="rounded-xl border border-slate-800 px-3 py-2 text-sm text-slate-300">Cancel</button>
              <button disabled={!form.asset_id || createMut.isPending} onClick={() => createMut.mutate()} className="rounded-xl bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 disabled:opacity-50">
                {createMut.isPending ? "Deploying…" : "Run pipeline"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
