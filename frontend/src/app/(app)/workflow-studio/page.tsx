"use client";

import Link from "next/link";
import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  Button, Dialog, DialogActions, DialogContent, DialogTitle, MenuItem, Stack, TextField,
} from "@mui/material";
import { flowiseApi, projectsApi, type FlowiseAgent } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

/**
 * /workflow-studio — AI Workflow Studio dashboard.
 * Lists Flowise-backed agents, with sync/import/create actions.
 */
export default function WorkflowStudioPage() {
  const auth = useAuth();
  const qc = useQueryClient();
  const tenantId = auth.activeTenantId;

  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("");
  const [openCreate, setOpenCreate] = useState(false);
  const [openImport, setOpenImport] = useState(false);

  const projectsQ = useQuery({
    queryKey: ["projects", tenantId],
    queryFn: () => projectsApi.list(tenantId ?? undefined),
    enabled: !!tenantId,
  });

  const healthQ = useQuery({
    queryKey: ["flowise", "health"],
    queryFn: () => flowiseApi.health(),
    refetchInterval: 30_000,
  });

  const agentsQ = useQuery({
    queryKey: ["flowise", "agents", tenantId, search, statusFilter],
    queryFn: () =>
      flowiseApi.list({
        tenant_id: tenantId,
        q: search || undefined,
        status: statusFilter || undefined,
      }),
    enabled: !!tenantId,
  });

  const items: FlowiseAgent[] = useMemo(
    () => (agentsQ.data as any)?.data ?? agentsQ.data ?? [],
    [agentsQ.data],
  );

  const sync = useMutation({
    mutationFn: () => flowiseApi.syncTenant(tenantId!),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["flowise"] }),
  });

  const removeMut = useMutation({
    mutationFn: (id: number) => flowiseApi.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["flowise", "agents"] }),
  });

  const duplicateMut = useMutation({
    mutationFn: (id: number) => flowiseApi.duplicate(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["flowise", "agents"] }),
  });

  const pauseMut = useMutation({
    mutationFn: ({ id, status }: { id: number; status: "active" | "paused" }) =>
      flowiseApi.update(id, { status }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["flowise", "agents"] }),
  });

  return (
    <div>
      <PageHeader
        eyebrow="Build · Workflow"
        title="AI Workflow Studio"
        description="Drag-and-drop visual builder for AI Agents — powered by FlowiseAI. Create chatflows, wire up tools / memory / models, run agents, and sync them across your project and FlowiseAI."
        actions={
          <>
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search agents…"
              className="rounded-2xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400/40"
            />
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="rounded-2xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-white outline-none"
            >
              <option value="">All statuses</option>
              {["draft", "active", "paused", "error", "archived"].map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
            <button
              onClick={() => sync.mutate()}
              disabled={sync.isPending || !healthQ.data?.ok}
              className="rounded-2xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-slate-200 hover:border-cyan-400/40 disabled:opacity-40"
              title={healthQ.data?.detail ?? ""}
            >
              {sync.isPending ? "Syncing…" : "Sync"}
            </button>
            <button
              onClick={() => setOpenImport(true)}
              className="rounded-2xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-slate-200 hover:border-cyan-400/40"
            >
              Import from Flowise
            </button>
            <button
              onClick={() => setOpenCreate(true)}
              className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20"
            >
              + New Agent
            </button>
          </>
        }
      />

      <FlowiseHealthBanner health={healthQ.data} />

      {agentsQ.isLoading ? (
        <div className="px-4 py-10 text-sm text-slate-400 lg:px-6">Loading agents…</div>
      ) : items.length === 0 ? (
        <EmptyState
          title="No Flowise agents yet"
          description="Build your first AI Agent visually. Drag tools onto the canvas, connect a chat model, add memory, and publish."
          action={
            <button
              onClick={() => setOpenCreate(true)}
              className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white"
            >
              + New Agent
            </button>
          }
        />
      ) : (
        <div className="overflow-x-auto px-4 py-6 lg:px-6">
          <table className="w-full overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/40 text-sm">
            <thead className="bg-slate-900/80 text-left text-xs uppercase tracking-wider text-slate-400">
              <tr>
                <th className="px-4 py-3">Agent</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Model</th>
                <th className="px-4 py-3">Tools</th>
                <th className="px-4 py-3">Last run</th>
                <th className="px-4 py-3">Updated</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800">
              {items.map((a) => (
                <tr key={a.id} className="hover:bg-slate-900/60">
                  <td className="px-4 py-3">
                    <Link href={`/workflow-studio/${a.id}`} className="font-semibold text-white hover:text-cyan-300">
                      {a.name}
                    </Link>
                    <div className="text-xs text-slate-500">
                      {a.flowise_chatflow_id ? `chatflow: ${a.flowise_chatflow_id.slice(0, 8)}…` : "local-only (not synced)"}
                    </div>
                  </td>
                  <td className="px-4 py-3"><StatusBadge value={a.status} /></td>
                  <td className="px-4 py-3 text-slate-300">{a.model_config?.name ?? "—"}</td>
                  <td className="px-4 py-3 text-slate-300">
                    <div className="flex flex-wrap gap-1">
                      {(a.tools_config ?? []).slice(0, 4).map((t, i) => (
                        <span key={i} className="rounded-full border border-slate-700 px-2 py-0.5 text-[10px] text-slate-300">
                          {t}
                        </span>
                      ))}
                      {(a.tools_config ?? []).length > 4 && (
                        <span className="text-[10px] text-slate-500">+{(a.tools_config ?? []).length - 4}</span>
                      )}
                      {(a.tools_config ?? []).length === 0 && <span className="text-xs text-slate-500">—</span>}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-slate-400">{a.last_run_status ?? "—"}</td>
                  <td className="px-4 py-3 text-slate-500">{new Date(a.updated_at).toLocaleString()}</td>
                  <td className="px-4 py-3 text-right">
                    <div className="inline-flex flex-wrap gap-2">
                      <Link href={`/workflow-studio/${a.id}`} className="rounded-xl border border-cyan-400/40 px-3 py-1 text-xs text-cyan-300 hover:bg-cyan-500/10">
                        Open
                      </Link>
                      {a.status === "active" ? (
                        <button onClick={() => pauseMut.mutate({ id: a.id, status: "paused" })} className="rounded-xl border border-amber-500/40 px-3 py-1 text-xs text-amber-300 hover:bg-amber-500/10">
                          Pause
                        </button>
                      ) : (
                        <button onClick={() => pauseMut.mutate({ id: a.id, status: "active" })} className="rounded-xl border border-emerald-500/40 px-3 py-1 text-xs text-emerald-300 hover:bg-emerald-500/10">
                          Activate
                        </button>
                      )}
                      <button onClick={() => duplicateMut.mutate(a.id)} className="rounded-xl border border-slate-700 px-3 py-1 text-xs text-slate-200 hover:border-cyan-400/40">
                        Duplicate
                      </button>
                      <button
                        onClick={() => { if (confirm(`Delete "${a.name}"? This will also delete it on the Flowise side.`)) removeMut.mutate(a.id); }}
                        className="rounded-xl border border-rose-700/60 px-3 py-1 text-xs text-rose-300 hover:bg-rose-500/10"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <CreateAgentDialog
        open={openCreate}
        onClose={() => setOpenCreate(false)}
        tenantId={tenantId}
        projects={projectsQ.data ?? []}
        onCreated={() => qc.invalidateQueries({ queryKey: ["flowise", "agents"] })}
      />
      <ImportDialog
        open={openImport}
        onClose={() => setOpenImport(false)}
        tenantId={tenantId}
        projects={projectsQ.data ?? []}
        onImported={() => qc.invalidateQueries({ queryKey: ["flowise", "agents"] })}
      />
    </div>
  );
}

function FlowiseHealthBanner({ health }: { health: any }) {
  if (!health) return null;
  if (health.ok) return null;
  return (
    <div className="mx-4 mt-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-xs text-amber-200 lg:mx-6">
      <strong className="font-semibold">Flowise unavailable —</strong> {health.detail}.
      Local agents will still save, but pushes/runs will be queued and retried.
    </div>
  );
}

function CreateAgentDialog({
  open, onClose, tenantId, projects, onCreated,
}: {
  open: boolean; onClose: () => void;
  tenantId: number | null; projects: any[]; onCreated: () => void;
}) {
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [projectId, setProjectId] = useState<number | "">("");

  const create = useMutation({
    mutationFn: () =>
      flowiseApi.create({
        tenant_id: tenantId!,
        project_id: projectId === "" ? null : Number(projectId),
        name,
        description,
        status: "draft",
      } as any),
    onSuccess: () => { onCreated(); onClose(); setName(""); setDescription(""); setProjectId(""); },
  });

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth PaperProps={{ sx: { background: "#0f172a", color: "white", borderRadius: 4 } }}>
      <DialogTitle>Create AI Agent</DialogTitle>
      <DialogContent>
        <Stack spacing={2} sx={{ mt: 1 }}>
          <TextField label="Name" value={name} onChange={(e) => setName(e.target.value)} fullWidth required />
          <TextField label="Description" value={description} onChange={(e) => setDescription(e.target.value)} fullWidth multiline minRows={3} />
          <TextField select label="Project" value={projectId} onChange={(e) => setProjectId(e.target.value === "" ? "" : Number(e.target.value))} fullWidth>
            <MenuItem value="">— none —</MenuItem>
            {projects.map((p: any) => <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>)}
          </TextField>
          <p className="text-xs text-slate-400">
            A blank chatflow will be created on the Flowise side. You can add nodes, tools, prompts, memory and models in the visual builder.
          </p>
        </Stack>
      </DialogContent>
      <DialogActions sx={{ px: 3, pb: 3 }}>
        <Button onClick={onClose} variant="text" color="inherit">Cancel</Button>
        <Button onClick={() => create.mutate()} disabled={!name || !tenantId || create.isPending} variant="contained">
          {create.isPending ? "Creating…" : "Create agent"}
        </Button>
      </DialogActions>
    </Dialog>
  );
}

function ImportDialog({
  open, onClose, tenantId, projects, onImported,
}: {
  open: boolean; onClose: () => void;
  tenantId: number | null; projects: any[]; onImported: () => void;
}) {
  const remoteQ = useQuery({
    queryKey: ["flowise", "remote-chatflows", open],
    queryFn: () => flowiseApi.chatflows(),
    enabled: open,
  });
  const [projectId, setProjectId] = useState<number | "">("");

  const importMut = useMutation({
    mutationFn: (chatflow_id: string) =>
      flowiseApi.importChatflow({
        tenant_id: tenantId!,
        project_id: projectId === "" ? null : Number(projectId),
        chatflow_id,
      }),
    onSuccess: () => { onImported(); },
  });

  const remote = (remoteQ.data as any)?.data ?? [];

  return (
    <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth PaperProps={{ sx: { background: "#0f172a", color: "white", borderRadius: 4 } }}>
      <DialogTitle>Import chatflow from Flowise</DialogTitle>
      <DialogContent>
        <Stack spacing={2} sx={{ mt: 1 }}>
          <TextField select label="Target project" value={projectId} onChange={(e) => setProjectId(e.target.value === "" ? "" : Number(e.target.value))} fullWidth>
            <MenuItem value="">— none —</MenuItem>
            {projects.map((p: any) => <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>)}
          </TextField>
          {remoteQ.isLoading ? (
            <p className="text-sm text-slate-400">Fetching chatflows from Flowise…</p>
          ) : remote.length === 0 ? (
            <p className="text-sm text-slate-400">No chatflows found on the Flowise side.</p>
          ) : (
            <div className="max-h-80 overflow-y-auto rounded-2xl border border-slate-800">
              <table className="w-full text-sm">
                <thead className="bg-slate-900 text-xs uppercase tracking-wider text-slate-500">
                  <tr><th className="px-3 py-2 text-left">Name</th><th className="px-3 py-2 text-left">ID</th><th className="px-3 py-2 text-right">Action</th></tr>
                </thead>
                <tbody>
                  {remote.map((cf: any) => (
                    <tr key={cf.id} className="border-b border-slate-900">
                      <td className="px-3 py-2 text-white">{cf.name ?? "(unnamed)"}</td>
                      <td className="px-3 py-2 font-mono text-[11px] text-slate-400">{cf.id}</td>
                      <td className="px-3 py-2 text-right">
                        <button
                          onClick={() => importMut.mutate(cf.id)}
                          disabled={importMut.isPending || !tenantId}
                          className="rounded-lg bg-cyan-500 px-3 py-1 text-xs font-semibold text-slate-950 disabled:opacity-50"
                        >
                          {importMut.isPending ? "Importing…" : "Import"}
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </Stack>
      </DialogContent>
      <DialogActions sx={{ px: 3, pb: 3 }}>
        <Button onClick={onClose} variant="text" color="inherit">Close</Button>
      </DialogActions>
    </Dialog>
  );
}
