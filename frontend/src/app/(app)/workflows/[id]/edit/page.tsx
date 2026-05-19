"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { WorkflowBuilder } from "@/components/workflow/WorkflowBuilder";
import { workflowsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader } from "@/components/shared/PageHeader";

export default function WorkflowEditPage() {
  const params = useParams();
  const id = Number(params.id);
  const auth = useAuth();

  const wf = useQuery({
    queryKey: ["workflow", id],
    queryFn: () => workflowsApi.get(id),
    enabled: !!id,
  });

  if (wf.isLoading || auth.loading) {
    return <div className="px-6 py-10 text-sm text-slate-400">Loading workflow…</div>;
  }

  if (!wf.data || !auth.activeTenantId || !auth.activeProjectId) {
    return (
      <div className="px-6 py-10 text-sm text-rose-300">
        Workflow not found or no active project selected.
        <Link href="/workflows" className="ml-2 text-cyan-300 hover:underline">← Back</Link>
      </div>
    );
  }

  const graph = wf.data.current_version?.graph_json ?? { nodes: [], edges: [] };

  return (
    <div>
      <PageHeader
        eyebrow="WF-001 · Visual builder"
        title={wf.data.name}
        description="Drag Trigger, Agent, and MCP Tool nodes. Save creates a new workflow version."
        actions={
          <Link href="/workflows" className="rounded-xl border border-slate-700 px-3 py-1.5 text-xs text-slate-200 hover:border-cyan-400/40">
            ← All workflows
          </Link>
        }
      />
      <div className="px-4 pb-6 lg:px-6">
        <WorkflowBuilder
          workflowId={id}
          tenantId={auth.activeTenantId}
          projectId={wf.data.project_id ?? auth.activeProjectId}
          initialGraph={graph}
        />
      </div>
    </div>
  );
}
