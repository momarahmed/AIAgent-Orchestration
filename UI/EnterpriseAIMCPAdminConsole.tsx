"use client";

import { type ReactNode, useMemo, useState } from "react";

type AdminSectionId =
  | "overview"
  | "tenants"
  | "users"
  | "roles"
  | "agents"
  | "mcp"
  | "workflows"
  | "templates"
  | "environments"
  | "secrets"
  | "policies"
  | "approvals"
  | "audit"
  | "observability"
  | "cost"
  | "settings";

type AdminTab = "summary" | "manage" | "policy" | "audit";
type Health = "Healthy" | "Warning" | "Critical" | "Disabled";
type Risk = "Low" | "Medium" | "High" | "Critical";
type Env = "Dev" | "Test" | "Staging" | "Production";

type SidebarItem = {
  id: AdminSectionId;
  label: string;
  subtitle: string;
  icon: string;
  badge?: string;
};

type AdminMetric = {
  label: string;
  value: string;
  change: string;
  icon: string;
  tone: "blue" | "green" | "amber" | "red" | "violet";
};

type ManagedAsset = {
  name: string;
  category: string;
  owner: string;
  env: Env;
  health: Health;
  risk: Risk;
  updated: string;
};

type AuditEvent = {
  time: string;
  actor: string;
  action: string;
  target: string;
  result: "Allowed" | "Denied" | "Approved" | "Failed";
  risk: Risk;
};

type PolicyRule = {
  name: string;
  scope: string;
  effect: "Allow" | "Deny" | "Approval Required";
  coverage: number;
};

const sidebarItems: SidebarItem[] = [
  { id: "overview", label: "Admin Overview", subtitle: "Control center", icon: "🏛️" },
  { id: "tenants", label: "Tenants", subtitle: "Organizations/projects", icon: "🏢", badge: "8" },
  { id: "users", label: "Users", subtitle: "People and service users", icon: "👥", badge: "146" },
  { id: "roles", label: "Roles + Access", subtitle: "RBAC / ABAC", icon: "🪪" },
  { id: "agents", label: "Agent Governance", subtitle: "Agent lifecycle admin", icon: "🤖", badge: "42" },
  { id: "mcp", label: "MCP Governance", subtitle: "MCP server admin", icon: "🧩", badge: "28" },
  { id: "workflows", label: "Workflow Governance", subtitle: "Workflow lifecycle", icon: "🔁", badge: "76" },
  { id: "templates", label: "Template Library", subtitle: "Import/export/sign", icon: "📦" },
  { id: "environments", label: "Environments", subtitle: "Dev/Test/Prod", icon: "🚀" },
  { id: "secrets", label: "Secrets + Vault", subtitle: "Credentials control", icon: "🔐" },
  { id: "policies", label: "Policy Engine", subtitle: "OPA rules", icon: "🛡️" },
  { id: "approvals", label: "Approval Center", subtitle: "Human gates", icon: "✅", badge: "9" },
  { id: "audit", label: "Audit Logs", subtitle: "Full activity trail", icon: "📜" },
  { id: "observability", label: "Observability", subtitle: "Logs/metrics/traces", icon: "📡" },
  { id: "cost", label: "Cost Control", subtitle: "Models/tools/spend", icon: "💳" },
  { id: "settings", label: "Platform Settings", subtitle: "Global configuration", icon: "⚙️" },
];

const adminMetrics: AdminMetric[] = [
  { label: "Tenants", value: "8", change: "+1 this month", icon: "🏢", tone: "blue" },
  { label: "Managed Users", value: "146", change: "18 service accounts", icon: "👥", tone: "green" },
  { label: "Policy Coverage", value: "96%", change: "+4% improved", icon: "🛡️", tone: "green" },
  { label: "Pending Approvals", value: "9", change: "3 high risk", icon: "✅", tone: "amber" },
  { label: "Critical Incidents", value: "2", change: "Needs review", icon: "🚨", tone: "red" },
  { label: "Monthly Spend", value: "$1.1K", change: "14% below budget", icon: "💳", tone: "violet" },
];

const managedAssets: ManagedAsset[] = [
  { name: "GIS Health Supervisor", category: "Agent", owner: "GIS Platform Team", env: "Production", health: "Healthy", risk: "Medium", updated: "Today" },
  { name: "ArcGIS Enterprise MCP", category: "MCP Server", owner: "GIS Platform Team", env: "Production", health: "Healthy", risk: "High", updated: "Today" },
  { name: "Browser CUA MCP", category: "MCP Server", owner: "Automation Team", env: "Staging", health: "Warning", risk: "Critical", updated: "Yesterday" },
  { name: "Daily GIS Health Report", category: "Workflow", owner: "GIS Ops", env: "Production", health: "Healthy", risk: "Low", updated: "Today" },
  { name: "Cloud Ops MCP", category: "MCP Server", owner: "DevOps", env: "Dev", health: "Critical", risk: "High", updated: "32m ago" },
  { name: "Security Review Agent", category: "Agent", owner: "Security", env: "Production", health: "Healthy", risk: "Medium", updated: "2d ago" },
];

const auditEvents: AuditEvent[] = [
  { time: "10:42", actor: "mohamed847433", action: "Approved restart action", target: "ArcGIS Map Service", result: "Approved", risk: "High" },
  { time: "10:35", actor: "Security Review Agent", action: "Blocked destructive tool", target: "Cloud Ops MCP", result: "Denied", risk: "Critical" },
  { time: "10:21", actor: "Platform Architect Agent", action: "Created agent template", target: "GIS Ops Agent Template", result: "Allowed", risk: "Low" },
  { time: "10:10", actor: "MCP Builder Agent", action: "Deployed MCP server", target: "PostgreSQL Safe Query MCP", result: "Allowed", risk: "Medium" },
  { time: "09:58", actor: "DevOps Agent", action: "Image scan failed", target: "Browser CUA MCP", result: "Failed", risk: "High" },
];

const policyRules: PolicyRule[] = [
  { name: "Tool Allowlist Enforcement", scope: "Agents → MCP tools", effect: "Allow", coverage: 100 },
  { name: "Admin Write Requires Approval", scope: "MCP admin tools", effect: "Approval Required", coverage: 98 },
  { name: "No Secrets in Templates", scope: "Templates + exports", effect: "Deny", coverage: 100 },
  { name: "Production Deploy Gate", scope: "Agents / MCP / Workflows", effect: "Approval Required", coverage: 94 },
  { name: "Tenant Boundary Isolation", scope: "All runtime actions", effect: "Deny", coverage: 99 },
];

const roleMatrix = [
  ["Platform Owner", "Full platform admin", "All tenants", "Deploy + approve + configure"],
  ["Agent Builder", "Create and update agents", "Assigned projects", "No production deploy without approval"],
  ["MCP Developer", "Create and debug MCP servers", "Dev/Test", "Cannot access raw secrets"],
  ["Workflow Designer", "Create workflows and templates", "Assigned projects", "Cannot run high-risk tools"],
  ["Security Approver", "Review high-risk actions", "All production assets", "Approve or deny execution"],
];

const approvalQueue = [
  ["Restart ArcGIS map service", "GIS Agent", "ArcGIS Enterprise MCP", "High", "12m"],
  ["Deploy Cloud Ops MCP to staging", "DevOps Agent", "Kubernetes", "Medium", "31m"],
  ["Export tenant workflow template", "Template Manager", "Template Library", "Low", "44m"],
  ["Enable Browser CUA production access", "Automation Team", "Browser CUA MCP", "Critical", "1h 08m"],
];

const environmentCards = [
  ["Development", "18 assets", "Auto deploy enabled", "Healthy"],
  ["Test", "24 assets", "Regression suite active", "Healthy"],
  ["Staging", "16 assets", "Approval required", "Warning"],
  ["Production", "51 assets", "Strict governance", "Healthy"],
];

function cn(...classes: Array<string | false | undefined>) {
  return classes.filter(Boolean).join(" ");
}

function Card({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <section className={cn("rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-2xl shadow-black/20", className)}>{children}</section>;
}

function Badge({ children, tone = "slate" }: { children: ReactNode; tone?: "slate" | "green" | "amber" | "red" | "blue" | "violet" }) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold",
        tone === "slate" && "border-slate-700 bg-slate-800 text-slate-300",
        tone === "green" && "border-emerald-500/30 bg-emerald-500/10 text-emerald-300",
        tone === "amber" && "border-amber-500/30 bg-amber-500/10 text-amber-300",
        tone === "red" && "border-rose-500/30 bg-rose-500/10 text-rose-300",
        tone === "blue" && "border-cyan-500/30 bg-cyan-500/10 text-cyan-300",
        tone === "violet" && "border-violet-500/30 bg-violet-500/10 text-violet-300",
      )}
    >
      {children}
    </span>
  );
}

function healthTone(status: Health) {
  if (status === "Healthy") return "green" as const;
  if (status === "Warning") return "amber" as const;
  if (status === "Critical") return "red" as const;
  return "slate" as const;
}

function riskTone(risk: Risk | string) {
  if (risk === "Critical") return "red" as const;
  if (risk === "High") return "red" as const;
  if (risk === "Medium") return "amber" as const;
  return "green" as const;
}

function effectTone(effect: PolicyRule["effect"]) {
  if (effect === "Allow") return "green" as const;
  if (effect === "Deny") return "red" as const;
  return "amber" as const;
}

function resultTone(result: AuditEvent["result"]) {
  if (result === "Allowed" || result === "Approved") return "green" as const;
  if (result === "Denied" || result === "Failed") return "red" as const;
  return "slate" as const;
}

function ProgressBar({ value, tone = "blue" }: { value: number; tone?: "blue" | "green" | "amber" | "red" | "violet" }) {
  const fill = {
    blue: "from-cyan-400 to-sky-500",
    green: "from-emerald-400 to-lime-500",
    amber: "from-amber-400 to-orange-500",
    red: "from-rose-400 to-red-500",
    violet: "from-violet-400 to-fuchsia-500",
  }[tone];

  return (
    <div className="h-2 overflow-hidden rounded-full bg-slate-800">
      <div className={cn("h-full rounded-full bg-gradient-to-r", fill)} style={{ width: `${value}%` }} />
    </div>
  );
}

function Sidebar({ active, setActive }: { active: AdminSectionId; setActive: (id: AdminSectionId) => void }) {
  return (
    <aside className="hidden h-screen w-[330px] shrink-0 border-r border-slate-800 bg-slate-950/95 p-4 xl:sticky xl:top-0 xl:block">
      <div className="rounded-3xl border border-slate-800 bg-slate-900/80 p-4">
        <div className="flex items-center gap-3">
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-violet-500 text-2xl">🏛️</div>
          <div>
            <div className="font-semibold text-white">Admin Console</div>
            <div className="text-xs text-slate-400">Enterprise AI MCP Platform</div>
          </div>
        </div>
        <div className="mt-4 rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-slate-500">Search admin settings...</div>
      </div>

      <nav className="mt-5 space-y-1.5">
        {sidebarItems.map((item) => {
          const selected = active === item.id;
          return (
            <button
              key={item.id}
              onClick={() => setActive(item.id)}
              className={cn(
                "flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left transition",
                selected ? "bg-cyan-500/10 text-white ring-1 ring-cyan-400/30" : "text-slate-400 hover:bg-slate-900 hover:text-white",
              )}
            >
              <span className={cn("flex h-10 w-10 items-center justify-center rounded-xl text-lg", selected ? "bg-cyan-400/20" : "bg-slate-900")}>{item.icon}</span>
              <span className="min-w-0 flex-1">
                <span className="flex items-center justify-between gap-2">
                  <span className="truncate text-sm font-medium">{item.label}</span>
                  {item.badge && <Badge tone={item.badge === "9" ? "amber" : "slate"}>{item.badge}</Badge>}
                </span>
                <span className="mt-0.5 block truncate text-xs text-slate-500">{item.subtitle}</span>
              </span>
            </button>
          );
        })}
      </nav>
    </aside>
  );
}

function MobileSectionTabs({ active, setActive }: { active: AdminSectionId; setActive: (id: AdminSectionId) => void }) {
  return (
    <div className="border-b border-slate-800 bg-slate-950 p-3 xl:hidden">
      <div className="flex gap-2 overflow-x-auto pb-1">
        {sidebarItems.map((item) => (
          <button
            key={item.id}
            onClick={() => setActive(item.id)}
            className={cn(
              "flex shrink-0 items-center gap-2 rounded-2xl border px-3 py-2 text-sm",
              active === item.id ? "border-cyan-400/40 bg-cyan-500/10 text-white" : "border-slate-800 bg-slate-900 text-slate-400",
            )}
          >
            <span>{item.icon}</span>
            {item.label}
          </button>
        ))}
      </div>
    </div>
  );
}

function TopBar({ current, tab, setTab }: { current: SidebarItem; tab: AdminTab; setTab: (tab: AdminTab) => void }) {
  const tabs: Array<[AdminTab, string]> = [
    ["summary", "Summary"],
    ["manage", "Manage"],
    ["policy", "Policy"],
    ["audit", "Audit"],
  ];

  return (
    <header className="sticky top-0 z-30 border-b border-slate-800 bg-slate-950/85 px-5 py-4 backdrop-blur-xl lg:px-8">
      <div className="flex flex-col gap-4 2xl:flex-row 2xl:items-center 2xl:justify-between">
        <div>
          <div className="flex flex-wrap items-center gap-2 text-sm text-slate-400">
            <Badge tone="blue">Admin</Badge>
            <span>Platform Governance</span>
            <span className="text-slate-600">/</span>
            <span className="text-cyan-300">{current.label}</span>
          </div>
          <h1 className="mt-2 text-2xl font-semibold tracking-tight text-white md:text-3xl">{current.label}</h1>
          <p className="mt-1 text-sm text-slate-400">{current.subtitle}</p>
        </div>
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center">
          <div className="flex flex-wrap gap-2">
            {tabs.map(([id, label]) => (
              <button
                key={id}
                onClick={() => setTab(id)}
                className={cn(
                  "rounded-2xl border px-4 py-2 text-sm font-medium transition",
                  tab === id ? "border-cyan-400/40 bg-cyan-500/10 text-white" : "border-slate-800 bg-slate-900 text-slate-400 hover:text-white",
                )}
              >
                {label}
              </button>
            ))}
          </div>
          <button className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20">
            Create Admin Action
          </button>
        </div>
      </div>
    </header>
  );
}

function AdminMetricGrid() {
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
      {adminMetrics.map((metric) => (
        <Card key={metric.label}>
          <div className="flex items-start justify-between gap-3">
            <div className="text-3xl">{metric.icon}</div>
            <Badge tone={metric.tone}>{metric.change}</Badge>
          </div>
          <div className="mt-5 text-3xl font-semibold text-white">{metric.value}</div>
          <div className="mt-1 text-sm text-slate-400">{metric.label}</div>
        </Card>
      ))}
    </div>
  );
}

function ManagedAssetsTable({ title = "Governed Assets" }: { title?: string }) {
  return (
    <Card className="overflow-hidden p-0">
      <div className="flex flex-col gap-3 border-b border-slate-800 px-5 py-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 className="font-semibold text-white">{title}</h2>
          <p className="text-sm text-slate-400">Agents, MCP servers, workflows, templates, and production assets under admin control.</p>
        </div>
        <div className="flex gap-2">
          <button className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-slate-300 hover:text-white">Filter</button>
          <button className="rounded-xl bg-cyan-500 px-3 py-2 text-sm font-semibold text-slate-950">Export</button>
        </div>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[940px] text-left text-sm">
          <thead className="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th className="px-5 py-3">Name</th>
              <th className="px-5 py-3">Category</th>
              <th className="px-5 py-3">Owner</th>
              <th className="px-5 py-3">Environment</th>
              <th className="px-5 py-3">Health</th>
              <th className="px-5 py-3">Risk</th>
              <th className="px-5 py-3">Updated</th>
              <th className="px-5 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            {managedAssets.map((asset) => (
              <tr key={asset.name} className="border-b border-slate-800/70 last:border-b-0">
                <td className="px-5 py-4 font-medium text-white">{asset.name}</td>
                <td className="px-5 py-4 text-slate-400">{asset.category}</td>
                <td className="px-5 py-4 text-slate-400">{asset.owner}</td>
                <td className="px-5 py-4"><Badge tone={asset.env === "Production" ? "green" : asset.env === "Staging" ? "blue" : "slate"}>{asset.env}</Badge></td>
                <td className="px-5 py-4"><Badge tone={healthTone(asset.health)}>{asset.health}</Badge></td>
                <td className="px-5 py-4"><Badge tone={riskTone(asset.risk)}>{asset.risk}</Badge></td>
                <td className="px-5 py-4 text-slate-400">{asset.updated}</td>
                <td className="px-5 py-4 text-right">
                  <div className="inline-flex gap-2">
                    <button className="rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-300 hover:text-white">View</button>
                    <button className="rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-xs text-slate-300 hover:text-white">Policy</button>
                    <button className="rounded-lg bg-violet-500 px-2.5 py-1.5 text-xs font-semibold text-white">Deploy</button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Card>
  );
}

function PolicyRulesPanel() {
  return (
    <Card>
      <div className="flex items-center justify-between">
        <div>
          <h2 className="font-semibold text-white">Policy Rules</h2>
          <p className="text-sm text-slate-400">OPA-style governance checks for agents, MCP tools, workflows, templates, and deployments.</p>
        </div>
        <Badge tone="green">96% coverage</Badge>
      </div>
      <div className="mt-6 space-y-4">
        {policyRules.map((rule) => (
          <div key={rule.name} className="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <div className="font-medium text-white">{rule.name}</div>
                <div className="mt-1 text-xs text-slate-500">Scope: {rule.scope}</div>
              </div>
              <Badge tone={effectTone(rule.effect)}>{rule.effect}</Badge>
            </div>
            <div className="mt-4 flex items-center gap-3">
              <div className="flex-1"><ProgressBar value={rule.coverage} tone={rule.coverage > 97 ? "green" : "amber"} /></div>
              <span className="text-xs font-semibold text-cyan-300">{rule.coverage}%</span>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

function RoleMatrixPanel() {
  return (
    <Card className="overflow-hidden p-0">
      <div className="border-b border-slate-800 px-5 py-4">
        <h2 className="font-semibold text-white">Role Matrix</h2>
        <p className="text-sm text-slate-400">Administrative roles mapped to scopes and restrictions.</p>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[760px] text-left text-sm">
          <thead className="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th className="px-5 py-3">Role</th>
              <th className="px-5 py-3">Responsibility</th>
              <th className="px-5 py-3">Scope</th>
              <th className="px-5 py-3">Restrictions</th>
            </tr>
          </thead>
          <tbody>
            {roleMatrix.map(([role, responsibility, scope, restrictions]) => (
              <tr key={role} className="border-b border-slate-800/70 last:border-b-0">
                <td className="px-5 py-4 font-medium text-white">{role}</td>
                <td className="px-5 py-4 text-slate-400">{responsibility}</td>
                <td className="px-5 py-4 text-slate-400">{scope}</td>
                <td className="px-5 py-4 text-slate-400">{restrictions}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Card>
  );
}

function ApprovalCenterPanel() {
  return (
    <Card>
      <div className="flex items-center justify-between">
        <div>
          <h2 className="font-semibold text-white">Approval Center</h2>
          <p className="text-sm text-slate-400">Risky tool execution, production deployment, export, and admin changes.</p>
        </div>
        <Badge tone="amber">{approvalQueue.length} pending</Badge>
      </div>
      <div className="mt-5 space-y-3">
        {approvalQueue.map(([title, requester, target, risk, age]) => (
          <div key={title} className="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <div className="font-medium text-white">{title}</div>
                <div className="mt-1 text-xs text-slate-500">{requester} → {target} • waiting {age}</div>
              </div>
              <Badge tone={riskTone(risk)}>{risk}</Badge>
            </div>
            <div className="mt-4 flex flex-wrap gap-2">
              <button className="rounded-xl border border-slate-700 px-3 py-2 text-xs text-slate-300 hover:text-white">Review Evidence</button>
              <button className="rounded-xl border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-xs font-semibold text-rose-300">Deny</button>
              <button className="rounded-xl bg-emerald-500 px-3 py-2 text-xs font-semibold text-slate-950">Approve</button>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

function AuditTrailPanel() {
  return (
    <Card className="overflow-hidden p-0">
      <div className="border-b border-slate-800 px-5 py-4">
        <h2 className="font-semibold text-white">Audit Trail</h2>
        <p className="text-sm text-slate-400">Immutable event stream for all admin and runtime governance actions.</p>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[820px] text-left text-sm">
          <thead className="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th className="px-5 py-3">Time</th>
              <th className="px-5 py-3">Actor</th>
              <th className="px-5 py-3">Action</th>
              <th className="px-5 py-3">Target</th>
              <th className="px-5 py-3">Result</th>
              <th className="px-5 py-3">Risk</th>
            </tr>
          </thead>
          <tbody>
            {auditEvents.map((event) => (
              <tr key={`${event.time}-${event.action}`} className="border-b border-slate-800/70 last:border-b-0">
                <td className="px-5 py-4 text-slate-500">{event.time}</td>
                <td className="px-5 py-4 font-medium text-white">{event.actor}</td>
                <td className="px-5 py-4 text-slate-400">{event.action}</td>
                <td className="px-5 py-4 text-slate-400">{event.target}</td>
                <td className="px-5 py-4"><Badge tone={resultTone(event.result)}>{event.result}</Badge></td>
                <td className="px-5 py-4"><Badge tone={riskTone(event.risk)}>{event.risk}</Badge></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Card>
  );
}

function EnvironmentPanel() {
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      {environmentCards.map(([name, assets, mode, status]) => (
        <Card key={name}>
          <div className="flex items-start justify-between gap-3">
            <div className="text-3xl">🚀</div>
            <Badge tone={healthTone(status as Health)}>{status}</Badge>
          </div>
          <div className="mt-5 text-xl font-semibold text-white">{name}</div>
          <div className="mt-1 text-sm text-slate-400">{assets}</div>
          <p className="mt-4 text-sm leading-6 text-slate-500">{mode}</p>
        </Card>
      ))}
    </div>
  );
}

function SettingsGrid({ active }: { active: AdminSectionId }) {
  const titleMap: Record<AdminSectionId, string> = {
    overview: "Platform Control Summary",
    tenants: "Tenant Administration",
    users: "User Administration",
    roles: "Role and Permission Administration",
    agents: "Agent Governance Administration",
    mcp: "MCP Server Governance Administration",
    workflows: "Workflow Governance Administration",
    templates: "Template Library Administration",
    environments: "Environment Administration",
    secrets: "Secrets and Vault Administration",
    policies: "Policy Engine Administration",
    approvals: "Approval Center Administration",
    audit: "Audit Administration",
    observability: "Observability Administration",
    cost: "Cost Control Administration",
    settings: "Global Platform Settings",
  };

  const cards = [
    ["Lifecycle Control", "Create, update, delete, debug, deploy, copy, import, and export assets with approval gates.", "🔁"],
    ["Access Control", "Control tenant, user, role, group, service account, and environment-level permissions.", "🪪"],
    ["Policy Enforcement", "Apply OPA-style policy checks to tools, agents, MCP servers, workflows, and templates.", "🛡️"],
    ["Operational Control", "Monitor runtime health, audit history, cost, logs, metrics, traces, and incidents.", "📡"],
  ];

  return (
    <Card>
      <div className="flex items-center justify-between gap-4">
        <div>
          <h2 className="text-xl font-semibold text-white">{titleMap[active]}</h2>
          <p className="mt-1 text-sm text-slate-400">Administrative controls for secure enterprise platform operations.</p>
        </div>
        <Badge tone="blue">Admin scope</Badge>
      </div>
      <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {cards.map(([title, description, icon]) => (
          <div key={title} className="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
            <div className="text-3xl">{icon}</div>
            <h3 className="mt-4 font-semibold text-white">{title}</h3>
            <p className="mt-2 text-sm leading-6 text-slate-400">{description}</p>
          </div>
        ))}
      </div>
    </Card>
  );
}

function SectionSummary({ active }: { active: AdminSectionId }) {
  if (active === "overview") {
    return (
      <div className="space-y-6">
        <AdminMetricGrid />
        <div className="grid gap-6 2xl:grid-cols-[1.25fr_0.75fr]">
          <ManagedAssetsTable />
          <ApprovalCenterPanel />
        </div>
        <div className="grid gap-6 xl:grid-cols-2">
          <PolicyRulesPanel />
          <AuditTrailPanel />
        </div>
      </div>
    );
  }

  if (active === "approvals") return <ApprovalCenterPanel />;
  if (active === "audit") return <AuditTrailPanel />;
  if (active === "policies") return <PolicyRulesPanel />;
  if (active === "roles" || active === "users") return <RoleMatrixPanel />;
  if (active === "environments") return <EnvironmentPanel />;

  return (
    <div className="space-y-6">
      <SettingsGrid active={active} />
      <ManagedAssetsTable title={`${sidebarItems.find((item) => item.id === active)?.label ?? "Admin"} Assets`} />
    </div>
  );
}

function ManageTab({ active }: { active: AdminSectionId }) {
  if (active === "environments") return <EnvironmentPanel />;
  if (active === "roles" || active === "users") return <RoleMatrixPanel />;
  if (active === "approvals") return <ApprovalCenterPanel />;

  return (
    <div className="space-y-6">
      <SettingsGrid active={active} />
      <ManagedAssetsTable title="Management Table" />
    </div>
  );
}

function PolicyTab({ active }: { active: AdminSectionId }) {
  return (
    <div className="space-y-6">
      <PolicyRulesPanel />
      <SettingsGrid active={active} />
    </div>
  );
}

function AuditTab() {
  return <AuditTrailPanel />;
}

function MainContent({ active, tab }: { active: AdminSectionId; tab: AdminTab }) {
  if (tab === "manage") return <ManageTab active={active} />;
  if (tab === "policy") return <PolicyTab active={active} />;
  if (tab === "audit") return <AuditTab />;
  return <SectionSummary active={active} />;
}

export default function EnterpriseAIMCPAdminConsole() {
  const [active, setActive] = useState<AdminSectionId>("overview");
  const [tab, setTab] = useState<AdminTab>("summary");

  const current = useMemo(() => sidebarItems.find((item) => item.id === active) ?? sidebarItems[0], [active]);

  function handleSetActive(id: AdminSectionId) {
    setActive(id);
    setTab("summary");
  }

  return (
    <main className="min-h-screen bg-slate-950 text-slate-100">
      <div className="pointer-events-none fixed inset-0 bg-[radial-gradient(circle_at_top_left,rgba(34,211,238,0.12),transparent_34%),radial-gradient(circle_at_top_right,rgba(139,92,246,0.14),transparent_30%),linear-gradient(to_bottom,rgba(15,23,42,.1),rgba(2,6,23,1))]" />
      <div className="relative flex">
        <Sidebar active={active} setActive={handleSetActive} />
        <div className="min-w-0 flex-1">
          <MobileSectionTabs active={active} setActive={handleSetActive} />
          <TopBar current={current} tab={tab} setTab={setTab} />
          <div className="px-5 py-6 lg:px-8">
            <MainContent active={active} tab={tab} />
          </div>
        </div>
      </div>
    </main>
  );
}
