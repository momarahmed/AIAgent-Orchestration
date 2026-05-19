# Enhanced PRD Appendix — Enterprise AI + MCP + Multi-Agent + Workflow Builder Platform

> **Purpose.** This appendix augments the five phase PRDs (`01..05-phase-*.md`) with the enterprise-grade structure, completeness, engineering-readiness, DevOps alignment, and UX rigor required by the *Enhance PRD* prompt. It is additive — original phase PRDs remain authoritative for scope; this appendix adds the **Improved** and **Added** sections.

---

## 1. Executive Summary  *(Improved)*

The Enterprise AI + MCP Platform is a vendor-neutral, build-custom-first control plane that unifies **AI Agents**, **MCP Servers**, **Workflows**, and **Templates** under a single asset model — with full lifecycle (create / debug / deploy / copy / template / version / rollback), policy-governed runtime, and enterprise integrations (ArcGIS, DB, File/PDF, Email, GitOps, Identity).

The product is delivered in **5 phases** spanning ~12 months. Phase 1 ships a working foundation (Studios, visual builder, in-process runtime, MCP gateway prototype, run history). Phases 2–5 layer durable execution (Temporal), policy-as-code (OPA), vault, A2A multi-agent coordination, and a marketplace.

Differentiators:

- **Unified asset model** — agents, MCP servers, workflows, and templates all share a `current_version_id` lineage, audit trail, and lifecycle state.
- **Build-custom-first** — no vendor lock-in to Activepieces, Dify, Flowise, SIM, CrewAI, or AutoGen. All accelerators are evaluated *after* the custom core proves the asset model.
- **Enterprise governance from day one** — every mutation produces an audit event; risk levels (L0..L4) are assigned to tools at registration; Phase 3 wires OPA + vault.

---

## 2. Problem Statement  *(Improved)*

Enterprises building AI agents, MCP servers, and orchestration workflows today face fragmented tooling: chat UIs from one vendor, agent SDKs from another, MCP gateways from a third, and CI/CD bolted on. The result is shadow-IT proliferation, no consistent governance, no unified audit, no policy enforcement on tool execution, and no shared asset lifecycle.

This platform replaces that patchwork with a single, governed control plane.

---

## 3. SMART Goals & Objectives  *(Added)*

| ID | Objective | Specific | Measurable | Achievable | Relevant | Time-bound |
|---|---|---|---|---|---|---|
| G1 | Ship Phase 1 foundation | Login, Studios, builder, runtime prototype, run history | All Phase 1 acceptance criteria #1–#10 | 1 squad × 8 weeks | Required for all later phases | End of Quarter 1 |
| G2 | Deliver durable runtime | Temporal-backed execution with retries, approvals, rollback | 99% completion rate for runs ≥ 10 minutes | Existing Temporal expertise | Production readiness | End of Quarter 2 |
| G3 | Achieve enterprise governance | Full RBAC/ABAC, OPA policy-as-code, vault, audit exports | 100% of tool calls policy-evaluated | Mature OPA ecosystem | Compliance & security | End of Quarter 3 |
| G4 | Enable multi-agent coordination | A2A gateway, event bus, Model Control Plane, RAG memory | < 250 ms inter-agent message latency p95 | Builds on Phase 2/3 foundations | Differentiation | End of Quarter 4 |
| G5 | Productize marketplace | Template marketplace, GitOps, multi-region | 50+ shared templates by GA | Adoption depends on Phases 1–4 | Revenue | End of Year 1 |

---

## 4. Stakeholders & RACI  *(Added)*

| Stakeholder | Responsibility | R | A | C | I |
|---|---|:-:|:-:|:-:|:-:|
| Platform CTO | Strategic direction, architecture sign-off |   | ✓ |   |   |
| Solution Architects | Asset model, API contracts, vendor choices | ✓ |   |   |   |
| Engineering Leads | Phase scoping, story breakdown, code review | ✓ |   | ✓ |   |
| DevOps / SRE | Docker, K8s, Terraform, observability, CI/CD | ✓ |   | ✓ |   |
| Security & GRC | OPA policies, vault, RBAC, audit, compliance |   |   | ✓ | ✓ |
| Product Owner | Backlog priority, acceptance, stakeholder comms | ✓ | ✓ |   |   |
| Business Analysts | User journeys, success metrics, persona insights |   |   | ✓ | ✓ |
| End Users (Builders, Operators, Viewers) | Daily usage, feedback loop |   |   |   | ✓ |

---

## 5. User Personas  *(Added)*

| Persona | Goals | Pain points | Success metric |
|---|---|---|---|
| **Platform Admin** (Mona) | Govern tenants, RBAC, integrations, security posture | Patchwork of vendor consoles | Single pane of glass; audit-ready |
| **Agent Designer** (Karim) | Author agents, iterate on prompts and tool choices | Slow feedback loops, no debug console | Time-to-first-run < 5 min |
| **MCP Developer** (Lina) | Build, register, version MCP servers and tools | Manual tool schemas, no validation | First tool live in < 30 min |
| **Automation Builder** (Saud) | Compose visual workflows with approvals | Hard to test, no rollback | Visual diff + 1-click rollback |
| **Business Operator** (Hala) | Run workflows on demand, see outcomes | No transparency into agent decisions | Per-node trace + final report |
| **SRE** (Yusuf) | Operate the platform, troubleshoot incidents | Runtime is opaque | Trace + metrics + replay |
| **Compliance Officer** (Reem) | Audit, evidence, separation of duties | Manual log scraping | Audit export + immutability |

---

## 6. User Journeys  *(Added)*

```
Builder Journey (Phase 1):
  Login → Select Tenant → Open Agent Studio → Create Agent (draft v1) →
  Open MCP Studio → Register MCP server → Add tools (input/output schemas) →
  Open Workflow Builder → Drag Trigger/Agent/Tool nodes → Wire edges → Save (v1) →
  Click Run → Watch run record → Inspect node tree → Export trace

Operator Journey (Phase 2 onward):
  Workflows → Pick "Generate ArcGIS layer report" → Run with input parameters →
  Approval gate triggers (high-risk tool) → Approve in queue →
  Resume execution → Final report delivered → Audit log populated

Compliance Journey (Phase 3 onward):
  Admin → Audit → Filter by tenant + date → Export CSV/JSON →
  Verify policy decisions per tool call → Reconcile with vault secret access logs
```

---

## 7. Functional Requirements (consolidated)  *(Improved)*

See each phase PRD's FR table. Every requirement carries an ID prefix:

- `UX-*` — user experience
- `AG-*` — agent management
- `MCP-*` — MCP server management
- `WF-*` — workflow management
- `RT-*` — runtime execution
- `OBS-*` — observability
- `SEC-*` — security & governance (Phases 3+)

Each FR row is mapped to a user story and acceptance criteria (Section 12 below).

---

## 8. Non-Functional Requirements  *(Improved)*

| Category | Phase 1 baseline | Phase 3 target | Phase 5 GA target |
|---|---|---|---|
| Availability | Local dev only | 99% (business hours) | 99.95% (multi-AZ) |
| Latency (UI p95) | < 2 s | < 1.5 s | < 1.2 s |
| Workflow throughput | 1 run/s | 100 runs/s | 1000 runs/s |
| Long-running workflows | Not supported | 24 h max | 30 days max |
| Security | Sanctum tokens, CORS allowlist, audit | OPA, vault, RBAC/ABAC, network allowlists | FedRAMP / HIPAA modules |
| Compliance | None | SOC 2 Type 1 readiness | SOC 2 Type 2, ISO 27001 |
| Audit retention | 90 days | 1 year | 7 years (configurable) |
| RPO / RTO | N/A | RPO 1 h / RTO 4 h | RPO 5 min / RTO 30 min |

---

## 9. Technical Considerations  *(Improved)*

### 9.1 Architecture (target state)

```
[ Browser (Next.js + MUI) ]
        │   HTTPS  +  Sanctum bearer
        ▼
[ Edge / WAF ] → [ API Gateway ] → [ Laravel API ]
                                    ├─ Tenant / Project / Asset CRUD
                                    ├─ Workflow Run API → [ Temporal Workers ] (Phase 2+)
                                    ├─ MCP Gateway → [ MCP Client Manager ] → MCP Servers (HTTP / stdio / SSE)
                                    ├─ OPA sidecar (Phase 3+)
                                    └─ Vault / Key Vault (Phase 3+)
        │                                │
        ▼                                ▼
   MySQL 8.4                          Redis 7
   (asset model,                      (cache,
    audit, runs)                       queue, sessions)
```

### 9.2 API contract (selected, Phase 1)

| Endpoint | Method | Purpose |
|---|---|---|
| `/api/auth/login` | POST | Issue Sanctum bearer |
| `/api/agents` | GET, POST, PUT, DELETE | Agent lifecycle (versioned on PUT) |
| `/api/mcp-servers/{id}/health` | POST | On-demand health probe |
| `/api/workflows/{id}/run` | POST | Synchronous Phase-1 run |
| `/api/runs/{id}` | GET | Full run trace including tool calls |
| `/api/metrics/overview` | GET | KPI roll-up for Platform Console |

### 9.3 Integration points

- **Identity**: Sanctum (Phase 1) → Keycloak / Entra ID / WebAuthn (Phase 3+).
- **LLM Providers**: OpenAI (Phase 1) → OpenAI Agents SDK + Claude Agent SDK + Google ADK (Phase 2+) behind a Model Control Plane (Phase 4).
- **Observability**: stdout JSON logs (Phase 1) → OpenTelemetry traces / metrics → Prometheus + Grafana + Loki + Tempo (Phase 3+).
- **Secrets**: `.env` (Phase 1) → HashiCorp Vault / Azure Key Vault (Phase 3+).
- **Policy**: in-code allowed-tool list (Phase 1) → Open Policy Agent (Phase 3+).

### 9.4 Cloud-native deployment design

- **Kubernetes** manifests defined in Phase 1 docs (deployed Phase 2+): one Deployment per service (frontend, api, agent-runtime, mcp-gateway, workers), HPA on CPU + custom metrics, NetworkPolicies for tenant isolation.
- **Terraform** modules (Phase 3+): VPC, EKS/AKS/GKE cluster, RDS MySQL, ElastiCache Redis, S3/Blob for template artifacts, IAM roles (least privilege), KMS for encryption-at-rest.
- **CI/CD**: GitHub Actions / GitLab CI pipelines — lint → unit → integration → container build → SBOM → vulnerability scan → push → deploy via GitOps (Argo CD).
- **Observability**: OpenTelemetry collector as DaemonSet; W3C trace context propagated through API → runtime → MCP tool call.

---

## 10. Data Model / Entities  *(Improved)*

Authoritative schema lives in `backend/database/migrations/2026_05_19_000001_create_platform_core_tables.php` and matches PRD Section 19.1. Key tables:

| Entity | PK | Notable columns | Phase introduced |
|---|---|---|---|
| `tenants` | `id` | `slug`, `environment`, `settings` | 1 |
| `projects` | `id` | `tenant_id`, `slug`, `metadata` | 1 |
| `agents` | `id` | `current_version_id`, `risk_level`, `status` | 1 |
| `agent_versions` | `id` | `model_config`, `allowed_mcp_servers`, `memory_scope` | 1 |
| `mcp_servers` | `id` | `transport`, `endpoint`, `auth_method`, `health` | 1 |
| `tools` | `id` | `mcp_server_id`, `input_schema`, `risk_level` | 1 |
| `workflows` + `workflow_versions` | `id` | `graph_json`, `variables`, `trigger_type` | 1 |
| `workflow_runs` / `task_runs` / `tool_calls` | `id` | full execution trace | 1 |
| `templates` | `id` | `asset_type`, `payload`, `parameters_schema` | 2 |
| `audit_events` | `id` | `event_type`, `subject_*`, `payload`, `ip_address` | 1 |
| Keycloak / OPA / Vault projections | — | external systems | 3 |
| Memory store (vector) | `id` | `tenant_id`, `embedding`, `scope` | 4 |

---

## 11. UX / UI Requirements  *(Improved)*

The frontend uses a futuristic dark enterprise design system (cyan/violet accents on slate-950 base) implemented in Next.js 15 + MUI 6 + Tailwind 3.

Reusable components shipped:

- `AppShell` — sidebar + top nav + tenant switcher + sign-out
- `PageHeader`, `StatusBadge`, `EmptyState`
- Login page with tenant selector + password / SSO / Passkey methods
- `Dashboard`, `Admin Console`, `Platform Console`, `Platform Page` (sourced from `/UI` reference TSX, integrated as authenticated routes)
- `Agent Studio`, `MCP Studio`, `Workflow Builder`, `Run History` — connected directly to the backend via TanStack Query

Future (Phase 2+): `Debug Console`, `Approval Queue`, `Deployment Manager`, `Version Diff`, `Chart Gallery` (line/multi-line/area/stacked/bar/pie/donut/gauge/radar/scatter/bubble/heatmap/treemap/funnel/waterfall/Sankey/map/Gantt + KPI cards & sparkline tables).

---

## 12. User Stories with Acceptance Criteria (Gherkin)  *(Added)*

```gherkin
Feature: Agent lifecycle (Phase 1)

  Scenario: Builder creates an agent and assigns allowed MCP tools
    Given I am authenticated as a Builder in tenant "ESRI Saudi"
    And the project "GIS Operations" exists
    And an MCP server "ArcGIS MCP" is registered with tool "find_features"
    When I open Agent Studio and create an agent named "GIS Health"
    And I assign model "gpt-4o-mini" and allowed tool "find_features"
    Then the agent is persisted with status "draft" and version 1
    And the audit_events table has a row with event_type="create" subject_type="agent"

  Scenario: Builder runs the anchor workflow
    Given the seeded workflow "Hello World" exists in "GIS Operations"
    When I click Run
    Then a workflow_run row is created with status "running"
    And task_run rows exist for nodes "t1", "a1", "m1"
    And on success the run transitions to status "completed"
    And the Run History view shows per-node duration_ms and outputs
```

```gherkin
Feature: MCP server health probe (Phase 1)

  Scenario: Operator probes a registered MCP server
    Given an MCP server with endpoint "http://reachable/mcp" exists
    When I click "Health check"
    Then the response shows health="healthy" with latency_ms recorded
    And the mcp_servers.last_health_check_at column is updated
    And an audit_events row with event_type="health" is created
```

---

## 13. Edge Cases & Error Handling  *(Added)*

| Edge case | Handling |
|---|---|
| Workflow has no current_version | API returns 422; UI surfaces inline error |
| Agent version PUT with no diff | No new version row created |
| MCP server endpoint unreachable | Health probe returns `unhealthy` with error detail; timeout 5 s |
| Cyclic workflow graph | Phase 1 topological sort visits each node once, ignoring cycles; Phase 2 validator rejects cycles |
| Tool call exceeds risk threshold (Phase 2/3) | OPA returns deny → run transitions to `awaiting_approval` |
| Sanctum token expired | API returns 401 → frontend interceptor redirects to /login |
| MySQL connection lost mid-run | Phase 1: run marked `failed`; Phase 2: Temporal retries automatically |
| Workflow runs in parallel for the same trigger | Phase 1 allowed; Phase 2 supports idempotency keys |

---

## 14. Assumptions & Constraints  *(Added)*

- Local dev requires Docker 24+ and Docker Compose v2.
- Frontend uses non-conflicting host ports because canonical 3000/8000 are occupied by other dev stacks on the build host (Frontend → 3020, Backend → 8089, MySQL → 3308, Redis → 6382).
- Phase 1 is laptop-class; production deploys ship from Phase 3.
- LLM provider creds (OpenAI) live in `backend/.env`; the system runs end-to-end with mock responses if absent.

---

## 15. Dependencies  *(Improved)*

External: Docker, MySQL 8.4, Redis 7, Composer, Node 20+. Phase 3+ adds Keycloak, Vault, OPA, Temporal, OTel Collector. Phase 4 adds an event bus (NATS / Kafka) and a vector store (PGVector / Qdrant).

Internal: Platform Architect approves the asset model contract before each phase; UX Lead delivers redlines for each studio; SRE delivers Helm charts before Phase 2 cutover.

---

## 16. Risks & Mitigations  *(Improved)*

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Synchronous runner blocks worker threads | High | Med | Phase 2 cutover to Temporal — interface designed today so engine swap is transparent |
| Lack of policy enforcement in Phase 1 | Med | High | Allowed-tool list per agent; risk level captured at registration; OPA enforced from Phase 3 |
| Secrets leakage from `.env` | Low | High | Deployment manager (Phase 2) blocks promotion of any asset whose secret_refs is non-vault path |
| LLM provider cost overrun | Med | Med | Token-aware budget caps per agent + tenant (Phase 4 Model Control Plane) |
| Vendor lock-in via accelerators (Activepieces, Dify, …) | Low | High | Build-Custom-First mandate — accelerators are evaluated only after the core stabilizes |
| Multi-tenant data leak | Low | Critical | Tenant_id column on every row + scoped queries; Phase 3 adds RLS-style policy enforcement |

---

## 17. Success Metrics & DORA  *(Added)*

| Metric | Target Phase 1 | Phase 3 | Phase 5 |
|---|---|---|---|
| Workflow run success rate | ≥ 90% | ≥ 99% | ≥ 99.5% |
| Time-to-first-run (new builder) | < 30 min | < 15 min | < 5 min |
| MTBF | n/a | > 7 days | > 30 days |
| MTTR | n/a | < 30 min | < 10 min |
| Deployment frequency (DORA) | weekly | daily | on-demand |
| Change failure rate (DORA) | < 30% | < 15% | < 5% |
| Lead time for changes (DORA) | < 1 week | < 1 day | < 1 hour |

---

## 18. Release Plan / Milestones  *(Improved)*

| Release | Calendar | Highlights |
|---|---|---|
| v0.1 — Phase 1 Foundation | Q1 | Login, Studios, builder, in-process runtime, run history (this repo) |
| v0.5 — Phase 2 Lifecycle | Q2 | Temporal, Debug Console, Deployment Manager, Templates, Approvals |
| v1.0 — Phase 3 Governance | Q3 | OPA, vault, ArcGIS / DB / File / Email MCPs, audit exports |
| v1.5 — Phase 4 Multi-agent | Q4 | A2A, event bus, MCP gateway scale-out, RAG memory, meta-agents |
| v2.0 — Phase 5 GA | Q4+1 | Marketplace, GitOps, multi-region, FedRAMP/HIPAA modules |

---

## 19. DevOps & Cloud Alignment  *(Added)*

- **Pipelines**: build → unit → integration (compose-based) → SAST/SCA → SBOM → push to OCI registry → deploy via Argo CD.
- **Observability**: OpenTelemetry SDK + Collector → traces (Tempo), metrics (Prometheus), logs (Loki). Dashboards (Grafana) cover run rate, success/failure ratio, p95 latency, MCP tool error budget.
- **Security**: WAF (CloudFront / Front Door), IAM least privilege, Secrets manager (Vault / KV), image signing (cosign), runtime policy (OPA Gatekeeper).
- **Rollback**: blue/green for stateless services, version pinning + 1-click rollback for assets via the Version Manager (Phase 2).

---

## 20. Output Format note

This appendix is published as `PRD/00-enhanced-prd-appendix.md`. Sections labelled *Improved* refine the original PRD; *Added* sections are net-new. Together with phase PRDs, this constitutes the **Enterprise-Grade PRD v1.2**.
