# Phase 5: Marketplace, Scale & Productization

> **Source PRD:** Enterprise AI + MCP + Multi-Agent + Workflow Builder Platform — PRD v1.1 (Technology-Mapped Edition), dated 2026-05-18.
> **Phase Sequence:** 5 of 5
> **Theme:** Turn the platform into a shippable product. Add the Template Marketplace, expand the enterprise connector library, ship GitOps via Argo CD/Flux, deliver HA/DR + multi-region, layer in advanced analytics and cost governance, harden enterprise audit exports, and provide migration adapters for legacy frameworks.

---

## Objective

Phase 1–4 built and matured a single-enterprise platform. Phase 5 makes it **a product** that can be sold, distributed, scaled across regions, governed at a portfolio level, and operated under enterprise SLAs.

By the end of Phase 5:

- Internal and external template marketplaces let teams (and customers) publish, discover, install, and version templates — with quality review automated by the Phase 4 meta-agents and supply-chain integrity guaranteed by Phase 3 security scanners.
- The full enterprise connector library expands far beyond Phase 3's core six MCP servers, covering common cloud platforms, ITSM systems, monitoring stacks, SaaS apps, and bespoke customer systems.
- GitOps deployment via Argo CD or Flux provides controlled promotion, drift detection, and one-touch rollback at the infrastructure level.
- HA/DR + multi-region deployment supports 99.9% availability with failover.
- Advanced analytics give platform owners insight into usage, cost, reliability, and template adoption.
- Enterprise audit exports satisfy auditors across multiple compliance regimes.
- AutoGen import is available for customers with legacy investments to migrate.

Phase 5 is where the platform becomes **portfolio-scale productized infrastructure**.

---

## Scope

In scope for this phase:

- **Template Marketplace** — internal (tenant-private) and external (cross-tenant / public) marketplaces; rating, search, version, install, update flows; quality-review automation via meta-agents; supply-chain signing.
- **Enterprise Connector Library expansion** — additional MCP servers beyond the Phase 3 six: Cloud MCP (Azure, AWS, GCP, Kubernetes), Workflow MCP (n8n, Activepieces, Temporal cross-cluster trigger), Monitoring MCP (full set with SIEM), ITSM MCP (advanced features), SharePoint/Confluence MCP, additional Activepieces piece exposure.
- **GitOps integration** — Argo CD or Flux for environment promotion, drift detection, and rollback.
- **HA / DR / Multi-region deployment** — Kubernetes multi-zone, managed DB with replication, object storage replication, regional failover playbook.
- **Advanced analytics** — usage analytics, cost insights, reliability insights, template adoption analytics, agent/MCP quality scores.
- **Cost governance at portfolio scale** — cross-tenant budget management, chargeback reports, model-mix optimization recommendations.
- **Enterprise audit exports** — multi-compliance evidence packs (SOC 2, ISO 27001, GDPR, region-specific), exportable in standard auditor formats.
- **Advanced security scanning** — continuous scanning of running images, SBOM diff alerts, vulnerability lifecycle tracking.
- **AutoGen import adapter** (PRD Section 24.5.14) — only if customer demand exists; legacy import only.
- **Tesslate Agent-Builder bridge / importer** (PRD Section 24.5.2) — optional, reference-driven.
- **Arabic localization** of the UI (the architecture has supported i18n since Phase 1; Phase 5 ships the first non-English locale).
- **Public API SDKs** — Python, TypeScript, and CLI for programmatic asset lifecycle management.
- **Operational dashboards for platform owners** — tenant health, quota usage, incident timeline.

Out of scope for this phase (post-1.0 considerations):

- Hosted SaaS offering (delivery model is on-premise / private-cloud install in Phase 5; SaaS is a separate productization track).
- Full RPA-suite parity (PRD Section 4.2 explicitly defers this).
- Replacing enterprise IAM or ITSM (PRD Section 4.2).
- Mobile apps.
- White-label customization beyond branding.

---

## Key Requirements

### Functional Requirements

This phase rolls up to the full PRD Section 17 requirement matrix being satisfied. The Phase 5–specific functional additions:

| ID | Requirement | Priority |
|---|---|---|
| MKT-001 | Users can publish templates to the marketplace | Must |
| MKT-002 | Users can browse, search, and filter marketplace templates | Must |
| MKT-003 | Users can install templates from the marketplace into their project | Must |
| MKT-004 | Users can rate and review templates | Should |
| MKT-005 | Template publishers receive notification of installs, ratings, and version-update opportunities | Should |
| MKT-006 | The marketplace enforces signature, schema, scan, and meta-agent quality checks on every published template | Must |
| MKT-007 | Templates have version history with semantic versioning and migration notes | Must |
| MKT-008 | Marketplace search supports natural-language queries powered by RAG over template descriptions | Should |
| API-001 | Public Python SDK supports full asset lifecycle | Must |
| API-002 | Public TypeScript SDK supports full asset lifecycle | Must |
| API-003 | Public CLI supports full asset lifecycle and CI/CD usage | Must |
| LOC-001 | The UI is available in English and Arabic | Must |
| LOC-002 | Layout supports RTL for Arabic | Must |

### Non-Functional Requirements (full PRD Section 18 set at production targets)

| Category | Requirement (Phase 5 target) |
|---|---|
| Availability | Production target 99.9% per PRD Section 18 — achieved via HA architecture and multi-region. |
| Scalability | Platform supports many tenants and many concurrent workflows per tenant; load-tested at 100× Phase 3 baseline. |
| Performance | All listed SLOs met under sustained production load. |
| Reliability | Workflow state survives node, zone, and region failures; documented RPO/RTO. |
| Security | Continuous image scanning; SBOM-diff alerts; supply-chain signing on every artifact. |
| Auditability | Full audit history exportable per tenant in multiple compliance formats. |
| Maintainability | GitOps reconciles drift automatically; version rollback at the infrastructure level is one git revert. |
| Extensibility | Every layer (MCP servers, model providers, memory backends, bridges, node types) extensible without core changes — proven by adding a new MCP server in under a sprint via the meta-agents. |
| Portability | Identical Helm + GitOps deployment runs on AWS, Azure, GCP, on-premise Kubernetes. |
| Compliance | Audit and evidence support align with SOC 2, ISO 27001, GDPR, and region-specific requirements. |
| Localization | English and Arabic UI in v1.0; architecture supports adding more locales. |

---

## User Stories / Use Cases

US-5.1 — As a **template publisher** (e.g., a GIS team in one business unit), I want to publish my "Daily GIS Health Report" workflow template to our internal marketplace, have it automatically reviewed by the meta-agents and security scanners, and be notified when other teams install or rate it.

US-5.2 — As a **template consumer** (e.g., a different business unit's automation builder), I want to browse the marketplace, find a "Daily GIS Health Report" template that fits my needs, install it into my project, customize parameters, and have it deployed via the standard governance pipeline.

US-5.3 — As a **DevOps Engineer**, I want every environment promotion (dev → test → staging → prod) to be a git commit reconciled by Argo CD or Flux, so that I have a complete declarative history of what is deployed where, and rollback is `git revert`.

US-5.4 — As a **Site Reliability Engineer**, I want the platform to survive a full regional outage by failing over to the secondary region within the documented RTO, with workflow state intact and durable.

US-5.5 — As a **Platform Owner / CFO**, I want a portfolio cost dashboard showing model spend per tenant, per project, per agent, and per workflow, with trend lines, anomaly detection, and chargeback exports.

US-5.6 — As a **compliance auditor**, I want to export an audit evidence package for SOC 2 controls (or ISO 27001, GDPR, etc.) that maps every required control to the platform's audit log, policy bundle, and approval history for a specific tenant and a specific time window.

US-5.7 — As an **automation builder in an Arabic-speaking enterprise**, I want to use the platform in Arabic with proper RTL layout and Arabic-friendly typography.

US-5.8 — As a **developer integrating the platform**, I want a stable public Python SDK, TypeScript SDK, and CLI so that I can manage assets and runs from my own CI/CD pipelines and tools.

US-5.9 — As a **customer with existing AutoGen workflows**, I want to import them via the Migration Agent's AutoGen adapter so I can decommission AutoGen and consolidate on this platform.

### Anchor Scenarios

Phase 5 introduces three new flagship scenarios:

- **Marketplace install:** A user types "Find me a template for monitoring SQL Server health daily and emailing a report" → marketplace RAG search returns three candidate templates → the user picks one → meta-agents review compatibility with the user's project and connectors → installation creates draft assets → user customizes parameters → deployment pipeline kicks off with the standard governance gates.
- **Regional failover:** A simulated region failure triggers Argo CD to promote the secondary region; in-flight Temporal workflows resume on the secondary region's workers from the last checkpoint; the dashboard shows the failover sequence and RPO/RTO measurements.
- **Audit export under deadline:** A compliance auditor requests SOC 2 evidence for tenant `acme-corp` over the past quarter; the Platform Admin clicks one button; an evidence package (PDF + CSV + JSON Lines, with control-mapping spreadsheet) is generated and downloaded within the documented SLA.

---

## Features

### 1. Template Marketplace

- **Internal marketplace** scoped to a tenant or to a group of trusted tenants.
- **External marketplace** for cross-organization template sharing (opt-in; can be entirely disabled).
- Template entries include: title, description, screenshots, category, version history with semantic versioning and migration notes, supported parameters, required connectors, rating, install count, publisher, signature.
- **Automated quality review** on publish: meta-agents (Documentation, QA/Test, Security Review) validate the template; Phase 3 security scanners run; OPA policies check that the template's risk profile is acceptable.
- **Signature requirement** — only signed templates can be installed in staging/prod environments; unsigned imports are blocked by default (locking in the PRD Section 21.3 default for "Import unsigned external template").
- **RAG-powered search** — natural-language queries over template descriptions, parameter docs, and READMEs.
- **Rating & review** with moderation.
- **Update notifications** for installed templates when a new version is published.
- **Publisher console** showing install counts, ratings, version adoption.

### 2. Enterprise Connector Library Expansion

Building on the Phase 3 baseline (ArcGIS, Database, File/PDF, Email, Monitoring basic, ITSM basic, Code/DevOps, Browser/CUA), Phase 5 adds:

- **Cloud MCP Server** — Azure / AWS / GCP / Kubernetes operations (list VMs, check resource health, inspect logs, restart service per PRD Section 14.2). Risk-classified.
- **Monitoring MCP Server (full)** — full Prometheus / Loki / SIEM coverage; alert correlation.
- **ITSM MCP Server (advanced)** — Jira and ServiceNow with attachment workflows, SLA tracking, change-management integration.
- **Workflow MCP Server** — trigger n8n / Activepieces / Temporal workflows on other clusters (PRD Section 14.2).
- **SharePoint / Confluence MCP Server** — document search, page read, page create.
- **Expanded Activepieces piece exposure** — beyond the Phase 3 starter set, the library exposes a broader set of governed pieces.
- Each new MCP server ships with: Helm chart, OPA policies, audit-event types, sample template, tests, docs, and Documentation-Agent–maintained reference docs.

### 3. GitOps with Argo CD or Flux

- All environment configurations live in a Git repository as Helm values and OPA bundle definitions.
- Argo CD (or Flux) reconciles each environment to its declared state.
- Drift detection alerts when actual state diverges from declared state.
- Promotions are pull requests; rollback is `git revert`.
- The Phase 2 Deployment Manager continues to handle application-level (asset-level) promotion; Phase 5's GitOps handles infrastructure and platform-config promotion. The two are integrated: an asset promotion to prod triggers the necessary Argo CD / Flux sync.

### 4. HA / DR / Multi-Region Deployment

- Kubernetes deployment across multiple availability zones in a region (Phase 3 baseline expanded).
- Second region as warm-standby; primary-to-secondary replication of PostgreSQL (managed), object storage (cross-region replication), Vault, and Temporal namespaces.
- Documented RPO and RTO targets (e.g., RPO ≤ 5 min, RTO ≤ 30 min — final numbers per customer SLA).
- Regional failover runbook with quarterly drills.
- Health-check probes, multi-zone load balancer, and graceful degradation paths.

### 5. Advanced Analytics

- **Usage analytics** — runs per tenant/project/agent/workflow/day; user adoption curves; feature usage heatmaps.
- **Cost insights** — model spend by tenant/project/agent/workflow; cost-per-run trend lines; anomaly detection; recommendations for cheaper model routing.
- **Reliability insights** — workflow success rate, tool failure rate, agent error rate, approval latency (PRD Section 22.2) — surfaced as managed dashboards with SLO trends.
- **Template adoption analytics** — for the marketplace.
- **Agent / MCP quality scores** — composite scores based on success rate, latency, cost-effectiveness, user ratings.

### 6. Cost Governance at Portfolio Scale

- Hierarchical budgets: organization → tenant → project → agent.
- Chargeback / showback reports per tenant.
- Model-mix optimization recommendations (e.g., "Routing this agent to a smaller model would save $X/month with negligible quality impact based on evaluation scores").
- Hard caps with degradation playbooks (e.g., automatic switch to local LLM when cloud budget hit).

### 7. Enterprise Audit Exports (Multi-Compliance)

- Pre-built control mappings for SOC 2, ISO 27001, GDPR, and region-specific frameworks.
- Evidence packs include: audit log slice, OPA policy snapshot, approval history, deployment history, secret-handling evidence, security scan reports.
- Export formats: PDF (auditor-ready), CSV, JSON Lines, plus a control-mapping spreadsheet.
- Tenant-scoped retention policies satisfied per region.

### 8. Advanced Security Scanning (Continuous)

- Continuous scanning of running container images (Trivy in continuous mode) — new CVEs trigger alerts.
- SBOM diff alerts when a dependency changes risk class.
- Vulnerability lifecycle tracking — every finding has a state (open, accepted-risk, fixed, false-positive) and a deadline.
- Generated-code security continues from Phase 3 (every OpenHands SDK PR scanned before merge).

### 9. AutoGen Import Adapter (Optional)

- PRD Section 24.5.14: "Build a future AutoGen import adapter only if customers already have AutoGen workflows." The Phase 5 Migration Agent (built in Phase 4) gains an AutoGen parser as one more best-effort import path.
- Output: a native LangGraph + Temporal workflow with confidence score and human-review checklist.

### 10. Tesslate Agent-Builder Bridge / Importer (Optional)

- PRD Section 24.5.2: "Reference / Optional". Use for visual-agent-builder pattern imports from existing Tesslate users.
- Feature-flagged per tenant; same governance pipeline as other bridges.

### 11. Arabic Localization

- UI strings translated to Arabic; RTL layout supported throughout (Tailwind RTL plugin + shadcn/ui RTL adjustments).
- LLM prompt support for Arabic content (verified across primary providers).
- Date, number, and currency formatting localized.

### 12. Public SDKs and CLI

- Python SDK, TypeScript SDK, and CLI covering: asset CRUD, runs (start/get/cancel/retry/replay), templates (import/export/instantiate), approvals (approve/reject), policies (read), and observability (logs/traces).
- Versioned with semantic versioning; backward-compatible deprecation window of two minor versions.
- CI/CD recipes (GitHub Actions, GitLab CI, Azure DevOps) shipped as templates.

### 13. Operational Dashboards

- Per-tenant health view: usage, cost, errors, approvals pending, deployments in flight.
- Platform-wide health view: SLO compliance, region status, queue depths, model provider availability.
- Tenant-self-service: each tenant admin sees their own dashboards but not other tenants'.

---

## Deliverables

1. **Template Marketplace service and UI** — internal and external modes, signature enforcement, RAG search, ratings, publisher console.
2. **Expanded Enterprise Connector Library** — Cloud MCP, full Monitoring MCP, advanced ITSM MCP, Workflow MCP, SharePoint/Confluence MCP, broader Activepieces exposure.
3. **GitOps integration** with Argo CD or Flux, drift detection, and end-to-end declarative environment management.
4. **HA / DR / Multi-region deployment** with documented RPO/RTO, replication setup, and failover runbook.
5. **Advanced analytics service** with usage, cost, reliability, and template adoption dashboards.
6. **Portfolio cost governance** — hierarchical budgets, chargeback reports, model-mix recommendations.
7. **Multi-compliance audit export system** — SOC 2, ISO 27001, GDPR, region-specific control mappings and evidence packs.
8. **Continuous security scanning** with SBOM diff alerts and vulnerability lifecycle tracking.
9. **AutoGen importer** (optional, conditional on demand) and **Tesslate Agent-Builder importer** (optional).
10. **Arabic localization** — fully translated UI with RTL.
11. **Public Python SDK, TypeScript SDK, and CLI** with semantic versioning and CI/CD recipes.
12. **Operational dashboards** for tenant admins and platform owners.
13. **v1.0 product launch package** — release notes, customer documentation, customer migration playbooks, support handbook, partner onboarding kit.
14. **Quarterly DR drill report** demonstrating successful regional failover within RTO.

---

## Dependencies

Depends on Phase 1, 2, 3, and 4 deliverables:

- All Phase 1–4 capabilities are prerequisites. Phase 5 productizes and scales them; it does not replace them.
- The Phase 4 meta-agents are critical to Phase 5's marketplace automated quality review.
- The Phase 4 Model Control Plane is the basis for Phase 5's portfolio cost governance.
- The Phase 3 security scanner pipeline is extended (not replaced) into continuous scanning.

External dependencies:

- Managed PostgreSQL with cross-region replication (e.g., AWS RDS multi-region read replicas, Azure Database for PostgreSQL with geo-replication, or equivalent).
- Multi-region Kubernetes (e.g., AWS EKS in two regions, Azure AKS in two regions, GCP GKE in two regions, or hybrid).
- Cross-region object storage replication (S3 / Azure Blob / GCS).
- Argo CD or Flux deployment.
- Managed Kafka or Confluent multi-region replication for the event bus.
- Managed Vault with multi-region replication.
- Marketplace artifact signing infrastructure (Sigstore / cosign or equivalent).

Internal team dependencies:

- Product management team owns marketplace policy (what is allowed to be published externally).
- Customer-success team owns the migration playbooks for customers coming from n8n / Flowise / Dify / AutoGen.
- Localization team owns Arabic translations and RTL QA.
- Compliance team owns the SOC 2 / ISO 27001 / GDPR control mappings and signs off on evidence packs.

Cross-phase dependencies:

- Phase 5 is the **terminal** phase of the PRD's planned roadmap. Future enhancements (SaaS offering, mobile, additional locales, full RPA parity, AutoGen runtime if it returns from maintenance mode) become post-1.0 backlog items.

---

## Acceptance Criteria

Phase 5 is complete when **all** of the following are true:

1. The Template Marketplace is live (internal mode at minimum; external mode optional per deployment). At least 25 high-quality templates spanning agents, MCP servers, workflows, policies, prompts, and deployments are published and installable. Every published template carries a signature and has passed automated meta-agent quality review and security scans.
2. The marketplace's RAG-powered natural-language search returns relevant results for at least 90% of a curated test query set.
3. Unsigned external template imports are blocked in staging and production environments by default (per PRD Section 21.3).
4. Cloud, full Monitoring, advanced ITSM, Workflow, and SharePoint/Confluence MCP servers are deployed and used in at least one customer workflow.
5. GitOps via Argo CD or Flux reconciles all environments. A simulated drift event is detected and corrected automatically.
6. A regional failover drill completes within the documented RTO with no workflow state loss beyond the documented RPO.
7. The advanced analytics dashboards display real data for usage, cost, reliability, and template adoption with one-day freshness or better.
8. Portfolio cost governance enforces hierarchical budgets: an automated test shows a tenant hitting its budget triggers degradation and notifications.
9. An auditor-ready SOC 2 (or ISO 27001 or GDPR) evidence pack can be exported for a specified tenant and time window in under fifteen minutes.
10. Continuous security scanning produces SBOM diff alerts; a planted-vulnerability test demonstrates the alert lifecycle from detection to remediation tracking.
11. The Arabic UI passes RTL QA; representative customer users in Arabic-speaking environments validate usability.
12. The public Python SDK, TypeScript SDK, and CLI cover the full asset lifecycle, ship to their package registries, and are demonstrated in CI/CD examples.
13. AutoGen importer (if delivered) successfully imports a representative non-trivial AutoGen flow with a meaningful confidence score.
14. The v1.0 launch package (release notes, customer docs, migration playbooks, support handbook, partner onboarding kit) is reviewed and signed off by Product, Engineering, Support, and Customer Success leads.
15. All PRD Section 28 acceptance criteria from APP and beyond are satisfied, and all NFRs in PRD Section 18 are met at production targets.

---

## Notes

- **Build Priority Alignment.** Phase 5 closes Build Priority 3 items 6–8 from PRD Section 30 (marketplace, GitOps, multi-region deployment).
- **Marketplace governance is a force multiplier.** The PRD Section 30 risk "Template imports are unsafe" is mitigated in Phase 5 by mandatory signing, automated meta-agent quality review, security scanners on publish, and OPA risk-policy gating on install. The marketplace must enforce these gates without exception.
- **Risk: Marketplace abuse or low-quality submissions.** Mitigation: automated meta-agent review on every publish; rating/review with moderation; deprecation/takedown workflow for problematic templates; per-publisher reputation scores.
- **Risk: GitOps misconfiguration causes accidental production change.** Mitigation: every Argo CD / Flux sync to prod requires the standard Phase 2/3 approval policies; "auto-sync" disabled for prod; manual sync requires Security Approver + Platform Owner per OPA policy.
- **Risk: Failover drill reveals data loss beyond RPO.** Mitigation: quarterly drills, not annual; documented gaps trigger immediate remediation; replication lag monitored in real time.
- **Risk: Compliance scope creep.** Mitigation: Phase 5 ships SOC 2 / ISO 27001 / GDPR mappings; region-specific frameworks added as customer engagements demand them; no commitment to all frameworks in v1.0.
- **Risk: Cost dashboard surprises tenants.** Mitigation: budgets and forecasts visible to tenant admins from day one; alerts well before hard caps; clear, predictable degradation behavior.
- **AutoGen treatment.** PRD Section 24.5.14 is explicit: "Treat as legacy/migration only; do not use as primary runtime." Phase 5 ships only an importer, not a runtime. If a future LangGraph/Temporal/AutoGen pattern emerges, that is post-1.0.
- **Tesslate, SIM, Flowise, Dify, CrewAI** remain optional bridges from Phase 4. Phase 5 maintains them — fixes, security patches, version updates — but does not invest heavily in deepening them. The product center of gravity is the **custom core**, per PRD Section 24.10's "Build the custom core first" principle.
- **Localization beyond Arabic.** The architecture supports adding any locale. Customer-driven prioritization decides which locale comes next post-1.0.
- **What "v1.0" means here.** Phase 5 marks the platform as **v1.0 product**. The PRD's Final Product Statement (Section 33) — Design-Time Control Plane + Runtime Control Plane + Multi-Agent Orchestration + MCP Tool Integration + A2A Communication + Visual Workflow Builder + Full Lifecycle Management + Templates and Reusability + Security and Governance + Observability and Audit + Deployment and DevSecOps — is realized in full by the end of Phase 5.
- **Beyond v1.0.** Post-1.0 candidates (hosted SaaS, mobile clients, full RPA parity, additional locales, sector-specific accelerator packs, MLOps integration, finetuning pipelines) are explicitly post-PRD and tracked in the product backlog.
