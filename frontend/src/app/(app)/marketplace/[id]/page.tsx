"use client";

import { useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { marketplaceApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";

export default function MarketplaceListingPage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const qc = useQueryClient();
  const id = Number(params.id);

  const [environment, setEnvironment] = useState("dev");
  const [rating, setRating] = useState(5);
  const [review, setReview] = useState("");
  const [parameters, setParameters] = useState("{\n}\n");

  const { data: listing, isLoading } = useQuery({
    queryKey: ["marketplace-listing", id],
    queryFn: () => marketplaceApi.get(id),
    enabled: Number.isFinite(id),
  });

  const installMut = useMutation({
    mutationFn: () =>
      marketplaceApi.install(id, {
        environment,
        parameters: safeParse(parameters),
      }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["marketplace-listing", id] });
      router.push("/marketplace");
    },
  });

  const rateMut = useMutation({
    mutationFn: () => marketplaceApi.rate(id, { rating, review }),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["marketplace-listing", id] }),
  });

  if (isLoading || !listing) {
    return <div className="px-6 py-10 text-slate-300">Loading marketplace listing…</div>;
  }
  const l: any = listing;

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow={`Marketplace · ${l.category}`}
        title={l.title}
        description={l.description}
        actions={
          <div className="flex items-center gap-2 text-xs">
            <span className="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-0.5 text-cyan-300">
              v{l.latest_version}
            </span>
            {l.signed && (
              <span className="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-emerald-200">
                signed
              </span>
            )}
            <span className="rounded-full border border-slate-700 px-2 py-0.5 text-slate-300">
              ⭐ {Number(l.rating_avg ?? 0).toFixed(1)} ({l.rating_count})
            </span>
          </div>
        }
      />

      <div className="grid gap-6 px-1 lg:grid-cols-[2fr_1fr]">
        <div className="space-y-6">
          <section className="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
            <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-400">README</h3>
            <pre className="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-200">
              {l.readme?.content ?? "No README provided."}
            </pre>
          </section>

          <section className="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
            <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Meta-agent review</h3>
            <pre className="mt-3 overflow-x-auto rounded-lg bg-slate-950/60 p-3 text-xs leading-5 text-slate-300">
              {JSON.stringify(l.quality_review ?? {}, null, 2)}
            </pre>
          </section>

          <section className="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
            <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Versions</h3>
            <ul className="mt-3 divide-y divide-slate-800">
              {(l.versions ?? []).map((v: any) => (
                <li key={v.id} className="flex items-center justify-between py-2 text-sm text-slate-300">
                  <span className="font-mono">{v.version}</span>
                  <span className="text-xs text-slate-500">{v.status}</span>
                  <span className="text-xs text-slate-500 truncate max-w-[40%]">sig: {v.signature?.slice(0, 16)}…</span>
                </li>
              ))}
            </ul>
          </section>
        </div>

        <div className="space-y-6">
          <section className="rounded-2xl border border-cyan-500/30 bg-cyan-500/5 p-6">
            <h3 className="text-sm font-semibold uppercase tracking-wider text-cyan-300">Install</h3>
            <div className="mt-3 space-y-3">
              <label className="block text-xs text-slate-300">
                Environment
                <select
                  value={environment}
                  onChange={(e) => setEnvironment(e.target.value)}
                  className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-white"
                >
                  <option value="dev">dev</option>
                  <option value="test">test</option>
                  <option value="staging">staging</option>
                  <option value="prod">prod</option>
                </select>
              </label>
              <label className="block text-xs text-slate-300">
                Parameters (JSON)
                <textarea
                  value={parameters}
                  onChange={(e) => setParameters(e.target.value)}
                  rows={5}
                  className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 font-mono text-xs text-white"
                />
              </label>
              <button
                onClick={() => installMut.mutate()}
                disabled={installMut.isPending}
                className="w-full rounded-lg bg-cyan-500/30 px-3 py-2 text-sm font-medium text-cyan-100 hover:bg-cyan-500/40 disabled:opacity-60"
              >
                {installMut.isPending ? "Installing…" : "Install template"}
              </button>
              {installMut.isError && (
                <p className="text-xs text-rose-300">{String((installMut.error as Error).message)}</p>
              )}
            </div>
          </section>

          <section className="rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
            <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Rate & review</h3>
            <div className="mt-3 space-y-3">
              <div className="flex items-center gap-2">
                {[1, 2, 3, 4, 5].map((n) => (
                  <button
                    key={n}
                    onClick={() => setRating(n)}
                    className={`h-9 w-9 rounded-full text-sm font-semibold ${
                      rating >= n ? "bg-amber-400 text-amber-900" : "bg-slate-800 text-slate-400"
                    }`}
                  >
                    {n}
                  </button>
                ))}
              </div>
              <textarea
                value={review}
                onChange={(e) => setReview(e.target.value)}
                rows={3}
                placeholder="Optional review"
                className="w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-white"
              />
              <button
                onClick={() => rateMut.mutate()}
                disabled={rateMut.isPending}
                className="w-full rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-200 hover:bg-slate-700"
              >
                {rateMut.isPending ? "Saving…" : "Submit rating"}
              </button>
            </div>
          </section>
        </div>
      </div>
    </div>
  );
}

function safeParse(input: string): Record<string, any> {
  try {
    return JSON.parse(input);
  } catch {
    return {};
  }
}
