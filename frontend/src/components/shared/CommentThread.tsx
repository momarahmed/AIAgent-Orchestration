"use client";

import { useEffect, useState } from "react";
import { commentsApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";

export function CommentThread({ assetType, assetId }: { assetType: string; assetId: number }) {
  const auth = useAuth();
  const [comments, setComments] = useState<any[]>([]);
  const [body, setBody] = useState("");

  const load = () => commentsApi.list(assetType, assetId).then(setComments);
  useEffect(() => { load(); }, [assetType, assetId]);

  const submit = async () => {
    if (!body.trim() || !auth.activeTenantId) return;
    await commentsApi.create({ tenant_id: auth.activeTenantId, asset_type: assetType, asset_id: assetId, body });
    setBody("");
    load();
  };

  return (
    <div className="rounded-2xl border border-slate-800 bg-slate-900/40 p-4">
      <div className="text-sm font-semibold text-white">Comments ({comments.length})</div>
      <div className="mt-3 space-y-2">
        {comments.map((c) => (
          <div key={c.id} className={`rounded-xl border border-slate-800 bg-slate-950/40 p-3 text-sm ${c.resolved ? "opacity-60" : ""}`}>
            <div className="flex items-center justify-between text-xs text-slate-500">
              <span>{c.author?.name || "anon"} · {new Date(c.created_at).toLocaleString()}</span>
              {!c.resolved && (
                <button onClick={() => commentsApi.resolve(c.id).then(load)} className="text-emerald-300 hover:underline">resolve</button>
              )}
            </div>
            <div className="mt-1 whitespace-pre-wrap text-slate-200">{c.body}</div>
          </div>
        ))}
      </div>
      <div className="mt-3 flex gap-2">
        <textarea className="flex-1 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-sm" placeholder="leave a comment, mention with @user" value={body} onChange={(e) => setBody(e.target.value)} />
        <button onClick={submit} className="rounded-xl bg-cyan-500/20 px-3 py-2 text-sm font-semibold text-cyan-200 hover:bg-cyan-500/30">Post</button>
      </div>
    </div>
  );
}
