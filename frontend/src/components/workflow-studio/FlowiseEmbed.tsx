"use client";

import { useEffect, useRef, useState } from "react";

/**
 * FlowiseEmbed
 * ------------
 * Renders the Flowise visual builder in an iframe, themed to match our
 * dark-slate chrome. The URL is resolved on the server (per-agent) via
 * GET /api/flowise/agents/:id/embed so we can swap canvas/chat strategies
 * without a frontend redeploy.
 *
 * The browser never sees FLOWISE_API_KEY — the embed URL points at the
 * Flowise UI which handles its own auth (Flowise basic-auth login).
 */
export function FlowiseEmbed({
  url,
  height = 720,
  fallbackMessage,
}: {
  url: string | null | undefined;
  height?: number;
  fallbackMessage?: string;
}) {
  const ref = useRef<HTMLIFrameElement | null>(null);
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    setLoaded(false);
  }, [url]);

  if (!url) {
    return (
      <div className="flex h-[480px] items-center justify-center rounded-3xl border border-dashed border-slate-800 bg-slate-900/40 px-6 text-center text-sm text-slate-400">
        {fallbackMessage ?? "Flowise embed URL not available. Configure FLOWISE_EMBED_URL in your environment."}
      </div>
    );
  }

  const themed = url + (url.includes("?") ? "&" : "?") + "embed=1&theme=dark";

  return (
    <div className="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-950 shadow-[0_20px_60px_-30px_rgba(34,211,238,0.35)]">
      {!loaded && (
        <div className="absolute inset-0 z-10 flex items-center justify-center bg-slate-950/80 text-sm text-slate-400">
          <span className="mr-2 h-2 w-2 animate-pulse rounded-full bg-cyan-400" />
          Loading Flowise canvas…
        </div>
      )}
      <iframe
        ref={ref}
        src={themed}
        title="Flowise visual builder"
        onLoad={() => setLoaded(true)}
        allow="clipboard-write; fullscreen"
        sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-downloads"
        style={{ width: "100%", height, border: 0, background: "#0f172a" }}
      />
    </div>
  );
}
