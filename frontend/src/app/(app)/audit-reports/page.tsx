"use client";

import { useEffect, useState } from "react";
import { auditReportsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

export default function AuditReportsPage() {
  const auth = useAuth();
  const [tab, setTab] = useState<"events" | "templates" | "exports">("events");
  const [events, setEvents] = useState<any[]>([]);
  const [templates, setTemplates] = useState<any[]>([]);
  const [exports, setExports] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [exporting, setExporting] = useState(false);
  const [exportFormat, setExportFormat] = useState<"csv" | "json" | "pdf">("csv");
  const [eventFilters, setEventFilters] = useState({ event_type: "", subject_type: "" });

  useEffect(() => {
    setLoading(true);
    const params: any = { tenant_id: auth.activeTenantId, per_page: 50 };
    if (eventFilters.event_type) params.event_type = eventFilters.event_type;
    if (eventFilters.subject_type) params.subject_type = eventFilters.subject_type;

    Promise.all([
      auditReportsApi.events(params).then((r: any) => setEvents(r.data || [])),
      auditReportsApi.templates().then(setTemplates),
      auditReportsApi.exports({ tenant_id: auth.activeTenantId }).then((r: any) => setExports(r.data || [])),
    ]).finally(() => setLoading(false));
  }, [auth.activeTenantId, eventFilters]);

  const handleExport = async (templateId?: number) => {
    setExporting(true);
    try {
      await auditReportsApi.export({
        tenant_id: auth.activeTenantId,
        format: exportFormat,
        template_id: templateId,
        period_start: new Date(Date.now() - 30 * 86400000).toISOString().split("T")[0],
        period_end: new Date().toISOString().split("T")[0],
      });
      const r: any = await auditReportsApi.exports({ tenant_id: auth.activeTenantId });
      setExports(r.data || []);
    } finally {
      setExporting(false);
    }
  };

  return (
    <div className="space-y-6 p-6">
      <PageHeader title="Audit Reports" description="Browse audit events, generate compliance reports, and export evidence packages." />

      <div className="flex items-center gap-4 border-b border-slate-800 pb-2">
        {(["events", "templates", "exports"] as const).map((t) => (
          <button key={t} onClick={() => setTab(t)}
            className={`rounded-lg px-4 py-2 text-sm font-medium transition ${tab === t ? "bg-cyan-500/10 text-cyan-400" : "text-slate-400 hover:text-white"}`}>
            {t === "events" ? "Audit Events" : t === "templates" ? "Report Templates" : "Exports"}
          </button>
        ))}
      </div>

      {tab === "events" && (
        <div className="space-y-4">
          <div className="flex gap-3">
            <select value={eventFilters.event_type} onChange={(e) => setEventFilters((f) => ({ ...f, event_type: e.target.value }))}
              className="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-white">
              <option value="">All Event Types</option>
              {["create", "update", "delete", "deploy", "approve", "reject", "export", "import", "tool_call", "security"].map((t) => (
                <option key={t} value={t}>{t}</option>
              ))}
            </select>
            <select value={eventFilters.subject_type} onChange={(e) => setEventFilters((f) => ({ ...f, subject_type: e.target.value }))}
              className="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-sm text-white">
              <option value="">All Subjects</option>
              {["agent", "mcp_server", "workflow", "deployment", "opa_policy", "role", "template"].map((t) => (
                <option key={t} value={t}>{t}</option>
              ))}
            </select>
          </div>

          <div className="overflow-x-auto rounded-2xl border border-slate-800">
            <table className="w-full text-left text-sm">
              <thead className="border-b border-slate-800 bg-slate-900/50 text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-4 py-3">Event</th>
                  <th className="px-4 py-3">Action</th>
                  <th className="px-4 py-3">Subject</th>
                  <th className="px-4 py-3">User</th>
                  <th className="px-4 py-3">IP</th>
                  <th className="px-4 py-3">Time</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/50">
                {events.map((e: any) => (
                  <tr key={e.id} className="hover:bg-slate-900/30">
                    <td className="px-4 py-3"><span className="rounded bg-violet-500/10 px-2 py-0.5 text-xs text-violet-400">{e.event_type}</span></td>
                    <td className="px-4 py-3 text-slate-300">{e.action}</td>
                    <td className="px-4 py-3 text-slate-400">{e.subject_type}#{e.subject_id}</td>
                    <td className="px-4 py-3 text-slate-400">{e.user_id || "—"}</td>
                    <td className="px-4 py-3 text-xs text-slate-500">{e.ip_address}</td>
                    <td className="px-4 py-3 text-xs text-slate-500">{new Date(e.created_at).toLocaleString()}</td>
                  </tr>
                ))}
                {events.length === 0 && (
                  <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-500">No audit events found.</td></tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {tab === "templates" && (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {templates.map((t: any) => (
            <div key={t.id} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
              <div className="text-sm font-semibold text-white">{t.name}</div>
              <div className="mt-1 flex items-center gap-2 text-xs text-slate-500">
                <span className="rounded bg-cyan-500/10 px-2 py-0.5 text-cyan-400">{t.category}</span>
                {t.is_system && <span className="rounded bg-amber-500/10 px-2 py-0.5 text-amber-400">System</span>}
              </div>
              {t.description && <div className="mt-2 text-xs text-slate-400">{t.description}</div>}
              <div className="mt-3 flex items-center gap-2">
                <select value={exportFormat} onChange={(e) => setExportFormat(e.target.value as any)}
                  className="rounded-lg border border-slate-800 bg-slate-900 px-2 py-1 text-xs text-white">
                  <option value="csv">CSV</option>
                  <option value="json">JSON</option>
                  <option value="pdf">PDF</option>
                </select>
                <button onClick={() => handleExport(t.id)} disabled={exporting}
                  className="rounded-lg bg-cyan-500/10 px-3 py-1 text-xs text-cyan-400 hover:bg-cyan-500/20 disabled:opacity-50">
                  {exporting ? "Exporting..." : "Export"}
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {tab === "exports" && (
        <div className="space-y-3">
          <button onClick={() => handleExport()} disabled={exporting}
            className="rounded-2xl bg-cyan-500/10 px-4 py-2 text-sm font-medium text-cyan-400 hover:bg-cyan-500/20 disabled:opacity-50">
            {exporting ? "Generating..." : "Generate 30-Day Export"}
          </button>
          {exports.map((e: any) => (
            <div key={e.id} className="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
              <div>
                <div className="flex items-center gap-2">
                  <span className="text-sm font-semibold text-white">{e.format?.toUpperCase()} Export</span>
                  <span className={`rounded px-2 py-0.5 text-xs ${e.status === "completed" ? "bg-emerald-500/10 text-emerald-400" : "bg-amber-500/10 text-amber-400"}`}>{e.status}</span>
                </div>
                <div className="mt-1 text-xs text-slate-500">{e.record_count} records | {new Date(e.created_at).toLocaleString()}</div>
              </div>
              {e.status === "completed" && (
                <button onClick={() => auditReportsApi.download(e.id)}
                  className="rounded-lg bg-violet-500/10 px-3 py-1 text-xs text-violet-400 hover:bg-violet-500/20">Download</button>
              )}
            </div>
          ))}
          {exports.length === 0 && <div className="py-8 text-center text-sm text-slate-500">No exports yet. Generate one above.</div>}
        </div>
      )}
    </div>
  );
}
