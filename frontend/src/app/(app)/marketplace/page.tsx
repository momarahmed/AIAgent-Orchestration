"use client";

import { useMemo, useState } from "react";
import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { marketplaceApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";
import { useI18n } from "@/lib/i18n-context";

const CATEGORIES = ["", "agent", "mcp", "workflow", "policy"];

export default function MarketplacePage() {
  const { t } = useI18n();
  const [category, setCategory] = useState("");
  const [query, setQuery] = useState("");
  const [submitted, setSubmitted] = useState("");

  const browse = useQuery({
    queryKey: ["marketplace-list", category],
    queryFn: () => marketplaceApi.list({ category: category || undefined, per_page: 24 }),
    enabled: submitted.length === 0,
  });

  const search = useQuery({
    queryKey: ["marketplace-search", submitted, category],
    queryFn: () => marketplaceApi.search(submitted, { category: category || undefined, limit: 24 }),
    enabled: submitted.length > 0,
  });

  const items = useMemo(() => {
    if (submitted) return (search.data as any)?.results ?? [];
    return (browse.data as any)?.data ?? [];
  }, [submitted, browse.data, search.data]);

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 5 · Marketplace"
        title={t("marketplace.title", "Template Marketplace")}
        description="Internal & external templates. Signed, scanned, meta-agent reviewed."
      />

      <div className="flex flex-wrap items-center gap-3 px-1">
        <form
          onSubmit={(e) => {
            e.preventDefault();
            setSubmitted(query.trim());
          }}
          className="flex flex-1 items-center gap-2"
        >
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder={t("marketplace.search_placeholder", "Find templates with natural language…")}
            className="w-full rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-white outline-none focus:border-cyan-400"
          />
          <button
            type="submit"
            className="rounded-lg bg-cyan-500/20 px-4 py-2 text-sm font-medium text-cyan-200 hover:bg-cyan-500/30"
          >
            {t("common.search", "Search")}
          </button>
          {submitted && (
            <button
              type="button"
              onClick={() => {
                setSubmitted("");
                setQuery("");
              }}
              className="rounded-lg border border-slate-700 px-3 py-2 text-xs text-slate-300 hover:text-white"
            >
              Clear
            </button>
          )}
        </form>
        <select
          value={category}
          onChange={(e) => setCategory(e.target.value)}
          className="rounded-lg border border-slate-700 bg-slate-900/60 px-3 py-2 text-sm text-slate-200"
        >
          {CATEGORIES.map((c) => (
            <option key={c} value={c}>
              {c || "All categories"}
            </option>
          ))}
        </select>
        <Link
          href="/marketplace/publish"
          className="rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-4 py-2 text-sm text-emerald-200 hover:bg-emerald-500/20"
        >
          {t("common.publish", "Publish")} →
        </Link>
      </div>

      <div className="grid grid-cols-1 gap-4 px-1 md:grid-cols-2 xl:grid-cols-3">
        {items.map((l: any) => (
          <Link
            key={l.id}
            href={`/marketplace/${l.id}`}
            className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 transition hover:border-cyan-500/50 hover:bg-slate-900"
          >
            <div className="flex items-center justify-between">
              <span className="rounded-full border border-cyan-500/30 bg-cyan-500/10 px-2 py-0.5 text-[10px] uppercase tracking-wider text-cyan-300">
                {l.category}
              </span>
              <span className="text-xs text-slate-400">v{l.latest_version}</span>
            </div>
            <div className="mt-3 text-lg font-semibold text-white">{l.title}</div>
            <p className="mt-2 line-clamp-2 text-sm text-slate-400">{l.description}</p>
            <div className="mt-4 flex items-center justify-between text-xs text-slate-400">
              <span>⭐ {Number(l.rating_avg ?? 0).toFixed(1)} · {l.install_count} installs</span>
              {l.signed && (
                <span className="rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-emerald-200">
                  signed
                </span>
              )}
            </div>
          </Link>
        ))}
        {items.length === 0 && (browse.isFetched || search.isFetched) && (
          <div className="col-span-full rounded-2xl border border-dashed border-slate-700 p-10 text-center text-slate-400">
            No templates match {submitted ? `“${submitted}”` : "your filters"}.
          </div>
        )}
      </div>
    </div>
  );
}
