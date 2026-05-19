# Phase 4 — Migration & Operations Guide

Upgrading an existing Phase 3 install to Phase 4 (Advanced Multi-Agent Platform).

## 1. Prerequisites

- Docker Compose v2 (already used in this repo)
- ~4 GB free RAM for new services (Redpanda, Ollama, Prometheus, Grafana, Loki)
- Optional but recommended: Qdrant 1.10+ for production vector search
  (the platform falls back to an in-memory store automatically when Qdrant is unavailable)

## 2. New environment variables

The following keys are read by the Laravel backend. Add them to `backend/.env` or
override via `docker-compose.yml` (defaults are already wired in compose):

```env
# ─── Event bus ────────────────────────────────────────────────
EVENT_BUS_DRIVER=in_process            # or "kafka" to use Redpanda
KAFKA_BROKERS=redpanda:9092
KAFKA_CLIENT_ID=eamcp-backend

# ─── Vector store ─────────────────────────────────────────────
QDRANT_URL=http://qdrant:6333           # leave blank to force in-memory
QDRANT_API_KEY=

# ─── Local LLM ────────────────────────────────────────────────
OLLAMA_BASE_URL=http://ollama:11434

# ─── Observability ────────────────────────────────────────────
PROMETHEUS_PUSHGATEWAY=                 # optional
LOKI_URL=http://loki:3100
OTEL_EXPORTER_OTLP_ENDPOINT=            # set when collector is enabled

# ─── A2A Gateway ──────────────────────────────────────────────
A2A_HMAC_TOLERANCE_SECONDS=300

# ─── Meta-agent autonomy ──────────────────────────────────────
META_AGENT_AUTOPILOT_DEFAULT=suggest    # suggest | autonomous

# ─── Cross-framework bridges (URLs only; per-tenant secrets stored in DB) ──
DIFY_BASE_URL=
FLOWISE_BASE_URL=
SIM_BASE_URL=
```

## 3. Service start-up order

```bash
# 1. Bring up infra
docker compose up -d mysql redis redpanda qdrant ollama prometheus loki grafana

# 2. Wait for redpanda + qdrant to be healthy
docker compose logs -f redpanda | grep "Successfully started"

# 3. Bring up Laravel + workers
docker compose up -d backend event-consumer

# 4. Run migrations + seeders
docker compose exec backend php artisan migrate --seed

# 5. Frontend
docker compose up -d frontend
```

## 4. Data migrations introduced

Migration: `2026_05_19_250000_create_phase4_advanced_platform_tables.php`

Tables added:
- `models`, `model_routing_rules`
- `prompts`, `prompt_versions`, `prompt_evaluations`
- `memory_collections`, `memory_items`
- `knowledge_graph_nodes`, `knowledge_graph_edges`
- `a2a_partners`, `a2a_messages`
- `event_subscriptions`, `event_consumer_offsets`, `event_log`
- `meta_agent_runs`, `meta_agent_actions`
- `migration_imports`
- `bridge_connections`, `bridge_executions`
- `asset_comments`
- `run_replays`

Schema alterations:
- `agents`: + `memory_scope`, `allowed_mcp_servers`
- `workflow_runs`: + `trace_id` (OpenTelemetry correlation)

Rollback (development only):

```bash
docker compose exec backend php artisan migrate:rollback \
  --path=database/migrations/2026_05_19_250000_create_phase4_advanced_platform_tables.php
```

## 5. New OPA policies

Mount or sync these into your OPA bundle:

- `infra/opa/a2a_federation.rego` — partner / capability authorization
- `infra/opa/memory_scope.rego` — enforce memory boundaries by scope
- `infra/opa/meta_agent.rego` — what meta-agents may do without human approval

## 6. Smoke tests post-deploy

```bash
# Event bus alive
curl -s http://localhost:8000/api/event-bus/status | jq

# Prometheus metrics scrape
curl -s http://localhost:8000/api/observability/metrics | head -20

# Models catalog
curl -s -H "Authorization: Bearer $TOKEN" http://localhost:8000/api/models | jq '.data[].slug'

# Run a planning query
curl -s -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" \
  -d '{"capabilities":["chat"]}' \
  http://localhost:8000/api/models/route/plan | jq
```

## 7. Operations

### Tailing event bus

```bash
docker compose exec backend php artisan eamcp:consume-event-bus \
  --topic=agent.events --group=ops-shell
```

### Replaying a workflow run

```bash
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -d '{"pin_versions":true}' \
  http://localhost:8000/api/observability/runs/123/replay
```

### Importing an external workflow

```bash
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -F "tenant_id=1" -F "source_format=n8n" -F "source=@/path/to/flow.json" \
  http://localhost:8000/api/migrations/import
```

## 8. Known caveats

- Kafka driver requires `php-rdkafka` *or* a Redpanda HTTP proxy reachable
  from the backend container; otherwise leave `EVENT_BUS_DRIVER=in_process`
  for dev work — events still flow but only in-process / via Redis Streams.
- Qdrant collections are created lazily on first `remember()` call.
- The CrewAI bridge runs in-process by default — there is no external CrewAI
  server required. For Dify/Flowise/SIM you must configure
  `endpoint_url` + `secret_ref` per connection.
- Ollama only ships with model *runtime*; pull a model after first boot:
  ```bash
  docker compose exec ollama ollama pull llama3.2:1b
  ```
