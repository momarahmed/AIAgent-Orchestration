# Phase 4 — Advanced Multi-Agent Platform · Checklist

> Status legend: ✅ shipped · 🟡 partial / mock · ⬜ planned

## 1. Agent-to-Agent (A2A) Gateway
- ✅ Partner directory (`a2a_partners`) with HMAC / Bearer / OAuth2 / mTLS auth types
- ✅ Outbound `POST /api/a2a/send` — partner row id, payload, message_type, priority
- ✅ Inbound webhook `POST /api/a2a/inbound/{partnerId}` (unauthenticated, HMAC-verified)
- ✅ OPA policy: `a2a/federation` (cross-partner authorization)
- ✅ Prompt-injection scan on inbound payloads
- ✅ Audit log via `a2a_messages` + `event_log` (`a2a.*` topics)
- 🟡 mTLS validation is stubbed for dev — wire to real cert store in prod

## 2. Event Bus at Scale
- ✅ Pluggable `EventBus` facade with Kafka/Redpanda (`KafkaEventBus`) + in-process fallback (`InProcessEventBus`)
- ✅ Topics (`EventBus::TOPICS`): `agent.events`, `tool.events`, `workflow.events`, `run.status`, `deployment.events`, `approval.events`, `a2a.events`
- ✅ Durable consumer command: `php artisan eamcp:consume-event-bus`
- ✅ Subscriptions table → workflow / webhook / agent handlers
- ✅ Consumer offset tracking (`event_consumer_offsets`)
- ✅ Immutable mirror to `event_log`
- 🟡 KRaft cluster in compose is single-node; add brokers + replication for prod

## 3. Full Model Control Plane
- ✅ Model registry (`models` table) — OpenAI, Claude, Gemini, Ollama, vLLM
- ✅ Provider implementations including `OllamaProvider` (local-first)
- ✅ Deterministic `ModelRouter` (capability / tag / residency / hint / cost / latency rules)
- ✅ Budget enforcement (per-tenant provider budgets)
- ✅ Fallback chains (e.g. Claude → GPT-4o → Llama)
- ✅ Prompt Registry with versioning, diff, activate, render, leaderboard
- ✅ Prompt Evaluation Service (cost / latency / quality scoring)

## 4. Memory & Knowledge Layer
- ✅ Short-term memory (Redis hash per session)
- ✅ Long-term memory (`memory_collections` + `memory_items` in MySQL)
- ✅ Vector store: Qdrant with graceful in-memory fallback
- ✅ Embeddings: OpenAI w/ deterministic hash fallback
- ✅ RAG retrieve with scope enforcement (session/project/tenant/none)
- ✅ Cross-tenant guard + audit
- ✅ Knowledge Graph (`knowledge_graph_nodes` / `edges`)
- ✅ OPA policy: `memory/scope`

## 5. Meta-Agents (productionized)
All 11 meta-agents registered in `MetaAgentOrchestrator::REGISTRY`:
- ✅ Platform Architect — decompose prompt → plan
- ✅ Agent Builder — create Agent + AgentVersion via OPA-aware writes
- ✅ MCP Builder — scaffold + register MCP server, optional OpenHands codegen
- ✅ Workflow Builder — emit native graph + WorkflowVersion
- ✅ Template Manager — package any asset as installable template
- ✅ QA / Test — generate + execute test suites
- ✅ Security Review — trigger SecurityScannerService + OPA review
- ✅ DevOps Deployment — call DeploymentService with approval gate
- ✅ Documentation — emit READMEs / diagrams
- ✅ Migration — import n8n / Flowise / Dify / JSON / YAML
- ✅ Governance — compliance audits + evidence collection
- ✅ Intent Router (`IntentRouterService::classify`)
- ✅ Autopilot endpoint `/api/meta-agents/autopilot`

## 6. Advanced Observability
- ✅ Prometheus scrape endpoint `/api/observability/metrics` (text format)
- ✅ JSON snapshot `/api/observability/metrics.json`
- ✅ Workflow run timeline `/api/observability/timeline/{run}`
- ✅ Run Replay (`POST /api/observability/runs/{run}/replay`, pin_versions opt-in)
- ✅ Grafana dashboards provisioned (`infra/grafana/provisioning`)
- ✅ Loki + Prometheus services in `docker-compose.yml`
- ✅ Knowledge-graph endpoint for topology view
- 🟡 OpenTelemetry collector wiring stub — set `OTEL_EXPORTER_OTLP_ENDPOINT` to enable

## 7. Cross-Framework Bridges
Supported frameworks: dify / flowise / sim / crewai
- ✅ `BridgeAdapter` base class with enable / approval / audit
- ✅ DifyBridge, FlowiseBridge, SimBridge, CrewAiBridge (CrewAI is in-process)
- ✅ Connection storage (`bridge_connections`) + execution audit (`bridge_executions`)
- ✅ Toggle endpoint, framework discovery endpoint

## 8. Local LLM Routing
- ✅ Ollama service in compose (`OLLAMA_BASE_URL` env)
- ✅ `is_local` flag on models + residency rule matching
- ✅ Seeded routing rule: `residency = local-only → ollama:llama3.2:1b`

## 9. Collaboration & Comments
- ✅ Threaded comments on any asset (`asset_comments`)
- ✅ Mention parsing + notification stub via event bus
- ✅ Resolve workflow
- ✅ Reusable `<CommentThread />` React component

## 10. Frontend
Pages added under `(app)/`:
- ✅ `/meta-agents` · `/models` · `/prompts` · `/memory`
- ✅ `/a2a` · `/bridges` · `/event-bus` · `/migrations` · `/observability`
- ✅ Sidebar group "Multi-Agent Platform"

## Validation steps

```bash
# Bring up Phase 4 infra
docker compose up -d redpanda redpanda-console ollama prometheus grafana loki

# Run migrations + Phase 4 seed
docker compose exec backend php artisan migrate --seed

# Verify routes
docker compose exec backend php artisan route:list | grep -E "models|prompts|memory|a2a|meta-agents|bridges|event-bus|migrations|observability|comments"

# Run Phase 4 feature tests
docker compose exec backend php artisan test --filter=PlatformPhase4Test
```
