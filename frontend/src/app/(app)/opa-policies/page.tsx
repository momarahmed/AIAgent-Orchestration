"use client";

import { useEffect, useState } from "react";
import { opaPoliciesApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

const CATEGORIES = ["tool_risk", "deployment", "approval", "network", "template_import", "tenant_isolation"] as const;
const STATUS_COLORS: Record<string, string> = {
  active: "bg-emerald-500/10 text-emerald-400",
  draft: "bg-amber-500/10 text-amber-400",
  disabled: "bg-slate-800 text-slate-500",
  archived: "bg-slate-800 text-slate-600",
};

export default function OpaPoliciesPage() {
  const auth = useAuth();
  const [policies, setPolicies] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState<string>("");
  const [selectedPolicy, setSelectedPolicy] = useState<any>(null);
  const [lintResult, setLintResult] = useState<any>(null);

  const fetchPolicies = () => {
    setLoading(true);
    opaPoliciesApi.list({
      tenant_id: auth.activeTenantId,
      ...(selectedCategory ? { category: selectedCategory } : {}),
    }).then((r: any) => setPolicies(r.data || []))
      .finally(() => setLoading(false));
  };

  useEffect(() => { fetchPolicies(); }, [auth.activeTenantId, selectedCategory]);

  const handleActivate = async (id: number) => {
    await opaPoliciesApi.activate(id);
    fetchPolicies();
  };

  const handleDisable = async (id: number) => {
    await opaPoliciesApi.disable(id);
    fetchPolicies();
  };

  const handleLint = async (code: string) => {
    const result = await opaPoliciesApi.lint(code);
    setLintResult(result);
  };

  return (
    <div className="space-y-6 p-6">
      <PageHeader title="OPA Policy Engine" description="Author, version, and manage policy-as-code rules for governance enforcement." />

      <div className="flex flex-wrap gap-2">
        <button onClick={() => setSelectedCategory("")}
          className={`rounded-lg px-3 py-1.5 text-xs font-medium transition ${!selectedCategory ? "bg-cyan-500/10 text-cyan-400" : "text-slate-400 hover:text-white"}`}>
          All
        </button>
        {CATEGORIES.map((c) => (
          <button key={c} onClick={() => setSelectedCategory(c)}
            className={`rounded-lg px-3 py-1.5 text-xs font-medium transition ${selectedCategory === c ? "bg-cyan-500/10 text-cyan-400" : "text-slate-400 hover:text-white"}`}>
            {c.replace(/_/g, " ")}
          </button>
        ))}
      </div>

      {loading ? (
        <div className="flex h-48 items-center justify-center text-slate-400">Loading policies...</div>
      ) : (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div className="space-y-3">
            {policies.length === 0 && <div className="py-8 text-center text-sm text-slate-500">No policies found.</div>}
            {policies.map((p: any) => (
              <div key={p.id} onClick={() => setSelectedPolicy(p)}
                className={`cursor-pointer rounded-2xl border p-4 transition ${selectedPolicy?.id === p.id ? "border-cyan-500/40 bg-cyan-500/5" : "border-slate-800 bg-slate-900/60 hover:border-slate-700"}`}>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-semibold text-white">{p.name}</span>
                    <span className={`rounded px-2 py-0.5 text-xs ${STATUS_COLORS[p.status] || ""}`}>{p.status}</span>
                  </div>
                  <span className="rounded bg-slate-800 px-2 py-0.5 text-[10px] text-slate-400">v{p.version}</span>
                </div>
                <div className="mt-1 flex items-center gap-2 text-xs text-slate-500">
                  <span className="rounded bg-violet-500/10 px-2 py-0.5 text-violet-400">{p.category}</span>
                  <span>{p.package_path}</span>
                </div>
                {p.description && <div className="mt-2 text-xs text-slate-400">{p.description}</div>}
                <div className="mt-3 flex gap-2">
                  {p.status === "draft" && (
                    <button onClick={(e) => { e.stopPropagation(); handleActivate(p.id); }}
                      className="rounded-lg bg-emerald-500/10 px-3 py-1 text-xs text-emerald-400 hover:bg-emerald-500/20">Activate</button>
                  )}
                  {p.status === "active" && (
                    <button onClick={(e) => { e.stopPropagation(); handleDisable(p.id); }}
                      className="rounded-lg bg-rose-500/10 px-3 py-1 text-xs text-rose-400 hover:bg-rose-500/20">Disable</button>
                  )}
                </div>
              </div>
            ))}
          </div>

          {selectedPolicy && (
            <div className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
              <div className="mb-3 flex items-center justify-between">
                <span className="text-sm font-semibold text-white">{selectedPolicy.name} — Rego Code</span>
                <button onClick={() => handleLint(selectedPolicy.rego_code)}
                  className="rounded-lg bg-cyan-500/10 px-3 py-1 text-xs text-cyan-400 hover:bg-cyan-500/20">Lint</button>
              </div>
              <pre className="max-h-[500px] overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-300">
                <code>{selectedPolicy.rego_code}</code>
              </pre>
              {lintResult && (
                <div className="mt-3 rounded-xl border border-slate-800 bg-slate-950 p-3">
                  <div className={`text-xs font-semibold ${lintResult.valid ? "text-emerald-400" : "text-rose-400"}`}>
                    {lintResult.valid ? "Valid" : "Issues Found"}
                  </div>
                  {lintResult.issues?.map((issue: any, i: number) => (
                    <div key={i} className={`mt-1 text-xs ${issue.severity === "error" ? "text-rose-400" : "text-amber-400"}`}>
                      [{issue.severity}] {issue.message}
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
