# Cerebrum

> OpenWolf's learning memory. Updated automatically as the AI learns from interactions.
> Do not edit manually unless correcting an error.
> Last updated: 2026-05-19

## User Preferences

<!-- How the user likes things done. Code style, tools, patterns, communication. -->

## Key Learnings

- **Cursor IDE:** OpenWolf lifecycle hooks are wired via `.cursor/hooks.json` → `.cursor/hooks/openwolf-*.sh` (not only `.claude/settings.json`). Restart Cursor after changing hooks.
- **Daemon:** If dashboard fails, run `fuser -k 18791/tcp` then `openwolf daemon start` from project root.
- **Project:** AI agent orchestration
- **Description:** A vendor-neutral, build-custom-first platform that unifies **AI Agents**, **MCP Servers**, **Visual Workflows**, **Templates**, **Approvals**, and **Observability** under a single asset model.
- **Phase 3 Architecture:** RBAC uses layered evaluation: role-based → ABAC attribute → deny-override. Admin/PlatformOwner roles bypass all checks.
- **Middleware Registration:** Phase 3 middleware aliases (rbac, tenant.isolation, prompt.injection) are registered in AppServiceProvider::boot() via Router::aliasMiddleware.
- **OPA Integration:** Policies are pushed to OPA via PUT /v1/policies/{slug}. The package path maps to the data path (eamcp.tool_call → /v1/data/eamcp/tool_call).
- **Seeder Pattern:** Phase3Seeder is called from DatabaseSeeder via $this->call(Phase3Seeder::class). Use firstOrCreate to make seeders idempotent.
- **MCP Server Sandbox:** Browser/CUA tools require sandbox (requires_sandbox=true). Sandbox config lives in mcp_servers.sandbox_config JSON column.
- **Secret Caching:** SecretService uses 5-minute cache (CACHE_TTL=300) with 1-minute grace for vault outages. Staging/prod never fall back to .env.
- **Audit Hash Chain:** Each audit event stores hash + prev_hash (SHA-256) for tamper evidence. The chain is verified by comparing prev_hash with the previous event's hash.

## Do-Not-Repeat

<!-- Mistakes made and corrected. Each entry prevents the same mistake recurring. -->
<!-- Format: [YYYY-MM-DD] Description of what went wrong and what to do instead. -->

## Decision Log

- [2026-05-19] **RBAC layered evaluation over flat role check:** Chose role → project-role → ABAC → deny-override pattern because PRD requires both RBAC and ABAC with environment/project scoping. Flat role checks couldn't express "builders can edit agents only in their own project."
- [2026-05-19] **OPA policies stored in DB + pushed to OPA server:** Policies are authored in DB (versioned, audited) and pushed to OPA on activation. This gives us UI-based policy management while keeping OPA as the runtime engine.
- [2026-05-19] **Prompt injection uses two-pass approach:** Heuristic regex (fast, always-on) + optional LLM guardrail (configurable). Single regex match → warning; 3+ matches → block. This balances false-positive rate with security.
- [2026-05-19] **Security scanner runs in-process with fallback:** Trivy/Grype run as shell processes. If binaries aren't installed (dev mode), simulated results are returned. This keeps the pipeline testable without requiring scanner binaries locally.
