# Phase 3: Enterprise Governance & Integrations

> **Source PRD:** Enterprise AI + MCP + Multi-Agent + Workflow Builder Platform — PRD v1.1 (Technology-Mapped Edition), dated 2026-05-18.
> **Phase Sequence:** 3 of 5
> **Theme:** Make the platform production-grade for one enterprise. Land the real enterprise MCP servers (ArcGIS, Database, File/PDF, Email), the connector hub (Activepieces), full RBAC/ABAC, policy-as-code, vault hardening, tool sandbox, security scanners, audit exports, and Kubernetes-based deployment.

---

## Objective

Take the operable platform from Phase 2 and harden it for **one** enterprise's production use. By the end of Phase 3:

- A real customer (GIS Administrator, DBA, Security Officer) can rely on the platform to monitor ArcGIS Enterprise, run safe SQL against production databases, extract data from PDFs/Office documents, and send emails — all governed by enterprise IAM, fine-grained RBAC/ABAC, policy-as-code, vault-managed secrets, and a complete audit trail.
- The platform is deployable on Kubernetes (not just Docker Compose), with a CI/CD pipeline that builds, scans, signs, and promotes images.
- The Activepieces bridge unlocks a large SaaS connector library exposed as governed MCP tools.
- OpenAI and Claude provider adapters from Phase 2 are joined by tuning, telemetry, and per-tenant cost controls.

Phase 3 makes the platform **enterprise-deployable**, but still single-tenant-grade. Multi-tenant scale, marketplace, A2A federation, and the full Model Control Plane land in Phases 4–5.

---

## Scope

In scope for this phase:

- **Real enterprise MCP servers** (Phase 1/2 used mocks): ArcGIS, Database, File/PDF/RAG, Email/Notification, Monitoring (basic), ITSM (basic), Code/DevOps.
- **Activepieces bridge** — call governed Activepieces flows from workflows; expose selected Activepieces pieces as MCP tools.
- **Provider adapters expanded** — OpenAI Agents SDK, Claude Agent SDK (from Phase 2), plus Google ADK adapter; per-tenant budget enforcement.
- **Full RBAC/ABAC** — fine-grained roles, project/asset/environment scoping, attribute-based policies.
- **Policy-as-code in OPA** — authoring UI, policy library (tool risk, deployment, approval, tenant isolation).
- **Vault hardening** — credential rotation, dynamic secrets where supported, automatic secret refresh, secret scanning on every export/import.
- **Audit reports** — exportable evidence for compliance (who-did-what-when across all assets and runs).
- **Tool Sandbox** — strict isolation for risky tools (Browser/CUA, Script/Code, DevOps).
- **Security Scanner pipeline** — Trivy, Syft/Grype, secret scanners on containers, templates, generated code, dependencies.
- **Kubernetes deployment** — Helm charts for the full platform, CI/CD pipeline running on GitHub Actions/GitLab CI/Azure DevOps.
- **Network policies and allowlists** — per MCP server, per environment.
- **Prompt-injection detection** (basic heuristics + LLM guardrails — Should-priority).

Out of scope for this phase (deferred):

- A2A gateway for cross-platform/external agent communication (→ Phase 4).
- Event bus at scale (Kafka cluster, async fan-out) (→ Phase 4).
- Full Model Control Plane with smart routing, evaluation, prompt registry UI (→ Phase 4).
- RAG/memory layer at scale, knowledge graphs, semantic memory tuning (→ Phase 4).
- Meta-agents acting fully autonomously to build new agents/MCP servers/workflows (→ Phase 4 productionization; Phase 3 only has the basic Agent/MCP/Workflow Builder Agents from Phase 2).
- Marketplace, GitOps with Argo CD/Flux, HA/DR, multi-region (→ Phase 5).

---

## Key Requirements

### Functional Requirements (from PRD Section 17)

User Experience additions:

| ID | Requirement | Priority |
|---|---|---|
| UX-007 | User can view audit history per asset | Must |

Governance and Security (full set):

| ID | Requirement | Priority |
|---|---|---|
| SEC-001 | Support SSO/OIDC/OAuth | Must |
| SEC-002 | Support RBAC and ABAC | Must |
| SEC-003 | Enforce tool-level permissions | Must |
| SEC-004 | Require approval for high-risk tools | Must |
| SEC-005 | Store secrets in vault only | Must |
| SEC-006 | Redact secrets from logs/templates | Must |
| SEC-007 | Maintain audit trail for every asset/action | Must |
| SEC-008 | Sandbox risky tools | Must |
| SEC-009 | Enforce network allowlists | Should |
| SEC-010 | Support policy-as-code | Must |
| SEC-011 | Detect prompt injection attempts | Should |
| SEC-012 | Support tenant isolation | Must |

Observability — adding cost and external export:

| ID | Requirement | Priority |
|---|---|---|
| OBS-005 | Track token and model cost | Must |
| OBS-007 | Alert on failures | Should |
| OBS-008 | Export logs/traces to external observability stack | Should |

### Non-Functional Requirements

| Category | Requirement (Phase 3 target) |
|---|---|
| Availability | Production-grade target of 99.5%+ availability per PRD Section 18. |
| Scalability | Horizontal scaling for API, agent workers, MCP gateways, workflow workers — verified via load test. |
| Security | Secrets never appear in prompts, templates, exports, or logs — enforced by automated export scanner. |
| Auditability | Every create/update/delete/deploy/tool-call/approval/import/export is auditable and exportable as evidence. |
| Compliance | Policy, approval, audit, and evidence export support a baseline compliance posture (SOC 2 readiness). |
| Portability | Platform deploys on Kubernetes across at least two clouds (AWS + Azure, or equivalent) using the same Helm charts. |

---

## User Stories / Use Cases

US-3.1 — As a **GIS Administrator**, I want to ask the platform "Check the health of our ArcGIS Portal, list any stopped services, look at the data store, and email the on-call DBA with the report" and have the platform execute it safely using the real ArcGIS MCP Server, real Database MCP Server, and real Email MCP Server, with the email step gated by approval if external recipients are involved.

US-3.2 — As a **Database Administrator**, I want all SQL executed through the Database MCP Server to be statically analyzed for safety (no `DROP`, no unbounded `UPDATE`, no privileged schema changes) before running, and to require approval for any L2+ action.

US-3.3 — As a **Security Officer**, I want to author OPA policies in the Admin Console — for example, "Production deployments to the ArcGIS MCP Server require both Security Approver and Platform Owner sign-off" — and see those policies enforced at runtime.

US-3.4 — As a **Platform Admin**, I want to export an audit report for the past 30 days showing every asset modification, every deployment, every approval decision, every high-risk tool call, and every template import/export, in CSV and PDF formats.

US-3.5 — As an **AI Agent Designer**, I want to use Activepieces SaaS connectors (Slack, Teams, Salesforce, Jira, GitHub, etc.) inside my workflows by selecting them like any other MCP tool, with the platform enforcing the same risk-level policies and approvals.

US-3.6 — As a **DevOps Engineer**, I want to deploy the platform to a fresh Kubernetes cluster using Helm charts from our private registry, with all images signed and scanned, all secrets sourced from the cluster's Vault, and the full CI/CD pipeline producing deployment evidence.

US-3.7 — As a **Business User**, I want my prompts and tool inputs scanned for prompt-injection patterns and to be warned if my input contains suspicious content from an uploaded document.

### Anchor Scenario

PRD Scenario 27.1 (Create GIS Health Agent that checks Portal, Server, Data Store, DB, logs, creates a report, emails it every morning) becomes **fully real** in Phase 3 — no mocks, no placeholders. Every component along the path is governed: tool risk enforced via OPA, secrets resolved via Vault, deployment promoted via the Phase 2 Deployment Manager (now with real Trivy/Syft/Grype scans), and the entire run produces an immutable audit trail.

---

## Features

### 1. Real Enterprise MCP Servers (PRD Sections 14.2, 24.8)

Built on the MCP Python SDK (PRD Section 24.5.8), each deployed as a containerized service registered in the MCP Gateway.

#### 1.1 ArcGIS MCP Server

- Tools: `portal_health`, `list_services`, `restart_service` (L2, approval-gated), `check_datastore`, `query_logs`, `inspect_item` (L0), `delete_item` (L3, mandatory approval per PRD Section 14.3).
- Auth: OAuth or service account; secrets via vault references.
- Network policy: allowlist Portal and Server hostnames per environment.
- Built using ArcGIS REST API / ArcGIS Python API.

#### 1.2 Database MCP Server

- Tools: `check_db_health`, `inspect_schema`, `run_safe_query` (with SQL safety analyzer), `analyze_slow_queries`, `explain_plan`.
- Auth: managed identity / connection string in vault.
- Enforces an allowlist of databases and schemas per agent.
- SQL safety policies block destructive verbs by default; risk-level upgrade required for `UPDATE`/`DELETE` on flagged tables.
- Built using SQLAlchemy and async DB drivers (PostgreSQL, SQL Server, Oracle).

#### 1.3 File/PDF MCP Server

- Tools: `read_pdf`, `read_docx`, `read_xlsx`, `extract_tables`, `create_report` (Markdown/HTML/PDF/DOCX), `export_docx`, `export_pdf`.
- Connects to Qdrant for chunk indexing and RAG retrieval used by File/Document Agents.
- Object storage backend for inputs and generated reports.

#### 1.4 Email/Notification MCP Server

- Tools: `create_draft` (L1), `send_email` (L1 internal recipients / L2 external recipients), `notify_user`.
- Approval required for external recipients (per PRD Section 21.3).
- Built on SMTP, Microsoft Graph, or Activepieces email pieces.

#### 1.5 Monitoring MCP Server (basic)

- Tools: `query_logs` (Loki), `get_metrics` (Prometheus), `analyze_alerts`.
- Read-only — L0/L1 risk only in Phase 3.

#### 1.6 ITSM MCP Server (basic)

- Tools: `create_ticket`, `update_ticket`, `attach_report` against Jira and ServiceNow.
- Built using REST APIs (or via Activepieces pieces).
- The platform creates/updates tickets but does not replace the ITSM (PRD Section 4.2).

#### 1.7 Code/DevOps MCP Server (productionized from Phase 2)

- Tools: `repo_edit`, `generate_code`, `run_tests`, `create_pr`, `run_pipeline`, `deploy_container`, `rollback_release`.
- Runs OpenHands SDK in a strictly sandboxed Kubernetes namespace.
- Required for the meta-agents that productionize in Phase 4.

### 2. Activepieces Bridge (PRD Section 24.5.5, 24.8)

- Adds Activepieces as an **Integrated Accelerator** (PRD Section 24.2 — "Integrated Accelerator" status).
- Two integration directions:
  1. **Workflow Node** — A workflow can call a governed Activepieces flow as a single node.
  2. **MCP Tool Exposure** — Selected Activepieces pieces (e.g., Slack, Teams, Salesforce, Jira) are wrapped as MCP tools and registered in the Tool Registry.
- Reduces the need to build hundreds of SaaS connectors from scratch (PRD Section 24.5.5).

### 3. Provider Adapters Completed

- Google ADK adapter added to the OpenAI Agents SDK and Claude Agent SDK adapters from Phase 2.
- Per-tenant model budgets enforced (rejection on quota exhaustion with clear user message).
- Telemetry per provider: latency, token count, error rate, cost.

### 4. Full RBAC/ABAC

- Roles from PRD Section 21.2 fully wired: Viewer, Runner, Builder, Publisher, Admin, Security Approver, Platform Owner.
- Attribute-based policies layered on top: e.g., "Builders can edit agents only in their own project", "Security Approvers can approve only L3+ actions for projects they are assigned to".
- Project/asset/environment-level scoping on every API endpoint.
- Tenant isolation verified by an automated cross-tenant access test in CI.

### 5. Policy-as-Code (OPA) — Full

- Policy authoring UI in the Admin Console with linting, dry-run, and policy library.
- Policy categories: tool risk, deployment promotion, approval requirements, network access, template import controls, tenant isolation.
- Policies live in Git (versioned, reviewed); the OPA bundle is built and rolled out via the Phase 2 Deployment Manager.
- Approval requirement matrix from PRD Section 21.3 fully expressed as policy.

### 6. Vault Hardening

- All secrets resolved through Vault or a cloud key vault — no exceptions in staging/prod (locks in the Phase 1/2 rule).
- Credential rotation supported (Vault dynamic secrets for databases where available).
- Automatic secret refresh inside long-running MCP server pods.
- Export scanner blocks any template export containing a non-vault-reference secret-like string.

### 7. Audit Reports

- All audit events (per PRD Section 22.1) captured to a tamper-evident audit log (append-only table + S3-archived JSON lines).
- Pre-built audit report templates: asset change log, deployment log, approval log, high-risk tool call log, template import/export log, security policy change log.
- Export formats: CSV, JSON, PDF.
- Per-tenant audit retention policy.

### 8. Tool Sandbox

- Strict Kubernetes sandbox for Browser/CUA, Code/DevOps, and any tool with risk level ≥ L3.
- No host filesystem mount; no privileged containers; network policy enforces egress allowlist per tool.
- Per-call sandbox for ephemeral risky operations.

### 9. Security Scanner Pipeline

- **Image scanning**: Trivy on every container build.
- **SBOM and dependency scanning**: Syft + Grype on every build.
- **Secret scanning**: gitleaks-style scanner on every template export and every code-agent PR.
- **Generated-code scanning**: applied to every OpenHands SDK output.
- Pipeline gates promotion: any high-severity finding blocks staging/prod deployment until resolved.

### 10. Kubernetes Deployment

- Helm charts for every platform service.
- CI/CD pipeline (GitHub Actions / GitLab CI / Azure DevOps — pick one as the customer's standard).
- Image registry with signed images.
- Per-environment values files: dev, test, staging, prod.
- Network policies between services (tightest in prod).

### 11. Browser/CUA MCP Server (introduced in Phase 3 because it requires the Tool Sandbox)

- Tools: `open_page`, `click_button`, `fill_form`, `capture_screen`, `read_ui_state`.
- Built with Playwright running in sandboxed browser workers per PRD Section 24.8.
- Used by Browser Agents (PRD Section 12.1).

### 12. Prompt-Injection Detection (Should-priority)

- Heuristic filter on user prompts and on tool outputs that feed back into prompts.
- LLM-guardrail second pass on suspicious inputs.
- Blocks or flags suspicious content; audit log records the detection.

---

## Deliverables

1. **Six production MCP servers**: ArcGIS, Database, File/PDF/RAG, Email/Notification, Monitoring, ITSM — each with tests, docs, container images, Helm subcharts, and tool risk classifications.
2. **Code/DevOps MCP Server** productionized with full sandbox.
3. **Browser/CUA MCP Server** with Playwright + sandbox.
4. **Activepieces Bridge** service plus seeded library of governed Activepieces tools.
5. **Google ADK provider adapter** added behind the `ModelProvider` interface.
6. **RBAC/ABAC engine** fully wired with project/asset/environment scoping.
7. **OPA policy authoring UI** plus policy library covering tool risk, deployment, approval, network, template imports, tenant isolation.
8. **Vault integration** with rotation, dynamic secrets, automated refresh, and export scanner.
9. **Audit log subsystem** plus pre-built audit report templates and exporters.
10. **Tool Sandbox** Kubernetes implementation with verified isolation for L3+ tools.
11. **Security Scanner pipeline** (Trivy + Syft/Grype + secret scanners) integrated into the Deployment Manager.
12. **Helm charts and CI/CD pipeline** for full-platform Kubernetes deployment.
13. **Network policies and per-environment allowlists** in MCP server configs.
14. **Prompt-injection detection module** (Should-priority).
15. **Compliance evidence package**: SOC 2 control mapping document, sample audit exports, policy bundles.
16. **Operations runbook** for the production-deployed platform.

---

## Dependencies

Depends on Phase 1 + Phase 2 deliverables:

- Phase 2's Deployment Manager, Test Runner, Approval Queue, OpenHands SDK sandbox, basic OPA, basic Vault, Temporal engine, provider adapters interface are all prerequisites.

External dependencies (must be available before Phase 3 production):

- Customer's ArcGIS Enterprise environment (Portal, Server, Data Store) reachable from the platform's Kubernetes cluster.
- Customer's production-grade PostgreSQL/SQL Server/Oracle access credentials (vault-managed).
- Customer's SMTP / Microsoft Graph / Activepieces email backend.
- Customer's Jira / ServiceNow API endpoints and credentials.
- Customer's identity provider (Keycloak federated to AzureAD/Okta/Google).
- HashiCorp Vault or cloud key vault in production-grade configuration.
- A production-grade Kubernetes cluster.
- Container registry with signing infrastructure.

Internal team dependencies:

- Security team owns the OPA policy library and the audit-export schemas; sign-off required before go-live.
- GIS engineering team partners with the platform team on ArcGIS MCP Server design and operations.
- DBAs partner on the Database MCP Server's SQL safety policy.

Cross-phase dependencies:

- Phase 4 (Advanced Multi-Agent) depends on the Tool Sandbox (Phase 3) for safely running meta-agents that build agents, on the full RBAC/ABAC system, and on the OPA policy library.
- Phase 5 (Scale & Productization) depends on the Phase 3 Kubernetes deployment baseline and security scanner pipeline.

---

## Acceptance Criteria

Phase 3 is complete when **all** of the following are true:

1. The ArcGIS MCP Server, Database MCP Server, File/PDF MCP Server, and Email MCP Server are running in production-grade Kubernetes, each with its own Helm chart, vault-managed secrets, and OPA-enforced risk policies. (PRD Section 28: "At least ArcGIS, Database, File/PDF, and Email MCP servers are supported" — fully satisfied here.)
2. PRD Scenario 27.1 (Create GIS Health Agent + daily workflow) runs end-to-end in production using only real MCP servers and produces a real email report.
3. RBAC/ABAC blocks every cross-tenant and cross-project access attempt — verified by an automated test suite in CI.
4. A non-trivial OPA policy (e.g., production deployment to ArcGIS MCP requires both Security Approver and Platform Owner) is authored in the UI, deployed, and enforced.
5. Secrets are never present in any database row, log line, template export, or audit row — verified by automated export scanner and a manual penetration check.
6. The audit-report exporter produces a 30-day compliance evidence package in CSV and PDF.
7. The Security Scanner pipeline (Trivy + Syft/Grype + secret scanners) gates every staging/prod promotion; promotion is blocked when high-severity findings exist.
8. The Tool Sandbox isolates Browser/CUA and Code/DevOps MCP servers: a sandbox-escape regression test fails closed.
9. Activepieces Bridge calls at least ten SaaS connectors through the platform's governance, including approval gating for external destinations.
10. Google ADK adapter executes a Gemini-backed agent under the same governance and observability as OpenAI and Claude adapters.
11. The platform is deployed to a fresh Kubernetes cluster end-to-end from the Helm charts and CI/CD pipeline, with all images signed and scanned.
12. Network policies enforce per-MCP-server egress allowlists; an automated test verifies that no MCP server can reach a host outside its allowlist.
13. The SOC 2 readiness control-mapping document is reviewed and approved by the customer's Security Officer.
14. Operations runbook covers incident response, secret rotation, MCP server rollback, OPA policy hotfix, and audit export procedures.

Phase 3 fully satisfies these PRD Section 28 acceptance criteria: "Tool permissions are enforced", "High-risk actions require approval", "Runs produce logs and traces", "Secrets are never exported", "At least ArcGIS, Database, File/PDF, and Email MCP servers are supported", "Kubernetes deployment design is documented".

---

## Notes

- **PRD Section 24.5.5 explicitly selects Activepieces** as the Integration Automation Hub: "Reduces the need to build hundreds of SaaS connectors from scratch." Phase 3 is where this comes to life.
- **Security note from PRD Section 24.5.3 (Flowise)** also applies generally to all bridges: "Do not expose low-code tool execution publicly without strong patching, sandboxing, auth, and network isolation." The Phase 3 Tool Sandbox is the answer.
- **The Browser/CUA MCP Server is intentionally placed in Phase 3** even though it appears in the architecture from Section 9. Reason: Browser/CUA tools are L3-risk by default and the platform must not run them in any environment without the Tool Sandbox, which itself requires the Phase 2 sandbox baseline plus Phase 3 hardening.
- **RPA parity is not in scope** (PRD Section 4.2 non-goal: "Replacing full RPA suites in APP. Browser/CUA automation is included but advanced enterprise RPA parity is later phase."). Phase 3 ships Browser/CUA basics only.
- **Risk: SQL safety bypass through prompt injection.** Mitigation: the Database MCP Server's `run_safe_query` performs static analysis on the SQL string before execution; risky verbs require an approval record; and the prompt-injection detector flags suspicious tool inputs.
- **Risk: Vault outage halts the platform.** Mitigation: cached short-lived tokens in MCP server pods with a grace window; warn-and-degrade mode; vault HA documented in the operations runbook.
- **Risk: Audit log tampering.** Mitigation: append-only table; periodic export to immutable object storage; hash chain checked daily.
- **Risk: Sandbox escape.** Mitigation: read-only root filesystem, no host mounts, restricted capabilities, runtime security (Falco or equivalent) monitoring, sandbox-escape regression test as a Phase 3 acceptance gate.
- **Risk: Cross-tenant data leakage through RAG vectors.** Mitigation: Qdrant collections are tenant-scoped with API-level enforcement; tenant ID required in every retrieval call; automated cross-tenant retrieval test in CI.
- **Compliance posture.** Phase 3 targets SOC 2 readiness. Full SOC 2 attestation and additional frameworks (ISO 27001, FedRAMP, etc.) are organizational efforts on top of the platform and are out of scope.
- **Localization (PRD Section 18).** The architecture allows future Arabic/English UI. Localization itself is not Phase 3 scope but the i18n scaffolding is laid down so Phase 5 can ship Arabic UI.
- **What is NOT in Phase 3.** The PRD's "Phase 3 Enterprise Governance" (Section 26) and "Phase 3 Enterprise Integrations" (Section 24.12) are combined here for cohesion. The PRD's Phase-4 items (A2A, event bus, model control plane, RAG/memory, meta-agents, advanced observability) remain explicitly out of Phase 3.
