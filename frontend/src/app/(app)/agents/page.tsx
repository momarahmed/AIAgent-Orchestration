"use client";

import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Button, Dialog, DialogActions, DialogContent, DialogTitle, MenuItem, Stack, TextField } from "@mui/material";
import { agentsApi, copyApi, debugApi, projectsApi, versionsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function AgentsPage() {
  const auth = useAuth();
  const qc = useQueryClient();
  const tenantId = auth.activeTenantId;

  const [open, setOpen] = useState(false);
  const [editAgent, setEditAgent] = useState<any | null>(null);
  const [debugAgent, setDebugAgent] = useState<any | null>(null);
  const [versionsAgent, setVersionsAgent] = useState<any | null>(null);
  const [search, setSearch] = useState("");

  const copyMut = useMutation({
    mutationFn: (id: number) => copyApi.agent(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["agents"] }),
  });

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

  const updateAgent = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: any }) => agentsApi.update(id, payload),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ["agents"] }); setEditAgent(null); },
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
                    <div className="inline-flex flex-wrap gap-2">
                      <button onClick={() => setDebugAgent(a)} className="rounded-xl border border-cyan-400/40 px-3 py-1 text-xs text-cyan-300 hover:bg-cyan-500/10">
                        Debug
                      </button>
                      <button onClick={() => setVersionsAgent(a)} className="rounded-xl border border-slate-700 px-3 py-1 text-xs text-slate-200 hover:border-cyan-400/40">
                        Versions
                      </button>
                      <button onClick={() => setEditAgent(a)} className="rounded-xl border border-slate-700 px-3 py-1 text-xs text-slate-200 hover:border-cyan-400/40">
                        Edit
                      </button>
                      <button onClick={() => copyMut.mutate(a.id)} className="rounded-xl border border-slate-700 px-3 py-1 text-xs text-slate-200 hover:border-cyan-400/40">
                        Copy
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
      {editAgent && (
        <EditAgentDialog
          agent={editAgent}
          onClose={() => setEditAgent(null)}
          onSubmit={(payload) => updateAgent.mutate({ id: editAgent.id, payload })}
          submitting={updateAgent.isPending}
        />
      )}
      {debugAgent && <DebugAgentDialog agent={debugAgent} onClose={() => setDebugAgent(null)} />}
      {versionsAgent && <AgentVersionsDialog agent={versionsAgent} onClose={() => setVersionsAgent(null)} onRollback={() => qc.invalidateQueries({ queryKey: ["agents"] })} />}
    </div>
  );
}

function DebugAgentDialog({ agent, onClose }: { agent: any; onClose: () => void }) {
  const [prompt, setPrompt] = useState("Hello, can you confirm you are online and list MCP tools you can call?");
  const [output, setOutput] = useState<any | null>(null);
  const debugMut = useMutation({
    mutationFn: () => debugApi.agent(agent.id, prompt),
    onSuccess: setOutput,
  });

  return (
    <Dialog open onClose={onClose} maxWidth="md" fullWidth PaperProps={{ sx: { background: "#0f172a", color: "white", borderRadius: 4 } }}>
      <DialogTitle>Debug — {agent.name}</DialogTitle>
      <DialogContent>
        <Stack spacing={2} sx={{ mt: 1 }}>
          <TextField label="Test prompt" value={prompt} onChange={(e) => setPrompt(e.target.value)} fullWidth multiline minRows={3} />
          {output && (
            <div className="rounded-2xl border border-slate-700 bg-slate-950 p-3 text-xs">
              <div className="text-slate-400">Latency: {output.latency_ms} ms · Mock: {String(output.mock ?? false)}</div>
              <pre className="mt-2 max-h-72 overflow-auto whitespace-pre-wrap text-slate-200">{JSON.stringify(output.output, null, 2)}</pre>
            </div>
          )}
        </Stack>
      </DialogContent>
      <DialogActions sx={{ px: 3, pb: 3 }}>
        <Button onClick={onClose} variant="text" color="inherit">Close</Button>
        <Button variant="contained" onClick={() => debugMut.mutate()} disabled={debugMut.isPending}>
          {debugMut.isPending ? "Running…" : "Run prompt"}
        </Button>
      </DialogActions>
    </Dialog>
  );
}

function AgentVersionsDialog({ agent, onClose, onRollback }: { agent: any; onClose: () => void; onRollback: () => void }) {
  const versionsQ = useQuery({ queryKey: ["agent-versions", agent.id], queryFn: () => versionsApi.agent(agent.id) });
  const [a, setA] = useState<number | "">("");
  const [b, setB] = useState<number | "">("");
  const [diff, setDiff] = useState<any | null>(null);

  const diffMut = useMutation({
    mutationFn: () => versionsApi.agentDiff(agent.id, Number(a), Number(b)),
    onSuccess: setDiff,
  });
  const rollbackMut = useMutation({
    mutationFn: (v: number) => versionsApi.agentRollback(agent.id, v),
    onSuccess: () => { onRollback(); onClose(); },
  });

  const versions = versionsQ.data ?? [];

  return (
    <Dialog open onClose={onClose} maxWidth="md" fullWidth PaperProps={{ sx: { background: "#0f172a", color: "white", borderRadius: 4 } }}>
      <DialogTitle>Versions — {agent.name}</DialogTitle>
      <DialogContent>
        <div className="mt-1 max-h-56 overflow-y-auto rounded-2xl border border-slate-800">
          <table className="w-full text-sm">
            <thead className="bg-slate-900 text-xs uppercase tracking-wider text-slate-500">
              <tr><th className="px-3 py-2 text-left">Version</th><th className="px-3 py-2 text-left">Created</th><th className="px-3 py-2 text-left">Model</th><th className="px-3 py-2 text-right">Actions</th></tr>
            </thead>
            <tbody>
              {versions.map((v: any) => (
                <tr key={v.id} className={`border-b border-slate-900 ${v.id === agent.current_version_id ? "bg-cyan-500/5" : ""}`}>
                  <td className="px-3 py-2 font-mono text-cyan-300">v{v.version}{v.id === agent.current_version_id && <span className="ml-2 text-[10px] text-emerald-300">current</span>}</td>
                  <td className="px-3 py-2 text-slate-400">{v.created_at?.slice(0,19).replace("T"," ")}</td>
                  <td className="px-3 py-2 text-slate-300">{v.model_config?.model ?? "—"}</td>
                  <td className="px-3 py-2 text-right">
                    {v.id !== agent.current_version_id && (
                      <button onClick={() => rollbackMut.mutate(v.version)} className="rounded-lg border border-violet-500/40 bg-violet-500/10 px-2 py-1 text-[11px] text-violet-300 hover:bg-violet-500/20">
                        Set as current
                      </button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <div className="mt-4 flex items-center gap-3 text-sm">
          <span className="text-slate-400">Diff</span>
          <select value={a} onChange={(e) => setA(Number(e.target.value))} className="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1 text-white">
            <option value="">A</option>
            {versions.map((v: any) => <option key={v.id} value={v.version}>v{v.version}</option>)}
          </select>
          <span className="text-slate-500">↔</span>
          <select value={b} onChange={(e) => setB(Number(e.target.value))} className="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1 text-white">
            <option value="">B</option>
            {versions.map((v: any) => <option key={v.id} value={v.version}>v{v.version}</option>)}
          </select>
          <button disabled={!a || !b} onClick={() => diffMut.mutate()} className="rounded-lg bg-cyan-500 px-3 py-1 text-xs font-semibold text-slate-950 disabled:opacity-50">Compare</button>
        </div>
        {diff && (
          <div className="mt-3 rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs">
            <div className="text-slate-400">{diff.changes?.length ?? 0} change(s) between v{diff.from} and v{diff.to}</div>
            <ul className="mt-2 max-h-56 space-y-1 overflow-y-auto">
              {(diff.changes ?? []).map((c: any, idx: number) => (
                <li key={idx} className="font-mono text-[11px]">
                  <span className={c.op === "add" ? "text-emerald-300" : c.op === "remove" ? "text-rose-300" : "text-amber-300"}>{c.op}</span>
                  <span className="ml-2 text-slate-300">{c.path}</span>
                </li>
              ))}
            </ul>
          </div>
        )}
      </DialogContent>
      <DialogActions sx={{ px: 3, pb: 3 }}>
        <Button onClick={onClose} variant="text" color="inherit">Close</Button>
      </DialogActions>
    </Dialog>
  );
}

function EditAgentDialog({
  agent, onClose, onSubmit, submitting,
}: { agent: any; onClose: () => void; onSubmit: (p: any) => void; submitting: boolean }) {
  const [name, setName] = useState(agent.name);
  const [description, setDescription] = useState(agent.description ?? "");
  const [systemInstructions, setSystemInstructions] = useState(agent.current_version?.system_instructions ?? "");
  const [model, setModel] = useState(agent.current_version?.model_config?.model ?? "gpt-4o-mini");
  const [memoryScope, setMemoryScope] = useState(agent.current_version?.memory_scope ?? "session");

  return (
    <Dialog open onClose={onClose} maxWidth="sm" fullWidth PaperProps={{ sx: { background: "#0f172a", color: "white", borderRadius: 4 } }}>
      <DialogTitle>Edit agent</DialogTitle>
      <DialogContent>
        <Stack spacing={2} sx={{ mt: 1 }}>
          <TextField label="Name" value={name} onChange={(e) => setName(e.target.value)} fullWidth />
          <TextField label="Description" value={description} onChange={(e) => setDescription(e.target.value)} fullWidth multiline minRows={2} />
          <TextField label="Model" value={model} onChange={(e) => setModel(e.target.value)} fullWidth />
          <TextField select label="Memory scope" value={memoryScope} onChange={(e) => setMemoryScope(e.target.value)} fullWidth>
            {["none", "session", "project", "tenant"].map((m) => <MenuItem key={m} value={m}>{m}</MenuItem>)}
          </TextField>
          <TextField label="System instructions" value={systemInstructions} onChange={(e) => setSystemInstructions(e.target.value)} fullWidth multiline minRows={4} />
        </Stack>
      </DialogContent>
      <DialogActions sx={{ px: 3, pb: 3 }}>
        <Button onClick={onClose} variant="text" color="inherit">Cancel</Button>
        <Button
          onClick={() => onSubmit({
            name, description, system_instructions: systemInstructions,
            memory_scope: memoryScope,
            model_config: { ...(agent.current_version?.model_config ?? {}), model },
          })}
          variant="contained"
          disabled={submitting}
        >{submitting ? "Saving…" : "Save"}</Button>
      </DialogActions>
    </Dialog>
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
