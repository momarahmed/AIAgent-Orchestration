# Phase 3 — Migration Guide

## Prerequisites

Phase 3 requires Phase 1 + Phase 2 to be fully migrated and seeded.

## Steps

### 1. Run Phase 3 Migration

```bash
docker compose exec backend php artisan migrate
```

This creates the following tables:
- `permissions`, `role_permissions`, `project_user`, `abac_policies`
- `opa_policies`, `opa_policy_versions`
- `audit_report_templates`, `audit_exports`
- `security_scans`
- `network_allowlists`
- `provider_budgets`
- `prompt_injection_logs`
- `activepieces_connections`

And extends:
- `roles` (adds `level`, `is_system`, `description`)
- `audit_events` (adds `hash`, `prev_hash`)
- `tool_calls` (adds `token_count`, `cost_usd`, `provider`, `model`)
- `mcp_servers` (adds `sandbox_config`, `network_policy`, `requires_sandbox`)

### 2. Run Phase 3 Seeder

```bash
docker compose exec backend php artisan db:seed --class=Database\\Seeders\\Phase3Seeder
```

Or re-seed everything:

```bash
docker compose exec backend php artisan db:seed
```

This creates:
- Full PRD role hierarchy (Viewer → Platform Owner)
- Permission catalogue (50+ permissions across 12 groups)
- Demo MCP servers (Database, File/PDF, Email, Monitoring, ITSM, Browser/CUA)
- Network allowlists for ArcGIS MCP Server
- Demo OPA policy (Production ArcGIS Dual Approval)
- Audit report templates (6 system templates)
- Provider budgets (OpenAI, Claude, Google ADK)
- Security Approver user

### 3. Bring Up New Services

```bash
docker compose up -d activepieces qdrant
```

### 4. Verify

```bash
# Check health
curl http://127.0.0.1:8089/api/health

# Check roles seeded
curl -H "Authorization: Bearer <TOKEN>" http://127.0.0.1:8089/api/rbac/roles

# Check OPA policies
curl -H "Authorization: Bearer <TOKEN>" http://127.0.0.1:8089/api/opa-policies
```

## New Environment Variables

| Variable | Description | Default |
|---|---|---|
| `GOOGLE_AI_API_KEY` | Google Gemini API key | — |
| `ACTIVEPIECES_URL` | Activepieces server URL | `http://activepieces:80` |
| `ACTIVEPIECES_API_KEY` | Activepieces API key | — |
| `QDRANT_URL` | Qdrant vector DB URL | `http://qdrant:6333` |
| `GUARDRAIL_ENABLED` | Enable LLM prompt-injection guardrail | `false` |
| `GUARDRAIL_PROVIDER` | Guardrail LLM provider | `openai` |
| `GUARDRAIL_MODEL` | Guardrail model | `gpt-4o-mini` |
| `CONTAINER_REGISTRY_URL` | Container registry for signed images | `ghcr.io/eamcp` |

## Breaking Changes

None. Phase 3 is additive to Phase 1+2. All existing routes and models remain unchanged.
The new `tenant.isolation` middleware is applied to the main auth group — previously open endpoints now verify tenant membership.

## New API Routes

| Method | Path | Description |
|---|---|---|
| GET | `/api/rbac/roles` | List all roles |
| POST | `/api/rbac/roles` | Create role |
| PUT | `/api/rbac/roles/{id}` | Update role |
| GET | `/api/rbac/permissions` | List permissions |
| GET | `/api/rbac/my-permissions` | Current user's permissions |
| POST | `/api/rbac/assign-tenant-role` | Assign tenant role |
| POST | `/api/rbac/assign-project-role` | Assign project role |
| GET/POST | `/api/rbac/abac-policies` | ABAC policy CRUD |
| GET/POST | `/api/opa-policies` | OPA policy CRUD |
| POST | `/api/opa-policies/{id}/activate` | Activate policy |
| POST | `/api/opa-policies/{id}/dry-run` | Dry-run policy |
| POST | `/api/opa-policies/lint` | Lint Rego code |
| GET | `/api/audit-reports/events` | Browse audit events |
| POST | `/api/audit-reports/export` | Generate audit export |
| GET/POST | `/api/security-scans` | Security scan CRUD |
| GET/POST | `/api/network-policies` | Network allowlist CRUD |
| GET/POST | `/api/provider-budgets` | Provider budget CRUD |
