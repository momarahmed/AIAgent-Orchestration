"use client";

import { useEffect, useState } from "react";
import { memoryApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, EmptyState } from "@/components/shared/PageHeader";

export default function MemoryPage() {
  const auth = useAuth();
  const [collections, setCollections] = useState<any[]>([]);
  const [selected, setSelected] = useState<any | null>(null);
  const [items, setItems] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<any[]>([]);
  const [newText, setNewText] = useState("");
  const [tags, setTags] = useState("");

  const load = () =>
    memoryApi.collections({ tenant_id: auth.activeTenantId }).then((list) => {
      setCollections(list);
      const first = selected ?? list[0];
      if (first) setSelected(first);
      if (first) memoryApi.items(first.id).then(setItems);
    }).finally(() => setLoading(false));

  useEffect(() => { if (auth.activeTenantId) { setLoading(true); load(); } }, [auth.activeTenantId]);

  const createCollection = async () => {
    const name = prompt("Collection name?");
    if (!name) return;
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, "-");
    await memoryApi.createCollection({ tenant_id: auth.activeTenantId, name, slug, scope: "tenant" });
    load();
  };

  const remember = async () => {
    if (!selected || !newText.trim()) return;
    const tagList = tags.split(",").map((t) => t.trim()).filter(Boolean);
    await memoryApi.remember(selected.id, { content: newText, metadata: { tags: tagList } });
    setNewText(""); setTags("");
    memoryApi.items(selected.id).then(setItems);
  };

  const retrieve = async () => {
    if (!selected || !query.trim()) return;
    const r = await memoryApi.retrieve(selected.id, { query, top_k: 5 });
    setResults(r.data || []);
  };

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading memory…</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader
        title="Memory & RAG"
        description="Short-term (Redis) and long-term (MySQL + Qdrant) memory with scope enforcement and embedding-backed retrieval."
        actions={<button onClick={createCollection} className="rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30">+ New Collection</button>}
      />

      {collections.length === 0 ? (
        <EmptyState title="No memory collections" description="Create your first collection to start storing tenant/project/agent-scoped memories with RAG retrieval." />
      ) : (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
          <aside className="space-y-2">
            {collections.map((c) => (
              <button key={c.id} onClick={() => { setSelected(c); memoryApi.items(c.id).then(setItems); }} className={`w-full rounded-xl border px-3 py-2 text-left text-sm ${selected?.id === c.id ? "border-cyan-500 bg-cyan-500/10 text-white" : "border-slate-800 bg-slate-900/40 text-slate-300 hover:bg-slate-900"}`}>
                <div className="font-medium">{c.name}</div>
                <div className="text-xs text-slate-500">scope: {c.scope} · dim {c.embedding_dim}</div>
              </button>
            ))}
          </aside>

          {selected && (
            <section className="space-y-4">
              <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h2 className="text-sm font-semibold text-white">Remember</h2>
                <textarea className="mt-2 h-24 w-full rounded-xl border border-slate-800 bg-slate-950 p-3 text-sm" value={newText} onChange={(e) => setNewText(e.target.value)} placeholder="Long-form text to remember…" />
                <div className="mt-2 flex gap-2">
                  <input className="flex-1 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs" placeholder="tags, comma separated" value={tags} onChange={(e) => setTags(e.target.value)} />
                  <button onClick={remember} className="rounded-xl bg-emerald-500/20 px-3 py-2 text-xs font-semibold text-emerald-200 hover:bg-emerald-500/30">Remember</button>
                </div>
              </div>

              <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h2 className="text-sm font-semibold text-white">Retrieve (RAG)</h2>
                <div className="mt-2 flex gap-2">
                  <input className="flex-1 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" placeholder="ask anything…" value={query} onChange={(e) => setQuery(e.target.value)} />
                  <button onClick={retrieve} className="rounded-xl bg-violet-500/20 px-3 py-2 text-sm font-semibold text-violet-200 hover:bg-violet-500/30">Retrieve</button>
                </div>
                <div className="mt-3 space-y-2">
                  {results.map((r, i) => (
                    <div key={i} className="rounded-xl border border-slate-800 bg-slate-950/60 p-3 text-sm text-slate-200">
                      <div className="text-xs text-cyan-300">score {Number(r.score).toFixed(3)}</div>
                      <div className="mt-1 whitespace-pre-wrap">{r.item?.content || r.content || r.text}</div>
                    </div>
                  ))}
                </div>
              </div>

              <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
                <h2 className="text-sm font-semibold text-white">Stored items ({items.length})</h2>
                <ul className="mt-3 max-h-96 space-y-2 overflow-auto text-sm">
                  {items.map((it) => {
                    const itemTags: string[] = it.metadata?.tags || [];
                    return (
                      <li key={it.id} className="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
                        <div className="text-xs text-slate-500">{new Date(it.created_at).toLocaleString()}</div>
                        <div className="mt-1 text-slate-200">{it.content}</div>
                        {itemTags.length > 0 && <div className="mt-2 flex gap-1">{itemTags.map((t) => <span key={t} className="rounded-full border border-slate-700 px-2 py-0.5 text-[10px] text-slate-400">{t}</span>)}</div>}
                      </li>
                    );
                  })}
                </ul>
              </div>
            </section>
          )}
        </div>
      )}
    </div>
  );
}
