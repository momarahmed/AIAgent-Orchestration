# Kubernetes Deployment Design (Phase 1 — Design Only)

Phase 1 runs on **Docker Compose** for local development. This document describes the target Kubernetes layout for production (Phase 2+).

## Namespace

`eamcp-platform`

## Workloads

| Deployment | Replicas | Image | Notes |
|------------|----------|-------|-------|
| `frontend` | 2+ | `eamcp/frontend` | Next.js SSR; ingress path `/` |
| `api` | 2+ | `eamcp/backend` | Laravel API; ingress `/api` |
| `mysql` | 1 (or managed RDS) | — | Prefer managed DB in prod |
| `redis` | 1 (or Elasticache) | — | Sessions, cache, queues |
| `otel-collector` | 1 | `otel/opentelemetry-collector-contrib` | OTLP gRPC 4317 |

Future phases add: `agent-runtime`, `mcp-gateway`, `keycloak`, `temporal`.

## Services

- `frontend` → ClusterIP :3000  
- `api` → ClusterIP :8000  
- `mysql` → ClusterIP :3306 (internal only)  
- `redis` → ClusterIP :6379  
- `otel-collector` → ClusterIP :4317/:4318  

## Ingress

Single host `app.example.com`:

- `/` → frontend  
- `/api` → api  

TLS via cert-manager.

## Secrets

- `eamcp-db` — DB credentials  
- `eamcp-sanctum` / `APP_KEY`  
- `eamcp-llm` — OpenAI/Ollama keys (Phase 1: env only)  

## ConfigMaps

- `eamcp-frontend-env` — `NEXT_PUBLIC_API_URL`  
- `eamcp-backend-env` — `FRONTEND_URL`, `OTEL_EXPORTER_OTLP_ENDPOINT`  

## Health probes

- API: `GET /api/health`  
- Frontend: `GET /`  

## Not deployed in Phase 1

Manifests are documentation-only. Apply after Phase 2 adds CI image builds and secret management.
