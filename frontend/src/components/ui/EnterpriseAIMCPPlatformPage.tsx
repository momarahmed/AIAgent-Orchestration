"use client";

import type { ReactNode } from "react";
import { motion } from "framer-motion";
import {
  Activity,
  ArrowRight,
  Bot,
  CheckCircle2,
  ChevronRight,
  CircuitBoard,
  Database,
  FileCode2,
  GitBranch,
  Globe2,
  Layers3,
  Library,
  LockKeyhole,
  MessageSquareText,
  Network,
  PackageCheck,
  PlayCircle,
  RefreshCw,
  Rocket,
  Route,
  ServerCog,
  ShieldCheck,
  Sparkles,
  Workflow,
  Wrench,
} from "lucide-react";

type IconType = typeof Bot;

type PlatformLayer = {
  title: string;
  description: string;
  icon: IconType;
  items: string[];
};

type Capability = {
  title: string;
  description: string;
  icon: IconType;
};

type TechItem = {
  category: string;
  selected: string;
  role: string;
  status: "Core" | "Optional" | "Adapter" | "Reference";
};

const platformLayers: PlatformLayer[] = [
  {
    title: "User Experience Layer",
    description: "Chat, visual workflow builder, agent studio, MCP studio, and admin console.",
    icon: Globe2,
    items: ["Chat UI", "Workflow Builder", "Agent Studio", "MCP Studio", "Admin Console"],
  },
  {
    title: "Design-Time Control Plane",
    description: "Create, update, debug, deploy, copy, import, and export platform assets.",
    icon: Wrench,
    items: ["Agent Lifecycle", "MCP Lifecycle", "Workflow Lifecycle", "Templates", "Versioning"],
  },
  {
    title: "Runtime Control Plane",
    description: "Runs agents, workflows, task queues, retries, approvals, and final outputs.",
    icon: PlayCircle,
    items: ["Supervisor", "Planner", "Router", "Workflow Engine", "Human Approval"],
  },
  {
    title: "MCP Tool Control Plane",
    description: "Central gateway for tools, MCP clients, permissions, validation, logs, and sandboxing.",
    icon: ServerCog,
    items: ["MCP Gateway", "Client Manager", "Tool Registry", "Permission Engine", "Sandbox"],
  },
  {
    title: "Governance + Observability",
    description: "Policy, secrets, audit, security, metrics, tracing, and cost visibility.",
    icon: ShieldCheck,
    items: ["RBAC/ABAC", "OPA", "Vault", "OpenTelemetry", "Audit Trail"],
  },
];

const lifecycleCapabilities: Capability[] = [
  { title: "Agent Factory", description: "Create, copy, test, debug, deploy, and version AI agents with controlled tool access.", icon: Bot },
  { title: "MCP Server Factory", description: "Generate MCP servers from APIs, databases, scripts, and enterprise system connectors.", icon: ServerCog },
  { title: "Workflow Studio", description: "Draw visual workflows with agents, MCP tools, approvals, conditions, retries, and templates.", icon: Workflow },
  { title: "Template Marketplace", description: "Create, import, export, approve, and reuse templates for agents, MCP servers, and workflows.", icon: Library },
  { title: "Debug + Replay", description: "Inspect prompts, tool calls, node outputs, traces, approvals, and failed steps.", icon: Activity },
  { title: "Safe Deployment", description: "Promote assets from draft to dev, test, staging, and production with approvals and rollback.", icon: Rocket },
];

const techStack: TechItem[] = [
  { category: "Frontend", selected: "Next.js + React Flow", role: "Chat UI, visual builder, studios, admin console", status: "Core" },
  { category: "Backend API", selected: "FastAPI", role: "Platform APIs, orchestration endpoints, asset services", status: "Core" },
  { category: "Agent Runtime", selected: "LangGraph", role: "Stateful multi-agent execution and human-in-the-loop flows", status: "Core" },
  { category: "Durable Workflows", selected: "Temporal", role: "Long-running workflows, retries, timers, queues, resumability", status: "Core" },
  { category: "MCP Runtime", selected: "MCP Python SDK + FastMCP", role: "Build and expose enterprise MCP servers and tools", status: "Core" },
  { category: "Automation Hub", selected: "Activepieces", role: "Optional workflow automation bridge exposed through MCP", status: "Optional" },
  { category: "Code Agents", selected: "OpenHands SDK", role: "Generate code, tests, MCP servers, repo edits, DevOps tasks", status: "Adapter" },
  { category: "Provider SDKs", selected: "OpenAI Agents SDK / Claude Agent SDK / Google ADK", role: "Provider-specific agent adapters", status: "Adapter" },
  { category: "Database", selected: "PostgreSQL", role: "Metadata, tenants, assets, audit, workflow state", status: "Core" },
  { category: "Vector Store", selected: "Qdrant", role: "RAG, semantic memory, template search", status: "Core" },
  { category: "Cache/Queue", selected: "Redis", role: "Session state, cache, lightweight queues, locks", status: "Core" },
  { category: "Identity", selected: "Keycloak", role: "SSO, OAuth, tenant-aware identity", status: "Core" },
  { category: "Policy", selected: "OPA", role: "Policy-as-code for tools, agents, workflows, deployment", status: "Core" },
  { category: "Secrets", selected: "Vault", role: "Secure credentials, service accounts, API keys", status: "Core" },
  { category: "Observability", selected: "OpenTelemetry + Prometheus + Grafana", role: "Traces, metrics, dashboards, cost and execution visibility", status: "Core" },
  { category: "Deployment", selected: "Docker + Kubernetes", role: "Containerized runtime, scaling, isolation, GitOps-ready deployment", status: "Core" },
  { category: "Reference Builders", selected: "SIM / Flowise / Dify / TesslateAI", role: "Reference patterns or optional accelerators, not core runtime", status: "Reference" },
  { category: "Multi-Agent Alternatives", selected: "CrewAI / AutoGen", role: "Optional compatibility experiments or migration references", status: "Reference" },
];

const workflowNodes = [
  { label: "User Prompt", icon: MessageSquareText, tone: "from-sky-500 to-cyan-400" },
  { label: "Intent Router", icon: Route, tone: "from-indigo-500 to-violet-500" },
  { label: "Design or Runtime", icon: GitBranch, tone: "from-fuchsia-500 to-pink-500" },
  { label: "Agents", icon: Bot, tone: "from-emerald-500 to-teal-400" },
  { label: "MCP Gateway", icon: ServerCog, tone: "from-orange-500 to-amber-400" },
  { label: "Enterprise Systems", icon: Database, tone: "from-slate-500 to-slate-400" },
  { label: "Final Output", icon: CheckCircle2, tone: "from-green-500 to-lime-400" },
];

const studios = [
  { name: "Agent Studio", icon: Bot, actions: ["Create", "Update", "Delete", "Debug", "Deploy", "Copy", "Template"] },
  { name: "MCP Studio", icon: ServerCog, actions: ["Create", "Update", "Delete", "Debug", "Deploy", "Copy", "Inspect"] },
  { name: "Workflow Studio", icon: Workflow, actions: ["Draw", "Update", "Delete", "Debug", "Deploy", "Copy", "Import", "Export"] },
];

const mcpServers = [
  ["ArcGIS MCP", "Portal, Server, Data Store, services, maps"],
  ["Database MCP", "PostgreSQL, SQL Server, Oracle, safe queries"],
  ["Browser/CUA MCP", "Playwright, screen control, UI automation"],
  ["Cloud MCP", "Azure, AWS, GCP, Kubernetes operations"],
  ["File/PDF MCP", "Documents, reports, Excel, SharePoint"],
  ["Email/ITSM MCP", "Outlook, Teams, Gmail, Jira, ServiceNow"],
];

function cn(...classes: Array<string | false | undefined>) {
  return classes.filter(Boolean).join(" ");
}

function Badge({ children, tone = "default" }: { children: ReactNode; tone?: "default" | "success" | "warning" | "blue" }) {
  return (
    <span className={cn(
      "inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium",
      tone === "default" && "border-white/10 bg-white/5 text-slate-300",
      tone === "success" && "border-emerald-400/30 bg-emerald-400/10 text-emerald-200",
      tone === "warning" && "border-amber-400/30 bg-amber-400/10 text-amber-200",
      tone === "blue" && "border-sky-400/30 bg-sky-400/10 text-sky-200",
    )}>{children}</span>
  );
}

function SectionTitle({ eyebrow, title, description }: { eyebrow: string; title: string; description: string }) {
  return (
    <div className="mx-auto max-w-3xl text-center">
      <Badge tone="blue">{eyebrow}</Badge>
      <h2 className="mt-4 text-3xl font-semibold tracking-tight text-white md:text-5xl">{title}</h2>
      <p className="mt-5 text-base leading-7 text-slate-300 md:text-lg">{description}</p>
    </div>
  );
}

function GlassCard({ children, className = "" }: { children: ReactNode; className?: string }) {
  return <div className={cn("rounded-3xl border border-white/10 bg-white/[0.06] p-6 shadow-2xl shadow-black/20 backdrop-blur", className)}>{children}</div>;
}

function StatusDot({ label }: { label: string }) {
  return (
    <div className="flex items-center gap-2 text-sm text-slate-300">
      <span className="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-lg shadow-emerald-400/40" />
      {label}
    </div>
  );
}

function Hero() {
  return (
    <section className="relative overflow-hidden px-6 pb-20 pt-8 md:px-10 md:pb-28">
      <div className="absolute left-1/2 top-0 h-[520px] w-[900px] -translate-x-1/2 rounded-full bg-cyan-500/20 blur-3xl" />
      <div className="absolute right-0 top-48 h-72 w-72 rounded-full bg-fuchsia-500/20 blur-3xl" />
      <nav className="relative z-10 mx-auto flex max-w-7xl items-center justify-between rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3 backdrop-blur">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-violet-500 shadow-lg shadow-cyan-500/20">
            <CircuitBoard className="h-5 w-5 text-white" />
          </div>
          <div>
            <div className="text-sm font-semibold text-white">Enterprise AI MCP</div>
            <div className="text-xs text-slate-400">Multi-Agent Platform Factory</div>
          </div>
        </div>
        <div className="hidden items-center gap-2 md:flex"><Badge>Agents</Badge><Badge>MCP</Badge><Badge>Workflows</Badge><Badge>Governance</Badge></div>
        <button className="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-cyan-100">Open Console</button>
      </nav>
      <div className="relative z-10 mx-auto mt-20 grid max-w-7xl items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
        <motion.div initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6 }}>
          <div className="flex flex-wrap gap-2"><Badge tone="success">A2A for Agent Communication</Badge><Badge tone="blue">MCP for Tool Communication</Badge><Badge tone="warning">Human Approval Gates</Badge></div>
          <h1 className="mt-7 text-5xl font-semibold tracking-tight text-white md:text-7xl">Build, debug, deploy, and govern your AI workforce.</h1>
          <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-300">A full enterprise platform for creating agents, MCP servers, visual workflows, templates, secure tool access, durable execution, and production observability.</p>
          <div className="mt-8 flex flex-col gap-3 sm:flex-row">
            <button className="group inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-6 py-4 text-sm font-semibold text-white shadow-xl shadow-cyan-500/20 transition hover:scale-[1.02]">Create Workflow <ArrowRight className="h-4 w-4 transition group-hover:translate-x-1" /></button>
            <button className="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-6 py-4 text-sm font-semibold text-white transition hover:bg-white/10"><PlayCircle className="h-4 w-4" /> Watch Execution</button>
          </div>
          <div className="mt-8 grid max-w-xl grid-cols-2 gap-3 sm:grid-cols-4"><StatusDot label="Secure" /><StatusDot label="Observable" /><StatusDot label="Versioned" /><StatusDot label="Deployable" /></div>
        </motion.div>
        <motion.div initial={{ opacity: 0, scale: 0.96 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.7, delay: 0.1 }}>
          <GlassCard className="relative overflow-hidden p-4">
            <div className="absolute inset-0 bg-gradient-to-br from-cyan-400/10 via-transparent to-violet-500/10" />
            <div className="relative rounded-2xl border border-white/10 bg-slate-950/80 p-4">
              <div className="flex items-center justify-between border-b border-white/10 pb-4">
                <div><div className="text-sm font-semibold text-white">Workflow Run #A2A-MCP-2048</div><div className="text-xs text-slate-400">GIS Health + DB + Report + Email</div></div>
                <Badge tone="success">Running</Badge>
              </div>
              <div className="mt-5 space-y-3">
                {workflowNodes.map((node, index) => {
                  const Icon = node.icon;
                  return (
                    <div key={node.label} className="flex items-center gap-3">
                      <div className={cn("flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br", node.tone)}><Icon className="h-5 w-5 text-white" /></div>
                      <div className="min-w-0 flex-1 rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                        <div className="flex items-center justify-between"><span className="text-sm font-medium text-white">{node.label}</span><span className="text-xs text-slate-400">0{index + 1}</span></div>
                        <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10"><div className="h-full rounded-full bg-gradient-to-r from-cyan-400 to-violet-500" style={{ width: `${92 - index * 8}%` }} /></div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </GlassCard>
        </motion.div>
      </div>
    </section>
  );
}

function ArchitectureSection() {
  return (
    <section className="px-6 py-20 md:px-10">
      <SectionTitle eyebrow="Platform Architecture" title="One platform. Two control planes. Full lifecycle governance." description="Design-time builds the assets. Runtime executes them safely. MCP connects agents to tools, while A2A and internal messaging coordinate agents." />
      <div className="mx-auto mt-14 grid max-w-7xl gap-5 md:grid-cols-2 lg:grid-cols-5">
        {platformLayers.map((layer) => {
          const Icon = layer.icon;
          return (
            <GlassCard key={layer.title} className="flex flex-col">
              <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10"><Icon className="h-6 w-6 text-cyan-200" /></div>
              <h3 className="text-lg font-semibold text-white">{layer.title}</h3>
              <p className="mt-3 flex-1 text-sm leading-6 text-slate-300">{layer.description}</p>
              <div className="mt-5 space-y-2">{layer.items.map((item) => <div key={item} className="flex items-center gap-2 text-sm text-slate-300"><ChevronRight className="h-4 w-4 text-cyan-300" />{item}</div>)}</div>
            </GlassCard>
          );
        })}
      </div>
    </section>
  );
}

function StudioSection() {
  return (
    <section className="px-6 py-20 md:px-10">
      <div className="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[0.9fr_1.1fr]">
        <div>
          <Badge tone="warning">Lifecycle Studios</Badge>
          <h2 className="mt-4 text-3xl font-semibold tracking-tight text-white md:text-5xl">Create, update, debug, deploy, copy, and template everything.</h2>
          <p className="mt-5 text-base leading-7 text-slate-300">The application is not only an AI chat system. It is a full platform factory for agents, MCP servers, visual workflows, templates, deployment, and controlled production rollout.</p>
          <div className="mt-8 grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
            {studios.map((studio) => {
              const Icon = studio.icon;
              return <GlassCard key={studio.name} className="p-4"><div className="flex items-start gap-4"><div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400/30 to-violet-500/30"><Icon className="h-5 w-5 text-cyan-100" /></div><div><h3 className="font-semibold text-white">{studio.name}</h3><div className="mt-3 flex flex-wrap gap-2">{studio.actions.map((action) => <Badge key={action}>{action}</Badge>)}</div></div></div></GlassCard>;
            })}
          </div>
        </div>
        <GlassCard className="overflow-hidden p-0">
          <div className="border-b border-white/10 bg-white/[0.04] px-5 py-4"><div className="flex items-center justify-between"><div><h3 className="font-semibold text-white">Visual Workflow Builder</h3><p className="text-sm text-slate-400">React Flow-style canvas with typed nodes and governed deployment.</p></div><Badge tone="success">Validated</Badge></div></div>
          <div className="relative min-h-[520px] overflow-hidden bg-[radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.12)_1px,transparent_0)] [background-size:24px_24px] p-6">
            <div className="absolute left-10 top-10 rounded-2xl border border-cyan-400/30 bg-cyan-400/10 p-4 shadow-xl shadow-cyan-500/10"><MessageSquareText className="mb-3 h-6 w-6 text-cyan-200" /><div className="text-sm font-semibold text-white">Trigger</div><div className="text-xs text-slate-300">Chat / Schedule / Webhook</div></div>
            <div className="absolute left-[260px] top-28 rounded-2xl border border-violet-400/30 bg-violet-400/10 p-4 shadow-xl shadow-violet-500/10"><Bot className="mb-3 h-6 w-6 text-violet-200" /><div className="text-sm font-semibold text-white">Supervisor Agent</div><div className="text-xs text-slate-300">Plan + route tasks</div></div>
            <div className="absolute right-12 top-10 rounded-2xl border border-emerald-400/30 bg-emerald-400/10 p-4 shadow-xl shadow-emerald-500/10"><ServerCog className="mb-3 h-6 w-6 text-emerald-200" /><div className="text-sm font-semibold text-white">MCP Gateway</div><div className="text-xs text-slate-300">Tools + permissions</div></div>
            <div className="absolute bottom-24 left-20 rounded-2xl border border-amber-400/30 bg-amber-400/10 p-4 shadow-xl shadow-amber-500/10"><ShieldCheck className="mb-3 h-6 w-6 text-amber-200" /><div className="text-sm font-semibold text-white">Approval Gate</div><div className="text-xs text-slate-300">Risky actions pause</div></div>
            <div className="absolute bottom-12 right-20 rounded-2xl border border-pink-400/30 bg-pink-400/10 p-4 shadow-xl shadow-pink-500/10"><PackageCheck className="mb-3 h-6 w-6 text-pink-200" /><div className="text-sm font-semibold text-white">Final Output</div><div className="text-xs text-slate-300">Report / Email / Ticket</div></div>
          </div>
        </GlassCard>
      </div>
    </section>
  );
}

function CapabilitiesSection() {
  return (
    <section className="px-6 py-20 md:px-10">
      <SectionTitle eyebrow="Core Capabilities" title="Everything required to move from prototype to production." description="The platform includes builders, debuggers, registries, deployment controls, memory, policy, and observability." />
      <div className="mx-auto mt-14 grid max-w-7xl gap-5 md:grid-cols-2 lg:grid-cols-3">
        {lifecycleCapabilities.map((capability) => {
          const Icon = capability.icon;
          return <GlassCard key={capability.title} className="group transition hover:-translate-y-1 hover:bg-white/[0.08]"><div className="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400/20 to-violet-500/20 transition group-hover:scale-110"><Icon className="h-6 w-6 text-cyan-100" /></div><h3 className="text-xl font-semibold text-white">{capability.title}</h3><p className="mt-3 text-sm leading-6 text-slate-300">{capability.description}</p></GlassCard>;
        })}
      </div>
    </section>
  );
}

function McpServerSection() {
  return (
    <section className="px-6 py-20 md:px-10">
      <div className="mx-auto max-w-7xl rounded-[2rem] border border-white/10 bg-white/[0.04] p-6 md:p-10">
        <div className="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
          <div><Badge tone="blue">MCP Server Layer</Badge><h2 className="mt-4 text-3xl font-semibold tracking-tight text-white md:text-5xl">Governed tools for enterprise systems.</h2><p className="mt-5 text-base leading-7 text-slate-300">MCP servers stay focused on exposing capabilities. Agents and workflows consume those capabilities through the MCP Gateway, policy engine, validator, and execution logs.</p><div className="mt-8 flex flex-wrap gap-2"><Badge>Tools</Badge><Badge>Resources</Badge><Badge>Prompts</Badge><Badge>Sandbox</Badge><Badge>Approval</Badge></div></div>
          <div className="grid gap-4 sm:grid-cols-2">{mcpServers.map(([name, description], index) => <div key={name} className="rounded-3xl border border-white/10 bg-slate-950/60 p-5"><div className="flex items-start justify-between gap-4"><div><h3 className="font-semibold text-white">{name}</h3><p className="mt-2 text-sm leading-6 text-slate-300">{description}</p></div><div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/10 text-sm font-semibold text-cyan-200">{String(index + 1).padStart(2, "0")}</div></div></div>)}</div>
        </div>
      </div>
    </section>
  );
}

function TechStackSection() {
  const statusTone: Record<TechItem["status"], "success" | "warning" | "blue" | "default"> = { Core: "success", Optional: "warning", Adapter: "blue", Reference: "default" };
  return (
    <section className="px-6 py-20 md:px-10">
      <SectionTitle eyebrow="Recommended Final Technology Stack" title="A pragmatic stack mapped to the product objective." description="Core technologies build the platform foundation. Optional tools become accelerators or adapters without controlling the architecture." />
      <div className="mx-auto mt-14 max-w-7xl overflow-hidden rounded-3xl border border-white/10 bg-white/[0.04]">
        <div className="grid grid-cols-12 border-b border-white/10 bg-white/[0.04] px-5 py-4 text-xs font-semibold uppercase tracking-wide text-slate-400"><div className="col-span-3">Category</div><div className="col-span-3">Selected Technology</div><div className="col-span-4">Role in Platform</div><div className="col-span-2 text-right">Status</div></div>
        {techStack.map((item) => <div key={`${item.category}-${item.selected}`} className="grid grid-cols-12 gap-3 border-b border-white/10 px-5 py-4 last:border-b-0"><div className="col-span-12 font-medium text-white md:col-span-3">{item.category}</div><div className="col-span-12 text-sm text-cyan-100 md:col-span-3">{item.selected}</div><div className="col-span-12 text-sm leading-6 text-slate-300 md:col-span-4">{item.role}</div><div className="col-span-12 flex justify-start md:col-span-2 md:justify-end"><Badge tone={statusTone[item.status]}>{item.status}</Badge></div></div>)}
      </div>
    </section>
  );
}

function MetricsSection() {
  const metrics = [
    { label: "Lifecycle Operations", value: "24+", icon: RefreshCw },
    { label: "Core Platform Layers", value: "10", icon: Layers3 },
    { label: "MCP Server Families", value: "10+", icon: Network },
    { label: "Governance Controls", value: "15+", icon: LockKeyhole },
  ];
  return <section className="px-6 py-20 md:px-10"><div className="mx-auto grid max-w-7xl gap-5 md:grid-cols-4">{metrics.map((metric) => { const Icon = metric.icon; return <GlassCard key={metric.label} className="text-center"><div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10"><Icon className="h-6 w-6 text-cyan-200" /></div><div className="mt-4 text-4xl font-semibold text-white">{metric.value}</div><div className="mt-2 text-sm text-slate-400">{metric.label}</div></GlassCard>; })}</div></section>;
}

function RoadmapSection() {
  const phases = [
    ["01", "Core Platform Foundation", "Frontend, backend, metadata, basic agent runtime, MCP gateway."],
    ["02", "Lifecycle + Runtime Reliability", "Debugging, versioning, deployment, Temporal workflows, state, retry."],
    ["03", "Enterprise Governance + Integrations", "OPA, Vault, Keycloak, observability, enterprise MCP servers."],
    ["04", "Advanced Multi-Agent Ecosystem", "A2A, provider SDK adapters, OpenHands, marketplace templates."],
    ["05", "Scale + Productization", "Multi-tenant SaaS readiness, GitOps, marketplace, enterprise operations."],
  ];
  return <section className="px-6 py-20 md:px-10"><SectionTitle eyebrow="Delivery Roadmap" title="Five phases from foundation to enterprise marketplace." description="Each phase can be shipped independently while preserving the final platform architecture." /><div className="mx-auto mt-14 max-w-4xl space-y-4">{phases.map(([number, title, description]) => <GlassCard key={number} className="flex items-start gap-5 p-5"><div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-violet-500 font-semibold text-white">{number}</div><div className="flex-1"><h3 className="text-lg font-semibold text-white">{title}</h3><p className="mt-2 text-sm leading-6 text-slate-300">{description}</p></div><ArrowRight className="mt-3 hidden h-5 w-5 text-slate-500 md:block" /></GlassCard>)}</div></section>;
}

function FooterCta() {
  return (
    <section className="px-6 pb-10 pt-20 md:px-10">
      <div className="mx-auto max-w-7xl overflow-hidden rounded-[2rem] border border-white/10 bg-gradient-to-br from-cyan-500/20 via-violet-500/20 to-fuchsia-500/20 p-8 md:p-12">
        <div className="grid items-center gap-8 lg:grid-cols-[1fr_auto]">
          <div><Badge tone="success">Ready for Build</Badge><h2 className="mt-4 text-3xl font-semibold tracking-tight text-white md:text-5xl">Turn your PRD into a working enterprise AI platform.</h2><p className="mt-5 max-w-3xl text-base leading-7 text-slate-200">Start with the core foundation, then add lifecycle operations, governance, enterprise MCP servers, marketplace templates, and advanced multi-agent collaboration.</p></div>
          <div className="flex flex-col gap-3 sm:flex-row lg:flex-col"><button className="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-6 py-4 text-sm font-semibold text-slate-950 transition hover:bg-cyan-100"><Sparkles className="h-4 w-4" /> Generate First Agent</button><button className="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-6 py-4 text-sm font-semibold text-white transition hover:bg-white/15"><FileCode2 className="h-4 w-4" /> Import Template</button></div>
        </div>
      </div>
    </section>
  );
}

export default function EnterpriseAIMCPPlatformPage() {
  return (
    <main className="min-h-screen bg-slate-950 text-slate-100">
      <div className="pointer-events-none fixed inset-0 bg-[linear-gradient(to_bottom,rgba(15,23,42,.2),rgba(2,6,23,1))]" />
      <div className="relative">
        <Hero />
        <MetricsSection />
        <ArchitectureSection />
        <StudioSection />
        <CapabilitiesSection />
        <McpServerSection />
        <TechStackSection />
        <RoadmapSection />
        <FooterCta />
      </div>
    </main>
  );
}
