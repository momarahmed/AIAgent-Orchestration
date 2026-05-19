"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { legacyImportsApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";

const SAMPLE_PAYLOADS: Record<string, any> = {
  autogen: {
    agents: [
      { name: "Researcher",  system_message: "Research the topic.", llm_config: { model: "gpt-4o" } },
      { name: "Writer",      system_message: "Write a final report.", llm_config: { model: "gpt-4o" } },
      { name: "Reviewer",    system_message: "Critique and improve.", llm_config: { model: "gpt-4o" } },
    ],
    messages: [],
  },
  tesslate: { components: [{ type: "agent", name: "Onboarding", config: { model: "gpt-4" } }], edges: [] },
  n8n: { nodes: [{ name: "Webhook", type: "n8n-nodes-base.webhook", parameters: { path: "/in" } }, { name: "Email", type: "n8n-nodes-base.emailSend", parameters: {} }], connections: {} },
  flowise: { nodes: [{ id: "n1", data: { label: "ChatAgent", inputs: {} } }], edges: [] },
  dify: { graph: { nodes: [{ id: "d1", data: { type: "start", title: "Start" } }] } },
  crewai: { crew: { agents: [{ role: "PM", goal: "Plan", backstory: "ex-startup PM", system_message: "Plan the project." }], tasks: [{ description: "Make a roadmap" }] } },
};

export default function LegacyImportPage() {
  const qc = useQueryClient();
  const [format, setFormat] = useState<keyof typeof SAMPLE_PAYLOADS>("autogen");
  const [payload, setPayload] = useState<string>(JSON.stringify(SAMPLE_PAYLOADS.autogen, null, 2));

  const jobs = useQuery({ queryKey: ["legacy-imports"], queryFn: () => legacyImportsApi.list() });

  const submit = useMutation({
    mutationFn: () => {
      let parsed: any;
      try {
        parsed = JSON.parse(payload);
      } catch {
        throw new Error("Invalid JSON");
      }
      return legacyImportsApi.create({ source_format: format, payload: parsed });
    },
    onSuccess: () => qc.invalidateQueries({ queryKey: ["legacy-imports"] }),
  });

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 5 · Legacy migration"
        title="AutoGen / Tesslate / n8n / Flowise / Dify / CrewAI importer"
        description="Convert legacy flows into native LangGraph + Temporal workflows with a confidence score and human-review checklist."
      />

      <section className="grid gap-4 px-1 lg:grid-cols-[2fr_1fr]">
        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <div className="flex flex-wrap items-center gap-2">
            {Object.keys(SAMPLE_PAYLOADS).map((k) => (
              <button
                key={k}
                onClick={() => {
                  setFormat(k as any);
                  setPayload(JSON.stringify(SAMPLE_PAYLOADS[k], null, 2));
                }}
                className={`rounded-full border px-3 py-1 text-xs ${
                  format === k ? "border-cyan-500/50 bg-cyan-500/20 text-cyan-100" : "border-slate-700 bg-slate-800 text-slate-300"
                }`}
              >
                {k}
              </button>
            ))}
          </div>
          <textarea
            value={payload}
            onChange={(e) => setPayload(e.target.value)}
            rows={18}
            className="mt-3 w-full rounded-lg border border-slate-700 bg-slate-950/60 px-3 py-2 font-mono text-xs text-slate-200"
          />
          <button
            onClick={() => submit.mutate()}
            disabled={submit.isPending}
            className="mt-3 rounded-lg bg-cyan-500/30 px-4 py-2 text-sm text-cyan-100 hover:bg-cyan-500/40 disabled:opacity-60"
          >
            {submit.isPending ? "Importing…" : "Convert to native workflow"}
          </button>
          {submit.isError && <p className="mt-2 text-xs text-rose-300">{String((submit.error as Error).message)}</p>}
        </div>

        <div className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
          <h3 className="text-sm font-semibold text-white">Recent imports</h3>
          <ul className="mt-3 space-y-2 text-sm">
            {((jobs.data as any)?.data ?? []).map((j: any) => (
              <li key={j.id} className="rounded-xl border border-slate-800 bg-slate-950/40 p-3">
                <div className="flex items-center justify-between">
                  <span className="text-slate-200">#{j.id} · {j.source_format}</span>
                  <span className="text-xs text-cyan-200">{Number(j.confidence_score).toFixed(0)}%</span>
                </div>
                <div className="mt-1 text-xs text-slate-500">{j.status}</div>
              </li>
            ))}
          </ul>
        </div>
      </section>

      {submit.data && (
        <section className="rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-5">
          <h3 className="text-sm font-semibold text-emerald-200">Converted workflow ({Number((submit.data as any).confidence_score).toFixed(0)}% confidence)</h3>
          <pre className="mt-3 overflow-x-auto rounded-lg bg-slate-950/60 p-3 text-xs text-emerald-100">
            {JSON.stringify((submit.data as any).output_payload, null, 2)}
          </pre>
          <h4 className="mt-4 text-xs uppercase tracking-wider text-emerald-200">Review checklist</h4>
          <ul className="mt-2 space-y-1 text-xs text-emerald-100">
            {((submit.data as any).review_checklist ?? []).map((c: any) => (
              <li key={c.id}>☐ {c.description}</li>
            ))}
          </ul>
        </section>
      )}
    </div>
  );
}
