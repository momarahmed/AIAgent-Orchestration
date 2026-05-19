# Phase 4: Advanced Multi-Agent Platform

> **Source PRD:** Enterprise AI + MCP + Multi-Agent + Workflow Builder Platform — PRD v1.1 (Technology-Mapped Edition), dated 2026-05-18.
> **Phase Sequence:** 4 of 5
> **Theme:** Turn the platform from a strong single-team automation tool into a true multi-agent ecosystem. Ship the A2A gateway, scale the event bus, productionize the Model Control Plane, build the Memory/RAG layer, fully activate the meta-agents, and add the cross-framework bridges (Dify, Flowise, SIM, CrewAI).

---

## Objective

Phase 1 built the core. Phase 2 made it operable. Phase 3 made it production-grade and integrated. Phase 4 makes it **a platform for building platforms**:

- Agents can compose, delegate, and federate — both internal-to-internal (orchestrator-mediated) and external (via the A2A gateway to other frameworks and vendor systems).
- Events flow at scale through Kafka (or comparable), enabling reactive workflows, multi-workflow choreography, and high-throughput agent fan-out.
- A full Model Control Plane gives the platform intelligent routing, fallback, prompt registry, evaluation tracking, and cost/budget enforcement across all providers.
- Long-term memory and RAG retrieval are first-class for every agent and project, backed by Qdrant and the Memory Service.
- The meta-agents — Platform Architect, Agent Builder, MCP Builder, Workflow Builder, Template Manager, QA/Test, Security Review, DevOps Deployment, Documentation, Migration, Governance — operate autonomously within policy, turning natural-language requests into deployed assets.
- Bridges to Dify, Flowise, SIM, and CrewAI let the platform interoperate with neighboring ecosystems.

Phase 4 is where the platform becomes the **"factory for building and operating AI-powered enterprise automation systems"** described in the PRD's Executive Summary.

---

## Scope

In scope for this phase:

- **A2A Gateway** — Agent-to-Agent communication for external/vendor/cross-framework agents (PRD Section 13.2, Reference: A2A Protocol).
- **Event Bus at scale** — Kafka cluster (or equivalent) replaces the Phase 2 NATS/Redis Streams baseline; supports asynchronous agent events, tool events, workflow events at high throughput.
- **Full Model Control Plane** — Model Registry, Model Router, Local LLM and Cloud LLM, Fallback, Cost Control, Token Budget, Prompt Registry, Evaluation Scores (PRD Section 9.2).
- **Memory / Knowledge Layer** — Short-Term Memory, Long-Term Memory, Vector DB (Qdrant), RAG patterns, Knowledge Graph metadata, Agent/Workflow/User/Project memory scoping (PRD Section 9.2).
- **Meta-Agents productionized** — Platform Architect, Agent Builder, MCP Builder, Workflow Builder, Template Manager, QA/Test, Security Review, DevOps Deployment, Documentation, Migration, Governance (PRD Section 12.2).
- **Advanced observability** — Full OpenTelemetry traces/metrics/logs (PRD Section 22), execution timelines, replay, alerting, cost dashboards.
- **Dify Bridge** — Optional integration as an LLM/RAG accelerator (PRD Section 24.5.4).
- **Flowise Bridge / Importer** — Optional flow import and prototype bridge (PRD Section 24.5.3).
- **SIM Bridge / Importer** — Optional AI workforce interoperability (PRD Section 24.5.1).
- **CrewAI template runtime** — Optional simple crew template runtime (PRD Section 24.5.13).
- **Local LLM routing** — Ollama / vLLM adapter inside the Model Control Plane for on-prem/local model use.
- **Migration Agent** — Convert workflows from n8n / Flowise / Dify / JSON / YAML where possible (PRD Section 12.2).

Out of scope for this phase (deferred):

- Template marketplace (internal + external) (→ Phase 5).
- GitOps deployment via Argo CD / Flux (→ Phase 5).
- HA/DR, multi-region (→ Phase 5).
- Advanced analytics on usage, cost, and reliability across tenants (→ Phase 5).
- Enterprise connector library expansion beyond Phase 3 (→ Phase 5).
- AutoGen import (→ Phase 5, only if customer demand exists).

---

## Key Requirements

### Functional Requirements (from PRD Section 17)

User Experience additions:

| ID | Requirement | Priority |
|---|---|---|
| UX-009 | User can comment on workflows/assets | Could |

Runtime Execution (full advanced set):

All RT-001 through RT-010 from PRD Section 17.5 are fully satisfied by combining Phase 2 (durable runtime) with Phase 4 (parallel branches at scale via event bus, advanced result aggregation, full validator agent).

Observability (full set):

| ID | Requirement | Priority |
|---|---|---|
| OBS-001 | Capture logs for every agent/tool/workflow run | Must |
| OBS-002 | Capture metrics | Must |
| OBS-003 | Capture traces | Must |
| OBS-004 | Display execution timeline | Must |
| OBS-005 | Track token and model cost | Must |
| OBS-006 | Support replay of workflow runs | Must |
| OBS-007 | Alert on failures | Should |
| OBS-008 | Export logs/traces to external observability stack | Should |

Agent Communication Layer (PRD Section 13):

| Requirement | Phase 4 Implementation |
|---|---|
| Orchestrator-mediated messages | LangGraph supervisor (built in Phases 1–2) |
| Shared workflow state | Temporal + state store (Phases 1–2) |
| Event bus | Kafka cluster (Phase 4) |
| A2A gateway | New A2A service (Phase 4) |
| Direct agent message | A2A gateway with policy control (Phase 4) |

Memory / Knowledge Layer:

| Requirement | Phase 4 Implementation |
|---|---|
| Short-Term Memory | Redis (production-grade cluster) |
| Long-Term Memory | PostgreSQL + object storage |
| Vector DB / RAG | Qdrant cluster (introduced for File/PDF MCP in Phase 3; scaled and tenant-isolated in Phase 4) |
| Knowledge graph metadata | PostgreSQL with graph extensions or a dedicated graph store |
| Memory scoping | Agent / Session / Project / Tenant (per agent config field) |

Model Control Plane:

| Component | Phase 4 Implementation |
|---|---|
| Model Registry | New service backed by PostgreSQL |
| Model Router | Smart routing rules (capability, latency, cost) |
| Local LLM | Ollama / vLLM adapter |
| Cloud LLM | OpenAI / Anthropic / Google adapters (productionized from Phases 2–3) |
| Fallback | Provider failover with circuit breakers |
| Cost Control | Per-tenant / per-project budgets enforced |
| Token Budget | Per-call/per-day/per-agent budgets |
| Prompt Registry | Versioned prompt library |
| Evaluation Scores | Stored alongside model versions and prompts |

### Non-Functional Requirements

| Category | Requirement (Phase 4 target) |
|---|---|
| Scalability | Event bus + workflow workers + agent runtimes auto-scale; load-tested to 10× Phase 3 throughput. |
| Performance | Memory retrieval (RAG lookup) returns top-k within 200ms p95 for project-scoped queries. |
| Reliability | A2A gateway failures degrade gracefully — internal multi-agent flows continue to work even when external A2A endpoints are unavailable. |
| Auditability | A2A messages are logged with from/to/conversation/run identifiers per PRD Section 13.3. |
| Extensibility | New provider adapters, new bridges, new memory backends all plug in behind interfaces without core changes. |
| Maintainability | Meta-agent outputs are reproducible — the same prompt with the same prompt-registry version produces deterministic asset specs (modulo LLM nondeterminism, which is bounded). |

---

## User Stories / Use Cases

US-4.1 — As a **Business User**, I want to type "Investigate why our ArcGIS Portal had an outage yesterday between 14:00 and 16:00, summarize the database, monitoring, and ticket evidence, and propose a remediation runbook" and have the Platform Architect Agent decompose this into specialist agents (Monitoring, Database, GIS, ITSM, Report), run them in parallel, aggregate their findings, validate the synthesis, and produce a runbook.

US-4.2 — As an **AI Agent Designer**, I want my agent to remember past conversations within the project scope and to retrieve relevant prior decisions via RAG, so that it can refer back to "what we decided about retention three weeks ago".

US-4.3 — As a **Platform Admin**, I want to set a monthly model budget of $X per tenant and have the Model Control Plane automatically route to cheaper or local models once 80% of the budget is consumed, with notifications to the tenant owner.

US-4.4 — As an **Integration Architect**, I want my platform to talk to an external partner agent (exposed via A2A) and to a CrewAI-based team I prototype internally, with the same governance, audit, and observability as my native LangGraph agents.

US-4.5 — As an **MCP Developer**, I want to type "Build me an MCP server that wraps our Inventory API and create an Inventory Audit Agent that uses it, plus a daily workflow that reports anomalies" and have the meta-agents (Platform Architect + MCP Builder + Agent Builder + Workflow Builder + QA + Security Review + Documentation + DevOps Deployment) collaboratively produce the deployed assets — gated by approvals at the appropriate steps.

US-4.6 — As a **Reliability Engineer**, I want event-driven workflows: when an alert fires in monitoring, the platform triggers an Investigation workflow automatically through the event bus, fans out to specialist agents in parallel, and posts results to a Slack channel.

US-4.7 — As a **GIS Administrator with an existing Flowise prototype**, I want to import my Flowise flow into the platform's workflow builder where possible, retaining as much of the original logic as the Migration Agent can reasonably translate.

### Anchor Scenarios

PRD Scenario 27.1 (Create GIS Health Agent) becomes the **happy path** for the meta-agent system in Phase 4. The full chain — Intent Router → Platform Architect Agent → Agent Builder Agent → MCP Builder Agent → Workflow Builder Agent → QA Agent → Security Agent → Deployment Agent → Approval Manager → Scheduler — runs autonomously within policy, producing deployed, tested, governed assets from a single natural-language prompt.

A new scenario emerges in Phase 4: a **federated investigation** where the platform's Monitoring Agent (LangGraph) coordinates with an external partner's Incident-Response agent (via A2A) and an internal CrewAI team handling change management. All three operate under the same OPA policies, audit log, and observability stack.

---

## Features

### 1. A2A Gateway (PRD Section 13.2)

- Implements the A2A Protocol (per the reference link in PRD Section 32 — `https://a2a-protocol.org/latest/`).
- Bidirectional: external agents can register and be called; internal agents can call out under policy.
- Every A2A message is logged with the schema from PRD Section 13.3 (`message_id`, `conversation_id`, `workflow_run_id`, `from_agent`, `to_agent`, `message_type`, `priority`, `payload`, `created_at`).
- OPA policies (added to the Phase 3 library) govern who can talk to whom across A2A boundaries.

### 2. Event Bus at Scale

- Kafka cluster (managed or self-hosted) replaces the Phase 2 NATS/Redis Streams baseline.
- Topic taxonomy: `agent.events`, `tool.events`, `workflow.events`, `run.status`, `deployment.events`, `approval.events`.
- Kafka Connect / Kafka Streams for downstream observability and analytics consumers.
- Webhook/event-driven workflow triggers (WF-012, Should-priority from Phase 2) become Must-priority in Phase 4 and are wired to Kafka events.
- Backward-compatible: existing NATS/Redis Streams paths continue to function for low-volume use cases.

### 3. Full Model Control Plane

- **Model Registry** — model metadata, capabilities, cost-per-token, context window, latency profile.
- **Model Router** — picks the right model per call based on capability needs, cost budget, latency requirement, and tenant policy.
- **Provider Adapters** — OpenAI Agents SDK, Claude Agent SDK, Google ADK (all from Phases 2–3) + Ollama/vLLM local adapter + future providers.
- **Fallback** — Primary model unavailable → fallback chain with circuit breakers; latency-based degradation.
- **Cost Control** — Per-tenant budgets, per-project budgets, per-agent budgets, soft warnings + hard caps.
- **Token Budget** — Per-call max tokens, per-day quota.
- **Prompt Registry** — Versioned prompts with diff, A/B testing hooks, and links back to evaluation results.
- **Evaluation Scores** — Stored test results per (prompt × model × dataset) tuple, surfaced when a designer picks a model for an agent.

### 4. Memory / Knowledge Layer

- **Short-Term Memory** — Redis-backed conversation/session state with TTLs.
- **Long-Term Memory** — PostgreSQL + object storage; structured + unstructured.
- **Vector DB (Qdrant)** — Tenant-scoped collections; project-scoped namespaces; per-agent retrieval policies; embedding model abstracted via Model Control Plane.
- **RAG Patterns** — Retrieval-Augmented Generation node in Workflow Builder; pluggable retrieval strategies (similarity, hybrid, rerank).
- **Knowledge Graph Metadata** — Lightweight graph store for agent capability discovery and template dependency graphs.
- **Memory Scoping** — Honors the `memory_scope` field on every agent: none / session / project / tenant.

### 5. Meta-Agents (PRD Sections 8.2, 12.2)

All meta-agents productionized, each built on LangGraph + OpenHands SDK + the Code/DevOps MCP Server from Phase 3:

| Meta-Agent | Purpose | Phase 4 Capability |
|---|---|---|
| Platform Architect Agent | Designs complete solutions from prompt | Decomposes prompts into agent/MCP/workflow/template specs; produces an architecture diagram and an approval-ready plan. |
| Agent Builder Agent | Creates/updates/debugs/deploys/copies agents | Reads prompts, generates agent specs, runs tests, deploys to dev, requests approval for prod. |
| MCP Builder Agent | Creates/updates/debugs/deploys/copies MCP servers | Generates MCP server code via OpenHands SDK, validates schemas, runs the Phase 3 security scanners, deploys. |
| Workflow Builder Agent | Creates/updates/debugs/deploys/copies workflows | Converts prompts and partial graphs into executable workflow specs; round-trips with the React Flow UI. |
| Template Manager Agent | Creates/imports/exports templates | Manages template lifecycle and resolves dependencies automatically. |
| QA/Test Agent | Creates and executes tests | Generates test suites from specs; runs them in CI; explains failures. |
| Security Review Agent | Risk and permission review | Cross-references generated assets against OPA policies before deployment. |
| DevOps Deployment Agent | Packages, deploys, validates, rolls back | Calls Helm + CI/CD + Kubernetes; runs smoke tests; auto-rollback on regression. |
| Documentation Agent | Generates docs, diagrams, changelogs, READMEs | Maintains living documentation alongside every asset version. |
| Migration Agent | Imports n8n/Flowise/Dify/JSON/YAML workflows | Parses external formats and produces best-effort native workflow specs with a confidence score and a human-review checklist. |
| Governance Agent | Checks compliance with enterprise policies | Periodic compliance audits, evidence collection, drift detection. |

Every meta-agent action is gated by approvals according to its tool-call risk levels.

### 6. Advanced Observability

- Full OpenTelemetry instrumentation across every service.
- Per-run distributed trace matching the PRD Section 22.3 trace structure.
- Pre-built Grafana dashboards: workflow success rate, workflow duration, tool failure rate, agent error rate, token usage, model cost, approval latency, deployment failure rate, template usage (PRD Section 22.2).
- Alerting on failures and SLO breaches.
- Replay: re-execute a previous run with the same inputs and pinned model versions.

### 7. Cross-Framework Bridges

- **Dify Bridge** (PRD Section 24.5.4) — Call Dify apps/workflows as workflow nodes; expose Dify knowledge bases as MCP resources.
- **Flowise Bridge / Importer** (PRD Section 24.5.3) — Import Flowise flows where possible; call external Flowise as a node.
- **SIM Bridge / Importer** (PRD Section 24.5.1) — If a SIM API is available, run SIM agents/workflows as nodes.
- **CrewAI Template Runtime** (PRD Section 24.5.13) — Run simple crew-pattern templates inside the platform under the same governance.

All bridges are **optional**, gated behind a feature flag per tenant, and pass through the same RBAC/OPA/audit pipeline as native MCP tools.

### 8. Local LLM Routing

- Ollama / vLLM adapter exposes local models through the Model Router.
- Use cases: cost-sensitive workloads, data-residency-sensitive workloads, dev/test routing to a local model.
- Latency and quality metrics tracked per local model to inform routing decisions.

### 9. Comment & Collaboration (UX-009, Could-priority)

- Comments on workflows, agents, MCP servers, templates, and runs.
- @mentions notify users via email or Teams/Slack (using the Phase 3 Email MCP + Activepieces).

---

## Deliverables

1. **A2A Gateway Service** with full A2A Protocol support, OPA-governed federation, and audit logging per PRD Section 13.3.
2. **Kafka-based Event Bus** with topic taxonomy, producers, consumers, and Kafka Connect to observability stack.
3. **Model Control Plane Service** — Model Registry, Model Router, Provider Adapters (OpenAI, Claude, Google, Ollama/vLLM), Cost Control, Token Budget, Prompt Registry, Evaluation Scores.
4. **Memory Service** with Short-Term (Redis), Long-Term (PG + object storage), Vector DB (Qdrant) scaled and tenant-isolated, and RAG retrieval API.
5. **Knowledge Graph store** (lightweight) for capability and dependency mapping.
6. **All eleven meta-agents** productionized and integrated into the Intent Router and the meta-agent orchestrator.
7. **Migration Agent** with parsers for n8n, Flowise, Dify, generic JSON, and generic YAML workflow formats.
8. **Advanced observability stack** — full OpenTelemetry instrumentation, Grafana dashboards, Prometheus alerting, Loki log aggregation, replay capability.
9. **Dify, Flowise, SIM, CrewAI bridges** as optional, feature-flagged integrations.
10. **Local LLM adapter** (Ollama / vLLM) integrated into Model Router.
11. **Comment & collaboration feature** on assets and runs (Could-priority).
12. **End-to-end meta-agent demo** showing PRD Scenario 27.1 ("Create GIS Health Agent…") executing **autonomously** from a single prompt through to a deployed, scheduled production workflow.
13. **Capacity and load-test report** demonstrating 10× Phase 3 throughput.

---

## Dependencies

Depends on Phase 1, 2, and 3 deliverables:

- All Phase 3 governance infrastructure (RBAC/ABAC, OPA policy library, vault hardening, audit log, tool sandbox, security scanner, Kubernetes deployment) is a prerequisite. The Phase 4 A2A, event bus, model control plane, memory, and meta-agents all build on this base.
- The Phase 3 ArcGIS / Database / File-PDF / Email / Monitoring / ITSM / Code-DevOps / Browser-CUA MCP servers are prerequisites for meta-agent end-to-end demos.

External dependencies:

- Kafka cluster (managed Confluent Cloud, AWS MSK, Azure Event Hubs Kafka, or self-hosted).
- Qdrant cluster (managed or self-hosted) at production scale.
- Ollama or vLLM infrastructure for local model serving (GPU-enabled nodes).
- Access credentials for any external A2A partners.
- Optional Dify, Flowise, SIM endpoints if those bridges will be used.

Internal team dependencies:

- Security team reviews A2A federation policies before any external partner can be onboarded.
- Data engineering team owns Kafka cluster operations.
- ML team owns the local LLM infrastructure and prompt registry curation.

Cross-phase dependencies:

- Phase 5 (Scale, Marketplace, Productization) depends on Phase 4's Model Control Plane (for cost governance) and on the meta-agents (for the marketplace template-quality-review automation).

---

## Acceptance Criteria

Phase 4 is complete when **all** of the following are true:

1. PRD Scenario 27.1 runs **autonomously end-to-end** from a single natural-language prompt: meta-agents collaborate, generate agents/MCP-tool-bindings/workflows/tests, request appropriate approvals, deploy to dev, run tests, request prod approval, and schedule the daily run.
2. The A2A Gateway accepts and emits A2A-compliant messages; an external partner agent can be invoked from a workflow under policy and audit, and a workflow can be invoked by an external A2A caller.
3. Kafka event bus handles a sustained load of 10× the Phase 3 baseline with p99 publish latency under 100ms.
4. The Model Control Plane routes calls intelligently across at least four providers (OpenAI, Anthropic, Google, local Ollama); a per-tenant budget cap demonstrably forces a route change when threshold is hit.
5. A prompt registered in the Prompt Registry with two versions is A/B-tested across two models, and evaluation scores are stored and surfaced in the Agent Studio UI.
6. RAG retrieval on a project-scoped Qdrant collection returns top-k in under 200ms p95, with cross-tenant access blocked and audited.
7. Memory scoping (`none` / `session` / `project` / `tenant`) is honored by the runtime — verified by an automated test that an agent with `memory_scope: none` cannot recall prior conversation content.
8. The Migration Agent successfully imports at least one non-trivial Flowise flow and one non-trivial n8n workflow into the platform's native workflow format, with the resulting native workflow runnable after minor human edits.
9. Dify, Flowise, SIM (if API available), and CrewAI bridges each have at least one end-to-end demo executing under platform governance.
10. The advanced observability stack shows a complete distributed trace for the Scenario 27.1 run, with the trace structure matching PRD Section 22.3.
11. The Documentation Agent generates and maintains current README + architecture diagrams + changelogs for every asset; an automated check verifies docs are within 24 hours of the latest asset version.
12. The capacity and load-test report is approved by the Platform Owner and ops team.
13. All eleven meta-agents pass their per-agent test suites (test coverage ≥ 85% on meta-agent code paths).

Phase 4 also satisfies PRD Section 28 acceptance criteria: "Runs produce logs and traces" (now with full distributed tracing) and provides the foundation needed by Phase 5 for the marketplace.

---

## Notes

- **Build Priority Alignment.** Phase 4 covers Build Priority 3 items 1–7 from PRD Section 30 (A2A gateway, event bus, meta-agent automation, model control plane, RAG/vector memory, marketplace foundation — marketplace itself ships Phase 5, but the prerequisites here, GitOps groundwork).
- **PRD Section 24.5.6 (LangGraph) and 24.5.7 (Temporal) remain the orchestration core.** Phase 4 does **not** replace them. LangGraph + Temporal remain the agent runtime and durable engine; meta-agents are LangGraph agents that produce LangGraph + Temporal artifacts.
- **MCP servers do not orchestrate (PRD Sections 13.1, 24.1).** This rule is re-emphasized in Phase 4 because Dify, Flowise, and SIM bridges might tempt teams to push orchestration responsibilities into bridged systems. The platform's LangGraph supervisor remains the only orchestrator.
- **A2A scope.** PRD Section 24.5 makes clear A2A is "for agent-to-agent communication" and not a workflow engine. The A2A Gateway in Phase 4 federates communication; the workflow engine (Temporal) and the orchestrator (LangGraph) remain in control of execution flow.
- **Risk: Meta-agent runaway actions.** Mitigation: every meta-agent action is bound to OPA policies inherited from Phase 3; risky steps (deploy to prod, grant admin tool access, import unsigned template) require human approval; budget caps prevent expensive autonomous loops; the Security Review Agent vets each meta-agent output.
- **Risk: A2A federation widens attack surface.** Mitigation: A2A partner agents must be allowlisted in OPA; all A2A traffic flows through the gateway (no direct agent-to-external connections); A2A messages are scanned for prompt injection.
- **Risk: Bridge sprawl.** PRD Section 24.1 explicitly warns: "Avoid tool sprawl. Dify, Flowise, SIM, Tesslate Agent Builder, CrewAI, ADK, OpenAI Agents SDK, and Claude Agent SDK should not all become core. Use them selectively." Phase 4 ships all bridges but defaults each to disabled per tenant; the platform owner enables a bridge only when there is a concrete user need.
- **Risk: Memory leakage across tenants.** Mitigation: Qdrant collections strictly tenant-scoped (Phase 3 design); tenant-ID enforcement re-tested in Phase 4 under the larger Memory Service; automated cross-tenant retrieval test continues in CI.
- **Risk: Kafka complexity.** Mitigation: managed Kafka in prod where possible; Kafka knowledge confined to a Platform Eventing team; consumers built on top of a thin `EventBus` interface; NATS/Redis-Streams path remains as a fallback for low-volume use.
- **AutoGen explicitly NOT a Phase 4 deliverable.** Per PRD Section 24.5.14: "Current official GitHub result indicates AutoGen is in maintenance mode. Build a future AutoGen import adapter only if customers already have AutoGen workflows." AutoGen is therefore moved to Phase 5 (legacy import only, if demanded).
- **Local LLM emphasis.** Phase 4 makes local LLM routing first-class. Customer data-residency and cost-sensitivity drivers are real; the platform must be able to run a meaningful workload on Ollama/vLLM without losing observability or governance.
- **Prompts as versioned assets.** The Prompt Registry treats prompts as first-class versioned assets — same lifecycle pattern as agents, MCP servers, workflows. This is an enhancement to the PRD: the PRD lists "Prompt Registry" under the Model Control Plane (Sections 9.2, 24.4) but does not detail its lifecycle. Phase 4 makes prompt lifecycle explicit.
