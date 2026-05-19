"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { costGovernanceApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";

export default function CostGovernancePage() {
  const qc = useQueryClient();
  const budgets = useQuery({ queryKey: ["budgets"], queryFn: () => costGovernanceApi.budgets() });
  const history = useQuery({ queryKey: ["chargeback-history"], queryFn: () => costGovernanceApi.chargebackHistory() });
  const recs = useQuery({ queryKey: ["cost-recommendations"], queryFn: () => costGovernanceApi.recommendations({}) });

  const [name, setName] = useState("");
  const [scopeType, setScopeType] = useState<"organization" | "tenant" | "project" | "agent">("tenant");
  const [scopeId, setScopeId] = useState<number | "">("");
  const [limit, setLimit] = useState<number>(1000);
  const [action, setAction] = useState<"alert" | "throttle" | "switch_to_local" | "block">("alert");

  const createBudget = useMutation({
    mutationFn: () =>
      costGovernanceApi.createBudget({
        name,
        scope_type: scopeType,
        scope_id: scopeId === "" ? null : Number(scopeId),
        monthly_limit_usd: Number(limit),
        action_on_exceed: action,
      }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["budgets"] }),
  });

  const generateChargeback = useMutation({
    mutationFn: () => costGovernanceApi.generateChargeback({}),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["chargeback-history"] }),
  });

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 5 · Cost"
        title="Portfolio Cost Governance"
        description="Hierarchical budgets, chargeback, and model-mix recommendations across tenants."
      />

      <section className="grid gap-4 px-1 lg:grid-cols-2">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">Active budgets</h3>
          <div className="mt-3 space-y-3">
            {((budgets.data as any)?.data ?? []).map((b: any) => (
              <div key={b.id} className="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
                <div className="flex items-center justify-between">
                  <div>
                    <div className="text-sm font-medium text-white">{b.name}</div>
                    <div className="text-xs text-slate-500">{b.scope_type}{b.scope_id ? ` #${b.scope_id}` : ""}</div>
                  </div>
                  <div className="text-right text-xs text-slate-400">
                    ${Number(b.current_spend_usd).toFixed(0)} / ${Number(b.monthly_limit_usd).toFixed(0)}
                  </div>
                </div>
                <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-800">
                  <div
                    className={`h-full ${b.utilization_percent > 90 ? "bg-rose-400" : b.utilization_percent > 70 ? "bg-amber-400" : "bg-emerald-400"}`}
                    style={{ width: `${Math.min(100, b.utilization_percent)}%` }}
                  />
                </div>
                <div className="mt-1 flex justify-between text-[10px] uppercase tracking-wider text-slate-500">
                  <span>action: {b.action_on_exceed}</span>
                  <span>{b.utilization_percent}%</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">New budget</h3>
          <form
            className="mt-3 space-y-3 text-sm text-slate-300"
            onSubmit={(e) => {
              e.preventDefault();
              createBudget.mutate();
            }}
          >
            <input
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="Budget name"
              className="w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-white"
              required
            />
            <div className="grid grid-cols-2 gap-2">
              <select value={scopeType} onChange={(e) => setScopeType(e.target.value as any)} className="rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-white">
                <option value="organization">organization</option>
                <option value="tenant">tenant</option>
                <option value="project">project</option>
                <option value="agent">agent</option>
              </select>
              <input
                value={scopeId}
                onChange={(e) => setScopeId(e.target.value === "" ? "" : Number(e.target.value))}
                placeholder="scope id (optional)"
                className="rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-white"
              />
            </div>
            <input type="number" value={limit} onChange={(e) => setLimit(Number(e.target.value))} placeholder="$ limit" className="w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-white" />
            <select value={action} onChange={(e) => setAction(e.target.value as any)} className="w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-white">
              <option value="alert">alert</option>
              <option value="throttle">throttle</option>
              <option value="switch_to_local">switch to local LLM</option>
              <option value="block">block</option>
            </select>
            <button type="submit" className="w-full rounded-lg bg-cyan-500/30 px-3 py-2 text-cyan-100 hover:bg-cyan-500/40">
              {createBudget.isPending ? "Saving…" : "Create budget"}
            </button>
          </form>
        </div>
      </section>

      <section className="grid gap-4 px-1 lg:grid-cols-2">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-semibold text-white">Chargeback reports</h3>
            <button
              onClick={() => generateChargeback.mutate()}
              className="rounded-lg border border-cyan-500/40 bg-cyan-500/10 px-3 py-1 text-xs text-cyan-200 hover:bg-cyan-500/20"
            >
              Generate now
            </button>
          </div>
          <ul className="mt-3 divide-y divide-slate-800 text-sm">
            {((history.data as any)?.data ?? []).map((c: any) => (
              <li key={c.id} className="flex items-center justify-between py-2">
                <span className="text-slate-300">
                  {c.period_start} → {c.period_end}
                </span>
                <span className="text-cyan-200">${Number(c.total_usd).toFixed(2)}</span>
              </li>
            ))}
          </ul>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">Model-mix recommendations</h3>
          <ul className="mt-3 space-y-2 text-sm">
            {((recs.data as any)?.recommendations ?? []).map((r: any) => (
              <li key={r.id} className="rounded-xl border border-amber-500/30 bg-amber-500/5 p-3 text-amber-100">
                <div className="font-medium">{r.message}</div>
                <div className="mt-1 text-xs text-amber-200/80">Est. savings ${Number(r.estimated_savings_usd).toFixed(0)} · {r.recommendation_type}</div>
              </li>
            ))}
          </ul>
        </div>
      </section>
    </div>
  );
}
