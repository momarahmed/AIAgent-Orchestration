"use client";

import { useEffect, useState } from "react";
import { rbacApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

type Role = { id: number; name: string; label: string; level: number; is_system: boolean; description?: string; permissions?: any[] };

export default function RbacPage() {
  const auth = useAuth();
  const [roles, setRoles] = useState<Role[]>([]);
  const [permissions, setPermissions] = useState<any[]>([]);
  const [myPerms, setMyPerms] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [tab, setTab] = useState<"roles" | "permissions" | "abac">("roles");
  const [abacPolicies, setAbacPolicies] = useState<any[]>([]);

  useEffect(() => {
    Promise.all([
      rbacApi.roles().then(setRoles),
      rbacApi.permissions().then(setPermissions),
      rbacApi.myPermissions({ tenant_id: auth.activeTenantId }).then(setMyPerms),
      rbacApi.abacPolicies({ tenant_id: auth.activeTenantId }).then((r: any) => setAbacPolicies(r.data || [])),
    ]).finally(() => setLoading(false));
  }, [auth.activeTenantId]);

  if (loading) return <div className="flex h-64 items-center justify-center text-slate-400">Loading RBAC...</div>;

  return (
    <div className="space-y-6 p-6">
      <PageHeader title="RBAC / Access Control" description="Manage roles, permissions, and attribute-based access policies." />

      {myPerms && (
        <div className="rounded-2xl border border-cyan-500/20 bg-cyan-500/5 p-4">
          <div className="text-xs uppercase tracking-wider text-slate-500">Your Current Role</div>
          <div className="mt-1 text-lg font-semibold text-white">{myPerms.tenant_role || "—"}</div>
          {myPerms.project_role && <div className="text-sm text-slate-400">Project role: {myPerms.project_role}</div>}
        </div>
      )}

      <div className="flex gap-2 border-b border-slate-800 pb-2">
        {(["roles", "permissions", "abac"] as const).map((t) => (
          <button key={t} onClick={() => setTab(t)}
            className={`rounded-lg px-4 py-2 text-sm font-medium transition ${tab === t ? "bg-cyan-500/10 text-cyan-400" : "text-slate-400 hover:text-white"}`}>
            {t === "roles" ? "Roles" : t === "permissions" ? "Permissions" : "ABAC Policies"}
          </button>
        ))}
      </div>

      {tab === "roles" && (
        <div className="space-y-3">
          {roles.map((role) => (
            <div key={role.id} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
              <div className="flex items-center justify-between">
                <div>
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-semibold text-white">{role.label}</span>
                    <span className="rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-400">{role.name}</span>
                    <span className="rounded bg-violet-500/10 px-2 py-0.5 text-xs text-violet-400">Level {role.level}</span>
                    {role.is_system && <span className="rounded bg-amber-500/10 px-2 py-0.5 text-xs text-amber-400">System</span>}
                  </div>
                  {role.description && <div className="mt-1 text-xs text-slate-500">{role.description}</div>}
                </div>
              </div>
              {role.permissions && role.permissions.length > 0 && (
                <div className="mt-2 flex flex-wrap gap-1">
                  {role.permissions.slice(0, 10).map((p: any) => (
                    <span key={p.id || p} className="rounded bg-slate-800 px-2 py-0.5 text-[10px] text-slate-400">{p.name || p}</span>
                  ))}
                  {role.permissions.length > 10 && <span className="text-[10px] text-slate-500">+{role.permissions.length - 10} more</span>}
                </div>
              )}
            </div>
          ))}
        </div>
      )}

      {tab === "permissions" && (
        <div className="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
          {Object.entries(permissions.reduce((acc: any, p: any) => { (acc[p.group] = acc[p.group] || []).push(p); return acc; }, {})).map(([group, perms]: any) => (
            <div key={group} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
              <div className="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{group}</div>
              <div className="space-y-1">
                {perms.map((p: any) => (
                  <div key={p.id} className="text-sm text-slate-300">{p.label}</div>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}

      {tab === "abac" && (
        <div className="space-y-3">
          {abacPolicies.length === 0 && <div className="text-center text-sm text-slate-500 py-8">No ABAC policies configured yet.</div>}
          {abacPolicies.map((p: any) => (
            <div key={p.id} className="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className="text-sm font-semibold text-white">{p.name}</span>
                  <span className={`rounded px-2 py-0.5 text-xs ${p.effect === "deny" ? "bg-rose-500/10 text-rose-400" : "bg-emerald-500/10 text-emerald-400"}`}>{p.effect}</span>
                  <span className="rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-400">{p.resource_type}.{p.action}</span>
                </div>
                <span className={`text-xs ${p.is_active ? "text-emerald-400" : "text-slate-500"}`}>{p.is_active ? "Active" : "Inactive"}</span>
              </div>
              {p.description && <div className="mt-1 text-xs text-slate-500">{p.description}</div>}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
