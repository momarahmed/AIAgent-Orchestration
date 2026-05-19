# @eamcp/sdk

Official TypeScript / Node.js SDK for the **Enterprise AI + MCP + Multi-Agent Platform** — v1.0 (Phase 5).

## Install

```bash
npm install @eamcp/sdk
```

## Quick start

```ts
import { Client } from "@eamcp/sdk";

const c = new Client({ baseUrl: "http://127.0.0.1:8089" });
await c.login("admin@example.com", "secret");

const results = await c.marketplace.search("daily GIS health report");
console.log(results.results.map((r: any) => r.title));

const wf = await c.workflows.create({ name: "Daily KPI", definition: { nodes: [], edges: [] } });
await c.workflows.run(wf.id, { input: { prompt: "Generate today's report" } });

const exp = await c.compliance.create({
  frameworks: ["SOC2"],
  period_start: "2026-01-01",
  period_end: "2026-03-31",
});
```

## CI/CD recipes

See `sdk/cli` for a CLI binary, and `docs/SDK_QUICKSTART.md` for GitHub Actions /
GitLab CI / Azure DevOps recipes.
