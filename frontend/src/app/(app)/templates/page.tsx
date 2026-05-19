"use client";

import { useQuery } from "@tanstack/react-query";
import { templatesApi } from "@/lib/api";
import { useAuth } from "@/lib/auth-context";
import { PageHeader, EmptyState } from "@/components/shared/PageHeader";

export default function TemplatesPage() {
  const auth = useAuth();
  const templates = useQuery({
    queryKey: ["templates", auth.activeTenantId],
    queryFn: () => templatesApi.list({ tenant_id: auth.activeTenantId }),
    enabled: !!auth.activeTenantId,
  });

  const items = templates.data ?? [];

  return (
    <div>
      <PageHeader
        eyebrow="Phase 1 · Placeholder"
        title="Templates"
        description="Template catalog stub for Phase 1. Full import/export and marketplace flows ship in Phase 2."
      />
      <div className="px-4 py-6 lg:px-6">
        {templates.isLoading ? (
          <p className="text-sm text-slate-400">Loading templates…</p>
        ) : items.length === 0 ? (
          <EmptyState
            title="No templates yet"
            description="Seeded workflow and agent templates will appear here. Use Agent Studio and Workflow Builder to create assets directly in Phase 1."
          />
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((t: any) => (
              <div key={t.id} className="rounded-3xl border border-slate-800 bg-slate-900/60 p-5">
                <div className="text-xs uppercase tracking-wider text-slate-500">{t.asset_type}</div>
                <h3 className="mt-1 text-lg font-semibold text-white">{t.name}</h3>
                <p className="mt-2 text-sm text-slate-400">{t.description}</p>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
