# SOC 2 Readiness — Control Mapping Document

> Enterprise AI MCP Platform — Phase 3

This document maps Phase 3 platform capabilities to SOC 2 Trust Service Criteria.
Prepared for Security Officer review per PRD Acceptance Criterion #13.

---

## Common Criteria (CC)

| SOC 2 Control | Platform Implementation | Evidence |
|---|---|---|
| CC1.1 — Integrity & ethical values | Code of conduct; all changes go through PR review. | Git history, CI/CD pipeline, approval records |
| CC2.1 — Board oversight | Platform Owner role with full governance authority. | RBAC role hierarchy, audit trail |
| CC3.1 — Risk assessment | Tool risk levels (L0–L4); OPA policy enforcement. | `risk_level` on tools; OPA policy library |
| CC5.1 — Control activities | RBAC/ABAC, approval gates, vault-managed secrets, network policies. | Middleware logs, approval queue, audit events |
| CC6.1 — Logical access | Sanctum auth + RBAC/ABAC with tenant isolation. | `EnsureTenantIsolation` middleware; role assignments |
| CC6.2 — Authentication | Token-based auth (Sanctum); SSO/OIDC ready (Keycloak federation point). | Login flow, token management |
| CC6.3 — Access removal | Tenant role revocation via API; token invalidation. | `RbacController::removeTenantRole` |
| CC7.1 — System operations | Docker Compose / Kubernetes deployment; health checks; scheduler. | `docker-compose.yml`, Helm charts, `/api/health` |
| CC7.2 — Change management | Versioned assets; deployment pipeline; approval gates. | `VersionController`, `DeploymentService`, CI/CD |
| CC7.3 — Configuration management | Per-environment values; vault-managed secrets; OPA policies. | `values-{dev,staging,prod}.yaml`, Vault integration |
| CC8.1 — Incident management | Operations runbook; alerting stubs; audit log. | `docs/OPERATIONS_RUNBOOK.md` |

---

## Availability (A)

| SOC 2 Control | Platform Implementation | Evidence |
|---|---|---|
| A1.1 — Capacity planning | Kubernetes HPA with CPU-based scaling; per-environment resource limits. | `values-prod.yaml` autoscaling config |
| A1.2 — Recovery | Workflow checkpoint persistence; database backups; Helm rollback. | `DurableWorkflowEngine` checkpoints; `kubectl rollout undo` |
| A1.3 — Incident response | Operations runbook with escalation paths. | `docs/OPERATIONS_RUNBOOK.md` |

---

## Confidentiality (C)

| SOC 2 Control | Platform Implementation | Evidence |
|---|---|---|
| C1.1 — Confidential data identification | Secret references marked `secret_ref`; risk levels on tools/data. | `SecretRef` model; tool risk classifications |
| C1.2 — Confidential data disposal | Secret rotation via Vault; template export strips all secrets. | `SecretService::rotateSecret()`; `TemplateService` export |
| C1.3 — Encryption | HTTPS for all APIs; Vault encryption at rest; MySQL TDE ready. | TLS termination; Vault seal/unseal |

---

## Processing Integrity (PI)

| SOC 2 Control | Platform Implementation | Evidence |
|---|---|---|
| PI1.1 — Completeness & accuracy | Workflow validation on save; schema validation on MCP tools. | `WorkflowGraphValidator`; tool input/output schema |
| PI1.2 — Processing monitoring | OpenTelemetry traces; run history; execution timeline. | OTLP export; `/api/runs`; task_run timestamps |
| PI1.3 — Error handling | Retry policies; error_handler nodes; approval queue for failures. | `retry_policy` on nodes; `runNodeWithRetry` |

---

## Privacy (P)

| SOC 2 Control | Platform Implementation | Evidence |
|---|---|---|
| P1.1 — Notice | Platform does not process end-user PII directly; enterprise-internal tool. | Architecture documentation |
| P3.1 — Collection limitation | Audit events track actions, not personal data content. | `AuditEvent` schema (no PII fields) |
| P6.1 — Disclosure | Secrets never exported; prompt-injection filter prevents data exfiltration. | `SecretService::scanForSecrets()`; `PromptInjectionFilter` |

---

## Security-Specific Controls

| Control Area | Implementation | Evidence Source |
|---|---|---|
| **Access Control** | RBAC with 7 roles (Viewer→Platform Owner); ABAC condition engine; tenant isolation middleware | `RbacService`, `AbacPolicy`, tests |
| **Policy Enforcement** | OPA policy-as-code: tool risk, deployment, approval, network, template import, tenant isolation | `infra/opa/*.rego`, `OpaPolicyService` |
| **Secret Management** | HashiCorp Vault; rotation; dynamic secrets; refresh; no plaintext in DB/logs/exports | `SecretService`, export scanner |
| **Audit Trail** | Tamper-evident hash chain; append-only; CSV/JSON/PDF export; 6 report templates | `AuditReportService`, `audit_events` table |
| **Vulnerability Management** | Trivy (containers), Syft+Grype (SBOM/dependencies), gitleaks (secrets), code scanning | CI/CD pipeline, `SecurityScannerService` |
| **Network Security** | Per-MCP-server egress allowlists; K8s NetworkPolicy generation; environment scoping | `NetworkPolicyService`, allowlists |
| **Sandboxing** | L3+ tools run in isolated K8s namespace; no host mounts; no privilege escalation; read-only rootfs | `ToolSandboxService`, sandbox config |
| **Change Control** | Asset versioning; diff; rollback; deployment pipeline with gates; approval queue | Version APIs, `DeploymentService` |
| **Image Integrity** | Cosign-signed container images; SBOM attached; promotion blocked on high-severity findings | CI/CD cosign step, `securityScanner` config |
| **Prompt Security** | Heuristic + LLM guardrail prompt-injection detection; suspicious input logged and blocked | `PromptInjectionService`, `PromptInjectionFilter` |

---

## Evidence Artifacts

| Artifact | Location | Frequency |
|---|---|---|
| Audit export (30-day) | `POST /api/audit-reports/export` | Monthly |
| Security scan results | `GET /api/security-scans` | Per deployment |
| RBAC role assignments | `GET /api/rbac/roles` | On demand |
| OPA policy bundle | `infra/opa/` + `GET /api/opa-policies` | Per change |
| Network policy manifests | `GET /api/network-policies/{id}/k8s-policy` | Per change |
| Approval decisions | `GET /api/approvals` | Continuous |
| CI/CD pipeline logs | GitHub Actions runs | Per commit |
| SBOM (SPDX) | CI artifact: `sbom-backend.json` | Per build |
| Signed images | `ghcr.io/eamcp/*` with Cosign signatures | Per release |

---

## Gaps & Remediation Plan

| Gap | Severity | Remediation | Target |
|---|---|---|---|
| Full SSO/OIDC via Keycloak federation | Low | Keycloak realm configured; needs AzureAD/Okta connection | Phase 3 ops |
| Automated access review | Medium | Manual via audit reports; automated review in Phase 5 | Phase 5 |
| Formal incident response drill | Medium | Runbook exists; tabletop exercise needed | Pre-audit |
| Data retention automation | Low | Manual exports; automated archival in Phase 5 | Phase 5 |

---

## Approval

| Role | Name | Date | Signature |
|---|---|---|---|
| Platform Owner | _________________ | ________ | ________ |
| Security Officer | _________________ | ________ | ________ |
| Security Approver | _________________ | ________ | ________ |
