"use client";

import { useMemo, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Button, Dialog, DialogActions, DialogContent, DialogTitle, MenuItem, Stack, TextField } from "@mui/material";
import { mcpServersApi, projectsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function McpServersPage() {
  const auth = useAuth();
  const qc = useQueryClient();
  const tenantId = auth.activeTenantId;

  const [open, setOpen] = useState(false);

  const projects = useQuery({
    queryKey: ["projects", tenantId],
    queryFn: () => projectsApi.list(tenantId ?? undefined),
    enabled: !!tenantId,
  });

  const mcps = useQuery({
    queryKey: ["mcp-servers", tenantId],
    queryFn: () => mcpServersApi.list({ tenant_id: tenantId }),
    enabled: !!tenantId,
  });

  const items = useMemo(() => mcps.data?.data ?? mcps.data ?? [], [mcps.data]);

  const create = useMutation({
    mutationFn: (payload: any) => mcpServersApi.create(payload),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ["mcp-servers"] }); setOpen(false); },
  });

  const archive = useMutation({
    mutationFn: (id: number) => mcpServersApi.remove(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["mcp-servers"] }),
  });

  const health = useMutation({
    mutationFn: (id: number) => mcpServersApi.health(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["mcp-servers"] }),
  });

  return (
    <div>
      <PageHeader
        eyebrow="Build · Phase 1"
        title="MCP Studio"
        description="Register MCP servers (stdio / http / streamable-http / sse), define tools with JSON schemas, assign risk levels, and run health checks."
        actions={
          <button onClick={() => setOpen(true)} className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20">
            + Register MCP Server
          </button>
        }
      />

      {mcps.isLoading ? (
        <div className="px-4 py-10 text-sm text-slate-400 lg:px-6">Loading MCP servers…</div>
      ) : items.length === 0 ? (
        <EmptyState
          title="No MCP servers yet"
          description="Register your first MCP server to expose its tools to agents and workflows."
          action={<button onClick={() => setOpen(true)} className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white">+ Register</button>}
        />
      ) : (
        <div className="grid gap-4 px-4 py-6 sm:grid-cols-2 lg:grid-cols-3 lg:px-6">
          {items.map((m: any) => (
            <div key={m.id} className="rounded-3xl border border-slate-800 bg-slate-900/60 p-5">
              <div className="flex items-start justify-between gap-3">
                <div>
                  <div className="text-xs uppercase tracking-wider text-slate-500">{m.transport} · {m.runtime}</div>
                  <h3 className="mt-1 text-lg font-semibold text-white">{m.name}</h3>
                  <p className="mt-1 line-clamp-2 text-xs text-slate-500">{m.description}</p>
                </div>
                <StatusBadge value={m.health || "unknown"} />
              </div>

              <dl className="mt-4 grid grid-cols-2 gap-2 text-xs">
                <div>
                  <dt className="text-slate-500">Endpoint</dt>
                  <dd className="truncate text-slate-300" title={m.endpoint}>{m.endpoint || "—"}</dd>
                </div>
                <div>
                  <dt className="text-slate-500">Auth</dt>
                  <dd className="text-slate-300">{m.auth_method}</dd>
                </div>
                <div>
                  <dt className="text-slate-500">Tools</dt>
                  <dd className="text-slate-300">{m.tools?.length ?? 0}</dd>
                </div>
                <div>
                  <dt className="text-slate-500">Status</dt>
                  <dd><StatusBadge value={m.status} /></dd>
                </div>
              </dl>

              <div className="mt-4 flex flex-wrap gap-2">
                <button onClick={() => health.mutate(m.id)} className="rounded-xl border border-slate-700 px-3 py-1.5 text-xs text-slate-200 hover:border-cyan-400/40">
                  Health check
                </button>
                <button onClick={() => { if (confirm("Archive MCP server?")) archive.mutate(m.id); }} className="rounded-xl border border-rose-700/60 px-3 py-1.5 text-xs text-rose-300 hover:bg-rose-500/10">
                  Archive
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      <NewMcpDialog open={open} onClose={() => setOpen(false)} projects={projects.data ?? []} tenantId={tenantId} onSubmit={(p) => create.mutate(p)} submitting={create.isPending} />
    </div>
  );
}

function NewMcpDialog({
  open, onClose, projects, tenantId, onSubmit, submitting,
}: { open: boolean; onClose: () => void; projects: any[]; tenantId: number | null; onSubmit: (p: any) => void; submitting: boolean }) {
  const [name, setName] = useState("");
  const [projectId, setProjectId] = useState<number | "">("");
  const [endpoint, setEndpoint] = useState("");
  const [transport, setTransport] = useState("http");
  const [runtime, setRuntime] = useState("python");
  const [authMethod, setAuthMethod] = useState("none");

  return (
    <Dialog open={open} onClose={onClose} maxWidth="sm" fullWidth PaperProps={{ sx: { background: "#0f172a", color: "white", borderRadius: 4 } }}>
      <DialogTitle>Register MCP server</DialogTitle>
      <DialogContent>
        <Stack spacing={2} sx={{ mt: 1 }}>
          <TextField label="Name" value={name} onChange={(e) => setName(e.target.value)} required fullWidth />
          <TextField select label="Project" value={projectId} onChange={(e) => setProjectId(Number(e.target.value))} required fullWidth>
            {projects.map((p: any) => <MenuItem key={p.id} value={p.id}>{p.name}</MenuItem>)}
          </TextField>
          <TextField label="Endpoint" value={endpoint} onChange={(e) => setEndpoint(e.target.value)} fullWidth placeholder="http://127.0.0.1:9100/mcp" />
          <TextField select label="Transport" value={transport} onChange={(e) => setTransport(e.target.value)} fullWidth>
            {["stdio", "http", "streamable-http", "sse"].map((t) => <MenuItem key={t} value={t}>{t}</MenuItem>)}
          </TextField>
          <TextField label="Runtime" value={runtime} onChange={(e) => setRuntime(e.target.value)} fullWidth />
          <TextField select label="Auth method" value={authMethod} onChange={(e) => setAuthMethod(e.target.value)} fullWidth>
            {["none", "api_key", "oauth2", "bearer"].map((t) => <MenuItem key={t} value={t}>{t}</MenuItem>)}
          </TextField>
        </Stack>
      </DialogContent>
      <DialogActions sx={{ px: 3, pb: 3 }}>
        <Button onClick={onClose} variant="text" color="inherit">Cancel</Button>
        <Button
          onClick={() => onSubmit({ tenant_id: tenantId, project_id: projectId, name, endpoint, transport, runtime, auth_method: authMethod })}
          variant="contained"
          disabled={!name || !projectId || submitting}
        >{submitting ? "Creating…" : "Register"}</Button>
      </DialogActions>
    </Dialog>
  );
}
