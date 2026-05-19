"use client";

import { useEffect, useState } from "react";
import { migrationsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, StatusBadge, EmptyState } from "@/components/shared/PageHeader";

export default function MigrationsPage() {
  const auth = useAuth();
  const [imports, setImports] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [form, setForm] = useState({ source_format: "n8n", source_url: "", source_payload: "" });

  const load = () => migrationsApi.list({ tenant_id: auth.activeTenantId }).then(setImports).finally(() => setLoading(false));
  useEffect(() => { if (auth.activeTenantId) { setLoading(true); load(); } }, [auth.activeTenantId]);

  const doImport = async () => {
    let source: any = form.source_payload;
    if (form.source_payload) {
      try { source = JSON.parse(form.source_payload); } catch { source = form.source_payload; }
    } else if (form.source_url) {
      source = { url: form.source_url };
    }
    await migrationsApi.import({
      tenant_id: auth.activeTenantId!,
      source_format: form.source_format,
      source,
    });
    load();
  };

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading migrations…</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader title="Migration Imports" description="Import workflows from n8n, Flowise, Dify, generic JSON or YAML. The Migration Agent normalizes them into native platform workflows." />

      <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
        <h3 className="text-sm font-semibold text-white">New import</h3>
        <div className="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
          <select className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" value={form.source_format} onChange={(e) => setForm({ ...form, source_format: e.target.value })}>
            {["n8n", "flowise", "dify", "json", "yaml"].map((f) => <option key={f}>{f}</option>)}
          </select>
          <input className="md:col-span-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" placeholder="source URL (optional)" value={form.source_url} onChange={(e) => setForm({ ...form, source_url: e.target.value })} />
          <textarea className="md:col-span-3 h-32 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 font-mono text-xs" placeholder="paste source payload here" value={form.source_payload} onChange={(e) => setForm({ ...form, source_payload: e.target.value })} />
          <button onClick={doImport} className="rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30 md:col-span-3">Run Import</button>
        </div>
      </section>

      {imports.length === 0 ? (
        <EmptyState title="No imports yet" description="Upload or paste an external workflow definition to migrate it onto the platform." />
      ) : (
        <section className="rounded-2xl border border-slate-800 bg-slate-900/40 p-5">
          <table className="w-full text-left text-sm">
            <thead className="text-xs uppercase text-slate-500"><tr><th className="px-2 py-2">When</th><th>Source</th><th>Status</th><th>Workflow</th><th>Warnings</th></tr></thead>
            <tbody>
              {imports.map((i) => (
                <tr key={i.id} className="border-t border-slate-800 text-slate-300">
                  <td className="px-2 py-2 text-xs">{new Date(i.created_at).toLocaleString()}</td>
                  <td className="px-2">{i.source_format}</td>
                  <td className="px-2"><StatusBadge value={i.status} /></td>
                  <td className="px-2">{i.workflow_id ?? i.workflow_version_id ?? "—"}</td>
                  <td className="px-2 text-xs text-amber-300">{(i.review_checklist || []).length}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </section>
      )}
    </div>
  );
}
