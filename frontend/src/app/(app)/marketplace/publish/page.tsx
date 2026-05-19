"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useMutation, useQuery } from "@tanstack/react-query";
import { marketplaceApi, templatesApi } from "@/lib/api";
import { PageHeader } from "@/components/shared/PageHeader";

export default function MarketplacePublishPage() {
  const router = useRouter();
  const [templateId, setTemplateId] = useState<number | "">("");
  const [visibility, setVisibility] = useState("internal");
  const [tags, setTags] = useState("");
  const [readme, setReadme] = useState("");
  const [requiredConnectors, setRequiredConnectors] = useState("");
  const [migrationNotes, setMigrationNotes] = useState("");

  const templates = useQuery({
    queryKey: ["templates-for-publish"],
    queryFn: () => templatesApi.list(),
  });

  const publisher = useQuery({
    queryKey: ["publisher-summary"],
    queryFn: () => marketplaceApi.publisherSummary(),
  });

  const publishMut = useMutation({
    mutationFn: () =>
      marketplaceApi.publish({
        template_id: Number(templateId),
        visibility,
        tags: tags
          .split(",")
          .map((s) => s.trim())
          .filter(Boolean),
        readme: readme ? { content: readme } : undefined,
        required_connectors: requiredConnectors
          .split(",")
          .map((s) => s.trim())
          .filter(Boolean),
        migration_notes: migrationNotes || undefined,
      }),
    onSuccess: (l: any) => router.push(`/marketplace/${l.id}`),
  });

  const summary = (publisher.data as any) ?? {};
  const tmpls = ((templates.data as any)?.data ?? (templates.data as any)) ?? [];

  return (
    <div className="space-y-6 px-6 py-6">
      <PageHeader
        eyebrow="Phase 5 · Marketplace"
        title="Publish a template"
        description="Templates are signed, scanned, and reviewed by the meta-agents before they go live."
      />

      <div className="grid gap-6 px-1 lg:grid-cols-[2fr_1fr]">
        <form
          onSubmit={(e) => {
            e.preventDefault();
            publishMut.mutate();
          }}
          className="space-y-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-6"
        >
          <label className="block text-sm text-slate-300">
            Template
            <select
              value={templateId}
              onChange={(e) => setTemplateId(Number(e.target.value))}
              required
              className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-white"
            >
              <option value="">Select a template…</option>
              {tmpls.map((t: any) => (
                <option key={t.id} value={t.id}>
                  {t.name} ({t.asset_type})
                </option>
              ))}
            </select>
          </label>

          <label className="block text-sm text-slate-300">
            Visibility
            <select
              value={visibility}
              onChange={(e) => setVisibility(e.target.value)}
              className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-white"
            >
              <option value="internal">internal — tenant only</option>
              <option value="trusted">trusted — group of trusted tenants</option>
              <option value="external">external — cross-organization</option>
            </select>
          </label>

          <label className="block text-sm text-slate-300">
            Tags (comma-separated)
            <input
              value={tags}
              onChange={(e) => setTags(e.target.value)}
              placeholder="monitoring, daily, gis"
              className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-white"
            />
          </label>

          <label className="block text-sm text-slate-300">
            Required connectors
            <input
              value={requiredConnectors}
              onChange={(e) => setRequiredConnectors(e.target.value)}
              placeholder="arcgis_mcp, email_mcp"
              className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-white"
            />
          </label>

          <label className="block text-sm text-slate-300">
            README
            <textarea
              value={readme}
              onChange={(e) => setReadme(e.target.value)}
              rows={6}
              className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-white"
            />
          </label>

          <label className="block text-sm text-slate-300">
            Migration notes
            <textarea
              value={migrationNotes}
              onChange={(e) => setMigrationNotes(e.target.value)}
              rows={3}
              className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-white"
            />
          </label>

          <button
            type="submit"
            disabled={publishMut.isPending || !templateId}
            className="rounded-lg bg-emerald-500/30 px-4 py-2 text-sm font-medium text-emerald-100 hover:bg-emerald-500/40 disabled:opacity-60"
          >
            {publishMut.isPending ? "Publishing…" : "Publish to marketplace"}
          </button>

          {publishMut.isError && (
            <p className="text-xs text-rose-300">{String((publishMut.error as Error).message)}</p>
          )}
        </form>

        <aside className="space-y-4">
          <section className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
            <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-400">Publisher console</h3>
            <div className="mt-3 grid grid-cols-2 gap-2 text-xs">
              <KV label="Listings" value={summary.listings_count ?? 0} />
              <KV label="Total installs" value={summary.total_installs ?? 0} />
              <KV label="Avg rating" value={summary.avg_rating ?? 0} />
              <KV label="Pending review" value={summary.pending_review ?? 0} />
            </div>
          </section>
          <section className="rounded-2xl border border-cyan-500/20 bg-cyan-500/5 p-5 text-xs leading-6 text-cyan-100">
            <strong className="text-cyan-200">Quality gates</strong>
            <ul className="mt-2 list-disc pl-4">
              <li>Meta-agents: Documentation, QA, Security review</li>
              <li>Trivy / SBOM scanner</li>
              <li>OPA marketplace policy (signature gate)</li>
              <li>SHA-256 HMAC signing on publish</li>
            </ul>
          </section>
        </aside>
      </div>
    </div>
  );
}

function KV({ label, value }: { label: string; value: number | string }) {
  return (
    <div className="rounded-lg border border-slate-800 bg-slate-950/30 p-3">
      <div className="text-[10px] uppercase tracking-wider text-slate-500">{label}</div>
      <div className="mt-1 text-lg font-semibold text-white">{value}</div>
    </div>
  );
}
