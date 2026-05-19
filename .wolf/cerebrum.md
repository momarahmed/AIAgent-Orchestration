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

- [2026-05-19] **`docker compose restart <svc>` does NOT pick up new env from compose.yml.** Container env is set at *create* time. After editing the `environment:` block of a service, run `docker compose up -d --force-recreate --no-deps <svc>` (and clear Laravel config cache: `php artisan config:clear`). Symptom for this project: the `/workflow-studio` health banner kept reading "FLOWISE_API_URL is not configured" even after `restart backend`, because the running container had been created before the new env keys existed.

## Decision Log

- [2026-05-19] **RBAC layered evaluation over flat role check:** Chose role → project-role → ABAC → deny-override pattern because PRD requires both RBAC and ABAC with environment/project scoping. Flat role checks couldn't express "builders can edit agents only in their own project."
- [2026-05-19] **OPA policies stored in DB + pushed to OPA server:** Policies are authored in DB (versioned, audited) and pushed to OPA on activation. This gives us UI-based policy management while keeping OPA as the runtime engine.
- [2026-05-19] **Prompt injection uses two-pass approach:** Heuristic regex (fast, always-on) + optional LLM guardrail (configurable). Single regex match → warning; 3+ matches → block. This balances false-positive rate with security.
- [2026-05-19] **Security scanner runs in-process with fallback:** Trivy/Grype run as shell processes. If binaries aren't installed (dev mode), simulated results are returned. This keeps the pipeline testable without requiring scanner binaries locally.
- [2026-05-19] **Phase 4 EventBus is pluggable with in-process fallback:** `EventBus` selects driver via `EVENT_BUS_DRIVER` env (default kafka, falls back to in_process). All publishes are mirrored to `event_log` regardless of driver — devs always see the audit trail even without Redpanda.
- [2026-05-19] **Phase 4 vector/LLM external services are optional:** `QdrantClient` has in-memory fallback when `QDRANT_URL` is empty; `OllamaProvider` returns mock completions when Ollama is unreachable. This keeps the dev loop fast without external deps.
- [2026-05-19] **Phase 4 migration ordering:** `2026_05_19_250000_create_phase4_advanced_platform_tables.php` ships **before** Phase 5's `2026_05_19_300000_create_phase5_marketplace_tables.php` so foreign keys (e.g. `models`, `prompts`) exist when Phase 5 references them.
- [2026-05-19] **Meta-agent orchestrator REGISTRY constant:** `MetaAgentOrchestrator::REGISTRY` is the single source of truth for the 11 meta-agents; the `/api/meta-agents/registry` endpoint iterates this constant. Adding a new meta-agent = add to REGISTRY + `BaseMetaAgent` subclass.
- [2026-05-19] **Phase 5 marketplace install signature gate:** Unsigned marketplace listings must throw before any OPA call when `environment in {staging, prod}` (`MarketplaceService::install`). OPA still gets called for signed installs (`eamcp.marketplace.install` in `infra/opa/marketplace.rego`), but the PHP-level gate guarantees signature enforcement even when OPA is unreachable.
- [2026-05-19] **Marketplace install slug generation:** `agents`, `mcp_servers`, and `workflows` tables all have a non-nullable `slug` column. `MarketplaceService::createAgent/Mcp/Workflow` now generates a unique slug via `Str::slug($name) . '-' . random_hex(6)`. Don't rely on Eloquent default values.
- [2026-05-19] **OpaPolicy active filter:** Use `where('status', 'active')` — there is no `is_active` column on `opa_policies`. Active state is encoded in the `status` enum (`draft|active|disabled|archived`).
- [2026-05-19] **User::tenant_id accessor:** `User` doesn't have a direct `tenant_id` column (tenants are via `tenant_user` pivot). The Phase 5 accessor resolves the active tenant from the `X-Tenant-Id` header (if attached) or the first attached tenant. Phase 5 controllers rely on this — don't read `tenants_user.tenant_id` directly.
- [2026-05-19] **LocalizationService caches 5 minutes:** Tests that mutate locales must `Cache::flush()` in `setUp()` (or use the `array` cache driver in testing). `RefreshDatabase` doesn't clear the cache, so stale empty dictionaries can leak between tests.
- [2026-05-19] **MigrationService skips workflow creation when project_id null:** `workflows.project_id` is non-nullable, so legacy imports without a project_id only persist the `migration_imports` row and skip the materialized workflow. Callers must pass a real project id to get a workflow.

## AI Workflow Studio (Flowise) — Key Learnings

- **Section path:** `/workflow-studio` (sidebar label "Workflow", icon 🪢, group `build` — placed right after `/workflows`). The pre-existing `/workflows` is the *native* graph builder; `/workflow-studio` is the *Flowise-backed* visual builder.
- **Backend tables:** `flowise_agents` / `flowise_runs` / `flowise_syncs` (migration `2026_05_19_400000_create_flowise_studio_tables.php`). Join key with Flowise = `flowise_chatflow_id` (unique).
- **Service entry point:** `App\Services\FlowiseService` — single class for push/pull/run/list/health. Reads `config('services.flowise.{url,api_key,embed_url,webhook_secret}')`. Browser MUST NEVER receive the API key — Laravel proxies all writes.
- **Controller:** `App\Http\Controllers\Api\FlowiseAgentController` mounted under `/api/flowise/*` (under `auth:sanctum + tenant.isolation`). Membership is asserted on every action via `assertMembership`/`authorizeAgent`.
- **Embed strategy:** the iframe URL (`/api/flowise/agents/:id/embed`) is resolved server-side so embed strategy can change without a frontend redeploy. Frontend uses `NEXT_PUBLIC_FLOWISE_EMBED_URL` host-mapped at `http://127.0.0.1:3500`.
- **Two-way sync:** push-on-save for any local mutation (`store`/`update`/`duplicate`/`destroy` always call `pushAgent`/`deleteAgent`); pull via dashboard "Sync" button (`POST /api/flowise/sync`). Failures are persisted to `flowise_syncs` with `sync_status=failed` for retry, never thrown back to the user as 500s.
- **Run record shape:** `flowise_runs` stores `input/output/tool_calls/conversation` JSON, plus token usage and `duration_ms`. Successful runs populate `agent.last_run_status='succeeded'` and `last_run_at=now()`.
- **Docker:** Flowise runs as service `flowise` on port `127.0.0.1:3500` → backend talks to it at `http://flowise:3000`. Volume `eamcp_flowise_data`.

## Phase 4 Key Learnings

- **Model Router request shape:** `plan(['capabilities' => ['chat'], 'tenant_id' => 1])` — note `capabilities` is an array. Rule matching uses `match.capability` (singular) checked against `request.capabilities` array.
- **Memory API:** controller field is `content` (not `text`); retrieve returns `{ data: [{item, score}], latency_ms }`.
- **Event Bus publish:** API requires `event_type` (not just topic+payload). Topic must be in `EventBus::TOPICS`.
- **A2A send:** `partner_id` in `/api/a2a/send` is the **numeric DB id**, not the string slug.
- **Prompt render:** returns plain string from controller (`{ rendered: string }`), not `{ text, version }`.
- **Migration import controller** validates a `source` field; service stores the parsed graph in `translated` and the raw input in `raw_source`.
- **Comments** require `tenant_id` on create; thread component is at `frontend/src/components/shared/CommentThread.tsx`.
