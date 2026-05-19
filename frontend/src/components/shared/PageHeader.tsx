"use client";

import { ReactNode } from "react";

export function PageHeader({
  eyebrow,
  title,
  description,
  actions,
}: {
  eyebrow?: string;
  title: string;
  description?: string;
  actions?: ReactNode;
}) {
  return (
    <div className="flex flex-wrap items-end justify-between gap-4 border-b border-slate-800 px-4 py-6 lg:px-6">
      <div>
        {eyebrow && <div className="text-xs uppercase tracking-wider text-cyan-300">{eyebrow}</div>}
        <h1 className="mt-1 text-2xl font-semibold text-white lg:text-3xl">{title}</h1>
        {description && <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-400">{description}</p>}
      </div>
      {actions && <div className="flex items-center gap-2">{actions}</div>}
    </div>
  );
}

export function StatusBadge({ value }: { value: string }) {
  const tone =
    value === "completed" || value === "production" || value === "healthy" ? "emerald" :
    value === "running" || value === "staging" ? "cyan" :
    value === "failed" || value === "unhealthy" ? "rose" :
    value === "awaiting_approval" || value === "degraded" ? "amber" :
    "slate";
  const cls: Record<string, string> = {
    emerald: "border-emerald-500/30 bg-emerald-500/10 text-emerald-300",
    cyan: "border-cyan-500/30 bg-cyan-500/10 text-cyan-300",
    rose: "border-rose-500/30 bg-rose-500/10 text-rose-300",
    amber: "border-amber-500/30 bg-amber-500/10 text-amber-300",
    slate: "border-slate-700 bg-slate-800 text-slate-300",
  };
  return <span className={`inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ${cls[tone]}`}>{value}</span>;
}

export function EmptyState({ title, description, action }: { title: string; description: string; action?: ReactNode }) {
  return (
    <div className="m-4 rounded-3xl border border-dashed border-slate-800 bg-slate-900/40 p-10 text-center lg:m-6">
      <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-800 text-2xl">📭</div>
      <h3 className="mt-4 text-lg font-semibold text-white">{title}</h3>
      <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-400">{description}</p>
      {action && <div className="mt-4">{action}</div>}
    </div>
  );
}
