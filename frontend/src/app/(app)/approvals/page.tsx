"use client";

import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { approvalsApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";

const STATUS_BADGE: Record<string, string> = {
  pending: "bg-amber-500/10 text-amber-300 border-amber-500/30",
  approved: "bg-emerald-500/10 text-emerald-300 border-emerald-500/30",
  rejected: "bg-rose-500/10 text-rose-300 border-rose-500/30",
  expired: "bg-slate-500/10 text-slate-300 border-slate-500/30",
};

const RISK_COLOR: Record<string, string> = {
  L0: "text-slate-300", L1: "text-cyan-300", L2: "text-amber-300", L3: "text-orange-300", L4: "text-rose-300",
};

export default function ApprovalsPage() {
  const qc = useQueryClient();
  const [filter, setFilter] = useState<string>("pending");
  const [comment, setComment] = useState<Record<number, string>>({});

  const { data: approvals = [], isLoading } = useQuery({
    queryKey: ["approvals", filter],
    queryFn: () => approvalsApi.list(filter ? { status: filter } : {}),
  });

  const approveMut = useMutation({
    mutationFn: ({ id, comment }: { id: number; comment?: string }) => approvalsApi.approve(id, comment),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["approvals"] }),
  });
  const rejectMut = useMutation({
    mutationFn: ({ id, comment }: { id: number; comment?: string }) => approvalsApi.reject(id, comment),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["approvals"] }),
  });

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 2 · Operate"
        title="Approval Queue"
        description="Human-in-the-loop gate for risk-gated tool calls and production deployments. Decisions resume paused workflows within seconds."
        actions={
          <select
            value={filter}
            onChange={(e) => setFilter(e.target.value)}
            className="rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-sm text-white"
          >
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="">All</option>
          </select>
        }
      />

      <div className="rounded-2xl border border-slate-800 bg-slate-950/60">
        {isLoading ? (
          <div className="p-8 text-center text-sm text-slate-500">Loading approvals…</div>
        ) : approvals.length === 0 ? (
          <div className="p-8 text-center text-sm text-slate-500">No approvals match this filter.</div>
        ) : (
          <table className="w-full text-sm">
            <thead className="border-b border-slate-800 bg-slate-900/40 text-xs uppercase tracking-wider text-slate-500">
              <tr>
                <th className="px-4 py-3 text-left">Subject</th>
                <th className="px-4 py-3 text-left">Reason</th>
                <th className="px-4 py-3 text-left">Risk</th>
                <th className="px-4 py-3 text-left">Status</th>
                <th className="px-4 py-3 text-left">Requested</th>
                <th className="px-4 py-3 text-right">Decision</th>
              </tr>
            </thead>
            <tbody>
              {approvals.map((a: any) => (
                <tr key={a.id} className="border-b border-slate-900 hover:bg-slate-900/30">
                  <td className="px-4 py-3 align-top">
                    <div className="font-medium text-white">{a.subject_type} #{a.subject_id}</div>
                    {a.payload?.tool_name && <div className="text-xs text-slate-500">tool: {a.payload.tool_name}</div>}
                    {a.payload?.node_id && <div className="text-xs text-slate-500">node: {a.payload.node_id}</div>}
                  </td>
                  <td className="px-4 py-3 align-top text-slate-300">{a.reason}</td>
                  <td className={`px-4 py-3 align-top font-mono ${RISK_COLOR[a.risk_level] ?? ""}`}>{a.risk_level}</td>
                  <td className="px-4 py-3 align-top">
                    <span className={`inline-flex rounded-full border px-2 py-0.5 text-[11px] ${STATUS_BADGE[a.status] ?? "border-slate-700 bg-slate-800/40 text-slate-300"}`}>{a.status}</span>
                  </td>
                  <td className="px-4 py-3 align-top text-slate-400">{a.created_at?.slice(0, 19).replace("T", " ")}</td>
                  <td className="px-4 py-3 align-top">
                    {a.status === "pending" ? (
                      <div className="flex flex-col items-end gap-2">
                        <input
                          value={comment[a.id] ?? ""}
                          onChange={(e) => setComment((c) => ({ ...c, [a.id]: e.target.value }))}
                          placeholder="Optional comment"
                          className="w-56 rounded-lg border border-slate-800 bg-slate-900 px-2 py-1 text-xs text-white"
                        />
                        <div className="flex gap-2">
                          <button
                            onClick={() => approveMut.mutate({ id: a.id, comment: comment[a.id] })}
                            className="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300 hover:bg-emerald-500/20"
                          >Approve</button>
                          <button
                            onClick={() => rejectMut.mutate({ id: a.id, comment: comment[a.id] })}
                            className="rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-1 text-xs font-semibold text-rose-300 hover:bg-rose-500/20"
                          >Reject</button>
                        </div>
                      </div>
                    ) : (
                      <div className="text-xs text-slate-500">{a.decision_comment ?? "—"}</div>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}
