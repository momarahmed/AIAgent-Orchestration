import { Suspense, ReactNode } from "react";

export default function RunsLayout({ children }: { children: ReactNode }) {
  return <Suspense fallback={<div className="px-6 py-10 text-sm text-slate-400">Loading runs…</div>}>{children}</Suspense>;
}
