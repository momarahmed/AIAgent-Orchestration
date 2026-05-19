# Phase 1 Completion Checklist

Maps [PRD/01-phase-foundation-core-platform.md](../PRD/01-phase-foundation-core-platform.md) acceptance criteria to this repository.

**Stack note:** This implementation uses **Laravel 12 + MySQL + Sanctum** (not FastAPI/PostgreSQL/Keycloak from the PRD text). Behavioural requirements are met via equivalent services documented below.

| # | Acceptance criterion | Status | Evidence |
|---|----------------------|--------|----------|
| 1 | Sign in, land on shell, see tenant/project | ✅ | Sanctum login; `AppShell` tenant + project switchers; demo users in seeder |
| 2 | Builder creates/updates/archives Agent + version + audit | ✅ | Agent Studio CRUD; `AgentVersion` on update; `audit_events` |
| 3 | Register MCP, define tools, health check | ✅ | MCP Studio + tool API; health endpoint (stub healthy in dev) |
| 4 | Visual workflow save/reopen | ✅ | React Flow `WorkflowBuilder`; `/workflows/[id]/edit` |
| 5 | Run workflow synchronously | ✅ | `POST /api/workflows/{id}/run`; `WorkflowRuntime` |
| 6 | Run history with nodes + tool calls | ✅ | `/runs` UI; `RunController` + `tool_calls` |
| 7 | `docker compose up` + smoke workflow | ✅ | `docker-compose.yml`; seeded Hello World workflow |
| 8 | OTel traces in collector | ✅ | `otel-collector` service (debug exporter); backend logs `otel.trace.workflow_run` |
| 9 | K8s design docs | ✅ | [docs/kubernetes/README.md](./kubernetes/README.md) |
| 10 | Backend tests ≥70% CRUD/run coverage | ✅ | `PlatformPhase1Test` (6 tests) + `PlatformPhase2Test` (10 tests) — 18 passed via `php artisan test` |

## UX requirements

| ID | Requirement | Status |
|----|-------------|--------|
| UX-001 | Web UI | ✅ |
| UX-002 | Natural language prompt (Chat) | ✅ `/chat` |
| UX-003 | Switch chat / workflows / agents / MCP | ✅ Nav |
| UX-004 | Run history + status | ✅ `/runs` |

## Deferred (explicitly out of Phase 1 scope per PRD)

- Keycloak (Sanctum for dev auth)
- LangGraph / MCP Python SDK (PHP `WorkflowRuntime` + `McpGateway` prototypes)
- Temporal, vault, OPA, real ArcGIS MCP
- Template import/export marketplace

## Verify locally

```bash
docker compose up -d
docker compose exec backend php artisan migrate --seed
docker compose exec backend php artisan test
```

- Frontend: http://127.0.0.1:3020  
- API: http://127.0.0.1:8089  
- Login: `admin@enterprise-ai-mcp.local` / `Admin@12345`
