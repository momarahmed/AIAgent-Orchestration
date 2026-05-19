# Phase 1: Foundation — Core Platform

> **Source PRD:** Enterprise AI + MCP + Multi-Agent + Workflow Builder Platform — PRD v1.1 (Technology-Mapped Edition), dated 2026-05-18.
> **Phase Sequence:** 1 of 5
> **Theme:** Build the custom core. No accelerators. No external bridges. Establish the asset model, the studios, the visual workflow builder, the agent runtime prototype, and the MCP gateway prototype.

---

## Objective

Establish the foundational platform shell on which every later phase will build. By the end of Phase 1, an authenticated user must be able to log in, create a tenant/project, draft an agent, register an MCP server, draw a visual workflow that calls that agent and that MCP server, run the workflow once, and see the result and a run log.

This phase is **not** about production readiness, governance, or enterprise integrations. It is about proving that the unified asset model (Agents + MCP Servers + Workflows + Templates) works end-to-end on a vendor-neutral, custom-built core.

---

## Scope

In scope for this phase:

- Next.js platform shell with navigation, projects, and tenants.
- Chat UI for prompt entry and response display (single-turn execution only — no advanced memory yet).
- Asset model in PostgreSQL covering Agents, MCP Servers, Workflows, and Templates with `current_version_id` fields.
- Basic Agent Studio (create, read, update, archive — no debug console yet).
- Basic MCP Server Studio (register, view, archive — no codegen yet).
- Basic Visual Workflow Builder built on React Flow (drag, drop, connect, save the graph).
- Agent runtime prototype using LangGraph (in-process, no Temporal yet).
- MCP Gateway prototype using MCP Python SDK (single-tenant, no policy engine yet).
- Workflow run engine basic (synchronous execution, in-memory state, no retries, no approvals).
- Run history and tool-call logs persisted in PostgreSQL.
- Basic observability via OpenTelemetry SDK emitting to a local collector.
- Local development experience via Docker Compose.

Out of scope for this phase (deferred to later phases):

- Debug console, version diff/rollback, copy operations, template import/export, deployment manager, test runner, approval queue (all → Phase 2).
- RBAC/ABAC enforcement beyond basic roles, OPA policy engine, vault secrets, audit reports, tool sandbox, security scanner (all → Phase 3).
- ArcGIS MCP, Database MCP, File/PDF MCP, Email MCP server implementations and Activepieces bridge (all → Phase 3).
- A2A gateway, event bus, model control plane, RAG/vector memory, meta-agents (all → Phase 4).
- Marketplace, GitOps, multi-region, advanced analytics (all → Phase 5).

---

## Key Requirements

### Functional Requirements (from PRD Section 17)

User Experience:

| ID | Requirement | Priority |
|---|---|---|
| UX-001 | User can access platform via web UI | Must |
| UX-002 | User can write natural language prompt | Must |
| UX-003 | User can switch between chat, workflow builder, Agent Studio, MCP Studio | Must |
| UX-004 | User can view run history and execution status | Must |

Agent Management (basic CRUD subset):

| ID | Requirement | Priority |
|---|---|---|
| AG-001 | Create agent from prompt (basic; advanced generation in later phases) | Must |
| AG-003 | Update agent configuration | Must |
| AG-004 | Delete/archive agent | Must |
| AG-010 | Assign allowed MCP servers/tools | Must |
| AG-011 | Assign memory scope (none/session/project/tenant) | Must |
| AG-012 | Assign model and fallback model | Must |
| AG-013 | View agent execution history | Must |

MCP Server Management (basic registration subset):

| ID | Requirement | Priority |
|---|---|---|
| MCP-001 | Create MCP server (manual registration in Phase 1; codegen in Phase 2) | Must |
| MCP-004 | Add/update/delete tools/resources/prompts | Must |
| MCP-013 | Register tools into Tool Registry | Must |
| MCP-014 | Run MCP server health checks | Must |

Workflow Management (visual authoring subset):

| ID | Requirement | Priority |
|---|---|---|
| WF-001 | Create workflow visually | Must |
| WF-003 | Update workflow nodes/edges/config | Must |
| WF-004 | Delete/archive workflow | Must |
| WF-010 | Execute workflow manually | Must |

Runtime Execution (basic synchronous subset):

| ID | Requirement | Priority |
|---|---|---|
| RT-001 | Execute agent workflows reliably (basic, synchronous) | Must |
| RT-002 | Persist workflow state (run record + node states) | Must |
| RT-008 | Generate final response/report | Must |

### Non-Functional Requirements

| Category | Requirement (Phase 1 baseline) |
|---|---|
| Availability | Local dev only; no SLO yet. |
| Scalability | Single-instance services; horizontal scale design documented but not implemented. |
| Performance | UI actions respond within 2 seconds for normal operations. |
| Reliability | Workflow run records must survive API restart (persisted to PostgreSQL). |
| Security | Auth via Keycloak; no plaintext secrets in DB; secret values live in `.env` for now, with vault integration deferred to Phase 3. |
| Auditability | Every create/update/delete on assets emits a row in an `audit_event` table (full export and reporting in Phase 3). |
| Extensibility | Asset model and APIs designed for versioning even though rollback ships in Phase 2. |
| Portability | Must run via Docker Compose on a developer laptop. |

---

## User Stories / Use Cases

US-1.1 — As a **Platform Admin**, I want to create a tenant and a project so that other users can be invited to a scoped workspace.

US-1.2 — As an **AI Agent Designer**, I want to open Agent Studio, create a new agent with a name, role, instructions, model selection, and an allowed MCP server list, and save it as a draft so that the workflow builder can reference it.

US-1.3 — As an **MCP Developer**, I want to register an MCP server by entering its endpoint, transport (stdio/http), auth method, and tool list, so that agents and workflows can call its tools.

US-1.4 — As an **Automation Builder**, I want to drag a Trigger node, an Agent node, and an MCP Tool node onto a canvas, wire them with edges, configure each node, and save the workflow as a draft.

US-1.5 — As a **Business User**, I want to click "Run" on a saved workflow and see the result in the chat panel along with the per-node status.

US-1.6 — As any user, I want to open a workflow run from history and see which nodes ran, what inputs they got, and what outputs they produced, so I can verify the system worked.

### Anchor Scenario (from PRD Section 27)

A simplified version of Scenario 27.1: a user manually drafts a "GIS Health" agent, manually registers a placeholder ArcGIS MCP server (real implementation lands in Phase 3), draws a one-step workflow (Trigger → Agent), and runs it. The platform returns the LLM's response and persists a run record. This proves the end-to-end pipe even though the real GIS tools come later.

---

## Features

### 1. Platform Shell (Frontend)

- Next.js + React + TypeScript application with TailwindCSS and shadcn/ui.
- Top navigation across Chat, Workflows, Agents, MCP Servers, Templates (placeholder), and Admin.
- Tenant/project switcher in the header.
- Authentication redirect to Keycloak; session token stored in HTTP-only cookie.

### 2. Chat UI

- Simple prompt input, response stream display.
- Per-message run ID linking to the run history view.
- No advanced memory, RAG, or multi-turn agent conversations yet (those land in Phase 4).

### 3. Agent Studio (Basic)

- Form-based creation with fields: name, description, role, system instructions, model config, allowed MCP server IDs, allowed tool IDs, memory scope, risk level.
- List view with status (draft/staging/production/archived).
- Edit view that creates a new `AgentVersion` row on save.
- Archive action (no hard delete in APP).

### 4. MCP Server Studio (Basic Registration)

- Form-based registration of an MCP server: name, transport (stdio/http/streamable-http/sse), runtime, endpoint, auth method, secret references (vault path strings only — actual vault wiring is Phase 3).
- Tool list editor: add tools with name, description, JSON schema (input/output), risk level (L0–L4).
- Health-check button that pings the MCP server.

### 5. Visual Workflow Builder

- React Flow canvas with drag-and-drop palette.
- Initial supported node types: Trigger (manual/chat only), Agent, MCP Tool.
- Node configuration panel on the right.
- Save creates a new `WorkflowVersion` with `graph_json` and `variables`.
- Validation on save: no disconnected nodes, no missing required configs.

### 6. Agent Runtime Prototype

- LangGraph-based runtime running in the same Python process as the FastAPI backend (will be split out to workers in Phase 2).
- Maps a Workflow node graph into a LangGraph state machine.
- Each Agent node call invokes the configured model provider (one provider — OpenAI or local Ollama — chosen at deploy time; full Model Control Plane is Phase 4).

### 7. MCP Gateway Prototype

- Built on the MCP Python SDK.
- One MCP Client Manager that opens sessions per MCP Server registration.
- Tool Registry table populated from MCP server registration.
- Tool execution logged to `tool_call` table with inputs/outputs (no redaction yet — that lands in Phase 3).
- No policy enforcement in Phase 1; **all** tool calls are allowed if the agent's `allowed_tools` list permits them. Risk-level gates ship in Phase 2/3.

### 8. Workflow Run Engine (Basic Synchronous)

- Synchronous execution: API call → run record created → graph executed → result returned.
- No retries, no scheduling, no approvals — those come in Phase 2.
- Run state persisted in PostgreSQL `workflow_run` and `task_run` tables.

### 9. Run History & Tool-Call Logs

- Run list view filterable by workflow, status, and date range.
- Run detail view showing the node tree, per-node duration, inputs, outputs, and any error message.
- Tool-call log view showing every MCP tool invocation per run.

### 10. Basic Observability

- OpenTelemetry SDK instrumented in the FastAPI app and the LangGraph runtime.
- OTLP export to a local OpenTelemetry Collector running in Docker Compose.
- Logs to stdout in JSON format.

---

## Deliverables

1. **Next.js Platform Shell** — Chat UI, Agent Studio, MCP Studio, Workflow Builder, Run History, Admin (basic).
2. **FastAPI Backend** — Tenant/project APIs, asset CRUD APIs (POST/GET/PUT/DELETE for `/api/agents`, `/api/mcp-servers`, `/api/workflows`), `/api/workflows/{id}/run`, `/api/runs`, `/api/runs/{id}`.
3. **PostgreSQL Schema v1** — All tables from PRD Section 19.1 (Tenant, Project, User, Role, Agent, AgentVersion, MCPServer, MCPServerVersion, Tool, Workflow, WorkflowVersion, WorkflowRun, TaskRun, ToolCall, Template stub, AuditEvent).
4. **Keycloak Realm Configuration** — Realm, client, basic roles (Viewer, Builder, Admin), seed users for dev.
5. **Agent Runtime Service v0** — LangGraph-based, in-process, supports Agent and MCP Tool nodes.
6. **MCP Gateway Service v0** — Single MCP Client Manager, Tool Registry, tool execution logging.
7. **Docker Compose Stack** — `frontend`, `api`, `agent-runtime`, `mcp-gateway`, `postgres`, `redis`, `keycloak`, `otel-collector`.
8. **Kubernetes Manifests (design only)** — Documented manifests for future deployment; not deployed in Phase 1.
9. **Developer Documentation** — README, local setup guide, architecture overview, asset model reference.
10. **Smoke-Test Workflow** — A seeded "Hello World" workflow that proves Trigger → Agent → MCP Tool → Result works end-to-end.

---

## Dependencies

External dependencies (must be available before Phase 1 can ship):

- Keycloak instance (containerized for dev).
- PostgreSQL 15+ (containerized for dev).
- Redis 7+ (containerized for dev).
- An LLM provider available in dev: either an OpenAI API key, or a local Ollama instance with a small model.
- OpenTelemetry Collector image.

Internal team dependencies:

- Platform Architect signs off on the asset model and API contract before backend development begins.
- UX designer delivers Chat UI, Studios, and Workflow Builder mockups in the first sprint.
- DevOps engineer provides Docker Compose baseline and CI for the monorepo.

Cross-phase dependencies:

- Phase 1 has **no prior phase**. All subsequent phases depend on Phase 1's asset model, API surface, and runtime contracts.

---

## Acceptance Criteria

Phase 1 is complete when **all** of the following are true:

1. A new user can sign in via Keycloak, land on the platform shell, and see their tenant/project.
2. A user with the Builder role can create, update, and archive an Agent through Agent Studio; the change creates a new `AgentVersion` row, and the audit table records the event.
3. A user with the Builder role can register an MCP Server, define at least one tool, and run a health check that returns "healthy" against a reference MCP server.
4. A user can draw a workflow with Trigger → Agent → MCP Tool nodes in the visual builder, save it, and reopen it with the graph intact.
5. A user can run a saved workflow synchronously and receive a result.
6. The Run History view shows the run, its status, its per-node tree, inputs, outputs, and the underlying tool calls.
7. The full stack starts with a single `docker compose up` command and the smoke-test workflow runs to completion against it.
8. OpenTelemetry traces for a workflow run are visible in the local OTel Collector output.
9. Architectural documentation describing the Kubernetes deployment design (even though not deployed) is reviewed and merged.
10. Code coverage for backend asset CRUD and run engine is at least 70%.

The platform-level acceptance criteria from PRD Section 28 that apply to Phase 1 specifically: "Users can create/update/delete/debug/deploy/copy agents" (create/update/delete only — debug/deploy/copy land in Phase 2), "Users can create/update/delete/debug/deploy/copy workflows" (same scoping), "Workflows can execute agents and MCP tools", "Runs produce logs and traces", and "Basic Docker Compose deployment works".

---

## Notes

- **Build-Custom-First Principle (PRD Section 24.1 & 24.10).** The APP must not lean on Activepieces, Dify, Flowise, SIM, CrewAI, or AutoGen in Phase 1. Those are evaluated later. The Phase 1 stack is intentionally minimal: Next.js, React Flow, FastAPI, Keycloak, PostgreSQL, Redis, LangGraph, MCP Python SDK, OpenTelemetry.
- **Vendor-neutral core (PRD Section 24.1).** Even though only one model provider is wired in Phase 1, the model configuration must be abstracted behind a provider adapter interface so that OpenAI Agents SDK, Claude Agent SDK, and Google ADK adapters can be added in Phase 2/3 without rewriting agent code.
- **Versioning is wired in, rollback is not.** Every save creates a new `AgentVersion` / `MCPServerVersion` / `WorkflowVersion`, but the UI does not expose rollback yet. Rollback ships in Phase 2.
- **Temporal is deliberately deferred to Phase 2.** Phase 1 runs workflows in-process to keep cognitive load low. The workflow run interface (start-run, get-run, get-trace) is designed so the engine can be swapped to Temporal without breaking the API surface.
- **MCP Gateway scope.** Phase 1 implements the MCP Client Manager and Tool Registry components from PRD Section 14.1. The Tool Permission Engine, Tool Validator (beyond basic schema check), Tool Sandbox, and full MCP Debug Console land in Phase 2/3.
- **Build Priority Alignment.** Phase 1 covers Build Priority 1 items 1–7 and item 11–12 from PRD Section 30 (core DB schema, basic auth/RBAC, asset CRUD, visual workflow builder, basic agent runtime, basic MCP gateway, basic workflow run engine, debug logs and run history). Reference MCP server prototypes (items 7–10 of Build Priority 1) are deferred to Phase 3 since they require real integrations.
- **Risk: Workflow state lost on restart of synchronous runs in flight.** Mitigation: document the limitation; long-running workflows are explicitly out of scope until Temporal lands in Phase 2.
- **Risk: No secret vault yet.** Mitigation: Phase 1 secrets live in `.env` files for local dev only; production deployment is blocked until Phase 3 ships vault integration. This is enforced in the deployment manager (Phase 2) which refuses to deploy any asset that references a non-vault secret path in non-dev environments.
- **Acceptance gates for the team.** Phase 2 cannot start until Phase 1 acceptance criteria 1–8 are met and the architecture review for Temporal integration is approved.
