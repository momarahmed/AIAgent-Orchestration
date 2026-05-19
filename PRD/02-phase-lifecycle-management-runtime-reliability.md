# Phase 2: Lifecycle Management & Runtime Reliability

> **Source PRD:** Enterprise AI + MCP + Multi-Agent + Workflow Builder Platform — PRD v1.1 (Technology-Mapped Edition), dated 2026-05-18.
> **Phase Sequence:** 2 of 5
> **Theme:** Turn the Phase 1 core into something you can actually operate. Add the full lifecycle (debug, deploy, copy, version, rollback, template import/export), durable execution (Temporal), tests, approvals, and code-generation accelerators (OpenHands SDK).

---

## Objective

Promote every asset type (Agents, MCP Servers, Workflows, Templates) from "draftable" to "operable". By the end of Phase 2, a user can debug an asset step-by-step, version it, copy it, deploy it across environments (dev/test/staging/prod) with promotion approvals, save it as a reusable template, import/export template packages, run automated tests, and execute long-running workflows that survive process restarts and pause for human approval.

Phase 2 turns the Phase 1 prototype into a **lifecycle factory** — but still without enterprise governance hardening (RBAC fine-grain, OPA, vault, audit exports) or real enterprise integrations (ArcGIS, DB, File/PDF, Email MCP servers). Those land in Phase 3.

---

## Scope

In scope for this phase:

- Debug Console for Agents, MCP Servers, and Workflows (step-by-step execution, replay, mock inputs, tool-call inspection).
- Version Manager — list versions, diff versions, set "current" version, rollback.
- Copy operations for all asset types (Agents, MCP Servers, Workflows, Templates).
- Template lifecycle — Create Template, Import Template, Export Template, Instantiate from Template, with secret stripping and parameterization.
- Deployment Manager — promote assets across `dev → test → staging → prod` environments with build, validation, smoke test, and rollback readiness.
- Durable Workflow Engine — Temporal integration; replaces the synchronous Phase 1 runner.
- Approval Queue — human-in-the-loop gate for risky tool calls and production deployments.
- Test Runner — unit/integration test execution for agents, MCP tools, and workflows.
- Code/DevOps Agent — OpenHands Software Agent SDK in a sandboxed workspace, used by the Agent Builder Agent, MCP Builder Agent, and Workflow Builder Agent to generate code, tests, and manifests.
- Provider Adapters v1 — OpenAI Agents SDK and Claude Agent SDK as routed providers behind the (placeholder) Model Control Plane.
- Basic OPA policy enforcement on tool risk levels (full policy-as-code lands in Phase 3).
- Basic Vault/Key Vault integration for secret references (full secret rotation and broad enterprise wiring lands in Phase 3).

Out of scope for this phase (deferred):

- Full RBAC/ABAC, comprehensive policy-as-code, audit reports, tool sandbox hardening, security scanner, network allowlists (all → Phase 3).
- Real enterprise MCP servers (ArcGIS, Database, File/PDF, Email) and Activepieces bridge (all → Phase 3).
- A2A gateway, event bus at scale, full Model Control Plane, RAG/memory, meta-agents (all → Phase 4).
- Marketplace, GitOps, HA/DR, multi-region, advanced analytics (all → Phase 5).

---

## Key Requirements

### Functional Requirements (from PRD Section 17)

User Experience additions:

| ID | Requirement | Priority |
|---|---|---|
| UX-005 | User can debug failed runs from UI | Must |
| UX-006 | User can import/export templates | Must |
| UX-008 | User can compare asset versions | Should |

Agent Management — completing the lifecycle:

| ID | Requirement | Priority |
|---|---|---|
| AG-002 | Create agent from template | Must |
| AG-005 | Debug agent with test prompts | Must |
| AG-006 | Deploy agent to environment | Must |
| AG-007 | Copy agent | Must |
| AG-008 | Save agent as template | Must |
| AG-009 | Import/export agent template | Must |
| AG-014 | Evaluate agent using test suite | Should |

MCP Server Management — completing the lifecycle:

| ID | Requirement | Priority |
|---|---|---|
| MCP-002 | Create MCP server from template | Must |
| MCP-003 | Generate tool schemas (via OpenHands SDK code agent) | Must |
| MCP-005 | Debug MCP server tools | Must |
| MCP-006 | Deploy MCP server to environment | Must |
| MCP-007 | Copy MCP server | Must |
| MCP-008 | Save MCP server as template | Must |
| MCP-009 | Import/export MCP server template | Must |
| MCP-011 | Validate tool input/output schemas | Must |
| MCP-012 | Assign tool risk levels (enforced by basic OPA in Phase 2) | Must |

Workflow Management — completing the lifecycle:

| ID | Requirement | Priority |
|---|---|---|
| WF-002 | Create workflow from prompt (via Workflow Builder Agent + OpenHands SDK) | Must |
| WF-005 | Debug workflow step-by-step | Must |
| WF-006 | Deploy workflow to environment | Must |
| WF-007 | Copy workflow | Must |
| WF-008 | Save workflow as template | Must |
| WF-009 | Import/export workflow template | Must |
| WF-011 | Execute workflow on schedule (via Temporal) | Must |
| WF-012 | Execute workflow from webhook/event | Should |
| WF-013 | Support approval nodes | Must |
| WF-014 | Support retry/error handling | Must |
| WF-015 | Support workflow version rollback | Must |

Runtime Execution — completing the durable runtime:

| ID | Requirement | Priority |
|---|---|---|
| RT-003 | Retry failed steps by policy | Must |
| RT-004 | Pause for human approval | Must |
| RT-005 | Resume workflow after approval | Must |
| RT-006 | Aggregate results | Must |
| RT-007 | Validate final output | Must |
| RT-009 | Support parallel branches | Should |
| RT-010 | Support long-running workflows | Must |

Observability additions:

| ID | Requirement | Priority |
|---|---|---|
| OBS-004 | Display execution timeline | Must |
| OBS-006 | Support replay of workflow runs | Must |

### Non-Functional Requirements

| Category | Requirement (Phase 2 target) |
|---|---|
| Availability | Internal dev/test environments target 99% uptime during business hours. |
| Scalability | Agent runtime, workflow workers, and MCP gateway each run as independent horizontally-scalable services. |
| Reliability | Workflow state must survive process restart — verified via chaos test (kill worker mid-run). |
| Maintainability | Every asset is versioned and rollbackable from the UI in a single click. |
| Extensibility | New node types (Approval, Loop, Parallel, Error Handler, RAG stub, Memory stub) can be added without changing the run engine contract. |

---

## User Stories / Use Cases

US-2.1 — As an **MCP Developer**, I want to type "Generate an MCP server for our internal Inventory REST API" and have the Code/DevOps Agent (OpenHands SDK) produce the Python MCP server scaffolding, tool schemas, and a test suite, so that I do not write boilerplate by hand.

US-2.2 — As an **Automation Builder**, I want to step through a failing workflow node by node in the Debug Console, inspect each LLM message, each MCP tool call's inputs and outputs, and replay a single node with edited inputs, so I can find and fix bugs fast.

US-2.3 — As a **Platform Admin**, I want to deploy a workflow from `dev → test → staging → prod` and have the deployment manager run validation, tests, security scan stubs, and request my approval before promotion to production.

US-2.4 — As an **AI Agent Designer**, I want to save my "GIS Health Monitor" agent as a Template, export it as a ZIP, send it to another team, and have them import it into their project — with secrets stripped and parameters preserved.

US-2.5 — As a **Security Officer**, I want every L2/L3/L4 tool call inside a running workflow to pause and wait in an Approval Queue until I approve or reject it, after which the workflow resumes automatically.

US-2.6 — As an **Automation Builder**, I want to roll back a workflow to a previous version with one click and have the scheduler immediately start using that version.

US-2.7 — As a **DevOps Engineer**, I want a workflow to run on a daily cron schedule, retry failed nodes with exponential backoff, and survive a restart of any platform worker.

### Anchor Scenarios

PRD Scenario 27.1 (full GIS health workflow) becomes runnable end-to-end at the runtime level in Phase 2 — but using placeholder MCP servers because the real ArcGIS/DB/File/Email MCP servers ship in Phase 3.

PRD Scenario 27.2 (Debug Failed MCP Server) is fully supported in Phase 2: MCP Studio opens server context, MCP Debug Console runs test input, Tool Validator checks schema, logs show error, MCP Builder Agent proposes fix via OpenHands SDK, Test Runner validates fix, Deployment Agent redeploys to dev, user approves production deployment.

PRD Scenario 27.3 (Import Workflow Template) is fully supported in Phase 2: Template Manager receives package, Import Manager validates manifest, basic Security Scanner checks for raw secrets, Dependency Mapper checks required agents/MCP servers, missing assets are created as drafts, user configures environment parameters, tests run, workflow deployed after approval.

---

## Features

### 1. Debug Console (PRD Sections 8.1, 11)

- **Agent Debug** — Send test prompts; view system prompt, model response, every tool call's request and response, token usage, latency.
- **MCP Server Debug** — Per-tool test runner; raw MCP protocol message inspector; schema validation; error details.
- **Workflow Debug** — Step-by-step execution with breakpoints on nodes; replay any past run from `WorkflowRun` history; edit node inputs and re-run only that node; visual timeline of node start/end/duration/status.

### 2. Version Manager

- List all versions of an Agent, MCP Server, or Workflow.
- Visual diff between any two versions (JSON-level for configs, graph-level for workflows).
- "Set as current" button writes the chosen version to `current_version_id` and is gated by Phase 2 approval rules.
- Rollback is just "set as current" on a prior version.

### 3. Copy Operations

- "Copy Agent / MCP Server / Workflow / Template" creates a new asset with a new UUID, copies the latest version's config, prompts the user for a new name and environment-specific secrets, and writes a new draft.
- Copies never carry secret values — only secret references (vault paths) — and the user is forced to re-map them.

### 4. Template Lifecycle

- **Create Template** from any existing asset — strips secret values, parameterizes endpoints/URLs/email recipients, generates a `manifest.yaml` per PRD Section 16.3.
- **Export Template** as JSON, YAML, or ZIP, following the package structure from PRD Section 16.2 (`manifest.yaml`, `agents/`, `mcp-servers/`, `workflows/`, `policies/`, `tests/`, `docs/`, `assets/`).
- **Import Template** — schema validation, signature validation (signature-required defaults to off in Phase 2 — enforced in Phase 3), basic security scan for raw secrets, dependency resolution (auto-create missing referenced agents/MCP servers as drafts).
- **Instantiate from Template** — prompts for required parameters, creates concrete assets, runs validation.

### 5. Deployment Manager (PRD Section 23)

- Promotion pipeline: `Save Asset → Schema Validation → Unit Tests → Integration Tests → Security Scan (stub in Phase 2) → Container Build → Image Scan (stub) → Deploy to Dev → Smoke Test → Approval → Deploy to Staging/Prod → Post-Deploy Validation`.
- Environment-specific config (dev/test/staging/prod) with separate secret reference sets per environment.
- "Deploy" button per asset with environment selector.
- Per-deployment record in `Deployment` table for audit and rollback.
- Refuses to deploy to staging/prod if any non-vault secret references are present (locks in the Phase 1 secret hygiene rule).

### 6. Durable Workflow Engine (Temporal)

- Replaces the Phase 1 in-process synchronous runner.
- Each Workflow Run becomes a Temporal workflow execution.
- Each Agent node call and each MCP Tool call becomes a Temporal Activity with configurable timeouts and retry policies.
- Scheduled workflows use Temporal Schedules (cron, intervals).
- Webhook/event triggers (Should-priority) post a signal to a long-running Temporal workflow.
- LangGraph remains the agent runtime — it runs inside a Temporal activity that can survive worker restarts.

### 7. Approval Queue

- Backed by a Temporal-aware service that pauses workflows on Approval nodes and on tool calls whose risk level exceeds the agent's `max_risk_level_without_approval`.
- Approval list UI with one-click approve/reject; approver may add a comment.
- Approval timeout policy (default: 7 days; configurable per workflow).
- Email/Teams notification stub for pending approvals (real Email MCP integration ships in Phase 3).

### 8. Test Runner

- Per-asset test suites stored in `tests/` of the template package.
- Test types: agent prompt assertion, MCP tool input/output assertion, workflow scenario assertion.
- Triggered manually or as part of the Deployment Manager pipeline.
- Results visible in the asset detail page.

### 9. Code/DevOps Agent (OpenHands Software Agent SDK)

- Runs in a sandboxed Docker/Kubernetes workspace (per PRD Section 24.5.9).
- Invoked by Meta-Agents (Agent Builder, MCP Builder, Workflow Builder) to generate code, tests, container manifests, and docs.
- No unrestricted shell access in any environment beyond `dev`.
- All generated code is committed to an internal Git repo with a PR for human review before merge.

### 10. Provider Adapters v1

- OpenAI Agents SDK adapter (PRD Section 24.5.10): used for OpenAI-specific agents that benefit from handoffs/guardrails/sessions.
- Claude Agent SDK adapter (PRD Section 24.5.11): used for Anthropic-backed agents and the Claude-based coding workflow option.
- Adapters live behind a thin `ModelProvider` interface so the full Model Control Plane (Phase 4) can swap them under the hood.

### 11. Basic Policy Enforcement (OPA)

- A single OPA policy file enforces: an agent may not invoke any tool whose `risk_level` exceeds the agent's `max_risk_level_without_approval` unless an Approval record exists.
- Policy bundle loaded at gateway startup; reload on save.
- Full policy-as-code authoring UI is Phase 3.

### 12. Basic Secrets Integration

- All `secret_refs` resolve through a `SecretService` that pulls from HashiCorp Vault or the configured cloud key vault.
- Local dev still allows `.env`-backed resolution, but staging/prod require vault paths.
- Rotation, automatic credential refresh, and full secret-injection-into-MCP-process land in Phase 3.

### 13. New Workflow Node Types

| New Node | Description |
|---|---|
| Approval Node | Pauses workflow until human approval. |
| Decision Node | Conditional branching on prior outputs/variables. |
| Transform Node | Format, map, filter, convert data. |
| Loop Node | Iterate over items. |
| Parallel Node | Run branches concurrently. |
| Error Handler Node | Retry/fallback path. |
| Template Node | Reusable sub-workflow. |

---

## Deliverables

1. **Debug Console** (Agent / MCP / Workflow) integrated into Agent Studio, MCP Studio, and Workflow Studio.
2. **Version Manager** with diff and rollback for all asset types.
3. **Copy operations** wired into the UI for all asset types.
4. **Template Manager** with create/import/export/instantiate, including manifest validation per PRD Section 16.3.
5. **Deployment Manager** with the full CI/CD pipeline from PRD Section 23.3 (scan stages stubbed in Phase 2, real scanners in Phase 3).
6. **Temporal cluster** wired in, plus workflow workers and the durable workflow engine.
7. **Approval Queue service and UI**.
8. **Test Runner service** with per-asset test suites.
9. **OpenHands Software Agent SDK** running as the Code/DevOps Agent in a sandboxed workspace.
10. **Provider adapters** for OpenAI Agents SDK and Claude Agent SDK behind a `ModelProvider` interface.
11. **Basic OPA policy bundle** enforcing risk-level approvals on tool calls.
12. **Basic Vault/Key Vault integration** for secret references.
13. **Expanded React Flow node palette** with the seven new node types listed above.
14. **Updated Docker Compose stack** plus first deployable Kubernetes Helm charts (used in `dev` cluster).
15. **Migration guide** from Phase 1's synchronous runner to Phase 2's Temporal-based runner (zero-downtime migration plan).
16. **Updated test coverage** ≥ 80% on lifecycle services.

---

## Dependencies

Depends on Phase 1 deliverables:

- Phase 1 asset model, API surface, Agent Studio, MCP Studio, Workflow Builder, Run History, and basic LangGraph runtime are all prerequisites. The Phase 2 Temporal engine reuses the Phase 1 run API contract.

External dependencies:

- Temporal cluster (self-hosted in dev/test/staging; managed Temporal Cloud option for prod).
- HashiCorp Vault or a cloud key vault (Azure Key Vault, AWS Secrets Manager, GCP Secret Manager).
- An OPA server / sidecar.
- Sandboxed runtime for OpenHands SDK (Docker-in-Docker or Kubernetes namespace with no host mount).
- Internal Git repository for OpenHands-generated code review.
- OpenAI API access and Anthropic API access (or local equivalents) for provider adapter testing.

Internal team dependencies:

- Security team reviews the OpenHands SDK sandbox configuration before it can run code in any non-local environment.
- DevOps team operates the Temporal cluster and OPA server.

Cross-phase dependencies:

- Phase 3 (Governance & Integrations) depends on Phase 2's Approval Queue, Deployment Manager, Test Runner, and basic OPA policy bundle.
- Phase 4 (Advanced Multi-Agent) depends on Phase 2's Temporal engine for durable agent workflows and on the provider adapter interface to extend into a full Model Control Plane.

---

## Acceptance Criteria

Phase 2 is complete when **all** of the following are true:

1. A user can step through any workflow node-by-node in the Debug Console, see inputs/outputs/errors, and replay a single node with edited inputs.
2. Every Agent, MCP Server, and Workflow has a version history visible in the UI, with diff and one-click rollback.
3. A user can copy any asset and the copy never contains secret values — only secret references.
4. A user can save an asset as a Template, export it as a ZIP, re-import it into a new project, and instantiate it to working assets after providing required parameters. Secrets are absent from the exported package (verified by automated test).
5. A workflow can be promoted from `dev → test → staging → prod` through the Deployment Manager. Promotion to staging or prod is blocked unless approval is granted and unless all secret references resolve through the vault.
6. A workflow scheduled via cron runs durably on Temporal, survives a forced worker kill, retries failed nodes per policy, and resumes cleanly.
7. Approval nodes pause workflows; an authorized approver clicks Approve in the UI and the workflow resumes within five seconds.
8. The Code/DevOps Agent (OpenHands SDK), when invoked from MCP Studio to "generate an MCP server for X", produces a runnable MCP server scaffold plus tests in the sandboxed workspace, and the result is committed to Git as a PR.
9. OPA policy correctly blocks a tool call whose risk level exceeds the agent's `max_risk_level_without_approval` until an Approval record is created.
10. The Test Runner can execute a test suite against an agent, MCP tool, and workflow and report pass/fail in the Deployment Manager pipeline.
11. PRD Scenario 27.2 (debug failed MCP server) is demonstrable end-to-end on the platform.
12. PRD Scenario 27.3 (import workflow template) is demonstrable end-to-end on the platform.
13. Zero-downtime migration from the Phase 1 in-process runner to the Phase 2 Temporal runner is performed in a staging environment.

The PRD Section 28 acceptance criteria fully met by Phase 2: "Users can create/update/delete/debug/deploy/copy agents", "Users can create/update/delete/debug/deploy/copy MCP servers", "Users can create/update/delete/debug/deploy/copy workflows", "Users can create/import/export templates", "Assets are versioned", "Rollback is supported", "Secrets are never exported".

---

## Notes

- **Build Priority Alignment.** Phase 2 covers all eight items of Build Priority 2 from PRD Section 30 (versioning and rollback, deployment manager, approval queue, test runner, template import/export, secret vault integration, policy engine, observability integration — the last is split: basic OPA and basic vault land here; full enterprise observability and audit reports land in Phase 3/5).
- **OpenHands SDK sandbox is non-negotiable.** Per PRD Section 24.5.9: "Run OpenHands SDK in sandboxed Docker/Kubernetes workspaces controlled by policy and approval. Do not allow unrestricted shell access in production." The Phase 2 design must enforce this from day one.
- **Temporal vs LangGraph relationship.** Per PRD Section 24.5.6 and 24.5.7: LangGraph remains the agent runtime; Temporal is the durable workflow engine that wraps it. LangGraph agents run as Temporal Activities or child workflows. Workflow Studio exports executable workflow definitions that run through Temporal workers.
- **MCP is a tool interface, not an orchestrator (PRD Section 24.1).** Phase 2 explicitly forbids MCP servers from orchestrating other MCP servers. The orchestrator (LangGraph + Temporal) controls the full workflow.
- **Risk: Template imports could carry malicious payloads.** Mitigation per PRD Section 29: signature validation (enforced in Phase 3), schema validation, secret scanning. Phase 2 ships schema + secret scanning; signature enforcement is wired but defaults to permissive until Phase 3.
- **Risk: OpenHands SDK generates broken code.** Mitigation: every OpenHands output must pass the Test Runner before being merged. The Code/DevOps Agent can iterate up to a configurable retry budget.
- **Risk: Temporal complexity.** Mitigation: hide Temporal behind a `WorkflowEngine` interface; developers build on top of the Phase 1 run API; Temporal-specific knowledge stays inside the workflow worker package.
- **Risk: Approval bottleneck.** Mitigation: configurable approval timeouts; "delegated approver" lists; metrics on approval latency (OBS metric from PRD Section 22.2 — "Approval latency").
- **Phase 2 does not yet support real ArcGIS, Database, File/PDF, or Email MCP servers.** Those land in Phase 3. Phase 2 demonstrations use a "mock-enterprise" MCP server bundle that exposes deterministic tools mimicking the real systems' interfaces, so workflow shapes can be developed in parallel with Phase 3 integration work.
