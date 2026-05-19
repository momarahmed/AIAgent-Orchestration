"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { codegenApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

export default function CodegenPage() {
  const auth = useAuth();
  const qc = useQueryClient();
  const [prompt, setPrompt] = useState("Generate an MCP server for our internal Inventory REST API with tools list_items, get_item, create_item.");
  const [name, setName] = useState("inventory-mcp");

  const { data: jobs = [] } = useQuery({
    queryKey: ["codegen-jobs"],
    queryFn: () => codegenApi.list({ tenant_id: auth.activeTenantId }),
    enabled: !!auth.activeTenantId,
  });

  const generateMut = useMutation({
    mutationFn: () => codegenApi.generateMcp({
      tenant_id: auth.activeTenantId!,
      prompt,
      inputs: { name, tools: [
        { name: "list_items", description: "List inventory items" },
        { name: "get_item", description: "Get item by id" },
        { name: "create_item", description: "Create a new item" },
      ] },
    }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["codegen-jobs"] }),
  });

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 2 · Build"
        title="Code/DevOps Agent"
        description="OpenHands SDK in a sandboxed workspace generates MCP servers, tool schemas, and tests. Outputs land as a PR for human review."
      />

      <div className="grid gap-4 md:grid-cols-2">
        <div className="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
          <div className="text-sm font-semibold text-white">Generate MCP server</div>
          <div className="mt-3 space-y-3 text-sm">
            <input
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="server slug"
              className="w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white"
            />
            <textarea
              rows={6}
              value={prompt}
              onChange={(e) => setPrompt(e.target.value)}
              className="w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-white"
            />
            <button
              disabled={generateMut.isPending}
              onClick={() => generateMut.mutate()}
              className="rounded-xl bg-cyan-500 px-4 py-2 text-sm font-semibold text-slate-950 disabled:opacity-50"
            >
              {generateMut.isPending ? "Generating…" : "Generate"}
            </button>
          </div>
        </div>

        <div className="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
          <div className="text-sm font-semibold text-white">Latest output</div>
          {generateMut.data ? (
            <div className="mt-3 space-y-2 text-xs">
              <div className="text-slate-400">Job #{generateMut.data.id} · {generateMut.data.status}</div>
              <div className="text-slate-400">Branch: <span className="font-mono text-cyan-300">{generateMut.data.repo_branch}</span></div>
              <pre className="max-h-64 overflow-auto rounded-xl border border-slate-800 bg-slate-900/50 p-3 text-[11px] text-slate-200">{JSON.stringify(generateMut.data.outputs, null, 2)}</pre>
            </div>
          ) : (
            <div className="mt-3 text-xs text-slate-500">Run a generation to see scaffolded files here.</div>
          )}
        </div>
      </div>

      <div className="rounded-2xl border border-slate-800 bg-slate-950/60">
        <div className="border-b border-slate-800 px-4 py-3 text-sm font-semibold text-white">History</div>
        <table className="w-full text-sm">
          <thead className="border-b border-slate-800 bg-slate-900/40 text-xs uppercase tracking-wider text-slate-500">
            <tr>
              <th className="px-4 py-3 text-left">Job</th>
              <th className="px-4 py-3 text-left">Kind</th>
              <th className="px-4 py-3 text-left">Status</th>
              <th className="px-4 py-3 text-left">Branch</th>
              <th className="px-4 py-3 text-left">Prompt</th>
            </tr>
          </thead>
          <tbody>
            {jobs.map((j: any) => (
              <tr key={j.id} className="border-b border-slate-900">
                <td className="px-4 py-3 font-mono text-slate-400">#{j.id}</td>
                <td className="px-4 py-3 text-slate-300">{j.kind}</td>
                <td className="px-4 py-3 text-slate-300">{j.status}</td>
                <td className="px-4 py-3 font-mono text-cyan-300">{j.repo_branch ?? "—"}</td>
                <td className="px-4 py-3 max-w-md truncate text-xs text-slate-400">{j.prompt}</td>
              </tr>
            ))}
            {jobs.length === 0 && (
              <tr><td colSpan={5} className="px-4 py-6 text-center text-sm text-slate-500">No codegen jobs yet.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
