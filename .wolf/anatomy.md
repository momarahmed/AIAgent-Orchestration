# anatomy.md

> Auto-maintained by OpenWolf. Last scanned: 2026-05-19T19:34:12.842Z
> Files: 498 tracked | Anatomy hits: 0 | Misses: 0

## ./

- `.gitignore` — Git ignore rules (~239 tok)
- `CLAUDE.md` — OpenWolf (~57 tok)
- `docker-compose.yml` — Docker Compose services (~5302 tok)
- `README.md` — Project documentation (~2187 tok)

## .claude/

- `settings.json` (~441 tok)

## .claude/rules/

- `openwolf.md` (~313 tok)

## .cursor/

- `hooks.json` (~308 tok)

## .cursor/hooks/

- `openwolf-env.sh` — Resolve project root and export vars OpenWolf hooks expect. (~68 tok)
- `openwolf-post-read.sh` (~68 tok)
- `openwolf-post-write.sh` (~68 tok)
- `openwolf-pre-read.sh` (~76 tok)
- `openwolf-pre-write.sh` (~68 tok)
- `openwolf-session.sh` (~68 tok)
- `openwolf-stop.sh` (~65 tok)
- `transform.mjs` — Transform Cursor hook stdin JSON → OpenWolf (Claude Code) hook format. (~662 tok)

## .github/workflows/

- `ci-cd.yml` — CI: EAMCP CI/CD Pipeline (~1682 tok)

## PRD/

- `00-enhanced-prd-appendix.md` — Enhanced PRD Appendix — Enterprise AI + MCP + Multi-Agent + Workflow Builder Platform (~4515 tok)
- `01-phase-foundation-core-platform.md` — Phase 1: Foundation — Core Platform (~4115 tok)
- `02-phase-lifecycle-management-runtime-reliability.md` — Phase 2: Lifecycle Management & Runtime Reliability (~5602 tok)
- `03-phase-enterprise-governance-integrations.md` — Phase 3: Enterprise Governance & Integrations (~5714 tok)
- `04-phase-advanced-multi-agent-platform.md` — Phase 4: Advanced Multi-Agent Platform (~6418 tok)
- `05-phase-marketplace-scale-productization.md` — Phase 5: Marketplace, Scale & Productization (~6412 tok)

## PRD_/

- `01-core-platform-foundation.md` — Phase 1: Core Platform Foundation (~3122 tok)
- `02-lifecycle-management-runtime-reliability.md` — Phase 2: Lifecycle Management and Runtime Reliability (~4847 tok)
- `03-enterprise-governance-integrations.md` — Phase 3: Enterprise Governance and Integrations (~3486 tok)
- `04-advanced-multi-agent-ecosystem-marketplace-adapters.md` — Phase 4: Advanced Multi-Agent Ecosystem and Marketplace Adapters (~3648 tok)
- `05-scale-productization-marketplace.md` — Phase 5: Scale, Productization, and Marketplace (~3551 tok)
- `enterprise_ai_mcp_multi_agent_platform_prd_v1_1_technology_mapped.md` — Product Requirements Document (PRD) (~26897 tok)
- `enterprise_ai_mcp_multi_agent_platform_prd.md` — Product Requirements Document (PRD) (~16745 tok)

## Propmpt/

- `2-enhance PRD_` (~557 tok)
- `dev-env.txt` (~790 tok)
- `start implemetion.txt` (~339 tok)
- `UI Designe.md` — Declares is (~1252 tok)

## UI/

- `EnterpriseAIMCPAdminConsole.tsx` — sidebarItems (~8598 tok)
- `EnterpriseAIMCPDashboard.tsx` — kpis — renders table (~6764 tok)
- `EnterpriseAIMCPLoginPage.tsx` — tenants — renders form — uses useState (~4689 tok)
- `EnterpriseAIMCPPlatformConsole.tsx` — navSections (~10632 tok)
- `EnterpriseAIMCPPlatformPage.tsx` — platformLayers (~7750 tok)

## backend/

- `.editorconfig` — Editor configuration (~68 tok)
- `.gitattributes` — Git attributes (~50 tok)
- `.gitignore` — Git ignore rules (~76 tok)
- `.phpunit.result.cache` (~1871 tok)
- `artisan` — Laravel CLI entry point (~114 tok)
- `composer.json` — PHP package manifest (~824 tok)
- `Dockerfile` — Docker container definition (~284 tok)
- `package.json` — Node.js package manifest (~119 tok)
- `phpunit.xml` (~378 tok)
- `README.md` — Project documentation (~978 tok)
- `vite.config.js` — Vite build configuration (~125 tok)

## backend/app/Console/Commands/

- `ConsumeEventBus.php` — EventBus consumer worker (Phase 4 — PRD §13, RT-* requirements). (~1254 tok)
- `DispatchScheduledWorkflows.php` — Artisan command: DispatchScheduledWorkflows (~795 tok)
- `EnsureAdmin.php` — Idempotently provision (or reset) the demo admin / builder / viewer (~1087 tok)

## backend/app/Contracts/

- `EventBusContract.php` — Event Bus abstraction (Phase 4 — PRD §13). (~280 tok)
- `ModelProvider.php` — Interface: ModelProvider (3 methods) (~115 tok)
- `WorkflowEngine.php` — WorkflowEngine — abstract contract that hides the underlying engine (~219 tok)

## backend/app/Http/Controllers/

- `Controller.php` — Controller: Controller (~21 tok)

## backend/app/Http/Controllers/Api/

- `A2AController.php` — A2A Gateway HTTP surface (PRD §13.2 — Phase 4). (~955 tok)
- `AgentController.php` — AG-013 — agent execution history via task runs on agent nodes. (~1776 tok)
- `AnalyticsController.php` — usage, cost, reliability, templateAdoption, qualityScores + 1 more (~471 tok)
- `ApprovalController.php` — index, show, approve, reject (~378 tok)
- `AuditController.php` — index (~192 tok)
- `AuditReportController.php` — Paginated audit events with filtering. (~1148 tok)
- `AuthController.php` — login, register, me, logout (~690 tok)
- `BridgeController.php` — frameworks, connections, storeConnection, call, toggle (~591 tok)
- `ChatController.php` — Phase-1 Chat UI backend — single-turn prompt execution with run record (UX-002). (~1060 tok)
- `CodegenController.php` — generateMcp, show, index (~351 tok)
- `CommentController.php` — index, store, resolve, destroy (~419 tok)
- `ComplianceExportController.php` — frameworks, index, store, show, download (~698 tok)
- `ContinuousScannerController.php` — snapshots, takeSnapshot, diffs, diffLatest, findings + 3 more (~866 tok)
- `CopyController.php` — copyAgent, copyMcp, copyWorkflow (~1340 tok)
- `DebugController.php` — debugAgent, debugTool, debugWorkflowNode, replayRun, resumeRun + 1 more (~754 tok)
- `DeploymentController.php` — index, show, store, approve, rollback (~560 tok)
- `EventBusController.php` — status, publish, subscriptions, storeSubscription, log (~655 tok)
- `FlowiseAgentController.php` — Backend surface for the AI Workflow Studio (FlowiseAI integration). (~3801 tok)
- `GitOpsController.php` — environments, registerEnvironment, sync, drift, syncs + 2 more (~647 tok)
- `LegacyImportController.php` — index, show, store (~386 tok)
- `LocaleController.php` — index, dictionary, upsert (~307 tok)
- `MarketplaceController.php` — Marketplace endpoints (Phase 5). (~1523 tok)
- `McpServerController.php` — index, store, show, update, destroy + 1 more (~1871 tok)
- `MemoryController.php` — collections, createCollection, items, remember, retrieve + 2 more (~890 tok)
- `MetaAgentController.php` — Meta-Agent HTTP surface (PRD §12.2 + Scenario 27.1 — Phase 4). (~1051 tok)
- `MetricsController.php` — Aggregate KPI / dashboard metrics for the Platform Console. (~924 tok)
- `MigrationController.php` — index, show, import (~388 tok)
- `ModelRegistryController.php` — Model Control Plane Controller (Phase 4 — PRD §9.2). (~996 tok)
- `NetworkPolicyController.php` — index, store, update, destroy, check + 1 more (~985 tok)
- `ObservabilityController.php` — metrics, metricsJson, timeline, replay, knowledgeGraph (~421 tok)
- `OpaPolicyController.php` — index, show, store, update, destroy + 5 more (~996 tok)
- `PortfolioBudgetController.php` — index, store, update, destroy, chargeback + 2 more (~1047 tok)
- `ProjectController.php` — index, store, show, update, destroy (~588 tok)
- `PromptController.php` — index, show, store, newVersion, activate + 4 more (~986 tok)
- `ProviderBudgetController.php` — index, store, update, destroy, check + 1 more (~808 tok)
- `RbacController.php` — roles, createRole, updateRole, permissions, assignTenantRole + 6 more (~1671 tok)
- `RunController.php` — index, show (~235 tok)
- `SecretController.php` — index, store, destroy (~421 tok)
- `SecurityScanController.php` — index, show, triggerScan, scanDeployment, promotionGate + 1 more (~903 tok)
- `TemplateController.php` — Phase-1 template stub — full lifecycle in Phase 2. (~370 tok)
- `TemplateLifecycleController.php` — fromAsset, exportJson, exportZip, importJson, importZip + 1 more (~723 tok)
- `TenantController.php` — index, store, show, update, destroy (~526 tok)
- `TestController.php` — index, store, show, run, execution (~485 tok)
- `ToolController.php` — index, store, update, destroy (~631 tok)
- `VersionController.php` — agentVersions, workflowVersions, mcpVersions, diffAgent, diffWorkflow + 3 more (~914 tok)
- `WorkflowController.php` — index, store, show, update, destroy + 1 more (~1653 tok)
- `FlowiseAgentController.php` — AI Workflow Studio (Flowise) CRUD + run + sync + import/export (~2400 tok)

## backend/app/Http/Middleware/

- `EnforceRbac.php` — Usage in routes: ->middleware('rbac:agents.create') (~691 tok)
- `EnsureTenantIsolation.php` — Verifies that the authenticated user belongs to the tenant referenced (~415 tok)
- `PromptInjectionFilter.php` — Scans prompt/input fields for injection patterns. (~447 tok)

## backend/app/Models/

- `A2AMessage.php` — Model — table: a2a_messages, 16 fields, 4 casts (~138 tok)
- `A2APartner.php` — Model — table: a2a_partners, 12 fields, 6 casts, 1 rels (~187 tok)
- `AbacPolicy.php` — Model — 10 fields, 6 casts, 2 rels (~184 tok)
- `ActivepiecesConnection.php` — Model — 10 fields, 6 casts, 2 rels (~193 tok)
- `Agent.php` — Model — 10 fields, 2 casts, 4 rels (~289 tok)
- `AgentQualityScore.php` — Model — 8 fields, 12 casts (~157 tok)
- `AgentVersion.php` — Model — 10 fields, 8 casts, 1 rels (~194 tok)
- `AnalyticsSnapshot.php` — Model — 7 fields, 6 casts (~98 tok)
- `Approval.php` — Model — 14 fields, 8 casts (~182 tok)
- `AssetComment.php` — Model — 9 fields, 6 casts, 2 rels (~183 tok)
- `AuditEvent.php` — Model — 10 fields, 2 casts (~116 tok)
- `AuditExport.php` — Model — 12 fields, 12 casts, 3 rels (~271 tok)
- `AuditReportTemplate.php` — Model — 7 fields, 6 casts (~98 tok)
- `BridgeConnection.php` — Model — 9 fields, 6 casts (~110 tok)
- `BridgeExecution.php` — Model — 9 fields, 6 casts, 1 rels (~154 tok)
- `ChargebackReport.php` — Model — 7 fields, 8 casts, 1 rels (~157 tok)
- `CodegenJob.php` — Model — 12 fields, 8 casts (~128 tok)
- `ComplianceExport.php` — Model — 12 fields, 12 casts, 1 rels (~206 tok)
- `ComplianceFramework.php` — Model — 5 fields, 4 casts (~84 tok)
- `CostRecommendation.php` — Model — 7 fields, 2 casts (~96 tok)
- `Deployment.php` — Model — 15 fields, 10 casts, 1 rels (~199 tok)
- `EventLog.php` — Model — table: event_log, 11 fields, 8 casts (~135 tok)
- `EventSubscription.php` — Model — 7 fields, 6 casts (~102 tok)
- `FlowiseAgent.php` — Model — flowise_agents, 13 fields, 5 casts, 4 rels (~280 tok)
- `FlowiseRun.php` — Model — flowise_runs, 14 fields, 6 casts, 2 rels (~210 tok)
- `FlowiseSync.php` — Model — flowise_syncs, 7 fields, 2 casts, 1 rels (~120 tok)
- `FlowiseAgent.php` — Model — 16 fields, 5 rels (~379 tok)
- `FlowiseRun.php` — Model — 16 fields, 2 rels (~269 tok)
- `FlowiseSync.php` — Model — 8 fields, 1 rels (~176 tok)
- `GitopsEnvironment.php` — Model — table: gitops_environments, 12 fields, 4 casts, 1 rels (~175 tok)
- `GitopsSync.php` — Model — table: gitops_syncs, 7 fields, 4 casts, 1 rels (~159 tok)
- `KnowledgeGraphEdge.php` — Model — 4 fields, 2 casts (~67 tok)
- `KnowledgeGraphNode.php` — Model — 6 fields, 2 casts (~71 tok)
- `LegacyImport.php` — Model — 9 fields, 8 casts (~134 tok)
- `Locale.php` — Model — 4 fields, 4 casts, 1 rels (~120 tok)
- `LocaleTranslation.php` — Model — 4 fields, 1 rels (~95 tok)
- `MarketplaceInstall.php` — Model — 9 fields, 4 casts, 3 rels (~217 tok)
- `MarketplaceListing.php` — Model — 22 fields, 16 casts, 6 rels (~449 tok)
- `MarketplaceRating.php` — Model — 5 fields, 2 rels (~134 tok)
- `MarketplaceSigningKey.php` — Model — 5 fields, 2 casts (~78 tok)
- `MarketplaceVersion.php` — Model — 11 fields, 8 casts, 1 rels (~188 tok)
- `McpServer.php` — Model — 17 fields, 10 casts, 4 rels (~346 tok)
- `McpServerVersion.php` — Model — 4 fields, 2 casts, 1 rels (~131 tok)
- `MemoryCollection.php` — Model — 9 fields, 2 casts, 2 rels (~162 tok)
- `MemoryItem.php` — Model — 11 fields, 4 casts, 1 rels (~159 tok)
- `MetaAgentAction.php` — Model — 13 fields, 10 casts, 2 rels (~215 tok)
- `MetaAgentRun.php` — Model — 15 fields, 12 casts, 3 rels (~257 tok)
- `MigrationImport.php` — Model — 11 fields, 8 casts, 1 rels (~171 tok)
- `ModelRecord.php` — Model — table: models, 12 fields, 12 casts (~160 tok)
- `ModelRoutingRule.php` — Model — 6 fields, 6 casts, 1 rels (~132 tok)
- `NetworkAllowlist.php` — Model — 10 fields, 4 casts, 2 rels (~175 tok)
- `OpaPolicy.php` — Model — 13 fields, 6 casts, 3 rels (~260 tok)
- `OpaPolicyVersion.php` — Model — 6 fields, 4 casts, 1 rels (~135 tok)
- `Permission.php` — Model — 4 fields, 1 rels (~101 tok)
- `PortfolioBudget.php` — Model — 11 fields, 12 casts, 2 rels (~386 tok)
- `Project.php` — Model — 5 fields, 2 casts, 4 rels (~239 tok)
- `Prompt.php` — Model — 8 fields, 5 rels (~248 tok)
- `PromptEvaluation.php` — Model — 9 fields, 8 casts (~127 tok)
- `PromptInjectionLog.php` — Model — 10 fields, 2 casts, 2 rels (~175 tok)
- `PromptVersion.php` — Model — 8 fields, 4 casts, 1 rels (~131 tok)
- `ProviderBudget.php` — Model — 10 fields, 10 casts, 1 rels (~269 tok)
- `RegionHealth.php` — Model — table: region_health, 6 fields, 6 casts (~125 tok)
- `Role.php` — Model — 6 fields, 6 casts, 1 rels (~242 tok)
- `RunReplay.php` — Model — 7 fields, 4 casts, 2 rels (~165 tok)
- `SbomDiff.php` — Model — 6 fields, 4 casts, 2 rels (~183 tok)
- `SbomSnapshot.php` — Model — 4 fields, 2 casts (~74 tok)
- `SecretRef.php` — Model — table: secret_refs, 8 fields, 2 casts (~115 tok)
- `SecurityScan.php` — Model — 16 fields, 16 casts, 3 rels (~344 tok)
- `TaskRun.php` — Model — 10 fields, 8 casts, 2 rels (~229 tok)
- `Template.php` — Model — 8 fields, 4 casts (~133 tok)
- `Tenant.php` — Model — 4 fields, 2 casts, 2 rels (~199 tok)
- `TestExecution.php` — Model — 11 fields, 6 casts, 1 rels (~150 tok)
- `TestSuite.php` — Model — 8 fields, 2 casts, 1 rels (~111 tok)
- `Tool.php` — Model — 7 fields, 6 casts, 1 rels (~172 tok)
- `ToolCall.php` — Model — 9 fields, 4 casts (~115 tok)
- `User.php` — Convenience accessor used by Phase 5 controllers / SDKs. (~646 tok)
- `VulnerabilityFinding.php` — Model — 11 fields, 4 casts (~231 tok)
- `Workflow.php` — Model — 11 fields, 4 casts, 4 rels (~298 tok)
- `WorkflowRun.php` — Model — 15 fields, 14 casts, 3 rels (~312 tok)
- `WorkflowVersion.php` — Model — 6 fields, 6 casts, 1 rels (~160 tok)

## backend/app/Providers/

- `AppServiceProvider.php` — Service provider: AppServiceProvider (~1100 tok)

## backend/app/Services/

- `A2AGatewayService.php` — A2A Gateway Service (PRD §13.2 / §13.3 — Phase 4) (~2536 tok)
- `ActivepiecesBridge.php` — Activepieces Integration Bridge (Phase 3). (~1411 tok)
- `AgentRuntime.php` — Agent runtime — uses ProviderRegistry (Phase 2) so OpenAI / Claude (~424 tok)
- `AnalyticsService.php` — Phase 5 advanced analytics. (~2344 tok)
- `ApprovalService.php` — Approval Queue — pause/resume gate for risky tool calls and deployments. (~679 tok)
- `AuditReportService.php` — Audit report generation, export (CSV/JSON/PDF), and tamper-evident hash chain. (~1616 tok)
- `AutogenImporterService.php` — Phase 5 — AutoGen / legacy import adapter. (~2528 tok)
- `BridgeRegistry.php` — Bridge Registry (PRD §24.5 — Phase 4). (~446 tok)
- `CommentService.php` — Comments / Collaboration Service (UX-009 — Phase 4). (~825 tok)
- `ComplianceExportService.php` — Multi-compliance audit export packs (Phase 5 Feature 7). (~2967 tok)
- `ContinuousScannerService.php` — Continuous security scanning (Phase 5 Feature 8). (~1965 tok)
- `DeploymentService.php` — Execute the Phase 2 promotion pipeline: (~1430 tok)
- `DurableWorkflowEngine.php` — Phase 2 durable workflow engine. (~4816 tok)
- `EmbeddingService.php` — Embedding service (PRD §9.2 — Phase 4). (~522 tok)
- `EventBus.php` — EventBus façade (Phase 4 — PRD §13). (~759 tok)
- `FlowiseService.php` — AI Workflow Studio Flowise client + 2-way sync engine (~2200 tok)
- `FlowiseService.php` — FlowiseService — central HTTP client + two-way sync engine for the (~3755 tok)
- `GitOpsService.php` — Phase 5 — GitOps integration (Argo CD / Flux). (~1926 tok)
- `InProcessEventBus.php` — Redis-Streams / in-process EventBus (Phase 2 baseline, Phase 4 fallback). (~701 tok)
- `IntentRouterService.php` — Intent Router (PRD §27.1 anchor scenario — Phase 4). (~892 tok)
- `KafkaEventBus.php` — Kafka / Redpanda event bus implementation (Phase 4). (~1795 tok)
- `KnowledgeGraphService.php` — Knowledge Graph Service (PRD §9.2 — Phase 4). (~725 tok)
- `LocalizationService.php` — Phase 5 — Localization service. (~584 tok)
- `MarketplaceService.php` — Template Marketplace orchestrator. (~3510 tok)
- `MarketplaceSignatureService.php` — Marketplace artifact signing — cosign / Sigstore–style HMAC stub. (~933 tok)
- `McpGateway.php` — Phase-1 MCP Gateway prototype — Tool Registry (DB) + Client Manager (HTTP ping). (~884 tok)
- `MemoryService.php` — Memory Service (PRD §9.2 / §17 — Phase 4) (~1681 tok)
- `MetaAgentOrchestrator.php` — Meta-Agent Orchestrator (PRD §8.2 / §12.2 — Phase 4). (~2007 tok)
- `MetaAgentReviewService.php` — Phase 4/5 meta-agents: Documentation, QA/Test, Security Review. (~1389 tok)
- `MigrationService.php` — Migration Service (PRD §12.2 / §24.5 — Phase 4). (~2861 tok)
- `ModelRegistryService.php` — Model Registry (PRD §9.2 / §17 / §24.4 — Phase 4) (~445 tok)
- `ModelRouter.php` — Model Router (PRD §9.2 — Phase 4) (~2010 tok)
- `NetworkPolicyService.php` — Network policy enforcement (Phase 3). (~1033 tok)
- `ObservabilityService.php` — Observability Service (PRD §22 — Phase 4). (~1315 tok)
- `OpaPolicyService.php` — Full policy-as-code engine (Phase 2 baseline + Phase 3 expansion). (~3166 tok)
- `OpenHandsClient.php` — OpenHands Software Agent SDK client (Phase 2). (~786 tok)
- `PortfolioCostService.php` — Portfolio-scale cost governance. (~1515 tok)
- `PromptEvaluationService.php` — Prompt Evaluation Service (PRD §9.2, AC-5 — Phase 4) (~1471 tok)
- `PromptInjectionService.php` — Prompt-injection detection (Phase 3 — Should-priority). (~1320 tok)
- `PromptRegistryService.php` — Prompt Registry (PRD §9.2, §24.4 — Phase 4) (~1200 tok)
- `ProviderBudgetService.php` — Per-tenant model budget enforcement (Phase 3). (~1270 tok)
- `ProviderRegistry.php` — Provider Registry — the runtime adapter map. (~471 tok)
- `QdrantClient.php` — Qdrant REST client (PRD §9.2 — Phase 4). (~1400 tok)
- `RagSearchService.php` — Lightweight RAG-style search for marketplace listings. (~1130 tok)
- `RbacService.php` — Full RBAC/ABAC engine (Phase 3). (~1911 tok)
- `ReplayService.php` — Replay Service (PRD §22 / OBS-006 — Phase 4). (~719 tok)
- `SecretService.php` — Vault-backed secret management (Phase 2 baseline + Phase 3 hardening). (~2162 tok)
- `SecurityScannerService.php` — Security Scanner pipeline (Phase 3). (~2862 tok)
- `TemplateService.php` — Template Manager — create, export (JSON/YAML/ZIP), import (manifest+secret-scan), (~3603 tok)
- `TestRunnerService.php` — TestRunnerService: execute, runForAsset (~1043 tok)
- `ToolSandboxService.php` — Tool Sandbox (Phase 3). (~1399 tok)
- `VersionDiffService.php` — Recursive JSON diff. Returns added/removed/changed. (~570 tok)
- `WorkflowRuntime.php` — Phase-1 synchronous workflow engine (LangGraph-style state machine in-process). (~1409 tok)

## backend/app/Services/Bridges/

- `BridgeAdapter.php` — Base class for Phase 4 cross-framework bridges (Dify / Flowise / SIM / CrewAI). (~664 tok)
- `CrewAiBridge.php` — CrewAI Template Runtime (PRD §24.5.13 — Phase 4). (~776 tok)
- `DifyBridge.php` — Dify Bridge (PRD §24.5.4 — Phase 4). (~399 tok)
- `FlowiseBridge.php` — Flowise Bridge (PRD §24.5.3 — Phase 4). (~355 tok)
- `SimBridge.php` — SIM Bridge (PRD §24.5.1 — Phase 4). (~308 tok)

## backend/app/Services/MetaAgents/

- `AgentBuilderAgent.php` — Agent Builder Agent (PRD §12.2 — Phase 4). (~935 tok)
- `BaseMetaAgent.php` — Base contract for all Phase 4 meta-agents. (~297 tok)
- `DevOpsDeploymentAgent.php` — DevOps Deployment Agent (PRD §12.2 — Phase 4). (~860 tok)
- `DocumentationAgent.php` — Documentation Agent (PRD §12.2 — Phase 4). (~1023 tok)
- `GovernanceAgent.php` — Governance Agent (PRD §12.2 — Phase 4). (~460 tok)
- `McpBuilderAgent.php` — MCP Builder Agent (PRD §12.2 — Phase 4). (~1105 tok)
- `MigrationAgent.php` — Migration Agent (PRD §12.2 — Phase 4). (~454 tok)
- `PlatformArchitectAgent.php` — Platform Architect Agent (PRD §12.2 — Phase 4). (~1094 tok)
- `QaTestAgent.php` — QA / Test Agent (PRD §12.2 — Phase 4). (~565 tok)
- `SecurityReviewAgent.php` — Security Review Agent (PRD §12.2 — Phase 4). (~556 tok)
- `TemplateManagerAgent.php` — Template Manager Agent (PRD §12.2 — Phase 4). (~605 tok)
- `WorkflowBuilderAgent.php` — Workflow Builder Agent (PRD §12.2 — Phase 4). (~1029 tok)

## backend/app/Services/Providers/

- `ClaudeProvider.php` — ClaudeProvider: name, supportsModel, complete (~543 tok)
- `GoogleAdkProvider.php` — Google ADK (Agent Development Kit) provider adapter (Phase 3). (~1180 tok)
- `OllamaProvider.php` — Ollama / vLLM-compatible local LLM provider (Phase 4 — PRD §9.2, §24.5). (~795 tok)
- `OpenAiProvider.php` — OpenAiProvider: name, supportsModel, complete (~496 tok)

## backend/app/Support/

- `Audit.php` — Tiny helper to record audit events. Phase 1 baseline; full export (~281 tok)
- `WorkflowGraphValidator.php` — Phase-1 workflow graph validation (WF-001 save rules). (~524 tok)

## backend/bootstrap/

- `app.php` (~275 tok)
- `providers.php` (~24 tok)

## backend/bootstrap/cache/

- `.gitignore` — Git ignore rules (~4 tok)
- `packages.php` (~256 tok)
- `services.php` (~5867 tok)

## backend/config/

- `app.php` (~1140 tok)
- `auth.php` (~1078 tok)
- `cache.php` (~983 tok)
- `cors.php` (~111 tok)
- `database.php` (~1862 tok)
- `filesystems.php` (~676 tok)
- `logging.php` (~1158 tok)
- `mail.php` — Declares of (~969 tok)
- `queue.php` (~1120 tok)
- `sanctum.php` (~828 tok)
- `services.php` (~443 tok)
- `session.php` (~2093 tok)

## backend/database/

- `.gitignore` — Git ignore rules (~3 tok)

## backend/database/factories/

- `UserFactory.php` — Model factory: UserFactory (~279 tok)

## backend/database/migrations/

- `0001_01_01_000000_create_users_table.php` — Run the migrations. (~393 tok)
- `0001_01_01_000001_create_cache_table.php` — Run the migrations. (~232 tok)
- `0001_01_01_000002_create_jobs_table.php` — Run the migrations. (~484 tok)
- `2026_05_19_000001_create_platform_core_tables.php` — Phase 1 / 2 asset model — Tenants, Projects, Users, Agents, MCP Servers, (~3231 tok)
- `2026_05_19_005628_create_personal_access_tokens_table.php` — Run the migrations. (~231 tok)
- `2026_05_19_100000_create_phase2_lifecycle_tables.php` — Phase 2 — Lifecycle & Reliability tables (~2585 tok)
- `2026_05_19_200000_create_phase3_governance_tables.php` — Phase 3 — Enterprise Governance & Integrations tables (~4019 tok)
- `2026_05_19_250000_create_phase4_advanced_platform_tables.php` — Phase 4 — Advanced Multi-Agent Platform (~5928 tok)
- `2026_05_19_300000_create_phase5_marketplace_tables.php` — Phase 5 — Marketplace, Scale & Productization (~4899 tok)
- `2026_05_19_400000_create_flowise_studio_tables.php` — flowise_agents/flowise_runs/flowise_syncs (~900 tok)
- `2026_05_19_400000_create_flowise_studio_tables.php` — AI Workflow Studio — FlowiseAI integration tables. (~1253 tok)

## backend/database/seeders/

- `DatabaseSeeder.php` — Database seeder: DatabaseSeeder (~2791 tok)
- `Phase3Seeder.php` — Database seeder: Phase3Seeder (~5072 tok)
- `Phase4Seeder.php` — Phase 4 seed data — Model catalog, routing rules, default prompts, (~2386 tok)
- `Phase5Seeder.php` — Phase 5 demo data — Marketplace + Analytics + Compliance + GitOps + Locales. (~5998 tok)

## backend/docker/

- `entrypoint.sh` — Laravel 12 dev entrypoint (~1235 tok)

## backend/public/

- `.htaccess` — Apache configuration (~198 tok)
- `index.php` (~145 tok)
- `robots.txt` (~6 tok)

## backend/resources/css/

- `app.css` — /*.blade.php'; (~112 tok)

## backend/resources/js/

- `app.js` (~7 tok)
- `bootstrap.js` (~37 tok)

## backend/resources/views/

- `welcome.blade.php` — Blade: welcome (~22019 tok)

## backend/routes/

- `api.php` (~7006 tok)
- `console.php` (~85 tok)
- `web.php` (~29 tok)

## backend/storage/

- `.seeded` (~0 tok)

## backend/storage/app/

- `.gitignore` — Git ignore rules (~9 tok)

## backend/storage/app/private/

- `.gitignore` — Git ignore rules (~4 tok)

## backend/storage/app/private/audit-exports/13/

- `a48e438e-68a2-4276-9ffb-63d566efe090.csv` (~63 tok)

## backend/storage/app/private/audit-exports/77/

- `8f755518-8bff-4e39-9d2f-04836499289e.csv` (~64 tok)
- `925d7869-0b15-43e4-afc1-014af89a9f03.csv` (~64 tok)
- `9ff7cf7c-3507-4a5f-a310-1f750f9f73a0.csv` (~63 tok)
- `b0b8af4a-8f25-4c91-b82a-7cdf28c97cdc.csv` (~63 tok)
- `c22ac894-3796-4357-a21c-e624873e3954.csv` (~64 tok)
- `d453e270-6503-4500-84ac-d0d5c0448de2.csv` (~64 tok)

## backend/storage/app/private/audit-exports/9/

- `3c787bcf-3b39-46c4-981d-010ed29e55fe.csv` (~63 tok)

## backend/storage/app/public/

- `.gitignore` — Git ignore rules (~4 tok)

## backend/storage/framework/

- `.gitignore` — Git ignore rules (~32 tok)

## backend/storage/framework/cache/

- `.gitignore` — Git ignore rules (~6 tok)

## backend/storage/framework/cache/data/

- `.gitignore` — Git ignore rules (~4 tok)

## backend/storage/framework/sessions/

- `.gitignore` — Git ignore rules (~4 tok)

## backend/storage/framework/testing/

- `.gitignore` — Git ignore rules (~4 tok)

## backend/storage/framework/views/

- `.gitignore` — Git ignore rules (~4 tok)
- `09ee47cb6b2ead6fe59c09eda6d900b2.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/header.blade.php ENDPAT... (~2765 tok)
- `0cd3f42f50837d1c0987bdb8d9888ff2.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/file-with-line.blade.ph... (~490 tok)
- `0d91cc84ca3ef31e353ece892735402b.php` — total: totalPages, hasPrevious, hasNext, visiblePages (~6103 tok)
- `136a7c656d1a0ed4e2d0901fc10aa84a.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/copy.blade.php EN... (~167 tok)
- `19d1ca22cd8db231f88e0685e9c3a20e.php` (~22080 tok)
- `1bbc6102c081b21bf9f2f593a9b0e218.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/routing-parameter.blade... (~1217 tok)
- `25559de93bb3f75197f65d31431cc854.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/vendor-frames.blade.php... (~2862 tok)
- `25b0d221b0b3a2f9f0f84bf62ea8d4d3.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/separator.blade.php END... (~103 tok)
- `26328897c1aa10f79e9e2d0d5329b82e.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/routing.blade.php ENDPA... (~956 tok)
- `269b0fee0189e73c1136e66adf27750b.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/chevrons-down-up.... (~212 tok)
- `2cd01824a837fcbad18f4451278dfa3d.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/topbar.blade.php ENDPAT... (~1668 tok)
- `419551074a50fbe34ef86a0e07ca7190.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/trace.blade.php ENDPATH... (~1864 tok)
- `508f1950e4e8efe8ee069df2bd0b7937.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/globe.blade.php E... (~262 tok)
- `5d354b2893d50f9829507cb5db08ec2b.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/laravel-ascii-spotlight... (~1093 tok)
- `6f7d30915538961ccb48e148b50dff1e.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/request-body.blade.php ... (~1194 tok)
- `6f8dc7bde97ea89b8aa5e7e3c794d591.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/http-method.blade.php E... (~1133 tok)
- `7ab7d5c4b4b95b090d192a15c186d211.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/laravel-ascii.bla... (~1065 tok)
- `80206a5900f7d9f27081a913e0b15676.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/vendor-frame.blade.php ... (~1219 tok)
- `8200d78d1269662c2e1ba8b7bf12d0c9.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/check.blade.php E... (~98 tok)
- `8b77322ea40de4645844c2e3622c3810.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/layout.blade.php ENDPAT... (~770 tok)
- `8c2da46ec4c83d4f3a4a8806fe1bffb0.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/request-url.blade.php E... (~2747 tok)
- `8d82578c6b7f2296eff92bdd034ec3f8.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/request-header.blade.ph... (~536 tok)
- `92b723efff48ff59a4baed38fab1e075.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/badge.blade.php ENDPATH... (~807 tok)
- `95e3164b72bdaa6d9137dfc0a56b4aae.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/chevron-left.blad... (~113 tok)
- `9745f6a6f3fcc1ddd95648c9a006bc71.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/markdown.blade.php ENDPATH**/ ?> (~693 tok)
- `ac4c02a786db2cf28ea74e9136b850c9.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/syntax-highlight.blade.... (~815 tok)
- `b0160d9ee887b000436a549b742bcf73.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/database.blade.ph... (~205 tok)
- `bae129cef9e600352d1c88ca55b5c61c.php` (~7984 tok)
- `bff06be78bce3fed83e9c65b8001e4b4.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/folder.blade.php ... (~239 tok)
- `c597eabc01b3b531b9dda87258a1b39e.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/chevrons-right.bl... (~140 tok)
- `cacad9aa301188bb671ec61542d90cdf.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/folder-open.blade... (~275 tok)
- `cfb3b0d08523933b25d0e9e498df3235.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/chevrons-left.bla... (~140 tok)
- `d1f36e3de69f0a26360ce06ac4b749a6.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/chevron-right.bla... (~113 tok)
- `d54b4b1eb6bf70ee3b6a52e36ce7e503.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/frame-code.blade.php EN... (~868 tok)
- `d8023e53ca38edd3353749f456935467.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/alert.blade.php E... (~564 tok)
- `ed53e6526bfb3c939b903194d34698dc.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/frame.blade.php ENDPATH... (~2933 tok)
- `ed6d86ec5660851da9f9a0dad3f4f308.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/icons/chevrons-up-down.... (~203 tok)
- `f188496d5460aa7137f13bb1da4b2db1.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/formatted-source.blade.... (~853 tok)
- `f497199d26d29b5bb4d0007bbb834737.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/section-container.blade... (~103 tok)
- `f905b6dee768cbfc0ad33952a263d185.php` — PATH /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/Providers/../resources/exceptions/renderer/components/empty-state.blade.php E... (~349 tok)

## backend/storage/logs/

- `.gitignore` — Git ignore rules (~4 tok)

## backend/tests/

- `TestCase.php` — Declares TestCase (~38 tok)

## backend/tests/Feature/

- `ExampleTest.php` — A basic test example. (~96 tok)
- `PlatformPhase1Test.php` — PlatformPhase1Test: test_health_endpoint, test_login_and_me, test_agent_crud_creates_version_and_audit, test_mcp_health_check + 2 more (~911 tok)
- `PlatformPhase2Test.php` — PlatformPhase2Test: test_approval_queue_endpoint_returns_paginated, test_approval_gated_workflow_pauses_and_resumes, test_agent_version_diff, test_... (~1809 tok)
- `PlatformPhase3Test.php` — PlatformPhase3Test: test_rbac_roles_endpoint_returns_roles, test_rbac_permissions_endpoint, test_rbac_my_permissions_returns_tenant_role, test_admi... (~3010 tok)
- `PlatformPhase4Test.php` — Phase 4 — Advanced Multi-Agent Platform (~2391 tok)
- `PlatformPhase5Test.php` — PlatformPhase5Test: test_marketplace_publish_signs_and_publishes_listing, test_marketplace_install_in_dev_succeeds_and_creates_workflow, test_marke... (~3106 tok)

## backend/tests/Unit/

- `ExampleTest.php` — A basic test example. (~65 tok)

## docs/

- `DR_RUNBOOK.md` — Regional Failover Runbook — v1.0 (Phase 5) (~735 tok)
- `MARKETPLACE_GUIDE.md` — Template Marketplace — Operator & Publisher Guide (~951 tok)
- `OPERATIONS_RUNBOOK.md` — Operations Runbook — Phase 3 (~1152 tok)
- `PHASE1_CHECKLIST.md` — Phase 1 Completion Checklist (~593 tok)
- `PHASE2_CHECKLIST.md` — Phase 2 — Lifecycle & Reliability — Completion Checklist (~1590 tok)
- `PHASE2_MIGRATION.md` — Phase 2 — Migration Guide (~344 tok)
- `PHASE3_CHECKLIST.md` — Phase 3 — Enterprise Governance & Integrations — Completion Checklist (~1705 tok)
- `PHASE3_MIGRATION.md` — Phase 3 — Migration Guide (~917 tok)
- `PHASE4_CHECKLIST.md` — Phase 4 — Advanced Multi-Agent Platform · Checklist (~1280 tok)
- `PHASE4_MIGRATION.md` — Phase 4 — Migration & Operations Guide (~1251 tok)
- `PHASE5_CHECKLIST.md` — Phase 5 — Marketplace, Scale & Productization — Completion Checklist (~1263 tok)
- `PHASE5_MIGRATION.md` — Phase 5 — Migration Guide (~663 tok)
- `SDK_QUICKSTART.md` — Public SDKs & CLI — Quickstart (~667 tok)
- `SOC2_CONTROL_MAPPING.md` — SOC 2 Readiness — Control Mapping Document (~1755 tok)
- `v1.0_RELEASE_NOTES.md` — Enterprise AI + MCP + Multi-Agent Platform — v1.0 Release Notes (~748 tok)

## docs/kubernetes/

- `README.md` — Project documentation (~378 tok)

## frontend/

- `Dockerfile` — Docker container definition (~146 tok)
- `next-env.d.ts` — / <reference types="next" /> (~75 tok)
- `next.config.mjs` — Next.js configuration (~113 tok)
- `package-lock.json` — npm lock file (~83048 tok)
- `package.json` — Node.js package manifest (~335 tok)
- `postcss.config.mjs` (~22 tok)
- `tailwind.config.ts` — Tailwind CSS configuration (~183 tok)
- `tsconfig.json` — TypeScript configuration (~173 tok)
- `tsconfig.tsbuildinfo` (~73725 tok)

## frontend/docker/

- `entrypoint.sh` — Next.js dev entrypoint — installs deps on first boot and starts the dev server. (~135 tok)

## frontend/src/app/

- `globals.css` — Styles: 4 rules (~230 tok)
- `layout.tsx` — inter (~331 tok)
- `page.tsx` — Home (~30 tok)
- `providers.tsx` — Providers — uses useState (~301 tok)

## frontend/src/app/(app)/

- `layout.tsx` — AppLayout (~54 tok)

## frontend/src/app/(app)/a2a/

- `page.tsx` — A2APage — renders table — uses useState, useEffect (~1648 tok)

## frontend/src/app/(app)/admin/

- `page.tsx` — AdminUI (~62 tok)

## frontend/src/app/(app)/agents/

- `page.tsx` — AgentsPage — renders table, modal — uses useState, useMutation, useQuery, useMemo (~5284 tok)

## frontend/src/app/(app)/analytics/

- `page.tsx` — AnalyticsPage — renders table — uses useQuery, useMemo (~2029 tok)

## frontend/src/app/(app)/approvals/

- `page.tsx` — STATUS_BADGE — renders table — uses useQuery, useMutation (~1733 tok)

## frontend/src/app/(app)/audit-reports/

- `page.tsx` — AuditReportsPage — renders table — uses useState, useEffect (~2486 tok)

## frontend/src/app/(app)/autogen-import/

- `page.tsx` — SAMPLE_PAYLOADS — uses useQuery, useMutation (~1593 tok)

## frontend/src/app/(app)/bridges/

- `page.tsx` — BridgesPage — uses useState, useEffect (~1286 tok)

## frontend/src/app/(app)/chat/

- `page.tsx` — ChatPage — renders form — uses useState (~1052 tok)

## frontend/src/app/(app)/codegen/

- `page.tsx` — CodegenPage — renders table — uses useState, useQuery, useMutation (~1448 tok)

## frontend/src/app/(app)/compliance/

- `page.tsx` — CompliancePage — renders table — uses useQuery, useState, useMutation (~1844 tok)

## frontend/src/app/(app)/console/

- `page.tsx` — ConsoleUI (~65 tok)

## frontend/src/app/(app)/continuous-security/

- `page.tsx` — RISK_TONE — renders table — uses useState, useQuery, useMutation (~2203 tok)

## frontend/src/app/(app)/cost-governance/

- `page.tsx` — CostGovernancePage — renders form — uses useQuery, useState, useMutation (~2201 tok)

## frontend/src/app/(app)/dashboard/

- `page.tsx` — DashboardUI — uses useQuery (~651 tok)

## frontend/src/app/(app)/deployments/

- `page.tsx` — ENVIRONMENTS — renders table — uses useState, useQuery, useMutation (~3097 tok)

## frontend/src/app/(app)/event-bus/

- `page.tsx` — EventBusPage — renders table — uses useState, useEffect (~1778 tok)

## frontend/src/app/(app)/gitops/

- `page.tsx` — DRIFT_TONE — renders table — uses useQuery, useState, useMutation (~1753 tok)

## frontend/src/app/(app)/marketplace/

- `page.tsx` — CATEGORIES — renders form — uses useState, useQuery, useMemo (~1437 tok)

## frontend/src/app/(app)/marketplace/[id]/

- `page.tsx` — MarketplaceListingPage — uses useRouter, useState, useQuery, useMutation (~2170 tok)

## frontend/src/app/(app)/marketplace/publish/

- `page.tsx` — MarketplacePublishPage — renders form — uses useRouter, useState, useQuery, useMutation (~2024 tok)

## frontend/src/app/(app)/mcp-servers/

- `page.tsx` — McpServersPage — renders modal (~2905 tok)

## frontend/src/app/(app)/memory/

- `page.tsx` — MemoryPage — uses useState, useEffect (~1874 tok)

## frontend/src/app/(app)/meta-agents/

- `page.tsx` — MetaAgentsPage — uses useState, useEffect (~2029 tok)

## frontend/src/app/(app)/migrations/

- `page.tsx` — MigrationsPage — renders table — uses useState, useEffect (~1142 tok)

## frontend/src/app/(app)/models/

- `page.tsx` — ModelsPage — uses useState, useEffect (~1527 tok)

## frontend/src/app/(app)/network-policies/

- `page.tsx` — NetworkPoliciesPage — renders table — uses useState, useEffect (~1131 tok)

## frontend/src/app/(app)/observability/

- `page.tsx` — ObservabilityPage — uses useState, useEffect (~1544 tok)

## frontend/src/app/(app)/opa-policies/

- `page.tsx` — CATEGORIES — uses useState, useEffect (~1775 tok)

## frontend/src/app/(app)/operations/

- `page.tsx` — OperationsDashboardPage — renders table — uses useQuery (~1653 tok)

## frontend/src/app/(app)/platform/

- `page.tsx` — PlatformUI (~65 tok)

## frontend/src/app/(app)/prompts/

- `page.tsx` — PromptsPage — uses useState, useEffect (~1846 tok)

## frontend/src/app/(app)/provider-budgets/

- `page.tsx` — PROVIDER_COLORS — renders table — uses useState, useEffect (~1501 tok)

## frontend/src/app/(app)/rbac/

- `page.tsx` — RbacPage — uses useState, useEffect (~1692 tok)

## frontend/src/app/(app)/runs/

- `layout.tsx` — RunsLayout (~71 tok)
- `page.tsx` — RunsPage — renders table (~3156 tok)

## frontend/src/app/(app)/security-scans/

- `page.tsx` — SEVERITY_COLORS — uses useState, useEffect (~1639 tok)

## frontend/src/app/(app)/templates/

- `page.tsx` — TemplatesPage — uses useState, useQuery, useMutation (~3261 tok)

## frontend/src/app/(app)/workflow-studio/

- `page.tsx` — /workflow-studio — AI Workflow Studio dashboard. (~4563 tok)

## frontend/src/app/(app)/workflow-studio/[id]/

- `page.tsx` — /workflow-studio/[id] — detail page. (~2981 tok)

## frontend/src/app/(app)/workflows/

- `page.tsx` — WorkflowsPage — renders table, modal — uses useState, useMutation, useQuery, useMemo (~3492 tok)

## frontend/src/app/(app)/workflows/[id]/edit/

- `page.tsx` — WorkflowEditPage — uses useParams, useQuery (~530 tok)

## frontend/src/app/(app)/workflow-studio/

- `page.tsx` — WorkflowStudioPage — Flowise agent dashboard, sync/import/create (~2700 tok)

## frontend/src/app/(app)/workflow-studio/[id]/

- `page.tsx` — WorkflowStudioAgentPage — embedded Flowise canvas + run/runs/settings/logs tabs (~2100 tok)

## frontend/src/components/workflow-studio/

- `FlowiseEmbed.tsx` — Themed iframe wrapper for Flowise canvas (~430 tok)
- `RunPanel.tsx` — Right-rail run/test UI calling /api/flowise/agents/:id/run (~720 tok)

## frontend/src/app/(auth)/login/

- `page.tsx` — tenants — renders form — uses useState (~5052 tok)

## frontend/src/components/app-shell/

- `AppShell.tsx` — NAV (~3374 tok)

## frontend/src/components/shared/

- `CommentThread.tsx` — CommentThread — uses useState, useEffect (~592 tok)
- `PageHeader.tsx` — PageHeader (~638 tok)

## frontend/src/components/ui/

- `EnterpriseAIMCPAdminConsole.tsx` — sidebarItems (~8598 tok)
- `EnterpriseAIMCPDashboard.tsx` — kpis — renders table (~6764 tok)
- `EnterpriseAIMCPLoginPage.tsx` — tenants — renders form — uses useState (~4689 tok)
- `EnterpriseAIMCPPlatformConsole.tsx` — navSections (~10632 tok)
- `EnterpriseAIMCPPlatformPage.tsx` — platformLayers (~7750 tok)

## frontend/src/components/workflow-studio/

- `FlowiseEmbed.tsx` — FlowiseEmbed (~586 tok)
- `RunPanel.tsx` — RunPanel (~1140 tok)

## frontend/src/components/workflow/

- `WorkflowBuilder.tsx` — NODE_TYPES_LIST — uses useState, useEffect, useMemo, useCallback (~3707 tok)

## frontend/src/lib/

- `api.ts` — Centralised API client for the Enterprise AI MCP Platform backend. (~9492 tok)
- `auth-context.tsx` — AuthContext (~1359 tok)
- `i18n-context.tsx` — I18nContext (~1447 tok)

## frontend/src/theme/

- `mui-theme.ts` — Futuristic enterprise dark theme — neon cyan + violet accents. (~388 tok)

## infra/

- `otel-collector-config.yaml` (~135 tok)

## infra/argocd/

- `project.yaml` — K8s AppProject: eamcp (~268 tok)
- `root-application.yaml` — K8s Application: eamcp-root (~150 tok)

## infra/argocd/apps/

- `eamcp-dev.yaml` — K8s Application: eamcp-dev (~148 tok)
- `eamcp-prod.yaml` — K8s Application: eamcp-prod (~203 tok)
- `eamcp-staging.yaml` — K8s Application: eamcp-staging (~156 tok)

## infra/grafana/provisioning/dashboards/

- `dashboards.yml` (~56 tok)
- `eamcp-overview.json` (~582 tok)

## infra/grafana/provisioning/datasources/

- `datasources.yml` (~73 tok)

## infra/helm/eamcp/

- `Chart.yaml` (~53 tok)
- `values-dev.yaml` (~488 tok)
- `values-multi-region.yaml` — Phase 5 — Multi-region overlay. (~380 tok)
- `values-prod.yaml` (~670 tok)
- `values-staging.yaml` (~548 tok)
- `values.yaml` (~556 tok)

## infra/helm/eamcp/templates/

- `_helpers.tpl` (~78 tok)
- `backend-deployment.yaml` — K8s Deployment: {{ (~302 tok)

## infra/opa/

- `a2a_federation.rego` — Phase 4 — A2A Federation Policies (PRD §13.2) (~295 tok)
- `approval.rego` — Phase 3 — Approval requirement matrix (PRD Section 21.3). (~432 tok)
- `deployment.rego` — Phase 3 — Deployment promotion policies. (~440 tok)
- `eamcp.rego` — Phase 2 baseline policy — mirrors the built-in fallback in OpaPolicyService. (~269 tok)
- `marketplace.rego` — Phase 5 — Marketplace install policy. (~431 tok)
- `memory_scope.rego` — Phase 4 — Memory Scoping Policy (PRD §9.2 + §17) (~178 tok)
- `meta_agent.rego` — Phase 4 — Meta-Agent Autonomy Policy (PRD §12.2) (~335 tok)
- `network.rego` — Phase 3 — Network access policies. (~230 tok)
- `template_import.rego` — Phase 3 — Template import policies. (~234 tok)
- `tenant_isolation.rego` — Phase 3 — Tenant isolation policies. (~165 tok)

## infra/openhands/

- `index.html` — OpenHands SDK Sandbox (Phase 2 stub) (~117 tok)

## infra/prometheus/

- `prometheus.yml` (~108 tok)

## sdk/cli/

- `package.json` — Node.js package manifest (~96 tok)
- `README.md` — Project documentation (~114 tok)

## sdk/cli/bin/

- `eamcp.mjs` — EAMCP Platform CLI (Phase 5 / v1.0). (~1735 tok)

## sdk/python/

- `pyproject.toml` — Python project configuration (~172 tok)
- `README.md` — Project documentation (~284 tok)

## sdk/python/eamcp/

- `__init__.py` — EAMCP — Enterprise AI + MCP + Multi-Agent Platform Python SDK. (~149 tok)
- `client.py` — View: list, get, create, update, delete, list, get, create, update, delete, list, get, list, list, list, get, list, get, create (~2426 tok)

## sdk/typescript/

- `package.json` — Node.js package manifest (~177 tok)
- `README.md` — Project documentation (~227 tok)
- `tsconfig.json` — TypeScript configuration (~82 tok)

## sdk/typescript/src/

- `index.ts` — EAMCP — Enterprise AI + MCP + Multi-Agent Platform TypeScript SDK. (~2416 tok)
