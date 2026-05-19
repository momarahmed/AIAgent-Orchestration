"use client";

import { type FormEvent, useEffect, useMemo, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/lib/auth-context";

type LoginMethod = "password" | "sso" | "passkey";
type TenantId = "esri-saudi" | "smart-city" | "platform-lab" | "demo";

const tenants: Array<{ id: TenantId; name: string; description: string; badge: string }> = [
  { id: "esri-saudi", name: "ESRI Saudi Enterprise", description: "Production tenant", badge: "Prod" },
  { id: "smart-city", name: "Smart City Innovation", description: "Urban AI workspace", badge: "R&D" },
  { id: "platform-lab", name: "Platform Engineering Lab", description: "Dev/Test tenant", badge: "Lab" },
  { id: "demo", name: "Demo Workspace", description: "Safe sandbox mode", badge: "Demo" },
];

const securitySignals = [
  { label: "SSO", value: "Enabled", tone: "green" as const },
  { label: "MFA", value: "Required", tone: "blue" as const },
  { label: "Vault", value: "Connected", tone: "violet" as const },
  { label: "Policy", value: "OPA Active", tone: "amber" as const },
];

const accessCards = [
  { title: "Agent Studio", description: "Create, debug, deploy, copy, and govern enterprise AI agents.", icon: "🤖" },
  { title: "MCP Studio", description: "Build secure MCP servers for ArcGIS, databases, cloud, files, and automation.", icon: "🧩" },
  { title: "Workflow Builder", description: "Draw visual workflows with approvals, policies, templates, and runtime traces.", icon: "🔁" },
];

function cn(...classes: Array<string | false | undefined>) {
  return classes.filter(Boolean).join(" ");
}

function Badge({ children, tone = "slate" }: { children: React.ReactNode; tone?: "slate" | "green" | "amber" | "blue" | "violet" | "red" }) {
  return (
    <span
      className={cn(
        "inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold",
        tone === "slate" && "border-slate-700 bg-slate-800 text-slate-300",
        tone === "green" && "border-emerald-500/30 bg-emerald-500/10 text-emerald-300",
        tone === "amber" && "border-amber-500/30 bg-amber-500/10 text-amber-300",
        tone === "blue" && "border-cyan-500/30 bg-cyan-500/10 text-cyan-300",
        tone === "violet" && "border-violet-500/30 bg-violet-500/10 text-violet-300",
        tone === "red" && "border-rose-500/30 bg-rose-500/10 text-rose-300",
      )}
    >
      {children}
    </span>
  );
}

function Card({ children, className = "" }: { children: React.ReactNode; className?: string }) {
  return <section className={cn("rounded-3xl border border-slate-800 bg-slate-900/80 p-5 shadow-2xl shadow-black/20", className)}>{children}</section>;
}

function BrandPanel() {
  return (
    <section className="relative hidden min-h-screen overflow-hidden border-r border-slate-800 bg-slate-950 p-8 lg:flex lg:w-[52%] xl:w-[56%]">
      <div className="pointer-events-none absolute -left-28 top-20 h-96 w-96 rounded-full bg-cyan-500/20 blur-3xl" />
      <div className="pointer-events-none absolute -right-32 bottom-10 h-[420px] w-[420px] rounded-full bg-violet-500/20 blur-3xl" />
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_1px_1px,rgba(148,163,184,0.13)_1px,transparent_0)] [background-size:28px_28px]" />

      <div className="relative z-10 flex w-full flex-col justify-between">
        <div className="flex items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-violet-500 text-2xl shadow-lg shadow-cyan-500/20">⚙️</div>
            <div>
              <div className="font-semibold text-white">Enterprise AI MCP Platform</div>
              <div className="text-xs text-slate-400">Agent + MCP + Workflow Factory</div>
            </div>
          </div>
          <Badge tone="green">Secure Access</Badge>
        </div>

        <div className="mx-auto max-w-3xl py-16">
          <div className="mb-5 flex flex-wrap gap-2">
            <Badge tone="blue">A2A Communication</Badge>
            <Badge tone="violet">MCP Tool Control</Badge>
            <Badge tone="green">Governed Deployment</Badge>
          </div>
          <h1 className="text-5xl font-semibold tracking-tight text-white xl:text-7xl">Sign in to command your AI workforce.</h1>
          <p className="mt-6 max-w-2xl text-lg leading-8 text-slate-300">
            Access the enterprise console to manage agents, MCP servers, workflows, templates, approvals, policies, observability, and secure deployments.
          </p>

          <div className="mt-10 grid gap-4 xl:grid-cols-3">
            {accessCards.map((card) => (
              <div key={card.title} className="rounded-3xl border border-white/10 bg-white/[0.06] p-5 backdrop-blur">
                <div className="text-3xl">{card.icon}</div>
                <h3 className="mt-4 font-semibold text-white">{card.title}</h3>
                <p className="mt-2 text-sm leading-6 text-slate-400">{card.description}</p>
              </div>
            ))}
          </div>
        </div>

        <div className="grid gap-3 xl:grid-cols-4">
          {securitySignals.map((signal) => (
            <div key={signal.label} className="rounded-2xl border border-slate-800 bg-slate-900/70 p-4">
              <div className="text-xs text-slate-500">{signal.label}</div>
              <div className="mt-2 flex items-center justify-between gap-3">
                <span className="text-sm font-semibold text-white">{signal.value}</span>
                <Badge tone={signal.tone}>Active</Badge>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}

function MethodButton({ method, active, icon, label, onClick }: { method: LoginMethod; active: LoginMethod; icon: string; label: string; onClick: (method: LoginMethod) => void }) {
  const isActive = method === active;
  return (
    <button
      type="button"
      onClick={() => onClick(method)}
      className={cn(
        "flex flex-1 items-center justify-center gap-2 rounded-2xl border px-3 py-3 text-sm font-semibold transition",
        isActive ? "border-cyan-400/40 bg-cyan-500/10 text-white" : "border-slate-800 bg-slate-950 text-slate-400 hover:text-white",
      )}
    >
      <span>{icon}</span>
      {label}
    </button>
  );
}

function TenantSelector({ tenant, setTenant }: { tenant: TenantId; setTenant: (tenant: TenantId) => void }) {
  return (
    <div>
      <label className="mb-2 block text-sm font-medium text-slate-300">Workspace / Tenant</label>
      <div className="grid gap-2">
        {tenants.map((item) => {
          const selected = item.id === tenant;
          return (
            <button
              key={item.id}
              type="button"
              onClick={() => setTenant(item.id)}
              className={cn(
                "flex items-center justify-between gap-4 rounded-2xl border px-4 py-3 text-left transition",
                selected ? "border-cyan-400/40 bg-cyan-500/10" : "border-slate-800 bg-slate-950 hover:border-slate-700",
              )}
            >
              <span>
                <span className="block text-sm font-semibold text-white">{item.name}</span>
                <span className="mt-0.5 block text-xs text-slate-500">{item.description}</span>
              </span>
              <Badge tone={selected ? "blue" : "slate"}>{item.badge}</Badge>
            </button>
          );
        })}
      </div>
    </div>
  );
}

function PasswordLoginForm() {
  const auth = useAuth();
  const [email, setEmail] = useState("admin@enterprise-ai-mcp.local");
  const [password, setPassword] = useState("Admin@12345");
  const [remember, setRemember] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      await auth.login(email, password);
    } catch (e: any) {
      setError(e?.response?.data?.message || e?.message || "Login failed.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label className="mb-2 block text-sm font-medium text-slate-300">Email address</label>
        <input
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          className="w-full rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-600 focus:border-cyan-400/60 focus:ring-4 focus:ring-cyan-400/10"
          placeholder="you@company.com"
          type="email"
          autoComplete="email"
          required
        />
      </div>
      <div>
        <div className="mb-2 flex items-center justify-between gap-3">
          <label className="block text-sm font-medium text-slate-300">Password</label>
          <button type="button" className="text-xs font-semibold text-cyan-300 hover:text-cyan-200">Forgot password?</button>
        </div>
        <input
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          className="w-full rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-white outline-none transition placeholder:text-slate-600 focus:border-cyan-400/60 focus:ring-4 focus:ring-cyan-400/10"
          placeholder="Enter your password"
          type="password"
          autoComplete="current-password"
          required
        />
      </div>
      <div className="flex items-center justify-between gap-3">
        <label className="flex items-center gap-3 text-sm text-slate-400">
          <input
            checked={remember}
            onChange={(event) => setRemember(event.target.checked)}
            type="checkbox"
            className="h-4 w-4 rounded border-slate-700 bg-slate-950 accent-cyan-400"
          />
          Remember this device
        </label>
        <Badge tone="green">MFA optional</Badge>
      </div>
      <button
        type="submit"
        disabled={submitting}
        className="w-full rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 transition hover:scale-[1.01] disabled:opacity-60"
      >
        {submitting ? "Signing in…" : "Sign in securely"}
      </button>
      {error && (
        <div className="rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4 text-sm leading-6 text-rose-200">
          {error}
        </div>
      )}
      <div className="rounded-2xl border border-slate-800 bg-slate-950 p-3 text-xs leading-5 text-slate-400">
        Demo credentials seeded for local dev: <code className="text-cyan-300">admin@enterprise-ai-mcp.local</code> / <code className="text-cyan-300">Admin@12345</code>
      </div>
    </form>
  );
}

function SsoLoginPanel() {
  return (
    <div className="space-y-3">
      {[
        ["Continue with Keycloak SSO", "Recommended enterprise identity provider", "🔑"],
        ["Continue with Microsoft Entra ID", "Use corporate Azure AD / Entra tenant", "🪪"],
        ["Continue with Google Workspace", "Use organization Google identity", "🌐"],
      ].map(([label, description, icon]) => (
        <button key={label} className="flex w-full items-center justify-between gap-4 rounded-2xl border border-slate-800 bg-slate-950 px-4 py-4 text-left transition hover:border-cyan-400/40 hover:bg-cyan-500/5">
          <span className="flex items-center gap-3">
            <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-900 text-xl">{icon}</span>
            <span>
              <span className="block text-sm font-semibold text-white">{label}</span>
              <span className="mt-0.5 block text-xs text-slate-500">{description}</span>
            </span>
          </span>
          <span className="text-slate-500">→</span>
        </button>
      ))}
      <p className="px-1 text-xs leading-5 text-slate-500">
        SSO flows are stubs in Phase 1. Full Keycloak / OIDC / WebAuthn lands in later phases per the PRD roadmap.
      </p>
    </div>
  );
}

function PasskeyPanel() {
  return (
    <div className="rounded-3xl border border-slate-800 bg-slate-950 p-5 text-center">
      <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-cyan-500/10 text-3xl">🔐</div>
      <h3 className="mt-5 text-lg font-semibold text-white">Use passkey authentication</h3>
      <p className="mt-2 text-sm leading-6 text-slate-400">
        Sign in with a hardware key, platform authenticator, or biometric passkey registered with your enterprise identity provider.
      </p>
      <button className="mt-5 w-full rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-cyan-100">
        Continue with passkey
      </button>
    </div>
  );
}

function LoginCard() {
  const [method, setMethod] = useState<LoginMethod>("password");
  const [tenant, setTenant] = useState<TenantId>("esri-saudi");
  const selectedTenant = useMemo(() => tenants.find((item) => item.id === tenant) ?? tenants[0], [tenant]);

  return (
    <section className="flex min-h-screen flex-1 items-center justify-center px-5 py-8 lg:px-8">
      <div className="w-full max-w-[520px]">
        <div className="mb-8 lg:hidden">
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-cyan-400 to-violet-500 text-2xl">⚙️</div>
            <div>
              <div className="font-semibold text-white">Enterprise AI MCP Platform</div>
              <div className="text-xs text-slate-400">Agent + MCP + Workflow Factory</div>
            </div>
          </div>
        </div>

        <Card className="p-6 md:p-7">
          <div className="flex items-start justify-between gap-4">
            <div>
              <Badge tone="blue">Secure Sign In</Badge>
              <h2 className="mt-4 text-3xl font-semibold tracking-tight text-white">Welcome back</h2>
              <p className="mt-2 text-sm leading-6 text-slate-400">
                Select your tenant and authentication method to access the admin console, dashboard, agent studio, MCP studio, and workflow builder.
              </p>
            </div>
            <div className="hidden rounded-2xl border border-slate-800 bg-slate-950 p-3 text-2xl md:block">🧠</div>
          </div>

          <div className="mt-6 rounded-2xl border border-slate-800 bg-slate-950/80 p-4">
            <div className="flex items-center justify-between gap-3">
              <div>
                <div className="text-xs uppercase tracking-wide text-slate-500">Selected workspace</div>
                <div className="mt-1 text-sm font-semibold text-white">{selectedTenant.name}</div>
              </div>
              <Badge tone={tenant === "demo" ? "amber" : "green"}>{selectedTenant.badge}</Badge>
            </div>
          </div>

          <div className="mt-5 flex gap-2">
            <MethodButton method="password" active={method} icon="✉️" label="Password" onClick={setMethod} />
            <MethodButton method="sso" active={method} icon="🔑" label="SSO" onClick={setMethod} />
            <MethodButton method="passkey" active={method} icon="🔐" label="Passkey" onClick={setMethod} />
          </div>

          <div className="mt-6 space-y-6">
            <TenantSelector tenant={tenant} setTenant={setTenant} />
            {method === "password" && <PasswordLoginForm />}
            {method === "sso" && <SsoLoginPanel />}
            {method === "passkey" && <PasskeyPanel />}
          </div>

          <div className="mt-6 rounded-2xl border border-slate-800 bg-slate-950 p-4">
            <div className="grid gap-3 sm:grid-cols-2">
              <div>
                <div className="text-xs text-slate-500">Security posture</div>
                <div className="mt-1 text-sm font-semibold text-emerald-300">Compliant</div>
              </div>
              <div>
                <div className="text-xs text-slate-500">Session policy</div>
                <div className="mt-1 text-sm font-semibold text-cyan-300">Tenant isolated</div>
              </div>
            </div>
          </div>
        </Card>

        <p className="mt-5 text-center text-xs leading-6 text-slate-500">
          By signing in, you agree to enterprise security policies, audit logging, tool execution controls, and approval workflows.
        </p>
      </div>
    </section>
  );
}

export default function LoginPage() {
  const router = useRouter();
  const params = useSearchParams();
  const auth = useAuth();

  useEffect(() => {
    if (!auth.loading && auth.user) {
      router.replace(params.get("next") || "/dashboard");
    }
  }, [auth.loading, auth.user, router, params]);

  return (
    <main className="min-h-screen bg-slate-950 text-slate-100">
      <div className="pointer-events-none fixed inset-0 bg-[radial-gradient(circle_at_top_left,rgba(34,211,238,0.12),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(139,92,246,0.14),transparent_30%),linear-gradient(to_bottom,rgba(15,23,42,.1),rgba(2,6,23,1))]" />
      <div className="relative flex min-h-screen">
        <BrandPanel />
        <LoginCard />
      </div>
    </main>
  );
}
