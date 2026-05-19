"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import {
  ReactFlow,
  Background,
  Controls,
  MiniMap,
  addEdge,
  useNodesState,
  useEdgesState,
  type Connection,
  type Node,
  type Edge,
  Panel,
} from "@xyflow/react";
import "@xyflow/react/dist/style.css";
import { agentsApi, mcpServersApi, workflowsApi, type Agent, type McpServer } from "@/lib/api";

const NODE_TYPES_LIST = [
  { type: "trigger", label: "Trigger", color: "#22d3ee" },
  { type: "agent", label: "Agent", color: "#a855f7" },
  { type: "mcp_tool", label: "MCP Tool", color: "#34d399" },
  { type: "approval", label: "Approval", color: "#f59e0b" },
  { type: "decision", label: "Decision", color: "#fbbf24" },
  { type: "transform", label: "Transform", color: "#60a5fa" },
  { type: "loop", label: "Loop", color: "#c084fc" },
  { type: "parallel", label: "Parallel", color: "#f472b6" },
  { type: "error_handler", label: "Error Handler", color: "#fb7185" },
  { type: "template", label: "Template", color: "#94a3b8" },
];

const nodeStyle = {
  background: "#0f172a",
  border: "1px solid rgba(34,211,238,0.4)",
  borderRadius: 12,
  color: "#e2e8f0",
  padding: 8,
  minWidth: 140,
};

function toFlowNodes(graph: { nodes?: any[] }): Node[] {
  return (graph?.nodes ?? []).map((n: any) => ({
    id: n.id,
    type: "default",
    position: n.position ?? { x: 100, y: 100 },
    data: { label: n.data?.label ?? n.type, ...n.data, nodeType: n.type },
    style: nodeStyle,
  }));
}

function toFlowEdges(graph: { edges?: any[] }): Edge[] {
  return (graph?.edges ?? []).map((e: any) => ({
    id: e.id,
    source: e.source,
    target: e.target,
    animated: true,
    style: { stroke: "#64748b" },
  }));
}

function fromFlow(nodes: Node[], edges: Edge[]) {
  return {
    nodes: nodes.map((n) => ({
      id: n.id,
      type: (n.data as any)?.nodeType ?? "trigger",
      position: n.position,
      data: {
        label: (n.data as any)?.label,
        agent_id: (n.data as any)?.agent_id,
        tool_id: (n.data as any)?.tool_id,
        prompt: (n.data as any)?.prompt,
        inputs: (n.data as any)?.inputs,
        retry_policy: (n.data as any)?.retry_policy,
        risk_level: (n.data as any)?.risk_level,
        reason: (n.data as any)?.reason,
        expression: (n.data as any)?.expression,
        mapping: (n.data as any)?.mapping,
        items_path: (n.data as any)?.items_path,
        max_iterations: (n.data as any)?.max_iterations,
        branches: (n.data as any)?.branches,
        template_id: (n.data as any)?.template_id,
      },
    })),
    edges: edges.map((e) => ({ id: e.id, source: e.source, target: e.target })),
  };
}

let nodeSeq = 1;

export function WorkflowBuilder({
  workflowId,
  tenantId,
  projectId,
  initialGraph,
  onSaved,
}: {
  workflowId: number;
  tenantId: number;
  projectId: number;
  initialGraph?: { nodes?: any[]; edges?: any[] };
  onSaved?: () => void;
}) {
  const [nodes, setNodes, onNodesChange] = useNodesState(toFlowNodes(initialGraph ?? {}));
  const [edges, setEdges, onEdgesChange] = useEdgesState(toFlowEdges(initialGraph ?? {}));
  const [selected, setSelected] = useState<Node | null>(null);
  const [agents, setAgents] = useState<Agent[]>([]);
  const [servers, setServers] = useState<McpServer[]>([]);
  const [saving, setSaving] = useState(false);
  const [msg, setMsg] = useState<string | null>(null);

  useEffect(() => {
    agentsApi.list({ tenant_id: tenantId, project_id: projectId }).then(setAgents).catch(() => {});
    mcpServersApi.list({ tenant_id: tenantId, project_id: projectId }).then(setServers).catch(() => {});
  }, [tenantId, projectId]);

  const tools = useMemo(
    () => servers.flatMap((s) => (s.tools ?? []).map((t) => ({ ...t, serverName: s.name }))),
    [servers],
  );

  const onConnect = useCallback(
    (c: Connection) => setEdges((eds) => addEdge({ ...c, animated: true, style: { stroke: "#64748b" } }, eds)),
    [setEdges],
  );

  const addNode = (type: string) => {
    const id = `${type[0]}${nodeSeq++}`;
    setNodes((nds) => [
      ...nds,
      {
        id,
        type: "default",
        position: { x: 120 + nds.length * 40, y: 120 + nds.length * 30 },
        data: { label: type, nodeType: type },
        style: nodeStyle,
      },
    ]);
  };

  const updateSelected = (patch: Record<string, unknown>) => {
    if (!selected) return;
    setNodes((nds) =>
      nds.map((n) => (n.id === selected.id ? { ...n, data: { ...n.data, ...patch } } : n)),
    );
    setSelected((s) => (s ? { ...s, data: { ...s.data, ...patch } } : s));
  };

  const save = async () => {
    setSaving(true);
    setMsg(null);
    try {
      await workflowsApi.update(workflowId, { graph_json: fromFlow(nodes, edges) });
      setMsg("Saved — new workflow version created.");
      onSaved?.();
    } catch (e: any) {
      const errs = e?.response?.data?.errors?.graph_json;
      setMsg(Array.isArray(errs) ? errs.join(" ") : e?.response?.data?.message ?? e?.message ?? "Save failed");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="flex h-[calc(100vh-8rem)] overflow-hidden rounded-3xl border border-slate-800 bg-slate-950">
      <aside className="w-52 shrink-0 border-r border-slate-800 bg-slate-900/80 p-4">
        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Palette</div>
        <div className="mt-3 space-y-2">
          {NODE_TYPES_LIST.map((t) => (
            <button
              key={t.type}
              type="button"
              onClick={() => addNode(t.type)}
              className="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-left text-sm text-white hover:border-cyan-400/40"
            >
              <span className="mr-2 inline-block h-2 w-2 rounded-full" style={{ background: t.color }} />
              {t.label}
            </button>
          ))}
        </div>
      </aside>

      <div className="min-w-0 flex-1">
        <ReactFlow
          nodes={nodes}
          edges={edges}
          onNodesChange={onNodesChange}
          onEdgesChange={onEdgesChange}
          onConnect={onConnect}
          onNodeClick={(_, n) => setSelected(n)}
          fitView
          colorMode="dark"
        >
          <Background gap={16} color="#1e293b" />
          <Controls />
          <MiniMap nodeColor="#334155" maskColor="rgb(2,6,23,0.8)" />
          <Panel position="top-right">
            <button
              onClick={save}
              disabled={saving}
              className="rounded-xl bg-gradient-to-r from-cyan-400 to-violet-500 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
            >
              {saving ? "Saving…" : "Save workflow"}
            </button>
          </Panel>
        </ReactFlow>
        {msg && <div className="border-t border-slate-800 bg-slate-900 px-4 py-2 text-xs text-cyan-300">{msg}</div>}
      </div>

      <aside className="w-72 shrink-0 border-l border-slate-800 bg-slate-900/80 p-4">
        <div className="text-xs font-semibold uppercase tracking-wider text-slate-500">Node config</div>
        {!selected ? (
          <p className="mt-3 text-sm text-slate-500">Select a node to configure.</p>
        ) : (
          <div className="mt-3 space-y-3 text-sm">
            <div className="text-slate-400">Type: {(selected.data as any)?.nodeType}</div>
            {(selected.data as any)?.nodeType === "agent" && (
              <>
                <label className="block text-slate-400">Agent</label>
                <select
                  className="w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                  value={(selected.data as any)?.agent_id ?? ""}
                  onChange={(e) => updateSelected({ agent_id: Number(e.target.value) })}
                >
                  <option value="">Select…</option>
                  {agents.map((a) => (
                    <option key={a.id} value={a.id}>{a.name}</option>
                  ))}
                </select>
                <label className="block text-slate-400">Prompt</label>
                <textarea
                  className="w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                  rows={3}
                  value={(selected.data as any)?.prompt ?? ""}
                  onChange={(e) => updateSelected({ prompt: e.target.value })}
                />
              </>
            )}
            {(selected.data as any)?.nodeType === "mcp_tool" && (
              <>
                <label className="block text-slate-400">Tool</label>
                <select
                  className="w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                  value={(selected.data as any)?.tool_id ?? ""}
                  onChange={(e) => updateSelected({ tool_id: Number(e.target.value) })}
                >
                  <option value="">Select…</option>
                  {tools.map((t: any) => (
                    <option key={t.id} value={t.id}>{t.serverName} / {t.name}</option>
                  ))}
                </select>
              </>
            )}
            {(selected.data as any)?.nodeType === "approval" && (
              <>
                <label className="block text-slate-400">Risk level</label>
                <select
                  className="w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                  value={(selected.data as any)?.risk_level ?? "L2"}
                  onChange={(e) => updateSelected({ risk_level: e.target.value })}
                >
                  {["L0","L1","L2","L3","L4"].map((r) => <option key={r} value={r}>{r}</option>)}
                </select>
                <label className="block text-slate-400">Reason</label>
                <input
                  className="w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                  value={(selected.data as any)?.reason ?? ""}
                  onChange={(e) => updateSelected({ reason: e.target.value })}
                />
              </>
            )}
            {(selected.data as any)?.nodeType === "decision" && (
              <>
                <label className="block text-slate-400">Left path</label>
                <input
                  className="w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                  value={(selected.data as any)?.expression?.left ?? ""}
                  onChange={(e) => updateSelected({ expression: { ...(selected.data as any)?.expression, left: e.target.value } })}
                />
                <label className="block text-slate-400">Operator</label>
                <select
                  className="w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                  value={(selected.data as any)?.expression?.op ?? "=="}
                  onChange={(e) => updateSelected({ expression: { ...(selected.data as any)?.expression, op: e.target.value } })}
                >
                  {["==","!=",">","<","in"].map((o) => <option key={o}>{o}</option>)}
                </select>
                <label className="block text-slate-400">Right value</label>
                <input
                  className="w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                  value={(selected.data as any)?.expression?.right ?? ""}
                  onChange={(e) => updateSelected({ expression: { ...(selected.data as any)?.expression, right: e.target.value } })}
                />
              </>
            )}
            {((selected.data as any)?.nodeType === "agent" || (selected.data as any)?.nodeType === "mcp_tool") && (
              <>
                <label className="block text-slate-400">Retry policy</label>
                <div className="flex gap-2">
                  <input
                    type="number" min={1} placeholder="attempts"
                    className="w-24 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                    value={(selected.data as any)?.retry_policy?.max_attempts ?? 1}
                    onChange={(e) => updateSelected({ retry_policy: { ...(selected.data as any)?.retry_policy, max_attempts: Number(e.target.value) } })}
                  />
                  <input
                    type="number" min={0} placeholder="backoff ms"
                    className="w-28 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-white"
                    value={(selected.data as any)?.retry_policy?.backoff_ms ?? 0}
                    onChange={(e) => updateSelected({ retry_policy: { ...(selected.data as any)?.retry_policy, backoff_ms: Number(e.target.value) } })}
                  />
                </div>
              </>
            )}
          </div>
        )}
      </aside>
    </div>
  );
}
