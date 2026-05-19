"use client";

import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { flowiseApi, type FlowiseAgent, type FlowiseRun } from "@/lib/api";

/**
 * RunPanel
 * --------
 * Right-rail input/run/result UI for /workflow-studio/[id]. Sends the
 * prompt to /api/flowise/agents/:id/run, which proxies the call to
 * Flowise's /api/v1/prediction/:chatflowId and persists a FlowiseRun row.
 */
export function RunPanel({ agent }: { agent: FlowiseAgent }) {
  const [question, setQuestion] = useState(
    "Hello! Please describe what tools you have access to and run a basic capability check.",
  );
  const [last, setLast] = useState<FlowiseRun | null>(null);

  const runMut = useMutation({
    mutationFn: () => flowiseApi.run(agent.id, { question }),
    onSuccess: setLast,
    onError: (e: any) =>
      setLast({
        id: 0,
        flowise_agent_id: agent.id,
        status: "failed",
        error_message: e?.response?.data?.message ?? e?.message ?? "Run failed",
        created_at: new Date().toISOString(),
      } as FlowiseRun),
  });

  const tone =
    last?.status === "succeeded" ? "border-emerald-500/40 text-emerald-300" :
    last?.status === "failed" ? "border-rose-500/40 text-rose-300" :
    "border-cyan-500/40 text-cyan-300";

  const reply =
    (last?.output && (last.output.text ?? last.output.answer ?? last.output.json?.text)) ||
    (last?.error_message ? `Error: ${last.error_message}` : "");

  return (
    <aside className="flex flex-col gap-3 rounded-3xl border border-slate-800 bg-slate-900/40 p-4">
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold text-white">Run / Test</h3>
        <span className="rounded-full border border-slate-700 px-2 py-0.5 text-[10px] uppercase tracking-wider text-slate-400">
          {agent.flowise_chatflow_id ? "live" : "not pushed"}
        </span>
      </div>

      <textarea
        value={question}
        onChange={(e) => setQuestion(e.target.value)}
        rows={5}
        className="w-full resize-y rounded-2xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400/50"
        placeholder="Ask the agent…"
      />

      <button
        onClick={() => runMut.mutate()}
        disabled={runMut.isPending || !question.trim() || !agent.flowise_chatflow_id}
        className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-cyan-500/20 disabled:opacity-50"
      >
        {runMut.isPending ? "Running…" : "Run agent"}
      </button>
      {!agent.flowise_chatflow_id && (
        <p className="text-[11px] text-amber-300">
          Push this agent to Flowise (Sync button) before running. The local row exists but no chatflow has been created yet.
        </p>
      )}

      {last && (
        <div className={`mt-2 rounded-2xl border bg-slate-950/70 p-3 text-xs ${tone}`}>
          <div className="flex items-center justify-between text-[11px] uppercase tracking-wider text-slate-400">
            <span>{last.status}</span>
            <span>
              {last.duration_ms != null ? `${last.duration_ms} ms` : ""}{" "}
              {last.total_tokens ? `· ${last.total_tokens} tok` : ""}
            </span>
          </div>
          <pre className="mt-2 max-h-72 overflow-auto whitespace-pre-wrap text-slate-200">
            {reply || JSON.stringify(last.output, null, 2)}
          </pre>
          {Array.isArray(last.tool_calls) && last.tool_calls.length > 0 && (
            <details className="mt-2">
              <summary className="cursor-pointer text-slate-400">Tool calls ({last.tool_calls.length})</summary>
              <pre className="mt-1 max-h-44 overflow-auto whitespace-pre-wrap text-[10px] text-slate-300">
                {JSON.stringify(last.tool_calls, null, 2)}
              </pre>
            </details>
          )}
        </div>
      )}
    </aside>
  );
}
