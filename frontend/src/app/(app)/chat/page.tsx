"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { chatApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";
import { useAuth } from "@/lib/auth-context";

type Message = { role: "user" | "assistant"; text: string; runId?: number };

export default function ChatPage() {
  const auth = useAuth();
  const [prompt, setPrompt] = useState("");
  const [messages, setMessages] = useState<Message[]>([]);
  const [loading, setLoading] = useState(false);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    if (!prompt.trim() || loading) return;
    const userMsg = prompt.trim();
    setPrompt("");
    setMessages((m) => [...m, { role: "user", text: userMsg }]);
    setLoading(true);
    try {
      const res = await chatApi.execute({
        prompt: userMsg,
        tenant_id: auth.activeTenantId ?? undefined,
        project_id: auth.activeProjectId ?? undefined,
      });
      setMessages((m) => [
        ...m,
        {
          role: "assistant",
          text: res.response ?? JSON.stringify(res),
          runId: res.run_id,
        },
      ]);
    } catch (err: any) {
      setMessages((m) => [
        ...m,
        { role: "assistant", text: err?.response?.data?.message ?? err?.message ?? "Request failed." },
      ]);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex h-[calc(100vh-4rem)] flex-col">
      <PageHeader
        eyebrow="UX-002 · Phase 1"
        title="Chat"
        description="Single-turn prompts against your enterprise agents. Each response links to a persisted run record."
      />
      <div className="flex flex-1 flex-col overflow-hidden px-4 pb-4 lg:px-6">
        <div className="flex-1 space-y-4 overflow-y-auto rounded-3xl border border-slate-800 bg-slate-900/40 p-4">
          {messages.length === 0 && (
            <p className="text-center text-sm text-slate-500">Ask the GIS Health agent or any configured agent…</p>
          )}
          {messages.map((m, i) => (
            <div key={i} className={`flex ${m.role === "user" ? "justify-end" : "justify-start"}`}>
              <div
                className={`max-w-[80%] rounded-2xl px-4 py-3 text-sm leading-6 ${
                  m.role === "user"
                    ? "bg-gradient-to-r from-cyan-500/20 to-violet-500/20 text-white"
                    : "border border-slate-700 bg-slate-950 text-slate-200"
                }`}
              >
                {m.text}
                {m.runId && (
                  <div className="mt-2">
                    <Link href={`/runs?run=${m.runId}`} className="text-xs font-semibold text-cyan-300 hover:underline">
                      View run #{m.runId} →
                    </Link>
                  </div>
                )}
              </div>
            </div>
          ))}
          {loading && <div className="text-sm text-slate-500">Agent is thinking…</div>}
        </div>
        <form onSubmit={onSubmit} className="mt-4 flex gap-2">
          <input
            value={prompt}
            onChange={(e) => setPrompt(e.target.value)}
            placeholder="Enter a natural language prompt…"
            className="flex-1 rounded-2xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-white outline-none focus:border-cyan-400/40"
          />
          <button
            type="submit"
            disabled={loading}
            className="rounded-2xl bg-gradient-to-r from-cyan-400 to-violet-500 px-5 py-3 text-sm font-semibold text-white disabled:opacity-60"
          >
            Send
          </button>
        </form>
      </div>
    </div>
  );
}
