"use client";

import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Button, Dialog, DialogActions, DialogContent, DialogTitle, MenuItem, Stack, TextField } from "@mui/material";
import { agentsApi, projectsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function AgentsPage() {
  const auth = useAuth();
  const qc = useQueryClient();
  const tenantId = auth.activeTenantId;

  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState("");

  const projectsQuery = useQuery({
    queryKey: ["projects", tenantId],
    queryFn: () => projectsApi.list(tenantId ?? undefined),
    enabled: !!tenantId,
  });

  const agentsQuery = useQuery({
    queryKey: ["agents", tenantId, search],
    queryFn: () => agentsApi.list({ tenant_id: tenantId, q: search || undefined }),
    enabled: !!tenantId,
  });

  const items = useMemo(() => agentsQuery.data?.data ?? agentsQuery.data ?? [], [agentsQuery.data]);

  const createAgent = useMutation({
    mutationFn: (payload: any) => agentsApi.create(payload),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ["agents"] }); setOpen(false); },
  });

  const archive = useMutation({
    mutationFn: (id: number) => agentsApi.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["agents"] }),
  });

  const duplicate = useMutation({
    mutationFn: (id: number) => agentsApi.duplicate(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["agents"] }),
  });

  return (
    <div>
      <PageHeader
        eyebrow="Build · Phase 1"
        title="Agent Studio"
        description="Create, configure, and govern enterprise AI agents — model routing, allowed MCP tools, memory scope, and risk classification."
        actions={
          <>
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Search agents…"
              className="rounded-2xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400/40"
            />
            <button onClick={() => setOpen(true)} className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20">
              + New Agent
            </button>
          </>
        }
      />

      {agentsQuery.isLoading ? (
        <div className="px-4 py-10 text-sm text-slate-400 lg:px-6">Loading agents…</div>
      ) : items.length === 0 ? (
        <EmptyState
          title="No agents yet"
          description="Create your first AI agent. Define its role, system instructions, model, and the MCP tools it is allowed to call."
          action={<button onClick={() => setOpen(true)} className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white">+ New Agent</button>}
        />
      ) : (
        <div className="overflow-x-auto px-4 py-6 lg:px-6">
          <table className="w-full overflow-hidden rounded-3xl border border-slate-800 bg-slate-900/40 text-sm">
            <thead className="bg-slate-900/80 text-left text-xs uppercase tracking-wider text-slate-400">
              <tr>
                <th className="px-4 py-3">Name</th>
                <th className="px-4 py-3">Model</th>
                <th className="px-4 py-3">Memory</th>
                <th className="px-4 py-3">Risk</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Updated</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800">
              {items.map((a: any) => (
                <tr key={a.id} className="hover:bg-slate-900/60">
                  <td className="px-4 py-3">
                    <div className="font-semibold text-white">{a.name}</div>
                    <div className="text-xs text-slate-500">{a.description ?? a.slug}</div>
                  </td>
                  <td className="px-4 py-3 text-slate-300">{a.current_version?.model_config?.model ?? "—"}</td>
                  <td className="px-4 py-3 text-slate-300">{a.current_version?.memory_scope ?? "—"}</td>
                  <td className="px-4 py-3"><StatusBadge value={a.risk_level} /></td>
                  <td className="px-4 py-3"><StatusBadge value={a.status} /></td>
                  <td className="px-4 py-3 text-slate-500">{new Date(a.updated_at).toLocaleString()}</td>
                  <td className="px-4 py-3 text-right">
                    <div className="inline-flex gap-2">
                      <button onClick={() => duplicate.mutate(a.id)} className="rounded-xl border border-slate-700 px-3 py-1 text-xs text-slate-200 hover:border-cyan-400/40">
                        Duplicate
                      </button>
                      <button onClick={() => { if (confirm("Archive agent?")) archive.mutate(a.id); }} className="rounded-xl border border-rose-700/60 px-3 py-1 text-xs text-rose-300 hover:bg-rose-500/10">
                        Archive
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      <NewAgentDialog
        open={open}
        onClose={() => setOpen(false)}
        projects={projectsQuery.data ?? []}
        tenantId={tenantId}
        onSubmit={(payload) => createAgent.mutate(payload)}
        submitting={createAgent.isPending}
      />
    </div>
  );
}

function NewAgentDialog({
  open, onClose, projects, tenantId, onSubmit, submitting,
}: {
  open: boolean; onClose: () => void; projects: any[]; tenantId: number | null;
  onSubmit: (payload: any) => void; submitting: boolean;
}) {
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [projectId, setProjectId] = useState<number | "">("");
  const [model, setModel] = useState("gpt-4o-mini");
  const [riskLevel, setRiskLevel] = useState("L1");
  const [systemInstructions, setSystemInstructions] = useState("You are a helpful enterprise AI agent.");

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth PaperProps={{ sx: { background: "#0f172a", color: "white", borderRadius: 4 } }}>
      <DialogTitle>Create new agent</DialogTitle>
      <DialogContent>
        <Stack spacing={2} sx={{ mt: 1 }}>
          <TextField label="Name" value={name} onChange={(e) => setName(e.target.value)} fullWidth required />
          <TextField label="Description" value={description} onChange={(e) => setDescription(e.target.value)} fullWidth multiline minRows={2} />
          <TextField select label="Project" value={projectId} onChange={(e) => setProjectId(Number(e.target.value))} fullWidth required>
            {projects.map((p: any) => (
              <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>
            ))}
          </TextField>
          <TextField label="Model" value={model} onChange={(e) => setModel(e.target.value)} fullWidth />
          <TextField select label="Risk level" value={riskLevel} onChange={(e) => setRiskLevel(e.target.value)} fullWidth>
            {["L0", "L1", "L2", "L3", "L4"].map((r) => <MenuItem key={r} value={r}>{r}</MenuItem>)}
          </TextField>
          <TextField label="System instructions" value={systemInstructions} onChange={(e) => setSystemInstructions(e.target.value)} fullWidth multiline minRows={4} />
        </Stack>
      </DialogContent>
      <DialogActions sx={{ px: 3, pb: 3 }}>
        <Button onClick={onClose} variant="text" color="inherit">Cancel</Button>
        <Button
          onClick={() => onSubmit({
            tenant_id: tenantId, project_id: projectId, name, description,
            risk_level: riskLevel, system_instructions: systemInstructions,
            model_config: { provider: "openai", model, temperature: 0.2 },
          })}
          variant="contained"
          disabled={!name || !projectId || submitting}
        >{submitting ? "Creating…" : "Create agent"}</Button>
      </DialogActions>
    </Dialog>
  );
}
