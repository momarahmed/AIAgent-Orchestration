# Project AIAgent Orchestration

**Enterprise AI MCP Management Platform** — a vendor-neutral, build-custom-first platform that unifies **AI Agents**, **MCP Servers**, **Visual Workflows**, **Templates**, **Approvals**, and **Observability** under a single asset model — implemented end-to-end in this repository per the v1.1 PRD.

| | |
|---|---|
| **GitHub** | [momarahmed/AIAgent-Orchestration](https://github.com/momarahmed/AIAgent-Orchestration) |
| **Current release** | `v1.0.1` |

> Source PRDs: see `PRD/01..05-phase-*.md` for the five phased product requirements documents (with the **Enhanced** appendix added per the enterprise-grade enhancement prompt).

| Component | Stack |
|---|---|
| Frontend | Next.js 15 (latest) · React 19 · MUI 6 · TailwindCSS 3 · TanStack Query · Recharts |
| Backend | Laravel 12 · PHP 8.3 · Sanctum (API tokens) · Eloquent ORM |
| Storage | MySQL 8.4 (primary), Redis 7 (cache / queue / sessions) |
| Local Runtime | Docker Compose |

---

## 🚀 Quick start

Pre-requisites: **Docker 24+** and **Docker Compose v2**.

```bash
# 1) Copy env file (all defaults work for local dev)
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env.local

# 2) Boot the stack
docker compose up -d --build

# 3) Tail logs (the first run installs Laravel + Composer deps + Sanctum,
#    runs migrations, seeds demo data, then starts php artisan serve.
#    The frontend installs node_modules then starts Next.js dev.)
docker compose logs -f backend frontend
```

Open: **http://127.0.0.1:3020**

Demo credentials (seeded on first boot):

| Role | Email | Password |
|---|---|---|
| Platform Admin | `admin@enterprise-ai-mcp.local` | `Admin@12345` |
| Builder | `builder@enterprise-ai-mcp.local` | `Builder@12345` |
| Viewer | `viewer@enterprise-ai-mcp.local` | `Viewer@12345` |

---

## 🌐 Service URLs

| Service | URL / Port |
|---|---|
| Frontend (Next.js + MUI) | http://127.0.0.1:3020 |
| Backend API (Laravel 12) | http://127.0.0.1:8089 |
| Backend health probe     | http://127.0.0.1:8089/api/health |
| MySQL 8.4 | `127.0.0.1:3308` (db `eamcp`, user `eamcp`, pass `eamcp_secret`) |
| Redis 7   | `127.0.0.1:6382` |

> Ports are non-conflicting alternatives chosen at scaffold time because the canonical ports `3000/8000/3306/6379` are already used by other dev stacks on this host. All configs reference the same values consistently.

---

## 🛠 Common commands

```bash
# Stop the stack
docker compose down

# Rebuild after Dockerfile change
docker compose build --no-cache

# Open a backend shell
docker compose exec backend bash

# Re-run migrations / fresh seed
docker compose exec backend php artisan migrate:fresh --seed

# Composer
docker compose exec backend composer require <package>

# Frontend deps
docker compose exec frontend npm install <package>
docker compose exec frontend npm run lint
```

### CORS / SPA auth

`backend/config/cors.php` allows the frontend origins `http://127.0.0.1:3020` and `http://localhost:3020` with credentials. Sanctum's `EnsureFrontendRequestsAreStateful` middleware is attached to API routes via `bootstrap/app.php`. The frontend stores the token in `localStorage` (`eamcp_token`) and sends it as `Authorization: Bearer <token>` on every request.

---

## 🧱 Asset model

| Entity | Purpose |
|---|---|
| `tenants` / `projects` | Multi-tenant isolation root + nested workspaces |
| `agents` + `agent_versions` | AI agent definitions with immutable versions |
| `mcp_servers` + `mcp_server_versions` + `tools` | MCP server registrations + tool catalog |
| `workflows` + `workflow_versions` | Visual graph (`graph_json`) per version |
| `workflow_runs` / `task_runs` / `tool_calls` | Execution trace |
| `templates` | Reusable agent / MCP / workflow templates |
| `audit_events` | Full audit trail for create/update/delete/run/health/auth events |

See `backend/database/migrations/2026_05_19_000001_create_platform_core_tables.php` for the canonical schema (Phase 1 baseline; later phases add OPA, vault, A2A, RAG memory, etc.).

---

## 🔌 API surface (Phase 1 baseline)

```
POST   /api/auth/login                  Public
POST   /api/auth/register               Public
GET    /api/health                      Public

GET    /api/auth/me                     Sanctum
POST   /api/auth/logout                 Sanctum

GET    /api/tenants                     CRUD tenants
GET    /api/projects                    CRUD projects (?tenant_id=)
GET    /api/agents                      CRUD agents (+ POST :id/duplicate)
GET    /api/mcp-servers                 CRUD MCP servers (+ POST :id/health)
GET    /api/workflows                   CRUD workflows (+ POST :id/run)
GET    /api/runs                        Run history
GET    /api/runs/{id}                   Run detail (tasks + tool calls)
GET    /api/audit                       Audit log
GET    /api/metrics/overview            KPI rollup for the Platform Console
```

All mutating endpoints emit a row to `audit_events` and (for assets) increment the `*_versions` table.

---

## 🧪 Anchor Scenario — "Hello World" workflow

Seeded automatically on first boot:

1. **GIS Operations** project under the **ESRI Saudi Enterprise** tenant.
2. **ArcGIS MCP Server** with `find_features` (L1) and `create_feature` (L3) tools.
3. **GIS Health Agent** wired to the ArcGIS MCP tools.
4. **Hello World** workflow: `Trigger → Agent → MCP Tool`.

Click **Workflows → Hello World → ▶ Run** in the UI (or `POST /api/workflows/{id}/run`). Inspect the result in **Run History**.

If `OPENAI_API_KEY` is set in `backend/.env`, the agent node performs a real chat completion. Otherwise it returns a deterministic mock response — the rest of the pipeline (audit, run record, task tree, tool-call log) is fully wired regardless.

---

## 🗂 Project structure

```
.
├── PRD/                           # Five phased PRDs (with enhanced appendix)
├── PRD_/                          # Original master PRDs
├── UI/                            # Reference TSX components (source of truth for UI)
├── Propmpt/                       # Prompts that drove this build
├── backend/                       # Laravel 12 application
│   ├── app/Http/Controllers/Api/  # REST controllers
│   ├── app/Models/                # Asset-model Eloquent models
│   ├── app/Support/Audit.php      # Tiny audit-event helper
│   ├── bootstrap/app.php          # API route + Sanctum stateful API middleware
│   ├── config/{cors,sanctum}.php  # CORS + Sanctum SPA config
│   ├── database/migrations/...    # Phase-1 schema
│   ├── database/seeders/...       # Demo users, ArcGIS MCP, GIS agent, anchor workflow
│   ├── docker/entrypoint.sh       # Bootstrap → install → migrate → seed → serve
│   └── Dockerfile
├── frontend/                      # Next.js 15 + MUI + Tailwind app
│   ├── src/app/(auth)/login       # Login screen (wired to /api/auth/login)
│   ├── src/app/(app)/             # Authenticated shell (dashboard, console, ...)
│   ├── src/components/ui/         # Imported reference TSX UIs (Login, Admin, ...)
│   ├── src/components/app-shell/  # Sidebar + top nav + tenant switcher
│   ├── src/components/shared/     # PageHeader / StatusBadge / EmptyState
│   ├── src/lib/api.ts             # Axios client + typed resource APIs
│   ├── src/lib/auth-context.tsx   # Auth provider (token, tenants, active tenant)
│   ├── src/theme/mui-theme.ts     # Futuristic dark MUI theme
│   └── Dockerfile
├── docker-compose.yml
└── README.md
```

---

## 🗺 Roadmap (per PRD)

| Phase | Scope | Status |
|---|---|---|
| **1 — Foundation** | Asset model, Studios, visual builder, in-process LangGraph runtime, MCP gateway prototype, run history, OTel | **Implemented** in this repo |
| 2 — Lifecycle & Reliability | Debug consoles, Temporal durable runtime, deployment manager, approvals, Test Runner, OpenHands code agent | Schema-ready, runtime seam in place |
| 3 — Governance & Integrations | Full OPA policy-as-code, vault, ArcGIS / DB / File / Email MCPs, Activepieces bridge | Deferred |
| 4 — Multi-Agent Platform | A2A gateway, event bus, Model Control Plane, RAG / vector memory, meta-agents | Deferred |
| 5 — Marketplace & Scale | Marketplace, GitOps, multi-region, advanced analytics | Deferred |

---

## 🔒 Security notes (Phase 1 baseline)

- Tokens via Laravel Sanctum (HMAC-signed personal access tokens).
- Audit trail on every mutation.
- CORS restricted to the configured frontend origin.
- Secrets live in `.env` for local dev only — vault integration ships in Phase 3 (the deployment manager will refuse to promote any asset that references a non-vault secret to non-dev environments).

---

## 📜 License

Proprietary — © Enterprise AI MCP Platform Working Group.
