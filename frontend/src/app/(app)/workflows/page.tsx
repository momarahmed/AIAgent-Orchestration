"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Button, Dialog, DialogActions, DialogContent, DialogTitle, MenuItem, Stack, TextField } from "@mui/material";
import { projectsApi, workflowsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function WorkflowsPage() {
  const auth = useAuth();
  const qc = useQueryClient();
  const tenantId = auth.activeTenantId;

  const [open, setOpen] = useState(false);
  const [running, setRunning] = useState<number | null>(null);

  const projects = useQuery({
    queryKey: ["projects", tenantId],
    queryFn: () => projectsApi.list(tenantId ?? undefined),
    enabled: !!tenantId,
  });

  const wfs = useQuery({
    queryKey: ["workflows", tenantId],
    queryFn: () => workflowsApi.list({ tenant_id: tenantId }),
    enabled: !!tenantId,
  });

  const items = useMemo(() => wfs.data?.data ?? wfs.data ?? [], [wfs.data]);

  const create = useMutation({
    mutationFn: (payload: any) => workflowsApi.create(payload),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ["workflows"] }); setOpen(false); },
  });

  const run = useMutation({
    mutationFn: (id: number) => workflowsApi.run(id, { prompt: "Run from Workflow Builder" }),
    onMutate: (id) => setRunning(id),
    onSettled: () => setRunning(null),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["workflows"] }),
  });

  return (
    <div>
      <PageHeader
        eyebrow="Build · Phase 1"
        title="Workflow Builder"
        description="Compose enterprise workflows from triggers, agents, MCP tools, conditions, and approval gates. Run synchronously today; durable Temporal execution lands in Phase 2."
        actions={
          <button onClick={() => setOpen(true)} className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20">
            + New Workflow
          </button>
        }
      />

      {wfs.isLoading ? (
        <div className="px-4 py-10 text-sm text-slate-400 lg:px-6">Loading workflows…</div>
      ) : items.length === 0 ? (
        <EmptyState
          title="No workflows yet"
          description="Compose a workflow with Trigger → Agent → MCP Tool nodes to prove the end-to-end pipe."
          action={<button onClick={() => setOpen(true)} className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white">+ New Workflow</button>}
        />
      ) : (
        <div className="grid gap-4 px-4 py-6 sm:grid-cols-2 lg:grid-cols-3 lg:px-6">
          {items.map((w: any) => (
            <div key={w.id} className="rounded-3xl border border-slate-800 bg-slate-900/60 p-5">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <div className="text-xs uppercase tracking-wider text-slate-500">{w.trigger_type} trigger · v{w.current_version?.version ?? 1}</div>
                  <h3 className="mt-1 text-lg font-semibold text-white">{w.name}</h3>
                  <p className="mt-1 line-clamp-2 text-xs text-slate-500">{w.description}</p>
                </div>
                <StatusBadge value={w.status} />
              </div>

              <dl className="mt-4 grid grid-cols-2 gap-2 text-xs">
                <div>
                  <dt className="text-slate-500">Nodes</dt>
                  <dd className="text-slate-300">{w.current_version?.graph_json?.nodes?.length ?? 0}</dd>
                </div>
                <div>
                  <dt className="text-slate-500">Edges</dt>
                  <dd className="text-slate-300">{w.current_version?.graph_json?.edges?.length ?? 0}</dd>
                </div>
                <div className="col-span-2">
                  <dt className="text-slate-500">Updated</dt>
                  <dd className="text-slate-300">{new Date(w.updated_at).toLocaleString()}</dd>
                </div>
              </dl>

              <div className="mt-4 flex flex-wrap gap-2">
                <button
                  onClick={() => run.mutate(w.id)}
                  disabled={running === w.id}
                  className="rounded-xl bg-gradient-to-r from-cyan-400 to-violet-500 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-60"
                >
                  {running === w.id ? "Running…" : "▶ Run"}
                </button>
                <Link href="/runs" className="rounded-xl border border-slate-700 px-3 py-1.5 text-xs text-slate-200 hover:border-cyan-400/40">
                  Run history
                </Link>
              </div>
            </div>
          ))}
        </div>
      )}

      <NewWorkflowDialog open={open} onClose={() => setOpen(false)} projects={projects.data ?? []} tenantId={tenantId} onSubmit={(p) => create.mutate(p)} submitting={create.isPending} />
    </div>
  );
}

function NewWorkflowDialog({
  open, onClose, projects, tenantId, onSubmit, submitting,
}: { open: boolean; onClose: () => void; projects: any[]; tenantId: number | null; onSubmit: (p: any) => void; submitting: boolean }) {
  const [name, setName] = useState("");
  const [projectId, setProjectId] = useState<number | "">("");
  const [trigger, setTrigger] = useState("manual");

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth PaperProps={{ sx: { background: "#0f172a", color: "white", borderRadius: 4 } }}>
      <DialogTitle>Create new workflow</DialogTitle>
      <DialogContent>
        <Stack spacing={2} sx={{ mt: 1 }}>
          <TextField label="Name" value={name} onChange={(e) => setName(e.target.value)} required fullWidth />
          <TextField select label="Project" value={projectId} onChange={(e) => setProjectId(Number(e.target.value))} required fullWidth>
            {projects.map((p: any) => <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>)}
          </TextField>
          <TextField select label="Trigger" value={trigger} onChange={(e) => setTrigger(e.target.value)} fullWidth>
            {["manual", "schedule", "webhook", "event"].map((t) => <MenuItem key={t} value={t}>{t}</MenuItem>)}
          </TextField>
        </Stack>
      </DialogContent>
      <DialogActions sx={{ px: 3, pb: 3 }}>
        <Button onClick={onClose} variant="text" color="inherit">Cancel</Button>
        <Button
          onClick={() => onSubmit({
            tenant_id: tenantId, project_id: projectId, name, trigger_type: trigger,
            graph_json: { nodes: [{ id: "t1", type: "trigger", position: { x: 80, y: 200 }, data: { label: "Manual" } }], edges: [] },
          })}
          variant="contained"
          disabled={!name || !projectId || submitting}
        >{submitting ? "Creating…" : "Create"}</Button>
      </DialogActions>
    </Dialog>
  );
}
