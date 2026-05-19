"use client";

import { useMemo, useState } from "react";

type DashboardMode = "overview" | "runtime" | "governance" | "cost";
type Status = "Healthy" | "Warning" | "Critical" | "Running" | "Queued" | "Approved" | "Pending";

type Kpi = {
  label: string;
  value: string;
  change: string;
  status: "good" | "watch" | "risk" | "neutral";
  icon: string;
  detail: string;
};

type Run = {
  workflow: string;
  status: Status;
  progress: number;
  agents: number;
  mcpCalls: number;
  duration: string;
  owner: string;
};

type HealthItem = {
  name: string;
  category: string;
  status: Status;
  uptime: string;
  latency: string;
  lastRun: string;
};

type Approval = {
  title: string;
  risk: "Low" | "Medium" | "High";
  requester: string;
  target: string;
  age: string;
};

const kpis: Kpi[] = [
  { label: "Active Agents", value: "42", change: "+8 this month", status: "good", icon: "🤖", detail: "12 supervisor, 24 specialist, 6 meta-agents" },
  { label: "MCP Servers", value: "28", change: "25 healthy", status: "good", icon: "🧩", detail: "ArcGIS, DB, Browser, Cloud, Email, ITSM" },
  { label: "Workflow Runs", value: "12.4K", change: "98.6% success", status: "good", icon: "🔁", detail: "Last 30 days across all tenants" },
  { label: "Approval Queue", value: "9", change: "3 high risk", status: "watch", icon: "🛡️", detail: "Human approval required before execution" },
  { label: "Avg Tool Latency", value: "420ms", change: "-12% faster", status: "good", icon: "⚡", detail: "MCP gateway p50 latency" },
  { label: "Open Incidents", value: "2", change: "1 critical", status: "risk", icon: "🚨", detail: "Browser CUA staging, Cloud Ops MCP" },
];

const liveRuns: Run[] = [
  { workflow: "Daily GIS Health Report", status: "Running", progress: 76, agents: 6, mcpCalls: 18, duration: "04m 12s", owner: "GIS Ops" },
  { workflow: "MCP Server Release Pipeline", status: "Queued", progress: 24, agents: 4, mcpCalls: 7, duration: "01m 02s", owner: "Platform Team" },
  { workflow: "ArcGIS Service Remediation", status: "Pending", progress: 63, agents: 5, mcpCalls: 11, duration: "06m 45s", owner: "GIS Platform" },
  { workflow: "Template Security Review", status: "Approved", progress: 100, agents: 3, mcpCalls: 5, duration: "02m 33s", owner: "Security" },
];

const agentHealth: HealthItem[] = [
  { name: "Supervisor Agent", category: "Core", status: "Healthy", uptime: "99.99%", latency: "180ms", lastRun: "Now" },
  { name: "GIS Agent", category: "Specialist", status: "Healthy", uptime: "99.92%", latency: "310ms", lastRun: "2m ago" },
  { name: "MCP Builder Agent", category: "Meta-Agent", status: "Warning", uptime: "98.80%", latency: "650ms", lastRun: "8m ago" },
  { name: "Security Review Agent", category: "Governance", status: "Healthy", uptime: "99.95%", latency: "240ms", lastRun: "1m ago" },
];

const mcpHealth: HealthItem[] = [
  { name: "ArcGIS Enterprise MCP", category: "GIS", status: "Healthy", uptime: "99.98%", latency: "260ms", lastRun: "Now" },
  { name: "PostgreSQL MCP", category: "Database", status: "Healthy", uptime: "99.99%", latency: "120ms", lastRun: "1m ago" },
  { name: "Browser CUA MCP", category: "Automation", status: "Warning", uptime: "97.70%", latency: "1.2s", lastRun: "12m ago" },
  { name: "Cloud Ops MCP", category: "DevOps", status: "Critical", uptime: "91.20%", latency: "2.4s", lastRun: "28m ago" },
];

const approvals: Approval[] = [
  { title: "Restart ArcGIS map service", risk: "High", requester: "GIS Agent", target: "ArcGIS Enterprise MCP", age: "12m" },
  { title: "Deploy Cloud Ops MCP to staging", risk: "Medium", requester: "DevOps Agent", target: "Kubernetes Cluster", age: "31m" },
  { title: "Export workflow template package", risk: "Low", requester: "Template Agent", target: "Template Library", age: "44m" },
];

const costRows = [
  ["OpenAI Agents SDK", "$428", "38%", "High reasoning tasks"],
  ["Claude Agent SDK", "$312", "28%", "Code review + documentation"],
  ["Local Ollama / vLLM", "$74", "7%", "Private low-cost tasks"],
  ["MCP Tool Execution", "$296", "27%", "Cloud/API/tool operations"],
];

function cn(...classes: Array<string | false | undefined>) {
  return classes.filter(Boolean).join(" ");
}

function Card({ children, className = "" }: { children: React.ReactNode; className?: string }) {
  return (
    <section className={cn("rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-2xl shadow-black/20", className)}>
      {children}
    </section>
  );
}

function Badge({ children, tone = "slate" }: { children: React.ReactNode; tone?: "slate" | "green" | "amber" | "red" | "blue" | "violet" }) {
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

function statusTone(status: Status) {
  if (status === "Healthy" || status === "Approved") return "green" as const;
  if (status === "Warning" || status === "Queued" || status === "Pending") return "amber" as const;
  if (status === "Critical") return "red" as const;
  if (status === "Running") return "blue" as const;
  return "slate" as const;
}

function riskTone(risk: Approval["risk"]) {
  if (risk === "High") return "red" as const;
  if (risk === "Medium") return "amber" as const;
  return "green" as const;
}

function ProgressBar({ value, tone = "cyan" }: { value: number; tone?: "cyan" | "green" | "amber" | "red" | "violet" }) {
  const fill = {
    cyan: "from-cyan-400 to-sky-500",
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

function MiniSparkline({ tone = "cyan" }: { tone?: "cyan" | "green" | "amber" | "red" | "violet" }) {
  const stroke = {
    cyan: "#22d3ee",
    green: "#34d399",
    amber: "#fbbf24",
    red: "#fb7185",
    violet: "#a78bfa",
  }[tone];

  return (
    <svg viewBox="0 0 120 36" className="h-9 w-full" aria-hidden="true">
      <path d="M0 28 C18 18 20 22 34 14 C49 5 52 22 66 16 C82 8 88 10 100 7 C110 5 116 10 120 8" fill="none" stroke={stroke} strokeWidth="3" strokeLinecap="round" />
      <path d="M0 35 C18 22 20 26 34 18 C49 9 52 26 66 20 C82 12 88 14 100 11 C110 9 116 14 120 12 L120 36 L0 36 Z" fill={stroke} opacity="0.08" />
    </svg>
  );
}

function TopNav({ mode, setMode }: { mode: DashboardMode; setMode: (mode: DashboardMode) => void }) {
  const modes: Array<[DashboardMode, string]> = [
    ["overview", "Overview"],
    ["runtime", "Runtime"],
    ["governance", "Governance"],
    ["cost", "Cost + Models"],
  ];

  return (
    <div className="flex flex-wrap gap-2">
      {modes.map(([id, label]) => (
        <button
          key={id}
          onClick={() => setMode(id)}
          className={cn(
            "rounded-2xl border px-4 py-2 text-sm font-medium transition",
            mode === id ? "border-cyan-400/40 bg-cyan-500/10 text-white" : "border-slate-800 bg-slate-900 text-slate-400 hover:text-white",
          )}
        >
          {label}
        </button>
      ))}
    </div>
  );
}

function Header({ mode, setMode }: { mode: DashboardMode; setMode: (mode: DashboardMode) => void }) {
  return (
    <header className="sticky top-0 z-30 border-b border-slate-800 bg-slate-950/85 px-6 py-4 backdrop-blur-xl xl:px-8">
      <div className="mx-auto flex max-w-[1500px] flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
          <div className="flex flex-wrap items-center gap-2">
            <Badge tone="blue">Enterprise AI + MCP</Badge>
            <Badge tone="green">Production Dashboard</Badge>
            <Badge tone="violet">Multi-Agent Runtime</Badge>
          </div>
          <h1 className="mt-3 text-2xl font-semibold tracking-tight text-white md:text-4xl">AI Workforce Command Dashboard</h1>
          <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-400">
            Monitor agents, MCP servers, workflow runs, approvals, model usage, security posture, and live orchestration health from one executive dashboard.
          </p>
        </div>
        <div className="flex flex-col gap-3 sm:flex-row xl:flex-col 2xl:flex-row">
          <TopNav mode={mode} setMode={setMode} />
          <button className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20">
            Launch New Workflow
          </button>
        </div>
      </div>
    </header>
  );
}

function KpiGrid() {
  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
      {kpis.map((kpi) => {
        const tone = kpi.status === "good" ? "green" : kpi.status === "watch" ? "amber" : kpi.status === "risk" ? "red" : "cyan";
        return (
          <Card key={kpi.label} className="overflow-hidden">
            <div className="flex items-start justify-between gap-3">
              <div className="text-3xl">{kpi.icon}</div>
              <Badge tone={tone === "cyan" ? "blue" : tone}>{kpi.change}</Badge>
            </div>
            <div className="mt-5 text-3xl font-semibold text-white">{kpi.value}</div>
            <div className="mt-1 text-sm font-medium text-slate-300">{kpi.label}</div>
            <p className="mt-3 min-h-[40px] text-xs leading-5 text-slate-500">{kpi.detail}</p>
            <div className="mt-4">
              <MiniSparkline tone={tone as "green" | "amber" | "red" | "cyan"} />
            </div>
          </Card>
        );
      })}
    </div>
  );
}

function LiveRunsPanel() {
  return (
    <Card className="overflow-hidden p-0">
      <div className="flex flex-col gap-3 border-b border-slate-800 px-5 py-4 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 className="font-semibold text-white">Live Workflow Runs</h2>
          <p className="text-sm text-slate-400">Real-time execution across agents, MCP tools, approvals, and final outputs.</p>
        </div>
        <Badge tone="green">Live telemetry</Badge>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full min-w-[820px] text-left text-sm">
          <thead className="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th className="px-5 py-3">Workflow</th>
              <th className="px-5 py-3">Status</th>
              <th className="px-5 py-3">Progress</th>
              <th className="px-5 py-3">Agents</th>
              <th className="px-5 py-3">MCP Calls</th>
              <th className="px-5 py-3">Duration</th>
              <th className="px-5 py-3">Owner</th>
            </tr>
          </thead>
          <tbody>
            {liveRuns.map((run) => (
              <tr key={run.workflow} className="border-b border-slate-800/70 last:border-b-0">
                <td className="px-5 py-4 font-medium text-white">{run.workflow}</td>
                <td className="px-5 py-4"><Badge tone={statusTone(run.status)}>{run.status}</Badge></td>
                <td className="px-5 py-4">
                  <div className="flex items-center gap-3">
                    <div className="w-32"><ProgressBar value={run.progress} tone={run.status === "Pending" ? "amber" : "cyan"} /></div>
                    <span className="text-xs text-slate-400">{run.progress}%</span>
                  </div>
                </td>
                <td className="px-5 py-4 text-slate-300">{run.agents}</td>
                <td className="px-5 py-4 text-slate-300">{run.mcpCalls}</td>
                <td className="px-5 py-4 text-slate-400">{run.duration}</td>
                <td className="px-5 py-4 text-slate-400">{run.owner}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </Card>
  );
}

function HealthList({ title, items }: { title: string; items: HealthItem[] }) {
  return (
    <Card>
      <div className="flex items-center justify-between">
        <div>
          <h2 className="font-semibold text-white">{title}</h2>
          <p className="text-sm text-slate-400">Health, uptime, latency, and last activity.</p>
        </div>
        <button className="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-slate-300 hover:text-white">View all</button>
      </div>
      <div className="mt-5 space-y-3">
        {items.map((item) => (
          <div key={item.name} className="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
            <div className="flex items-start justify-between gap-4">
              <div>
                <div className="font-medium text-white">{item.name}</div>
                <div className="mt-1 text-xs text-slate-500">{item.category} • Last run {item.lastRun}</div>
              </div>
              <Badge tone={statusTone(item.status)}>{item.status}</Badge>
            </div>
            <div className="mt-4 grid grid-cols-2 gap-3 text-xs">
              <div className="rounded-xl bg-slate-900 p-3">
                <div className="text-slate-500">Uptime</div>
                <div className="mt-1 font-semibold text-slate-200">{item.uptime}</div>
              </div>
              <div className="rounded-xl bg-slate-900 p-3">
                <div className="text-slate-500">Latency</div>
                <div className="mt-1 font-semibold text-slate-200">{item.latency}</div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

function ApprovalQueue() {
  return (
    <Card>
      <div className="flex items-center justify-between">
        <div>
          <h2 className="font-semibold text-white">Approval Queue</h2>
          <p className="text-sm text-slate-400">Risky actions paused before execution.</p>
        </div>
        <Badge tone="amber">9 pending</Badge>
      </div>
      <div className="mt-5 space-y-3">
        {approvals.map((approval) => (
          <div key={approval.title} className="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
            <div className="flex items-start justify-between gap-3">
              <div>
                <div className="font-medium text-white">{approval.title}</div>
                <div className="mt-1 text-xs text-slate-500">{approval.requester} → {approval.target}</div>
              </div>
              <Badge tone={riskTone(approval.risk)}>{approval.risk}</Badge>
            </div>
            <div className="mt-4 flex items-center justify-between text-xs text-slate-500">
              <span>Waiting {approval.age}</span>
              <div className="flex gap-2">
                <button className="rounded-lg border border-slate-700 px-2.5 py-1 text-slate-300 hover:text-white">Review</button>
                <button className="rounded-lg bg-emerald-500 px-2.5 py-1 font-semibold text-slate-950">Approve</button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

function OrchestrationMap() {
  const nodes = [
    { label: "User Prompt", x: "left-6 top-10", tone: "cyan" },
    { label: "Intent Router", x: "left-[220px] top-28", tone: "violet" },
    { label: "Supervisor Agent", x: "left-[430px] top-10", tone: "green" },
    { label: "MCP Gateway", x: "right-[220px] top-28", tone: "amber" },
    { label: "Enterprise Tools", x: "right-6 top-10", tone: "red" },
    { label: "Final Output", x: "left-1/2 bottom-8 -translate-x-1/2", tone: "cyan" },
  ];

  const toneClass: Record<string, string> = {
    cyan: "border-cyan-400/30 bg-cyan-500/10 text-cyan-200",
    violet: "border-violet-400/30 bg-violet-500/10 text-violet-200",
    green: "border-emerald-400/30 bg-emerald-500/10 text-emerald-200",
    amber: "border-amber-400/30 bg-amber-500/10 text-amber-200",
    red: "border-rose-400/30 bg-rose-500/10 text-rose-200",
  };

  return (
    <Card className="overflow-hidden p-0">
      <div className="border-b border-slate-800 px-5 py-4">
        <h2 className="font-semibold text-white">Live Orchestration Map</h2>
        <p className="text-sm text-slate-400">How prompts move through agents, MCP gateway, tools, and final output.</p>
      </div>
      <div className="relative min-h-[360px] overflow-hidden bg-[radial-gradient(circle_at_1px_1px,rgba(148,163,184,0.15)_1px,transparent_0)] [background-size:24px_24px]">
        <svg className="absolute inset-0 h-full w-full" aria-hidden="true">
          <path d="M140 70 C210 65 205 135 265 145" stroke="rgba(34,211,238,.55)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
          <path d="M365 145 C430 125 430 78 500 70" stroke="rgba(167,139,250,.55)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
          <path d="M625 70 C690 78 675 135 745 145" stroke="rgba(52,211,153,.55)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
          <path d="M845 145 C910 135 895 68 970 70" stroke="rgba(251,191,36,.55)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
          <path d="M520 105 C480 230 540 250 560 300" stroke="rgba(34,211,238,.45)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
          <path d="M795 175 C740 250 650 275 600 300" stroke="rgba(34,211,238,.45)" strokeWidth="2" fill="none" strokeDasharray="8 8" />
        </svg>
        {nodes.map((node) => (
          <div key={node.label} className={cn("absolute w-[160px] rounded-2xl border p-4 text-center text-sm font-semibold shadow-xl backdrop-blur", toneClass[node.tone], node.x)}>
            {node.label}
          </div>
        ))}
      </div>
    </Card>
  );
}

function GovernancePanel() {
  const controls = [
    ["RBAC / ABAC", "User, tenant, project, and tool-scope access control", "100%"],
    ["OPA Policies", "Policy-as-code checks before deploy and tool execution", "94%"],
    ["Vault Secrets", "No raw secrets in prompts, templates, or logs", "100%"],
    ["Audit Coverage", "Agent, MCP, workflow, approval, and deployment audit", "98%"],
  ];

  return (
    <div className="grid gap-6 xl:grid-cols-[1fr_0.9fr]">
      <Card>
        <h2 className="font-semibold text-white">Governance Coverage</h2>
        <p className="mt-1 text-sm text-slate-400">Security and compliance controls across platform lifecycle.</p>
        <div className="mt-6 space-y-4">
          {controls.map(([name, desc, value]) => (
            <div key={name}>
              <div className="mb-2 flex items-center justify-between gap-3">
                <div>
                  <div className="text-sm font-medium text-white">{name}</div>
                  <div className="text-xs text-slate-500">{desc}</div>
                </div>
                <span className="text-sm font-semibold text-cyan-300">{value}</span>
              </div>
              <ProgressBar value={Number(value.replace("%", ""))} tone="green" />
            </div>
          ))}
        </div>
      </Card>
      <ApprovalQueue />
    </div>
  );
}

function CostPanel() {
  return (
    <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
      <Card>
        <h2 className="font-semibold text-white">Monthly AI + Tool Cost</h2>
        <p className="mt-1 text-sm text-slate-400">Model and MCP execution spend by provider.</p>
        <div className="mt-8 text-5xl font-semibold text-white">$1,110</div>
        <div className="mt-2 text-sm text-emerald-300">14% below budget</div>
        <div className="mt-8 space-y-4">
          {costRows.map(([name, cost, pct]) => (
            <div key={name}>
              <div className="mb-2 flex items-center justify-between text-sm">
                <span className="text-slate-300">{name}</span>
                <span className="font-semibold text-white">{cost}</span>
              </div>
              <ProgressBar value={Number(pct.replace("%", ""))} tone="violet" />
            </div>
          ))}
        </div>
      </Card>
      <Card className="overflow-hidden p-0">
        <div className="border-b border-slate-800 px-5 py-4">
          <h2 className="font-semibold text-white">Model Routing Breakdown</h2>
          <p className="text-sm text-slate-400">Where model calls are routed and why.</p>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[640px] text-left text-sm">
            <thead className="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th className="px-5 py-3">Provider</th>
                <th className="px-5 py-3">Cost</th>
                <th className="px-5 py-3">Share</th>
                <th className="px-5 py-3">Primary Use</th>
              </tr>
            </thead>
            <tbody>
              {costRows.map(([provider, cost, share, use]) => (
                <tr key={provider} className="border-b border-slate-800/70 last:border-b-0">
                  <td className="px-5 py-4 font-medium text-white">{provider}</td>
                  <td className="px-5 py-4 text-slate-300">{cost}</td>
                  <td className="px-5 py-4 text-slate-300">{share}</td>
                  <td className="px-5 py-4 text-slate-400">{use}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
}

function RuntimePanel() {
  return (
    <div className="space-y-6">
      <LiveRunsPanel />
      <div className="grid gap-6 xl:grid-cols-2">
        <HealthList title="Agent Runtime Health" items={agentHealth} />
        <HealthList title="MCP Server Health" items={mcpHealth} />
      </div>
    </div>
  );
}

function OverviewPanel() {
  return (
    <div className="space-y-6">
      <KpiGrid />
      <div className="grid gap-6 2xl:grid-cols-[1.25fr_0.75fr]">
        <OrchestrationMap />
        <ApprovalQueue />
      </div>
      <div className="grid gap-6 xl:grid-cols-2">
        <HealthList title="Agent Runtime Health" items={agentHealth} />
        <HealthList title="MCP Server Health" items={mcpHealth} />
      </div>
      <LiveRunsPanel />
    </div>
  );
}

function DashboardBody({ mode }: { mode: DashboardMode }) {
  const content = useMemo(() => {
    if (mode === "runtime") return <RuntimePanel />;
    if (mode === "governance") return <GovernancePanel />;
    if (mode === "cost") return <CostPanel />;
    return <OverviewPanel />;
  }, [mode]);

  return <div className="mx-auto max-w-[1500px] px-6 py-6 xl:px-8">{content}</div>;
}

export default function EnterpriseAIMCPDashboard() {
  const [mode, setMode] = useState<DashboardMode>("overview");

  return (
    <main className="min-h-screen bg-slate-950 text-slate-100">
      <div className="pointer-events-none fixed inset-0 bg-[radial-gradient(circle_at_top_left,rgba(34,211,238,0.12),transparent_35%),radial-gradient(circle_at_top_right,rgba(139,92,246,0.14),transparent_30%),linear-gradient(to_bottom,rgba(15,23,42,.2),rgba(2,6,23,1))]" />
      <div className="relative">
        <Header mode={mode} setMode={setMode} />
        <DashboardBody mode={mode} />
      </div>
    </main>
  );
}
