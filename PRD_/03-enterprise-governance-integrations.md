# Phase 3: Enterprise Governance and Integrations

## Objective
Add enterprise-grade security, governance, compliance controls, and the first production-grade integration layer for ArcGIS, databases, files/RAG, email, code/devops, and automation connectors.

This phase makes the platform safe and useful for enterprise operations by enforcing RBAC/ABAC, policy-as-code, secret isolation, sandboxing, security scanning, audit reports, and governed MCP integrations.

## Scope
This phase includes:

- Enterprise RBAC/ABAC expansion.
- Policy-as-code using OPA.
- Secret management using Vault or cloud key vault.
- Audit reports and evidence export.
- Tool sandbox for risky execution.
- Security scanner for templates, generated code, dependencies, and images.
- Tool risk classification and approval enforcement.
- Production-grade MCP server integrations.
- ArcGIS MCP Server.
- Database MCP Server.
- File/PDF/RAG MCP Server.
- Email/Notification MCP Server.
- Code/DevOps MCP Server.
- Activepieces bridge as connector hub.
- Provider adapters for OpenAI and Claude where required.
- Kubernetes deployment and CI/CD-ready production packaging.

## Key Requirements

### Functional Requirements

| Requirement ID | Requirement | Priority | Phase 3 Scope |
|---|---|---:|---|
| UX-007 | User can view audit history per asset | Must | Add full audit views and exportable evidence. |
| AG-010 | Assign allowed MCP servers/tools | Must | Enforce tool-level permissions through policy. |
| AG-011 | Assign memory scope | Must | Enforce memory scope by tenant/project/session. |
| AG-012 | Assign model and fallback model | Must | Govern provider adapter access and model usage. |
| MCP-010 | Store secrets only as vault references | Must | Enforce no raw secrets in database, logs, exports, or templates. |
| MCP-012 | Assign tool risk levels | Must | Tool risk levels L0-L4 required. |
| RT-007 | Validate final output | Must | Add safety/completeness checks before final output. |
| SEC-001 | Support SSO/OIDC/OAuth | Must | Production Keycloak/OIDC integration. |
| SEC-002 | Support RBAC and ABAC | Must | Role and attribute-based controls. |
| SEC-003 | Enforce tool-level permissions | Must | Gate every MCP tool call. |
| SEC-004 | Require approval for high-risk tools | Must | Enforce based on risk level and policy. |
| SEC-005 | Store secrets in vault only | Must | Secret references only. |
| SEC-006 | Redact secrets from logs/templates | Must | Redaction pipeline for logs and exports. |
| SEC-007 | Maintain audit trail for every asset/action | Must | Capture asset, deployment, run, tool, approval, import/export actions. |
| SEC-008 | Sandbox risky tools | Must | Isolated execution for scripts, generated code, browser/CUA, deploy actions. |
| SEC-009 | Enforce network allowlists | Should | Add outbound/inbound policies for MCP servers. |
| SEC-010 | Support policy-as-code | Must | Use OPA policies. |
| SEC-011 | Detect prompt injection attempts | Should | Add scanning and rule-based checks. |
| SEC-012 | Support tenant isolation | Must | Enforce cross-tenant access boundaries. |
| OBS-007 | Alert on failures | Should | Integrate failure alerts. |
| OBS-008 | Export logs/traces to external observability stack | Should | Export to OpenTelemetry stack. |

### Tool Risk Requirements

| Risk Level | Examples | Required Control |
|---|---|---|
| L0 Read-only | List services, inspect schema | Normal RBAC. |
| L1 Low-risk write | Create draft, create report | RBAC + audit. |
| L2 Operational write | Restart service, update metadata | Approval based on policy. |
| L3 Destructive | Delete item, drop table, remove user | Mandatory approval. |
| L4 Admin/system | Execute script, modify IAM, deploy infra | Security approval + sandbox + audit. |

### Approval Requirements

| Action | Approval Required? |
|---|---|
| Read-only tool | No. |
| Create report | No. |
| Send email outside allowed domain | Yes. |
| Restart service | Configurable. |
| Delete resource | Yes. |
| Execute command/script | Yes. |
| Deploy MCP server to production | Yes. |
| Grant admin tool access to agent | Yes. |
| Import unsigned external template | Yes or blocked by default. |

## User Stories / Use Cases

### User Story 1: Security Officer Reviews Tool Access
As a security officer, I want to review which agents can access which MCP servers and tools so that high-risk tools are only available to approved users and agents.

Flow:

1. Security officer opens Admin Console.
2. Officer selects agent, MCP server, or tool.
3. Platform displays assigned roles, policies, risk levels, and approval requirements.
4. Officer updates allowed tools or policy rules.
5. Policy changes are audited.

### User Story 2: GIS Administrator Runs Governed ArcGIS Health Workflow
As a GIS administrator, I want to run an ArcGIS health workflow using approved MCP tools so I can check Portal, Server, Data Store, logs, and services safely.

Flow:

1. User runs GIS health workflow.
2. GIS Agent calls ArcGIS MCP Server tools.
3. Read-only checks execute immediately.
4. Any operational write such as restart service pauses for approval based on policy.
5. Result is aggregated and saved to run history.

### User Story 3: MCP Developer Deploys Secure MCP Server
As an MCP developer, I want generated MCP server code to be scanned before deployment so production tools cannot include secrets, insecure dependencies, or unsafe commands.

Flow:

1. Developer requests deployment.
2. Security scanner checks code, package, container image, manifest, and secrets.
3. OPA checks policy rules.
4. Security approver reviews if high-risk.
5. Deployment proceeds only if controls pass.

### User Story 4: Auditor Exports Evidence
As an auditor, I want to export evidence showing who created, updated, deployed, approved, and ran assets so that compliance requirements can be reviewed.

Flow:

1. Auditor opens Audit Reports.
2. Filters by tenant, project, asset type, date range, user, risk level.
3. Platform exports evidence package.
4. Evidence includes audit events, approvals, deployments, run history, and tool-call logs with secrets redacted.

## Features

### 1. Governance and Security Layer

| Feature | Description | Technology Mapping |
|---|---|---|
| SSO/OIDC/OAuth | Enterprise identity integration | Keycloak |
| RBAC/ABAC | Role and attribute-based permission model | Keycloak + PostgreSQL + OPA |
| Policy-as-code | Programmable rules for tools, deployments, approvals | OPA |
| Secret vault | Store all credentials externally | Vault / Azure Key Vault / AWS Secrets Manager / GCP Secret Manager |
| Tenant isolation | Separate tenants, projects, and environments | FastAPI middleware + database scopes + policies |
| Approval governance | High-risk tools and deployments require approval | Approval Queue + OPA |
| Audit trail | Full audit for asset/action/tool/deploy/import/export | PostgreSQL + object storage exports |
| Secret redaction | Remove sensitive data from logs/templates/exports | Redaction middleware and export processors |
| Prompt injection detection | Detect suspicious prompt/tool input patterns | Rule-based scanner + future ML hooks |
| Tool sandbox | Isolated execution for risky tools | Containers, network policies, restricted runtime |

### 2. Security Scanner

| Scan Target | Requirement |
|---|---|
| Template packages | Validate manifest, schema, signatures, and no secrets. |
| Generated MCP server code | Check unsafe commands, credential leakage, risky imports. |
| Dependencies | Generate SBOM and scan vulnerabilities. |
| Container images | Scan images before deployment. |
| Workflow graphs | Detect risky nodes and missing approval gates. |
| Tool schemas | Validate input/output schemas and risk labels. |

Recommended tools: Trivy, Syft, Grype, secret scanners, custom policy checks.

### 3. Enterprise MCP Integrations

| MCP Server | Example Tools | Technology Mapping |
|---|---|---|
| ArcGIS MCP Server | portal_health, list_services, restart_service, check_datastore, query_logs | MCP Python SDK + ArcGIS REST API/Python API |
| Database MCP Server | check_db_health, inspect_schema, run_safe_query, analyze_slow_queries | MCP Python SDK + SQLAlchemy/async drivers |
| File/PDF/RAG MCP Server | read_pdf, extract_tables, create_report, export_docx, export_pdf | MCP Python SDK + document parsers + Qdrant |
| Email MCP Server | create_draft, send_email, notify_user | MCP Python SDK or Activepieces bridge |
| DevOps MCP Server | create_branch, run_pipeline, deploy_container, rollback_release | MCP Python SDK + OpenHands SDK + CI/CD |
| Workflow MCP Server | trigger_activepieces, run_temporal_workflow | MCP wrapper over Activepieces and Temporal |

### 4. Activepieces Bridge

| Capability | Phase 3 Use |
|---|---|
| Connector hub | Expose selected Activepieces pieces as governed MCP-compatible tools. |
| SaaS automation | Use for email, notifications, productivity apps, CRM, and common integrations. |
| Policy wrapper | All Activepieces calls must pass MCP Gateway and OPA checks. |
| Audit | Log every Activepieces-triggered automation as a tool call. |

### 5. Provider Adapters

| Provider Adapter | Phase 3 Use |
|---|---|
| OpenAI Agents SDK | Use for OpenAI-specific agents, handoffs, sessions, or guardrails when needed. |
| Claude Agent SDK | Use for Claude-powered coding/building agents and provider-specific workflows when needed. |
| Local models | Continue support through generic model provider abstraction. |

### 6. Production Deployment Baseline

| Deployment Unit | Requirement |
|---|---|
| Frontend Web App | Container/static hosting. |
| API Gateway | Container/Kubernetes. |
| Agent Runtime Workers | Container/Kubernetes workers. |
| Workflow Engine | Temporal. |
| MCP Gateway | Container/Kubernetes. |
| MCP Servers | Local, containerized, or remote depending connector. |
| PostgreSQL | Managed or self-hosted. |
| Redis | Managed or self-hosted. |
| Qdrant | Managed or self-hosted. |
| Object Storage | S3/Azure Blob/GCS/MinIO. |
| Observability | OpenTelemetry collector + Prometheus/Grafana/Loki. |
| Secrets | Vault or cloud secret manager. |

## Deliverables

| Deliverable | Description | Technology |
|---|---|---|
| Enterprise RBAC/ABAC | Role and attribute controls | Keycloak + OPA + PostgreSQL |
| Policy-as-code | Tool/deploy/approval policies | OPA |
| Secret vault integration | Secret references only | Vault or cloud key vault |
| Audit reports | Exportable evidence | PostgreSQL + object storage |
| Tool sandbox | Isolated risky execution | Containers + network policies |
| Security scanner | Templates/code/dependencies/images | Trivy + Syft/Grype + secret scanning |
| ArcGIS MCP Server | ArcGIS Enterprise/Online operations | MCP Python SDK + ArcGIS APIs |
| Database MCP Server | Safe DB operations | MCP Python SDK + SQLAlchemy |
| File/PDF/RAG MCP Server | Document extraction and report generation | MCP Python SDK + Qdrant |
| Email MCP Server | Email drafts/sending/notifications | MCP Python SDK or Activepieces |
| Code/DevOps MCP Server | Repo/pipeline/container operations | OpenHands SDK + CI/CD |
| Activepieces bridge | Connector-rich automation | Activepieces API + MCP wrapper |
| Provider adapters | OpenAI/Claude integration | Provider SDK wrappers |
| Kubernetes deployment baseline | Containerized platform runtime | Docker + Kubernetes + CI/CD |

## Dependencies

| Dependency | Details |
|---|---|
| Phase 1 | Requires platform shell, asset registry, basic studios, MCP gateway prototype. |
| Phase 2 | Requires lifecycle operations, deployment manager, test runner, version manager, approval queue. |
| Enterprise IAM | Keycloak or enterprise OIDC provider configuration. |
| Vault/key vault | Required before production secrets are used. |
| OPA | Required for policy enforcement. |
| Security tools | Trivy, Syft/Grype, secret scanners. |
| ArcGIS access | ArcGIS Enterprise/Online URL, service accounts, tokens, permissions. |
| Database access | Database connection details and read-only/safe operation accounts. |
| Object storage | Required for reports, exports, evidence packages. |
| CI/CD | Required for deployment validation and scanning. |
| Kubernetes | Required for production baseline deployment. |

## Acceptance Criteria

Phase 3 is complete when:

- SSO/OIDC/OAuth works through Keycloak or enterprise identity provider.
- RBAC and ABAC rules are enforced across UI, API, agents, MCP servers, tools, workflows, and templates.
- OPA policies control tool access, deployments, approvals, imports, and risky actions.
- Secrets are stored only as vault/key vault references.
- Logs, templates, exports, and run history redact secrets.
- Every create/update/delete/deploy/import/export/tool-call/approval action creates an audit event.
- Tool risk levels L0-L4 are assigned and enforced.
- High-risk tools require approval according to policy.
- Tool sandboxing is available for scripts, generated code, browser/CUA, and deployment actions.
- Security scanner runs on templates, generated MCP code, dependencies, and container images.
- ArcGIS, Database, File/PDF/RAG, Email, and Code/DevOps MCP servers are available and governed.
- Activepieces bridge can expose selected connector actions as MCP-governed tools.
- Kubernetes deployment baseline is functional for core services.
- Audit reports can be exported for a selected tenant/project/date range.

## Notes

- Phase 3 is where the platform becomes enterprise-safe.
- No raw credentials should ever be stored in prompts, templates, exports, logs, or asset records.
- MCP servers are powerful because they expose tools; all tool execution must pass through permission, policy, logging, and risk checks.
- ArcGIS MCP Server is a priority integration because the target use cases include GIS operations and ArcGIS Enterprise health workflows.
- Activepieces is not a replacement for the MCP Gateway; it is a connector hub wrapped by MCP governance.
