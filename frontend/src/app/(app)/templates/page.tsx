"use client";

import { useRef, useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { agentsApi, mcpServersApi, projectsApi, templateLifecycleApi, templatesApi, workflowsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, EmptyState } from "@/components/shared/PageHeader";

export default function TemplatesPage() {
  const auth = useAuth();
  const qc = useQueryClient();
  const fileRef = useRef<HTMLInputElement>(null);
  const [showFromAsset, setShowFromAsset] = useState(false);
  const [instantiate, setInstantiate] = useState<any | null>(null);

  const templates = useQuery({
    queryKey: ["templates", auth.activeTenantId],
    queryFn: () => templatesApi.list({ tenant_id: auth.activeTenantId }),
    enabled: !!auth.activeTenantId,
  });

  const importMut = useMutation({
    mutationFn: (manifest: any) => templateLifecycleApi.importJson(manifest, auth.activeTenantId ?? undefined),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["templates"] }),
  });

  const handleImport = async (file: File) => {
    const text = await file.text();
    try {
      const manifest = JSON.parse(text);
      importMut.mutate(manifest);
    } catch (e) {
      alert("File must be a valid template JSON");
    }
  };

  const items = templates.data ?? [];

  return (
    <div>
      <PageHeader
        eyebrow="Phase 2 · Lifecycle"
        title="Templates"
        description="Save assets as templates, export ZIP/JSON, import packages, and instantiate concrete assets with parameters. Secrets are stripped automatically."
        actions={
          <div className="flex gap-2">
            <input
              ref={fileRef}
              type="file"
              accept=".json"
              className="hidden"
              onChange={(e) => e.target.files?.[0] && handleImport(e.target.files[0])}
            />
            <button onClick={() => fileRef.current?.click()} className="rounded-2xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:border-cyan-400/40">
              Import JSON
            </button>
            <button onClick={() => setShowFromAsset(true)} className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20">
              + Save asset as template
            </button>
          </div>
        }
      />
      <div className="px-4 py-6 lg:px-6">
        {importMut.data?.issues?.length ? (
          <div className="mb-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 p-3 text-xs text-amber-200">
            <div className="font-semibold">Import warnings</div>
            <ul className="mt-1 list-disc pl-4">
              {importMut.data.issues.map((i: string, idx: number) => <li key={idx}>{i}</li>)}
            </ul>
          </div>
        ) : null}
        {templates.isLoading ? (
          <p className="text-sm text-slate-400">Loading templates…</p>
        ) : items.length === 0 ? (
          <EmptyState
            title="No templates yet"
            description="Save an asset as a template, or import one from JSON/ZIP."
          />
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((t: any) => (
              <div key={t.id} className="rounded-3xl border border-slate-800 bg-slate-900/60 p-5">
                <div className="text-xs uppercase tracking-wider text-slate-500">{t.asset_type}</div>
                <h3 className="mt-1 text-lg font-semibold text-white">{t.name}</h3>
                <p className="mt-2 text-sm text-slate-400">{t.description ?? "—"}</p>
                <div className="mt-4 flex flex-wrap gap-2">
                  <a
                    href={`/api/templates/${t.id}/export.zip`}
                    className="rounded-xl border border-slate-700 px-3 py-1 text-xs text-slate-200 hover:border-cyan-400/40"
                  >Export ZIP</a>
                  <button
                    onClick={async () => {
                      const data = await templateLifecycleApi.exportJson(t.id);
                      const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" });
                      const url = URL.createObjectURL(blob);
                      const a = document.createElement("a");
                      a.href = url; a.download = `${t.slug}.json`; a.click();
                      URL.revokeObjectURL(url);
                    }}
                    className="rounded-xl border border-slate-700 px-3 py-1 text-xs text-slate-200 hover:border-cyan-400/40"
                  >Export JSON</button>
                  <button onClick={() => setInstantiate(t)} className="rounded-xl bg-cyan-500/10 px-3 py-1 text-xs text-cyan-300 hover:bg-cyan-500/20">
                    Instantiate
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {showFromAsset && <FromAssetDialog onClose={() => setShowFromAsset(false)} onCreated={() => { setShowFromAsset(false); qc.invalidateQueries({ queryKey: ["templates"] }); }} />}
      {instantiate && <InstantiateDialog template={instantiate} onClose={() => setInstantiate(null)} />}
    </div>
  );
}

function FromAssetDialog({ onClose, onCreated }: { onClose: () => void; onCreated: () => void }) {
  const auth = useAuth();
  const [type, setType] = useState<"agent" | "mcp_server" | "workflow">("workflow");
  const [assetId, setAssetId] = useState<number | "">("");
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");

  const { data: agents = [] } = useQuery({ queryKey: ["agents", auth.activeTenantId, "tpl"], queryFn: () => agentsApi.list({ tenant_id: auth.activeTenantId }), enabled: !!auth.activeTenantId });
  const { data: mcps = [] } = useQuery({ queryKey: ["mcps", auth.activeTenantId, "tpl"], queryFn: () => mcpServersApi.list({ tenant_id: auth.activeTenantId }), enabled: !!auth.activeTenantId });
  const { data: workflows = [] } = useQuery({ queryKey: ["workflows", auth.activeTenantId, "tpl"], queryFn: () => workflowsApi.list({ tenant_id: auth.activeTenantId }), enabled: !!auth.activeTenantId });

  const list = type === "agent" ? agents : type === "mcp_server" ? mcps : workflows;

  const createMut = useMutation({
    mutationFn: () => templateLifecycleApi.fromAsset({ asset_type: type, asset_id: Number(assetId), name, description, tenant_id: auth.activeTenantId }),
    onSuccess: onCreated,
  });

  return (
    <div className="fixed inset-0 z-30 flex items-center justify-center bg-slate-950/80 px-4">
      <div className="w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-950 p-6">
        <div className="text-lg font-semibold text-white">Save asset as template</div>
        <div className="mt-4 space-y-3 text-sm">
          <select value={type} onChange={(e) => { setType(e.target.value as any); setAssetId(""); }} className="w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white">
            <option value="agent">Agent</option>
            <option value="mcp_server">MCP Server</option>
            <option value="workflow">Workflow</option>
          </select>
          <select value={assetId} onChange={(e) => setAssetId(Number(e.target.value))} className="w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white">
            <option value="">Select asset…</option>
            {list.map((a: any) => <option key={a.id} value={a.id}>{a.name}</option>)}
          </select>
          <input value={name} onChange={(e) => setName(e.target.value)} placeholder="Template name" className="w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white" />
          <textarea value={description} onChange={(e) => setDescription(e.target.value)} placeholder="Description" rows={3} className="w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white" />
        </div>
        <div className="mt-5 flex justify-end gap-2">
          <button onClick={onClose} className="rounded-xl border border-slate-800 px-3 py-2 text-sm text-slate-300">Cancel</button>
          <button disabled={!assetId || !name || createMut.isPending} onClick={() => createMut.mutate()} className="rounded-xl bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 disabled:opacity-50">
            {createMut.isPending ? "Saving…" : "Create template"}
          </button>
        </div>
      </div>
    </div>
  );
}

function InstantiateDialog({ template, onClose }: { template: any; onClose: () => void }) {
  const auth = useAuth();
  const [params, setParams] = useState<Record<string, string>>({});
  const { data: projects = [] } = useQuery({ queryKey: ["projects", auth.activeTenantId, "inst"], queryFn: () => projectsApi.list(auth.activeTenantId ?? undefined), enabled: !!auth.activeTenantId });
  const [projectId, setProjectId] = useState<number | "">(auth.activeProjectId ?? "");

  const instantiate = useMutation({
    mutationFn: () => templateLifecycleApi.instantiate(template.id, {
      tenant_id: auth.activeTenantId, project_id: projectId, parameters: params,
    }),
    onSuccess: (data) => { alert(`Created ${data.type} #${data.id}`); onClose(); },
  });

  const schema = template.parameters_schema ?? {};

  return (
    <div className="fixed inset-0 z-30 flex items-center justify-center bg-slate-950/80 px-4">
      <div className="w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-950 p-6">
        <div className="text-lg font-semibold text-white">Instantiate {template.name}</div>
        <div className="mt-1 text-xs text-slate-500">{template.asset_type}</div>
        <div className="mt-4 space-y-3 text-sm">
          <div>
            <label className="text-xs uppercase tracking-wider text-slate-500">Project</label>
            <select value={projectId} onChange={(e) => setProjectId(Number(e.target.value))} className="mt-1 w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white">
              <option value="">Select…</option>
              {projects.map((p: any) => <option key={p.id} value={p.id}>{p.name}</option>)}
            </select>
          </div>
          {Object.entries(schema).map(([k, v]: [string, any]) => (
            <div key={k}>
              <label className="text-xs uppercase tracking-wider text-slate-500">{k} {v.required && <span className="text-rose-400">*</span>}</label>
              <input
                value={params[k] ?? ""}
                onChange={(e) => setParams({ ...params, [k]: e.target.value })}
                placeholder={v.description ?? ""}
                className="mt-1 w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white"
              />
            </div>
          ))}
        </div>
        <div className="mt-5 flex justify-end gap-2">
          <button onClick={onClose} className="rounded-xl border border-slate-800 px-3 py-2 text-sm text-slate-300">Cancel</button>
          <button disabled={!projectId || instantiate.isPending} onClick={() => instantiate.mutate()} className="rounded-xl bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 disabled:opacity-50">
            {instantiate.isPending ? "Creating…" : "Create"}
          </button>
        </div>
      </div>
    </div>
  );
}
