"use client";

import { useEffect, useState } from "react";
import { promptsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function PromptsPage() {
  const auth = useAuth();
  const [prompts, setPrompts] = useState<any[]>([]);
  const [selected, setSelected] = useState<any | null>(null);
  const [loading, setLoading] = useState(true);
  const [draft, setDraft] = useState("");
  const [changelog, setChangelog] = useState("");
  const [rendered, setRendered] = useState<any>(null);
  const [variables, setVariables] = useState("{}");

  const load = (keepId?: number) => {
    promptsApi.list({ tenant_id: auth.activeTenantId }).then((list) => {
      setPrompts(list);
      const id = keepId ?? selected?.id ?? list[0]?.id;
      if (id) promptsApi.get(id).then(setSelected);
    }).finally(() => setLoading(false));
  };

  useEffect(() => { if (auth.activeTenantId) { setLoading(true); load(); } }, [auth.activeTenantId]);

  const createPrompt = async () => {
    const name = prompt("Prompt name?");
    if (!name) return;
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    const body = prompt("Initial body (use {{variable}})") || "Hello {{name}}";
    await promptsApi.create({ tenant_id: auth.activeTenantId, name, slug, category: "agent_system", body, changelog: "Initial version" });
    load();
  };

  const newVersion = async () => {
    if (!selected) return;
    await promptsApi.newVersion(selected.id, { body: draft || selected.versions?.[0]?.body, changelog: changelog || "edit", activate: true });
    load(selected.id);
  };

  const render = async () => {
    if (!selected) return;
    try {
      const r = await promptsApi.render(selected.id, JSON.parse(variables || "{}"));
      setRendered(r);
    } catch (e: any) {
      setRendered({ error: e?.message || "invalid JSON" });
    }
  };

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading prompts…</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader
        title="Prompt Registry"
        description="Versioned prompts with diffing, evaluation, A/B testing and runtime rendering. Treat prompts as code."
        actions={<button onClick={createPrompt} className="rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30">+ New Prompt</button>}
      />

      {prompts.length === 0 ? (
        <EmptyState title="No prompts yet" description="Register your first prompt to start versioning system, tool-select and meta-agent templates." />
      ) : (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
          <aside className="space-y-2">
            {prompts.map((p) => (
              <button key={p.id} onClick={() => promptsApi.get(p.id).then(setSelected)} className={`w-full rounded-xl border px-3 py-2 text-left text-sm ${selected?.id === p.id ? "border-cyan-500 bg-cyan-500/10 text-white" : "border-slate-800 bg-slate-900/40 text-slate-300 hover:bg-slate-900"}`}>
                <div className="font-medium">{p.name}</div>
                <div className="text-xs text-slate-500">{p.category} · v{p.current_version}</div>
              </button>
            ))}
          </aside>

          {selected && (
            <section className="space-y-4">
              <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <div className="flex items-center justify-between">
                  <div>
                    <h2 className="text-lg font-semibold text-white">{selected.name}</h2>
                    <div className="text-xs text-slate-500">{selected.slug} · current v{selected.current_version}</div>
                  </div>
                  <StatusBadge value={selected.versions?.[0]?.status || "draft"} />
                </div>
                <div className="mt-4">
                  <label className="text-xs font-semibold uppercase text-slate-400">New version body</label>
                  <textarea className="mt-1 h-40 w-full rounded-xl border border-slate-800 bg-slate-950 p-3 font-mono text-xs text-slate-200" defaultValue={selected.versions?.[0]?.body} onChange={(e) => setDraft(e.target.value)} />
                  <div className="mt-2 flex gap-2">
                    <input className="flex-1 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs" placeholder="changelog" value={changelog} onChange={(e) => setChangelog(e.target.value)} />
                    <button onClick={newVersion} className="rounded-xl bg-emerald-500/20 px-3 py-2 text-xs font-semibold text-emerald-200 hover:bg-emerald-500/30">Save & Activate</button>
                  </div>
                </div>
              </div>

              <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h3 className="text-sm font-semibold text-white">Render preview</h3>
                <textarea className="mt-2 h-24 w-full rounded-xl border border-slate-800 bg-slate-950 p-3 font-mono text-xs text-slate-200" value={variables} onChange={(e) => setVariables(e.target.value)} />
                <button onClick={render} className="mt-2 rounded-xl bg-violet-500/20 px-3 py-2 text-xs font-semibold text-violet-200 hover:bg-violet-500/30">Render</button>
                {rendered && (
                  <pre className="mt-3 overflow-auto rounded-xl border border-slate-800 bg-slate-950 p-3 text-xs text-cyan-200">{JSON.stringify(rendered, null, 2)}</pre>
                )}
              </div>

              <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h3 className="text-sm font-semibold text-white">Version history</h3>
                <ul className="mt-2 divide-y divide-slate-800 text-sm">
                  {(selected.versions || []).map((v: any) => (
                    <li key={v.id} className="flex items-center justify-between py-2">
                      <div>
                        <div className="text-white">v{v.version}</div>
                        <div className="text-xs text-slate-500">{v.changelog}</div>
                      </div>
                      <StatusBadge value={v.status} />
                    </li>
                  ))}
                </ul>
              </div>
            </section>
          )}
        </div>
      )}
    </div>
  );
}
