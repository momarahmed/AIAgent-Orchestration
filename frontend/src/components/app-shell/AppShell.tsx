"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { ReactNode, useEffect, useMemo, useState } from "react";
import { useAuth } from "@/lib/auth-context";

type NavItem = { href: string; label: string; icon: string; group: "core" | "build" | "ops" };

const NAV: NavItem[] = [
  { href: "/chat",        label: "Chat",             icon: "💬", group: "core" },
  { href: "/dashboard",   label: "Dashboard",        icon: "📊", group: "core" },
  { href: "/console",     label: "Platform Console", icon: "🛰️", group: "core" },
  { href: "/platform",    label: "Platform",         icon: "🌐", group: "core" },

  { href: "/agents",      label: "Agent Studio",     icon: "🤖", group: "build" },
  { href: "/mcp-servers", label: "MCP Studio",       icon: "🧩", group: "build" },
  { href: "/workflows",   label: "Workflow Builder", icon: "🔁", group: "build" },
  { href: "/templates",   label: "Templates",        icon: "📋", group: "build" },
  { href: "/codegen",     label: "Code Agent",       icon: "🛠️", group: "build" },

  { href: "/runs",            label: "Run History",        icon: "🧪", group: "ops" },
  { href: "/approvals",       label: "Approvals",          icon: "✅", group: "ops" },
  { href: "/deployments",     label: "Deployments",        icon: "🚀", group: "ops" },
  { href: "/audit-reports",   label: "Audit Reports",      icon: "📑", group: "ops" },
  { href: "/security-scans",  label: "Security Scanner",   icon: "🔒", group: "ops" },
  { href: "/rbac",            label: "RBAC / Access",      icon: "🔐", group: "ops" },
  { href: "/opa-policies",    label: "Policy Engine",      icon: "📜", group: "ops" },
  { href: "/network-policies",label: "Network Policies",   icon: "🌐", group: "ops" },
  { href: "/provider-budgets",label: "Provider Budgets",   icon: "💰", group: "ops" },
  { href: "/admin",           label: "Admin Console",      icon: "🛡️", group: "ops" },
];

export function AppShell({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const auth = useAuth();
  const [collapsed, setCollapsed] = useState(false);

  useEffect(() => {
    if (!auth.loading && !auth.user) router.replace("/login");
  }, [auth.loading, auth.user, router]);

  const grouped = useMemo(() => {
    return {
      core: NAV.filter((n) => n.group === "core"),
      build: NAV.filter((n) => n.group === "build"),
      ops: NAV.filter((n) => n.group === "ops"),
    };
  }, []);

  if (auth.loading || !auth.user) {
    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-950 text-slate-400">
        <div className="flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/80 px-5 py-4 text-sm">
          <span className="h-2 w-2 animate-pulse rounded-full bg-cyan-400" />
          Loading workspace…
        </div>
      </div>
    );
  }

  const activeTenant = auth.tenants.find((t) => t.id === auth.activeTenantId) ?? auth.tenants[0];

  return (
    <div className="flex min-h-screen bg-slate-950 text-slate-100">
      <aside className={`hidden shrink-0 border-r border-slate-800 bg-slate-950 lg:flex lg:flex-col ${collapsed ? "lg:w-[76px]" : "lg:w-[260px]"}`}>
        <div className="flex h-16 items-center justify-between gap-3 border-b border-slate-800 px-4">
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-violet-500 text-lg">⚙️</div>
            {!collapsed && (
              <div>
                <div className="text-sm font-semibold text-white">EAMCP Platform</div>
                <div className="text-[11px] text-slate-500">Agent · MCP · Workflows</div>
              </div>
            )}
          </div>
          <button
            onClick={() => setCollapsed((v) => !v)}
            className="rounded-lg border border-slate-800 bg-slate-900 px-2 py-1 text-xs text-slate-400 hover:text-white"
            aria-label="Toggle sidebar"
          >
            {collapsed ? "›" : "‹"}
          </button>
        </div>

        <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-4">
          {(["core", "build", "ops"] as const).map((g) => (
            <div key={g}>
              {!collapsed && (
                <div className="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                  {g === "core" ? "Overview" : g === "build" ? "Build" : "Operate"}
                </div>
              )}
              <ul className="space-y-1">
                {grouped[g].map((item) => {
                  const active = pathname === item.href || pathname.startsWith(item.href + "/");
                  return (
                    <li key={item.href}>
                      <Link
                        href={item.href}
                        className={`flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition ${
                          active
                            ? "bg-cyan-500/10 text-white shadow-[inset_0_0_0_1px_rgba(34,211,238,0.4)]"
                            : "text-slate-400 hover:bg-slate-900 hover:text-white"
                        }`}
                      >
                        <span className="text-base">{item.icon}</span>
                        {!collapsed && <span>{item.label}</span>}
                      </Link>
                    </li>
                  );
                })}
              </ul>
            </div>
          ))}
        </nav>

        {!collapsed && (
          <div className="m-3 rounded-2xl border border-slate-800 bg-slate-900/80 p-3 text-xs">
            <div className="text-slate-500">Phase</div>
            <div className="mt-1 font-semibold text-white">Phase 3 — Enterprise Governance</div>
            <div className="mt-2 text-slate-500">v1.3.0 · {process.env.NEXT_PUBLIC_BRAND_NAME || "EAMCP"}</div>
          </div>
        )}
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-800 bg-slate-950/80 px-4 backdrop-blur lg:px-6">
          <div className="flex items-center gap-3">
            <div className="lg:hidden">
              <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-violet-500 text-lg">⚙️</div>
            </div>
            <div className="hidden items-center gap-3 md:flex">
              <div>
              <div className="text-xs uppercase tracking-wider text-slate-500">Tenant</div>
              <select
                value={auth.activeTenantId ?? ""}
                onChange={(e) => auth.setActiveTenant(Number(e.target.value))}
                className="mt-1 rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-sm text-white outline-none focus:border-cyan-400/40"
              >
                {auth.tenants.map((t) => (
                  <option key={t.id} value={t.id}>{t.name} · {t.environment}</option>
                ))}
              </select>
              </div>
              <div>
                <div className="text-xs uppercase tracking-wider text-slate-500">Project</div>
                <select
                  value={auth.activeProjectId ?? ""}
                  onChange={(e) => auth.setActiveProject(Number(e.target.value))}
                  disabled={!auth.projects.length}
                  className="mt-1 rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-sm text-white outline-none focus:border-cyan-400/40 disabled:opacity-50"
                >
                  {auth.projects.map((p) => (
                    <option key={p.id} value={p.id}>{p.name}</option>
                  ))}
                </select>
              </div>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="hidden items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs text-slate-400 md:flex">
              <span className="h-2 w-2 rounded-full bg-emerald-400" /> System Healthy
            </div>
            <div className="flex items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900 px-3 py-1.5">
              <div className="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400 to-violet-500 text-xs font-bold text-white">
                {auth.user.name.charAt(0).toUpperCase()}
              </div>
              <div className="hidden text-left text-xs sm:block">
                <div className="font-semibold text-white">{auth.user.name}</div>
                <div className="text-slate-500">{auth.user.email}</div>
              </div>
            </div>
            <button
              onClick={() => auth.logout()}
              className="rounded-2xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-rose-400/40 hover:text-rose-300"
            >
              Sign out
            </button>
          </div>
        </header>

        <main className="min-w-0 flex-1 overflow-x-hidden">{children}</main>
      </div>
    </div>
  );
}
