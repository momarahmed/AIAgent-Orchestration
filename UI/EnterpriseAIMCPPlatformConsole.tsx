"use client";

import { ReactNode, useMemo, useState } from "react";
import { motion } from "framer-motion";
import {
  Activity,
  AlertTriangle,
  Bell,
  Bot,
  Boxes,
  BrainCircuit,
  CheckCircle2,
  ChevronRight,
  CircleDot,
  ClipboardCheck,
  Copy,
  Database,
  Eye,
  Fingerprint,
  Globe2,
  HardDrive,
  Import,
  KeyRound,
  LayoutDashboard,
  Library,
  LockKeyhole,
  Network,
  PackageCheck,
  PlugZap,
  Plus,
  Rocket,
  Route,
  Save,
  Search,
  ServerCog,
  Settings2,
  ShieldCheck,
  Sparkles,
  TerminalSquare,
  TimerReset,
  UploadCloud,
  Workflow,
  Wrench,
  Zap,
} from "lucide-react";

type SectionId =
  | "dashboard"
  | "agents"
  | "mcp"
  | "orchestration"
  | "workflows"
  | "templates"
  | "runtime"
  | "models"
  | "security"
  | "integrations"
  | "observability"
  | "deployment";

type PanelTab = "overview" | "build" | "debug" | "deploy";
type IconType = typeof Bot;

type NavSection = {
  id: SectionId;
  label: string;
  description: string;
  icon: IconType;
  badge?: string;
};

type Row = {
  name: string;
  type: string;
  status: "Production" | "Draft" | "Debugging" | "Staging" | "Disabled";
  owner: string;
  updated: string;
};

const navSections: NavSection[] = [
  { id: "dashboard", label: "Command Center", description: "Platform overview", icon: LayoutDashboard },
  { id: "agents", label: "Agents", description: "Agent Studio", icon: Bot, badge: "12" },
  { id: "mcp", label: "MCP Servers", description: "MCP Studio", icon: ServerCog, badge: "18" },
  { id: "orchestration", label: "Orchestration", description: "A2A + routing", icon: Route },
  { id: "workflows", label: "Workflows", description: "Visual builder", icon: Workflow, badge: "26" },
  { id: "templates", label: "Templates", description: "Import / export", icon: Library },
  { id: "runtime", label: "Runtime Runs", description: "Execution monitor", icon: Activity, badge: "Live" },
  { id: "models", label: "Models + Memory", description: "LLM routing + RAG", icon: BrainCircuit },
  { id: "security", label: "Security", description: "Policy + approvals", icon: ShieldCheck },
  { id: "integrations", label: "Integrations", description: "Enterprise systems", icon: PlugZap },
  { id: "observability", label: "Observability", description: "Logs + traces", icon: Eye },
  { id: "deployment", label: "Deployment", description: "Dev/Test/Prod", icon: Rocket },
];

const stats = [
  { label: "Active Agents", value: "12", delta: "+3 this week", icon: Bot },
  { label: "MCP Servers", value: "18", delta: "15 healthy", icon: ServerCog },
  { label: "Workflow Runs", value: "2,481", delta: "98.4% success", icon: Workflow },
  { label: "Approval Queue", value: "7", delta: "3 high risk", icon: ClipboardCheck },
];

const lifecycleActions = [
  { label: "Create", description: "Generate a new asset from prompt, template, or manual configuration.", icon: Plus },
  { label: "Update", description: "Edit prompts, tools, schemas, nodes, permissions, and runtime options.", icon: Save },
  { label: "Delete", description: "Archive safely after dependency and impact checks.", icon: AlertTriangle },
  { label: "Debug", description: "Run tests, inspect traces, replay tool calls, and compare outputs.", icon: TerminalSquare },
  { label: "Deploy", description: "Promote from draft to dev, test, staging, and production.", icon: Rocket },
  { label: "Copy", description: "Clone an asset into a new version, tenant, project, or environment.", icon: Copy },
];

const agentRows: Row[] = [
  { name: "GIS Health Supervisor", type: "Supervisor Agent", status: "Production", owner: "GIS Platform Team", updated: "Today" },
  { name: "ArcGIS Service Doctor", type: "Specialist Agent", status: "Staging", owner: "GIS Ops", updated: "Yesterday" },
  { name: "Security Review Agent", type: "Governance Agent", status: "Production", owner: "Security", updated: "2 days ago" },
  { name: "MCP Builder Agent", type: "Meta-Agent", status: "Debugging", owner: "Platform Team", updated: "Today" },
];

const mcpRows: Row[] = [
  { name: "ArcGIS Enterprise MCP", type: "GIS / Admin", status: "Production", owner: "GIS Platform Team", updated: "Today" },
  { name: "PostgreSQL Safe Query MCP", type: "Database", status: "Production", owner: "Data Team", updated: "3 days ago" },
  { name: "Browser CUA MCP", type: "Automation", status: "Staging", owner: "Automation Team", updated: "Yesterday" },
  { name: "Cloud Ops MCP", type: "Azure / GCP / K8s", status: "Draft", owner: "DevOps", updated: "Today" },
];

const workflowRows: Row[] = [
  { name: "Daily GIS Health Report", type: "Scheduled Workflow", status: "Production", owner: "GIS Ops", updated: "Today" },
  { name: "MCP Server Release Pipeline", type: "Deployment Workflow", status: "Staging", owner: "Platform Team", updated: "Today" },
  { name: "Security Approval Review", type: "Human-in-loop", status: "Production", owner: "Security", updated: "1 week ago" },
  { name: "Agent Regression Test Suite", type: "QA Workflow", status: "Debugging", owner: "QA", updated: "Yesterday" },
];

const runtimeTimeline = [
  ["09:00", "Trigger received", "Daily GIS Health Report started from schedule."],
  ["09:01", "Supervisor planned tasks", "GIS, DB, Monitoring, Security, Report, Email agents selected."],
  ["09:02", "MCP tools executed", "ArcGIS, PostgreSQL, Monitoring, and Document MCP servers returned results."],
  ["09:04", "Approval skipped", "Read-only workflow passed policy checks."],
  ["09:05", "Report delivered", "Final PDF and summary email sent to GIS admin."],
];

function cn(...classes: Array<string | false | undefined>) {
  return classes.filter(Boolean).join(" ");
}

function Badge({ children, tone = "slate" }: { children: ReactNode; tone?: "slate" | "green" | "amber" | "blue" | "red" | "violet" }) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium",
        tone === "slate" && "border-slate-700 bg-slate-800 text-slate-300",
        tone === "green" && "border-emerald-500/30 bg-emerald-500/10 text-emerald-300",
        tone === "amber" && "border-amber-500/30 bg-amber-500/10 text-amber-300",
        tone === "blue" && "border-cyan-500/30 bg-cyan-500/10 text-cyan-300",
        tone === "red" && "border-rose-500/30 bg-rose-500/10 text-rose-300",
        tone === "violet" && "border-violet-500/30 bg-violet-500/10 text-violet-300"
      )}
    >
      {children}
    </span>
  );
}

function Card({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <div className={cn("rounded-3xl border border-slate-800 bg-slate-900/70 p-5 shadow-2xl shadow-black/20", className)}>{children}</div>;
}

function StatusBadge({ status }: { status: Row["status"] }) {
  const tone = status === "Production" ? "green" : status === "Staging" ? "blue" : status === "Debugging" ? "amber" : status === "Draft" ? "violet" : "red";
  return <Badge tone={tone}>{status}</Badge>;
}

function Sidebar({ active, setActive }: { active: SectionId; setActive: (id: SectionId) => void }) {
  return (
    <aside className="hidden h-screen w-[310px] shrink-0 border-r border-slate-800 bg-slate-950/95 p-4 lg:sticky lg:top-0 lg:block">
      <div className="flex items-center gap-3 rounded-3xl border border-slate-800 bg-slate-900/80 p-4">
        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-violet-500">
          <Boxes className="h-6 w-6 text-white" />
        </div>
        <div>
          <div className="font-semibold text-white">Enterprise AI MCP</div>
          <div className="text-xs text-slate-400">Platform Console</div>
        </div>
      </div>

      <div className="mt-5 rounded-2xl border border-slate-800 bg-slate-900/60 px-3 py-2">
        <div className="flex items-center gap-2 text-sm text-slate-400">
          <Search className="h-4 w-4" />
          Search agents, MCP, workflows...
        </div>
      </div>

      <nav className="mt-5 space-y-1.5">
        {navSections.map((section) => {
          const Icon = section.icon;
          const isActive = active === section.id;
          return (
            <button
              key={section.id}
              onClick={() => setActive(section.id)}
              className={cn(
                "group flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left transition",
                isActive ? "bg-cyan-500/10 text-white ring-1 ring-cyan-400/30" : "text-slate-400 hover:bg-slate-900 hover:text-white"
              )}
            >
              <div className={cn("flex h-10 w-10 items-center justify-center rounded-xl", isActive ? "bg-cyan-400/20 text-cyan-200" : "bg-slate-900 text-slate-500 group-hover:text-slate-200")}>
                <Icon className="h-5 w-5" />
              </div>
              <div className="min-w-0 flex-1">
                <div className="flex items-center justify-between gap-2">
                  <span className="truncate text-sm font-medium">{section.label}</span>
                  {section.badge && <Badge tone={section.badge === "Live" ? "green" : "slate"}>{section.badge}</Badge>}
                </div>
                <div className="mt-0.5 truncate text-xs text-slate-500">{section.description}</div>
              </div>
            </button>
          );
        })}
      </nav>
    </aside>
  );
}

function MobileSectionTabs({ active, setActive }: { active: SectionId; setActive: (id: SectionId) => void }) {
  return (
    <div className="border-b border-slate-800 bg-slate-950 p-3 lg:hidden">
      <div className="flex gap-2 overflow-x-auto pb-1">
        {navSections.map((section) => {
          const Icon = section.icon;
          return (
            <button
              key={section.id}
              onClick={() => setActive(section.id)}
              className={cn(
                "flex shrink-0 items-center gap-2 rounded-2xl border px-3 py-2 text-sm",
                active === section.id ? "border-cyan-400/40 bg-cyan-500/10 text-white" : "border-slate-800 bg-slate-900 text-slate-400"
              )}
            >
              <Icon className="h-4 w-4" />
              {section.label}
            </button>
          );
        })}
      </div>
    </div>
  );
}

function TopBar({ current }: { current: NavSection }) {
  return (
    <header className="sticky top-0 z-20 border-b border-slate-800 bg-slate-950/85 px-5 py-4 backdrop-blur-xl lg:px-8">
      <div className="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <div className="flex items-center gap-2 text-sm text-slate-400">
            Platform Console <ChevronRight className="h-4 w-4" /> <span className="text-cyan-300">{current.label}</span>
          </div>
          <h1 className="mt-1 text-2xl font-semibold tracking-tight text-white md:text-3xl">{current.label}</h1>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <button className="inline-flex items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900 px-4 py-2 text-sm text-slate-300 transition hover:text-white"><Bell className="h-4 w-4" /> Alerts</button>
          <button className="inline-flex items-center gap-2 rounded-2xl border border-slate-800 bg-slate-900 px-4 py-2 text-sm text-slate-300 transition hover:text-white"><Eye className="h-4 w-4" /> Audit</button>
          <button className="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20"><Plus className="h-4 w-4" /> New Asset</button>
        </div>
      </div>
    </header>
  );
}

function PanelTabs({ activeTab, setActiveTab }: { activeTab: PanelTab; setActiveTab: (tab: PanelTab) => void }) {
  const tabs: Array<{ id: PanelTab; label: string; icon: IconType }> = [
    { id: "overview", label: "Overview", icon: LayoutDashboard },
    { id: "build", label: "Build", icon: Wrench },
    { id: "debug", label: "Debug", icon: TerminalSquare },
    { id: "deploy", label: "Deploy", icon: Rocket },
  ];

  return (
    <div className="flex flex-wrap gap-2">
      {tabs.map((tab) => {
        const Icon = tab.icon;
        return (
          <button
            key={tab.id}
            onClick={() => setActiveTab(tab.id)}
            className={cn(
              "inline-flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm transition",
              activeTab === tab.id ? "border-cyan-400/40 bg-cyan-500/10 text-white" : "border-slate-800 bg-slate-900 text-slate-400 hover:text-white"
            )}
          >
            <Icon className="h-4 w-4" /> {tab.label}
          </button>
        );
      })}
    </div>
  );
}

function StatGrid() {
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      {stats.map((stat) => {
        const Icon = stat.icon;
        return (
          <Card key={stat.label}>
            <div className="flex items-start justify-between">
              <div>
                <p className="text-sm text-slate-400">{stat.label}</p>
                <div className="mt-3 text-3xl font-semibold text-white">{stat.value}</div>
                <p className="mt-2 text-sm text-emerald-300">{stat.delta}</p>
              </div>
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-500/10 text-cyan-300"><Icon className="h-6 w-6" /></div>
            </div>
          </Card>
        );
      })}
    </div>
  );
}

function LifecycleBoard({ title }: { title: string }) {
  return (
    <Card>
      <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 className="text-xl font-semibold text-white">{title} Lifecycle Operations</h2>
          <p className="mt-1 text-sm text-slate-400">Every asset follows controlled create, update, debug, deploy, copy, and delete operations.</p>
        </div>
        <Badge tone="blue">Governed Lifecycle</Badge>
      </div>
      <div className="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {lifecycleActions.map((action) => {
          const Icon = action.icon;
          return (
            <div key={action.label} className="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
              <div className="flex items-start gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-500/10 text-violet-300"><Icon className="h-5 w-5" /></div>
                <div>
                  <h3 className="font-semibold text-white">{action.label}</h3>
                  <p className="mt-1 text-sm leading-6 text-slate-400">{action.description}</p>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </Card>
  );
}

function AssetTable({ rows, title }: { rows: Row[]; title: string }) {
  return (
    <Card className="overflow-hidden p-0">
      <div className="flex items-center justify-between border-b border-slate-800 px-5 py-4">
        <div>
          <h2 className="font-semibold text-white">{title}</h2>
          <p className="text-sm text-slate-400">Production, staging, draft, and debugging assets.</p>
        </div>
        <button className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-slate-300 hover:text-white">View All</button>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[760px] text-left text-sm">
          <thead className="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
            <tr><th className="px-5 py-3">Name</th><th className="px-5 py-3">Type</th><th className="px-5 py-3">Status</th><th className="px-5 py-3">Owner</th><th className="px-5 py-3">Updated</th><th className="px-5 py-3 text-right">Actions</th></tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={row.name} className="border-b border-slate-800/70 last:border-b-0">
                <td className="px-5 py-4 font-medium text-white">{row.name}</td>
                <td className="px-5 py-4 text-slate-400">{row.type}</td>
                <td className="px-5 py-4"><StatusBadge status={row.status} /></td>
                <td className="px-5 py-4 text-slate-400">{row.owner}</td>
                <td className="px-5 py-4 text-slate-400">{row.updated}</td>
                <td className="px-5 py-4 text-right">
                  <div className="inline-flex gap-2">
                    <button className="rounded-xl border border-slate-800 bg-slate-950 p-2 text-slate-400 hover:text-white"><Eye className="h-4 w-4" /></button>
                    <button className="rounded-xl border border-slate-800 bg-slate-950 p-2 text-slate-400 hover:text-white"><Copy className="h-4 w-4" /></button>
                    <button className="rounded-xl border border-slate-800 bg-slate-950 p-2 text-slate-400 hover:text-white"><Rocket className="h-4 w-4" /></button>
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

function WorkflowCanvasMock() {
  const nodes = [
    { label: "Trigger", sub: "Schedule / Webhook / Chat", icon: Zap, x: "left-6 top-8", tone: "border-cyan-400/30 bg-cyan-500/10 text-cyan-200" },
    { label: "Supervisor", sub: "Planner + router", icon: Bot, x: "left-[260px] top-28", tone: "border-violet-400/30 bg-violet-500/10 text-violet-200" },
    { label: "MCP Gateway", sub: "Tools + policy", icon: ServerCog, x: "right-8 top-8", tone: "border-emerald-400/30 bg-emerald-500/10 text-emerald-200" },
    { label: "Approval", sub: "Human-in-loop", icon: ShieldCheck, x: "left-16 bottom-16", tone: "border-amber-400/30 bg-amber-500/10 text-amber-200" },
    { label: "Final Output", sub: "Report / ticket / email", icon: PackageCheck, x: "right-20 bottom-12", tone: "border-rose-400/30 bg-rose-500/10 text-rose-200" },
  ];

  return (
    <Card className="overflow-hidden p-0">
      <div className="flex flex-col gap-3 border-b border-slate-800 px-5 py-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 className="font-semibold text-white">Workflow Canvas</h2>
          <p className="text-sm text-slate-400">Mock visual layout for the final React Flow builder.</p>
        </div>
        <div className="flex gap-2">
          <button className="inline-flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-slate-300"><Import className="h-4 w-4" /> Import</button>
          <button className="inline-flex items-center gap-2 rounded-xl bg-cyan-500 px-3 py-2 text-sm font-semibold text-slate-950"><UploadCloud className="h-4 w-4" /> Export</button>
        </div>
      </div>
      <div className="relative min-h-[460px] overflow-hidden bg-[radial-gradient(circle_at_1px_1px,rgba(148,163,184,0.16)_1px,transparent_0)] [background-size:24px_24px]">
        <svg className="absolute inset-0 h-full w-full" aria-hidden="true">
          <path d="M150 72 C230 72 210 150 270 150" stroke="rgba(34,211,238,.55)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
          <path d="M425 152 C525 152 500 78 600 78" stroke="rgba(167,139,250,.55)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
          <path d="M645 140 C660 250 345 280 220 360" stroke="rgba(16,185,129,.55)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
          <path d="M245 380 C390 430 495 395 590 410" stroke="rgba(251,191,36,.55)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
        </svg>
        {nodes.map((node) => {
          const Icon = node.icon;
          return (
            <div key={node.label} className={cn("absolute w-[190px] rounded-2xl border p-4 shadow-xl backdrop-blur", node.tone, node.x)}>
              <Icon className="mb-3 h-6 w-6" />
              <div className="font-semibold text-white">{node.label}</div>
              <div className="mt-1 text-xs text-slate-300">{node.sub}</div>
            </div>
          );
        })}
      </div>
    </Card>
  );
}

function RuntimeTimeline() {
  return (
    <Card>
      <div className="flex items-center justify-between">
        <div>
          <h2 className="font-semibold text-white">Live Execution Timeline</h2>
          <p className="text-sm text-slate-400">Step-by-step trace across agents, MCP tools, and approvals.</p>
        </div>
        <Badge tone="green">Live</Badge>
      </div>
      <div className="mt-6 space-y-5">
        {runtimeTimeline.map(([time, title, description], index) => (
          <div key={title} className="flex gap-4">
            <div className="flex flex-col items-center">
              <div className="flex h-9 w-9 items-center justify-center rounded-full bg-cyan-500/10 text-cyan-300">
                {index === runtimeTimeline.length - 1 ? <CheckCircle2 className="h-5 w-5" /> : <CircleDot className="h-5 w-5" />}
              </div>
              {index < runtimeTimeline.length - 1 && <div className="mt-2 h-10 w-px bg-slate-800" />}
            </div>
            <div>
              <div className="flex items-center gap-3"><span className="font-medium text-white">{title}</span><span className="text-xs text-slate-500">{time}</span></div>
              <p className="mt-1 text-sm leading-6 text-slate-400">{description}</p>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

function BuilderPanel({ title, subtitle, icon: Icon }: { title: string; subtitle: string; icon: IconType }) {
  const modes = [
    ["Prompt Builder", "Describe what you want to build in natural language.", Sparkles],
    ["Template Builder", "Start from a reusable approved blueprint.", Library],
    ["Manual Builder", "Configure schema, tools, permissions, and deployment directly.", Settings2],
  ] as const;
  return (
    <Card>
      <div className="flex items-start gap-4">
        <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-cyan-500/10 text-cyan-300"><Icon className="h-6 w-6" /></div>
        <div><h2 className="text-xl font-semibold text-white">{title}</h2><p className="mt-1 text-sm leading-6 text-slate-400">{subtitle}</p></div>
      </div>
      <div className="mt-6 grid gap-4 xl:grid-cols-3">
        {modes.map(([label, desc, ModeIcon]) => (
          <button key={label} className="rounded-2xl border border-slate-800 bg-slate-950/70 p-5 text-left transition hover:border-cyan-400/40 hover:bg-cyan-500/5">
            <ModeIcon className="h-6 w-6 text-cyan-300" />
            <div className="mt-4 font-semibold text-white">{label}</div>
            <p className="mt-2 text-sm leading-6 text-slate-400">{desc}</p>
          </button>
        ))}
      </div>
    </Card>
  );
}

function DeploymentPanel({ asset }: { asset: string }) {
  return (
    <Card>
      <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div><h2 className="text-xl font-semibold text-white">{asset} Deployment Pipeline</h2><p className="mt-1 text-sm text-slate-400">Promotion requires tests, security scan, approval, and rollback plan.</p></div>
        <button className="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white"><Rocket className="h-4 w-4" /> Deploy</button>
      </div>
      <div className="mt-8 grid gap-4 md:grid-cols-5">
        {["Draft", "Dev", "Test", "Staging", "Production"].map((step, index) => (
          <div key={step} className="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
            <div className="flex items-center justify-between"><div className="flex h-9 w-9 items-center justify-center rounded-full bg-cyan-500/10 text-cyan-300">{index + 1}</div>{index < 3 ? <CheckCircle2 className="h-5 w-5 text-emerald-300" /> : <TimerReset className="h-5 w-5 text-amber-300" />}</div>
            <h3 className="mt-4 font-semibold text-white">{step}</h3>
            <p className="mt-2 text-xs leading-5 text-slate-500">Policy, tests, approval, release notes, and rollback checkpoint.</p>
          </div>
        ))}
      </div>
    </Card>
  );
}

function DebugPanel({ target }: { target: string }) {
  return (
    <div className="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
      <Card>
        <h2 className="text-xl font-semibold text-white">Debug Session</h2>
        <p className="mt-1 text-sm text-slate-400">Target: {target}</p>
        <div className="mt-5 space-y-3">
          {["Prompt test", "Tool-call replay", "Policy check", "Regression test", "Trace export"].map((item) => (
            <div key={item} className="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-950/70 p-4"><span className="text-sm text-slate-300">{item}</span><CheckCircle2 className="h-5 w-5 text-emerald-300" /></div>
          ))}
        </div>
      </Card>
      <RuntimeTimeline />
    </div>
  );
}

function CodePanel() {
  return (
    <Card className="overflow-hidden p-0">
      <div className="flex items-center justify-between border-b border-slate-800 px-5 py-4">
        <div><h2 className="font-semibold text-white">MCP Tool Manifest Preview</h2><p className="text-sm text-slate-400">Tool schemas are validated before deployment.</p></div>
        <Badge tone="green">Schema valid</Badge>
      </div>
      <pre className="overflow-x-auto p-5 text-sm leading-7 text-slate-300">{`{
  "server": "arcgis-enterprise-mcp",
  "transport": "streamable-http",
  "tools": [
    { "name": "check_portal_health", "risk": "read_only", "approval_required": false },
    { "name": "restart_arcgis_service", "risk": "admin_write", "approval_required": true }
  ],
  "policies": ["tenant_scope", "tool_allowlist", "audit_required"]
}`}</pre>
    </Card>
  );
}

function DashboardView() {
  return (
    <div className="space-y-6">
      <StatGrid />
      <div className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]"><WorkflowCanvasMock /><RuntimeTimeline /></div>
      <div className="grid gap-6 xl:grid-cols-3">
        {[
          ["Agent Control Plane", "Supervisor, planner, router, specialist agents, meta-agents, A2A gateway, and internal messaging.", Bot],
          ["MCP Tool Control Plane", "MCP gateway, client manager, tool registry, resource registry, validators, permissions, logs, and sandbox.", ServerCog],
          ["Governance Layer", "RBAC/ABAC, OPA, Vault, audit, approval gates, signed templates, network allowlists, and traces.", ShieldCheck],
        ].map(([title, desc, IconValue]) => {
          const Icon = IconValue as IconType;
          return <Card key={String(title)}><div className="flex items-center gap-3"><Icon className="h-6 w-6 text-cyan-300" /><h3 className="font-semibold text-white">{title}</h3></div><p className="mt-3 text-sm leading-6 text-slate-400">{desc}</p></Card>;
        })}
      </div>
    </div>
  );
}

function AgentsView({ activeTab }: { activeTab: PanelTab }) {
  return <div className="space-y-6"><LifecycleBoard title="Agent" />{activeTab === "overview" && <AssetTable rows={agentRows} title="Agent Catalog" />}{activeTab === "build" && <BuilderPanel title="Create Agent" subtitle="Generate a specialist, supervisor, or meta-agent from prompt, template, or manual configuration." icon={Bot} />}{activeTab === "debug" && <DebugPanel target="GIS Health Supervisor Agent" />}{activeTab === "deploy" && <DeploymentPanel asset="Agent" />}</div>;
}

function McpView({ activeTab }: { activeTab: PanelTab }) {
  return <div className="space-y-6"><LifecycleBoard title="MCP Server" />{activeTab === "overview" && <AssetTable rows={mcpRows} title="MCP Server Registry" />}{activeTab === "build" && <BuilderPanel title="Create MCP Server" subtitle="Generate a server from OpenAPI, database schema, Python script, ArcGIS REST endpoint, or manual tool definitions." icon={ServerCog} />}{activeTab === "debug" && <CodePanel />}{activeTab === "deploy" && <DeploymentPanel asset="MCP Server" />}</div>;
}

function WorkflowsView({ activeTab }: { activeTab: PanelTab }) {
  return <div className="space-y-6"><LifecycleBoard title="Workflow" />{activeTab === "overview" && <AssetTable rows={workflowRows} title="Workflow Catalog" />}{activeTab === "build" && <WorkflowCanvasMock />}{activeTab === "debug" && <RuntimeTimeline />}{activeTab === "deploy" && <DeploymentPanel asset="Workflow" />}</div>;
}

function GenericGrid({ items, iconColor = "text-cyan-300" }: { items: Array<[string, string, IconType]>; iconColor?: string }) {
  return <div className="grid gap-6 xl:grid-cols-3">{items.map(([title, desc, Icon]) => <Card key={title}><Icon className={cn("h-6 w-6", iconColor)} /><h3 className="mt-4 font-semibold text-white">{title}</h3><p className="mt-2 text-sm leading-6 text-slate-400">{desc}</p></Card>)}</div>;
}

function GenericSectionView({ active }: { active: SectionId; activeTab: PanelTab }) {
  const copy: Record<SectionId, { title: string; subtitle: string; icon: IconType; bullets: string[] }> = {
    dashboard: { title: "Command Center", subtitle: "Unified operational overview.", icon: LayoutDashboard, bullets: [] },
    agents: { title: "Agents", subtitle: "Create and manage AI workers.", icon: Bot, bullets: [] },
    mcp: { title: "MCP Servers", subtitle: "Create and manage MCP tool providers.", icon: ServerCog, bullets: [] },
    orchestration: { title: "Orchestration", subtitle: "Coordinate supervisor agents, specialist agents, A2A, event bus, routing, policies, and workflow state.", icon: Route, bullets: ["Supervisor / planner / router", "A2A gateway for external agents", "Event bus for async workloads", "Shared workflow state", "Policy-aware task routing"] },
    workflows: { title: "Workflows", subtitle: "Visual graph workflows.", icon: Workflow, bullets: [] },
    templates: { title: "Templates", subtitle: "Create, import, export, approve, and reuse blueprints across tenants and environments.", icon: Library, bullets: ["Agent templates", "MCP server templates", "Workflow templates", "Signed packages", "Secret redaction", "Import validation"] },
    runtime: { title: "Runtime Runs", subtitle: "Monitor live and historical executions with step-by-step traces and replay.", icon: Activity, bullets: ["Run timeline", "Retry history", "Node outputs", "Tool-call trace", "Approval decisions", "Replay failed run"] },
    models: { title: "Models + Memory", subtitle: "Route tasks across local and cloud models with memory, RAG, and evaluation scores.", icon: BrainCircuit, bullets: ["Model registry", "Model router", "Ollama / vLLM", "OpenAI / Claude / Gemini adapters", "Qdrant RAG", "Token and cost control"] },
    security: { title: "Security", subtitle: "Govern identities, policies, secrets, approvals, and tool-level access.", icon: ShieldCheck, bullets: ["Keycloak SSO", "OPA policy-as-code", "Vault secrets", "RBAC / ABAC", "Human approval", "Prompt injection protection"] },
    integrations: { title: "Integrations", subtitle: "Connect enterprise systems through MCP servers, Activepieces, APIs, browser automation, and webhooks.", icon: PlugZap, bullets: ["ArcGIS Enterprise", "PostgreSQL / SQL Server / Oracle", "Azure / GCP / AWS", "Jira / ServiceNow", "Email / Teams", "Browser apps"] },
    observability: { title: "Observability", subtitle: "Track traces, logs, metrics, token usage, costs, failures, and agent behavior.", icon: Eye, bullets: ["OpenTelemetry traces", "Prometheus metrics", "Grafana dashboards", "Loki logs", "Cost analytics", "Audit timeline"] },
    deployment: { title: "Deployment", subtitle: "Promote agents, MCP servers, and workflows across environments with GitOps and rollback.", icon: Rocket, bullets: ["Docker containers", "Kubernetes runtime", "CI/CD", "GitOps", "Image scanning", "Blue-green rollout"] },
  };

  const item = copy[active];
  const Icon = item.icon;

  if (active === "runtime") return <RuntimeTimeline />;
  if (active === "deployment") return <DeploymentPanel asset="Platform Asset" />;
  if (active === "templates") {
    return <div className="space-y-6"><BuilderPanel title="Template Library" subtitle="Package assets as reusable, versioned, secure templates." icon={Library} /><GenericGrid items={[["Agent Template", "Create, import, export, sign, validate, and deploy reusable agent blueprints.", Bot], ["MCP Server Template", "Package MCP server manifests, tool schemas, tests, and deployment profiles.", ServerCog], ["Workflow Template", "Reusable visual workflow packages with variables, nodes, approvals, and deployment metadata.", Workflow]]} /></div>;
  }

  return (
    <div className="space-y-6">
      <Card>
        <div className="flex items-start gap-4">
          <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-cyan-500/10 text-cyan-300"><Icon className="h-7 w-7" /></div>
          <div><h2 className="text-2xl font-semibold text-white">{item.title}</h2><p className="mt-2 max-w-3xl text-sm leading-6 text-slate-400">{item.subtitle}</p></div>
        </div>
        <div className="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          {item.bullets.map((bullet) => <div key={bullet} className="flex items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/70 p-4 text-sm text-slate-300"><CheckCircle2 className="h-5 w-5 text-emerald-300" /> {bullet}</div>)}
        </div>
      </Card>
      {active === "orchestration" && <WorkflowCanvasMock />}
      {active === "models" && <GenericGrid items={[["Model Router", "Routes tasks to OpenAI, Claude, Gemini, Groq, Ollama, or vLLM based on cost, quality, and privacy.", BrainCircuit], ["Memory Layer", "Short-term memory, long-term project memory, workflow state, and agent memory.", HardDrive], ["RAG / Qdrant", "Document retrieval, semantic template search, previous run knowledge, and reusable lessons.", Database]]} />}
      {active === "security" && <GenericGrid iconColor="text-emerald-300" items={[["Identity", "Keycloak SSO, OAuth, service accounts, tenant/project scope, and role mapping.", Fingerprint], ["Policy", "OPA policy-as-code for agent, workflow, MCP server, and tool permissions.", LockKeyhole], ["Secrets", "Vault references, no raw secrets in prompts/templates, rotation, and audit.", KeyRound]]} />}
      {active === "integrations" && <GenericGrid iconColor="text-violet-300" items={[["GIS", "ArcGIS Enterprise, ArcGIS Online, Portal, Server, Data Store, services, web maps.", Globe2], ["Data", "PostgreSQL, SQL Server, Oracle, files, SharePoint, OneDrive, PDFs, Excel.", Database], ["Automation", "Activepieces, browser automation, Playwright, webhooks, ITSM, email, Teams.", PlugZap]]} />}
      {active === "observability" && <GenericGrid items={[["Traces", "OpenTelemetry traces for agent steps, tool calls, MCP sessions, and workflows.", Network], ["Metrics", "Prometheus metrics for success rate, latency, queue depth, failures, and cost.", Activity], ["Dashboards", "Grafana dashboards for platform health, asset lifecycle, and business outcomes.", LayoutDashboard]]} />}
    </div>
  );
}

function MainContent({ active, activeTab }: { active: SectionId; activeTab: PanelTab }) {
  if (active === "dashboard") return <DashboardView />;
  if (active === "agents") return <AgentsView activeTab={activeTab} />;
  if (active === "mcp") return <McpView activeTab={activeTab} />;
  if (active === "workflows") return <WorkflowsView activeTab={activeTab} />;
  return <GenericSectionView active={active} activeTab={activeTab} />;
}

export default function EnterpriseAIMCPPlatformConsole() {
  const [active, setActive] = useState<SectionId>("dashboard");
  const [activeTab, setActiveTab] = useState<PanelTab>("overview");
  const current = useMemo(() => navSections.find((section) => section.id === active) ?? navSections[0], [active]);

  function handleSetActive(id: SectionId) {
    setActive(id);
    setActiveTab("overview");
  }

  return (
    <main className="min-h-screen bg-slate-950 text-slate-100">
      <div className="flex">
        <Sidebar active={active} setActive={handleSetActive} />
        <div className="min-w-0 flex-1">
          <MobileSectionTabs active={active} setActive={handleSetActive} />
          <TopBar current={current} />
          <div className="px-5 py-6 lg:px-8">
            <div className="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
              <div>
                <div className="flex items-center gap-2"><Badge tone="blue">{current.description}</Badge><Badge tone="green">Production-ready concept</Badge></div>
                <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-400">Manage the full platform lifecycle: agents, MCP servers, orchestration, workflows, templates, runtime execution, security, integrations, observability, and deployment.</p>
              </div>
              <PanelTabs activeTab={activeTab} setActiveTab={setActiveTab} />
            </div>
            <motion.div key={`${active}-${activeTab}`} initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.25 }}>
              <MainContent active={active} activeTab={activeTab} />
            </motion.div>
          </div>
        </div>
      </div>
    </main>
  );
}
