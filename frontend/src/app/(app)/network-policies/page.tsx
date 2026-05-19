"use client";

import { useEffect, useState } from "react";
import { networkPoliciesApi, mcpServersApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

export default function NetworkPoliciesPage() {
  const auth = useAuth();
  const [entries, setEntries] = useState<any[]>([]);
  const [mcpServers, setMcpServers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [filterEnv, setFilterEnv] = useState<string>("");

  useEffect(() => {
    setLoading(true);
    const params: any = { tenant_id: auth.activeTenantId };
    if (filterEnv) params.environment = filterEnv;

    Promise.all([
      networkPoliciesApi.list(params).then((r: any) => setEntries(r.data || [])),
      mcpServersApi.list().then(setMcpServers),
    ]).finally(() => setLoading(false));
  }, [auth.activeTenantId, filterEnv]);

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading network policies...</div>;

  const grouped = entries.reduce((acc: any, e: any) => {
    const key = e.mcp_server?.name || `MCP #${e.mcp_server_id}`;
    (acc[key] = acc[key] || []).push(e);
    return acc;
  }, {});

  return (
    <div className="space-y-6 p-6">
      <PageHeader title="Network Policies" description="Per-MCP-server egress allowlists. Enforced by Kubernetes NetworkPolicy in staging/prod." />

      <div className="flex gap-2">
        {["", "dev", "test", "staging", "prod"].map((env) => (
          <button key={env} onClick={() => setFilterEnv(env)}
            className={`rounded-lg px-3 py-1.5 text-xs font-medium transition ${filterEnv === env ? "bg-cyan-500/10 text-cyan-400" : "text-slate-400 hover:text-white"}`}>
            {env || "All"}
          </button>
        ))}
      </div>

      <div className="space-y-4">
        {Object.keys(grouped).length === 0 && <div className="py-8 text-center text-sm text-slate-500">No network allowlist entries configured.</div>}
        {Object.entries(grouped).map(([serverName, rules]: any) => (
          <div key={serverName} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
            <div className="mb-3 text-sm font-semibold text-white">{serverName}</div>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="text-xs uppercase text-slate-500">
                  <tr>
                    <th className="pb-2 pr-4">Direction</th>
                    <th className="pb-2 pr-4">Host</th>
                    <th className="pb-2 pr-4">Port</th>
                    <th className="pb-2 pr-4">Protocol</th>
                    <th className="pb-2 pr-4">Environment</th>
                    <th className="pb-2 pr-4">Status</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-800/50">
                  {rules.map((rule: any) => (
                    <tr key={rule.id}>
                      <td className="py-2 pr-4"><span className="rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-400">{rule.direction}</span></td>
                      <td className="py-2 pr-4 font-mono text-xs text-cyan-400">{rule.host}</td>
                      <td className="py-2 pr-4 text-slate-300">{rule.port || "*"}</td>
                      <td className="py-2 pr-4 text-slate-400">{rule.protocol}</td>
                      <td className="py-2 pr-4"><span className="rounded bg-violet-500/10 px-2 py-0.5 text-xs text-violet-400">{rule.environment}</span></td>
                      <td className="py-2 pr-4"><span className={`text-xs ${rule.is_active ? "text-emerald-400" : "text-slate-500"}`}>{rule.is_active ? "Active" : "Disabled"}</span></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
