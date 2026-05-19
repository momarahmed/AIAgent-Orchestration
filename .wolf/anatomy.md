# anatomy.md

> Auto-maintained by OpenWolf. Last scanned: 2026-05-19T03:50:35.314Z
> Files: 314 tracked | Anatomy hits: 0 | Misses: 0

## ./

- `.gitignore` — Git ignore rules (~239 tok)
- `CLAUDE.md` — OpenWolf (~57 tok)
- `docker-compose.yml` — Docker Compose services (~2914 tok)
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
- `.phpunit.result.cache` (~158 tok)
- `artisan` — Laravel CLI entry point (~114 tok)
- `composer.json` — PHP package manifest (~824 tok)
- `Dockerfile` — Docker container definition (~284 tok)
- `package.json` — Node.js package manifest (~119 tok)
- `phpunit.xml` (~378 tok)
- `README.md` — Project documentation (~978 tok)
- `vite.config.js` — Vite build configuration (~125 tok)

## backend/app/Console/Commands/

- `DispatchScheduledWorkflows.php` — DispatchScheduledWorkflows: handle (~795 tok)

## backend/app/Contracts/

- `ModelProvider.php` — Interface: ModelProvider (3 methods) (~115 tok)
- `WorkflowEngine.php` — WorkflowEngine — abstract contract that hides the underlying engine (~219 tok)

## backend/app/Http/Controllers/

- `Controller.php` — Controller: Controller (~21 tok)

## backend/app/Http/Controllers/Api/

- `AgentController.php` — AG-013 — agent execution history via task runs on agent nodes. (~1776 tok)
- `ApprovalController.php` — index, show, approve, reject (~378 tok)
- `AuditController.php` — index (~192 tok)
- `AuditReportController.php` — Paginated audit events with filtering. (~1148 tok)
- `AuthController.php` — login, register, me, logout (~690 tok)
- `ChatController.php` — Phase-1 Chat UI backend — single-turn prompt execution with run record (UX-002). (~1060 tok)
- `CodegenController.php` — generateMcp, show, index (~351 tok)
- `CopyController.php` — copyAgent, copyMcp, copyWorkflow (~1340 tok)
- `DebugController.php` — debugAgent, debugTool, debugWorkflowNode, replayRun, resumeRun + 1 more (~754 tok)
- `DeploymentController.php` — index, show, store, approve, rollback (~560 tok)
- `McpServerController.php` — index, store, show, update, destroy + 1 more (~1871 tok)
- `MetricsController.php` — Aggregate KPI / dashboard metrics for the Platform Console. (~924 tok)
- `NetworkPolicyController.php` — index, store, update, destroy, check + 1 more (~985 tok)
- `OpaPolicyController.php` — index, show, store, update, destroy + 5 more (~996 tok)
- `ProjectController.php` — index, store, show, update, destroy (~588 tok)
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

## backend/app/Http/Middleware/

- `EnforceRbac.php` — Phase 3 RBAC middleware — resolves tenant/project from route, checks permission via RbacService (~600 tok)
- `EnforceRbac.php` — Usage in routes: ->middleware('rbac:agents.create') (~691 tok)
- `EnsureTenantIsolation.php` — Phase 3 tenant isolation — blocks cross-tenant access (~350 tok)
- `EnsureTenantIsolation.php` — Verifies that the authenticated user belongs to the tenant referenced (~415 tok)
- `PromptInjectionFilter.php` — Phase 3 prompt-injection filter on chat/workflow routes (~400 tok)
- `PromptInjectionFilter.php` — Scans prompt/input fields for injection patterns. (~447 tok)

## backend/app/Models/

- `AbacPolicy.php` — Model — 10 fields, 2 rels (~184 tok)
- `ActivepiecesConnection.php` — Model — 10 fields, 2 rels (~193 tok)
- `Agent.php` — Model — 10 fields, 2 casts, 4 rels (~289 tok)
- `AgentVersion.php` — Model — 10 fields, 8 casts, 1 rels (~194 tok)
- `Approval.php` — Model — 14 fields, 8 casts (~182 tok)
- `AuditEvent.php` — Model — 10 fields, 2 casts (~116 tok)
- `AuditExport.php` — Model — 12 fields, 3 rels (~271 tok)
- `AuditReportTemplate.php` — Model — 7 fields (~98 tok)
- `CodegenJob.php` — Model — 12 fields, 8 casts (~128 tok)
- `Deployment.php` — Model — 15 fields, 10 casts, 1 rels (~199 tok)
- `McpServer.php` — Model — 17 fields, 4 rels (~346 tok)
- `McpServerVersion.php` — Model — 4 fields, 2 casts, 1 rels (~131 tok)
- `NetworkAllowlist.php` — Model — 10 fields, 2 rels (~175 tok)
- `OpaPolicy.php` — Model — 13 fields, 3 rels (~260 tok)
- `OpaPolicyVersion.php` — Model — 6 fields, 1 rels (~135 tok)
- `Permission.php` — Model — 4 fields, 1 rels (~101 tok)
- `Project.php` — Model — 5 fields, 2 casts, 4 rels (~239 tok)
- `PromptInjectionLog.php` — Model — 10 fields, 2 rels (~175 tok)
- `ProviderBudget.php` — Model — 10 fields, 1 rels (~269 tok)
- `Role.php` — Model — 6 fields, 1 rels (~242 tok)
- `SecretRef.php` — Model — table: secret_refs, 8 fields, 2 casts (~115 tok)
- `SecurityScan.php` — Model — 16 fields, 3 rels (~344 tok)
- `TaskRun.php` — Model — 10 fields, 8 casts, 2 rels (~229 tok)
- `Template.php` — Model — 8 fields, 4 casts (~133 tok)
- `Tenant.php` — Model — 4 fields, 2 casts, 2 rels (~199 tok)
- `TestExecution.php` — Model — 11 fields, 6 casts, 1 rels (~150 tok)
- `TestSuite.php` — Model — 8 fields, 2 casts, 1 rels (~111 tok)
- `Tool.php` — Model — 7 fields, 6 casts, 1 rels (~172 tok)
- `ToolCall.php` — Model — 9 fields, 4 casts (~115 tok)
- `User.php` — Model — 3 fields, 2 rels (~461 tok)
- `Workflow.php` — Model — 11 fields, 4 casts, 4 rels (~298 tok)
- `WorkflowRun.php` — Model — 14 fields, 14 casts, 3 rels (~309 tok)
- `WorkflowVersion.php` — Model — 6 fields, 6 casts, 1 rels (~160 tok)

## backend/app/Providers/

- `AppServiceProvider.php` — AppServiceProvider: register, boot (~508 tok)

## backend/app/Services/

- `ActivepiecesBridge.php` — Activepieces Integration Bridge (Phase 3). (~1411 tok)
- `AgentRuntime.php` — Agent runtime — uses ProviderRegistry (Phase 2) so OpenAI / Claude (~424 tok)
- `ApprovalService.php` — Approval Queue — pause/resume gate for risky tool calls and deployments. (~679 tok)
- `AuditReportService.php` — Audit report generation, export (CSV/JSON/PDF), and tamper-evident hash chain. (~1616 tok)
- `DeploymentService.php` — Execute the Phase 2 promotion pipeline: (~1430 tok)
- `DurableWorkflowEngine.php` — Phase 2 durable workflow engine. (~4816 tok)
- `McpGateway.php` — Phase-1 MCP Gateway prototype — Tool Registry (DB) + Client Manager (HTTP ping). (~884 tok)
- `NetworkPolicyService.php` — Network policy enforcement (Phase 3). (~1033 tok)
- `OpaPolicyService.php` — Full policy-as-code engine (Phase 2 baseline + Phase 3 expansion). (~2944 tok)
- `OpenHandsClient.php` — OpenHands Software Agent SDK client (Phase 2). (~786 tok)
- `PromptInjectionService.php` — Prompt-injection detection (Phase 3 — Should-priority). (~1320 tok)
- `ProviderBudgetService.php` — Per-tenant model budget enforcement (Phase 3). (~1270 tok)
- `ProviderRegistry.php` — Model Control Plane (Phase 2 baseline) — routes calls to a configured provider. (~256 tok)
- `RbacService.php` — Full RBAC/ABAC engine (Phase 3). (~1911 tok)
- `SecretService.php` — Vault-backed secret management (Phase 2 baseline + Phase 3 hardening). (~2162 tok)
- `SecurityScannerService.php` — Security Scanner pipeline (Phase 3). (~2862 tok)
- `TemplateService.php` — Template Manager — create, export (JSON/YAML/ZIP), import (manifest+secret-scan), (~3603 tok)
- `TestRunnerService.php` — TestRunnerService: execute, runForAsset (~1043 tok)
- `ToolSandboxService.php` — Tool Sandbox (Phase 3). (~1399 tok)
- `VersionDiffService.php` — Recursive JSON diff. Returns added/removed/changed. (~570 tok)
- `WorkflowRuntime.php` — Phase-1 synchronous workflow engine (LangGraph-style state machine in-process). (~1409 tok)

## backend/app/Services/Providers/

- `ClaudeProvider.php` — ClaudeProvider: name, supportsModel, complete (~543 tok)
- `GoogleAdkProvider.php` — Google ADK (Agent Development Kit) provider adapter (Phase 3). (~1180 tok)
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
- `services.php` (~358 tok)
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

## backend/database/seeders/

- `DatabaseSeeder.php` — DatabaseSeeder: run (~2769 tok)
- `Phase3Seeder.php` — Phase3Seeder: run (~5072 tok)

## backend/docker/

- `entrypoint.sh` — ------------------------------------------------------------------- (~1030 tok)

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

- `api.php` (~3175 tok)
- `console.php` (~85 tok)
- `web.php` (~29 tok)

## backend/storage/

- `.seeded` (~0 tok)

## backend/storage/app/

- `.gitignore` — Git ignore rules (~9 tok)

## backend/storage/app/private/

- `.gitignore` — Git ignore rules (~4 tok)

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
- `laravel.log` (~106711 tok)

## backend/tests/

- `TestCase.php` — Declares TestCase (~38 tok)

## backend/tests/Feature/

- `ExampleTest.php` — A basic test example. (~96 tok)
- `PlatformPhase1Test.php` — PlatformPhase1Test: test_health_endpoint, test_login_and_me, test_agent_crud_creates_version_and_audit, test_mcp_health_check + 2 more (~911 tok)
- `PlatformPhase2Test.php` — PlatformPhase2Test: test_approval_queue_endpoint_returns_paginated, test_approval_gated_workflow_pau (~1809 tok)
- `PlatformPhase3Test.php` — PlatformPhase3Test: test_rbac_roles_endpoint_returns_roles, test_rbac_permissions_endpoint, test_rba (~3010 tok)

## backend/tests/Unit/

- `ExampleTest.php` — A basic test example. (~65 tok)

## docs/

- `OPERATIONS_RUNBOOK.md` — Operations Runbook — Phase 3 (~1152 tok)
- `PHASE1_CHECKLIST.md` — Phase 1 Completion Checklist (~593 tok)
- `PHASE2_CHECKLIST.md` — Phase 2 — Lifecycle & Reliability — Completion Checklist (~1590 tok)
- `PHASE2_MIGRATION.md` — Phase 2 — Migration Guide (~344 tok)
- `PHASE3_CHECKLIST.md` — Phase 3 — Enterprise Governance & Integrations — Completion Checklist (~1705 tok)
- `PHASE3_MIGRATION.md` — Phase 3 — Migration Guide (~917 tok)
- `SOC2_CONTROL_MAPPING.md` — SOC 2 Readiness — Control Mapping Document (~1755 tok)

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
- `tsconfig.tsbuildinfo` (~72483 tok)

## frontend/docker/

- `entrypoint.sh` — Next.js dev entrypoint — installs deps on first boot and starts the dev server. (~135 tok)

## frontend/src/app/

- `globals.css` — Styles: 4 rules (~230 tok)
- `layout.tsx` — inter (~331 tok)
- `page.tsx` — Home (~30 tok)
- `providers.tsx` — Providers — uses useState (~271 tok)

## frontend/src/app/(app)/

- `layout.tsx` — AppLayout (~54 tok)

## frontend/src/app/(app)/admin/

- `page.tsx` — AdminUI (~62 tok)

## frontend/src/app/(app)/agents/

- `page.tsx` — AgentsPage — renders table, modal (~5284 tok)

## frontend/src/app/(app)/approvals/

- `page.tsx` — STATUS_BADGE — renders table (~1733 tok)

## frontend/src/app/(app)/audit-reports/

- `page.tsx` — AuditReportsPage — renders table (~2486 tok)

## frontend/src/app/(app)/chat/

- `page.tsx` — ChatPage — renders form — uses useState (~1052 tok)

## frontend/src/app/(app)/codegen/

- `page.tsx` — CodegenPage — renders table (~1448 tok)

## frontend/src/app/(app)/console/

- `page.tsx` — ConsoleUI (~65 tok)

## frontend/src/app/(app)/dashboard/

- `page.tsx` — DashboardUI — uses useQuery (~651 tok)

## frontend/src/app/(app)/deployments/

- `page.tsx` — ENVIRONMENTS — renders table (~3097 tok)

## frontend/src/app/(app)/mcp-servers/

- `page.tsx` — McpServersPage — renders modal (~2905 tok)

## frontend/src/app/(app)/network-policies/

- `page.tsx` — NetworkPoliciesPage — renders table (~1131 tok)

## frontend/src/app/(app)/opa-policies/

- `page.tsx` — CATEGORIES (~1775 tok)

## frontend/src/app/(app)/platform/

- `page.tsx` — PlatformUI (~65 tok)

## frontend/src/app/(app)/provider-budgets/

- `page.tsx` — PROVIDER_COLORS — renders table (~1501 tok)

## frontend/src/app/(app)/rbac/

- `page.tsx` — RbacPage (~1692 tok)

## frontend/src/app/(app)/runs/

- `layout.tsx` — RunsLayout (~71 tok)
- `page.tsx` — RunsPage — renders table (~3156 tok)

## frontend/src/app/(app)/security-scans/

- `page.tsx` — SEVERITY_COLORS (~1639 tok)

## frontend/src/app/(app)/templates/

- `page.tsx` — TemplatesPage (~3261 tok)

## frontend/src/app/(app)/workflows/

- `page.tsx` — WorkflowsPage — renders table, modal (~3492 tok)

## frontend/src/app/(app)/workflows/[id]/edit/

- `page.tsx` — WorkflowEditPage — uses useParams, useQuery (~530 tok)

## frontend/src/app/(auth)/login/

- `page.tsx` — tenants — renders form (~5052 tok)

## frontend/src/components/app-shell/

- `AppShell.tsx` — NAV (~2644 tok)

## frontend/src/components/shared/

- `PageHeader.tsx` — PageHeader (~638 tok)

## frontend/src/components/ui/

- `EnterpriseAIMCPAdminConsole.tsx` — sidebarItems (~8598 tok)
- `EnterpriseAIMCPDashboard.tsx` — kpis — renders table (~6764 tok)
- `EnterpriseAIMCPLoginPage.tsx` — tenants — renders form — uses useState (~4689 tok)
- `EnterpriseAIMCPPlatformConsole.tsx` — navSections (~10632 tok)
- `EnterpriseAIMCPPlatformPage.tsx` — platformLayers (~7750 tok)

## frontend/src/components/workflow/

- `WorkflowBuilder.tsx` — NODE_TYPES_LIST (~3707 tok)

## frontend/src/lib/

- `api.ts` — Centralised API client for the Enterprise AI MCP Platform backend. (~4860 tok)
- `auth-context.tsx` — AuthContext (~1359 tok)

## frontend/src/theme/

- `mui-theme.ts` — Futuristic enterprise dark theme — neon cyan + violet accents. (~388 tok)

## infra/

- `otel-collector-config.yaml` (~135 tok)

## infra/helm/eamcp/

- `Chart.yaml` (~53 tok)
- `values-dev.yaml` (~488 tok)
- `values-prod.yaml` (~670 tok)
- `values-staging.yaml` (~548 tok)
- `values.yaml` (~556 tok)

## infra/helm/eamcp/templates/

- `_helpers.tpl` (~78 tok)
- `backend-deployment.yaml` — K8s Deployment (~302 tok)

## infra/opa/

- `approval.rego` (~432 tok)
- `deployment.rego` (~440 tok)
- `eamcp.rego` (~269 tok)
- `network.rego` (~230 tok)
- `template_import.rego` (~234 tok)
- `tenant_isolation.rego` (~165 tok)

## infra/openhands/

- `index.html` — OpenHands SDK Sandbox (Phase 2 stub) (~117 tok)
