# anatomy.md

> Auto-maintained by OpenWolf. Last scanned: 2026-05-19T01:29:01.693Z
> Files: 188 tracked | Anatomy hits: 0 | Misses: 0

## ./

- `.gitignore` — Git ignore rules (~229 tok)
- `CLAUDE.md` — OpenWolf (~57 tok)
- `docker-compose.yml` — Docker Compose services (~1202 tok)
- `README.md` — Project documentation (~2178 tok)

## .claude/

- `settings.json` (~441 tok)

## .claude/rules/

- `openwolf.md` (~313 tok)

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
- `artisan` — Laravel CLI entry point (~114 tok)
- `composer.json` — PHP package manifest (~824 tok)
- `Dockerfile` — Docker container definition (~284 tok)
- `package.json` — Node.js package manifest (~119 tok)
- `phpunit.xml` (~378 tok)
- `README.md` — Project documentation (~978 tok)
- `vite.config.js` — Vite build configuration (~125 tok)

## backend/app/Http/Controllers/

- `Controller.php` — Controller: Controller (~21 tok)

## backend/app/Http/Controllers/Api/

- `AgentController.php` — index, store, show, update, destroy + 1 more (~1661 tok)
- `AuditController.php` — index (~192 tok)
- `AuthController.php` — login, register, me, logout (~690 tok)
- `McpServerController.php` — index, store, show, update, destroy + 1 more (~1658 tok)
- `MetricsController.php` — Aggregate KPI / dashboard metrics for the Platform Console. (~924 tok)
- `ProjectController.php` — index, store, show, update, destroy (~588 tok)
- `RunController.php` — index, show (~192 tok)
- `TenantController.php` — index, store, show, update, destroy (~526 tok)
- `WorkflowController.php` — Execute a workflow synchronously. This is the Phase-1 in-process runner — (~3323 tok)

## backend/app/Models/

- `Agent.php` — Model — 8 fields, 4 rels (~252 tok)
- `AgentVersion.php` — Model — 10 fields, 8 casts, 1 rels (~194 tok)
- `AuditEvent.php` — Model — 10 fields, 2 casts (~116 tok)
- `McpServer.php` — Model — 14 fields, 4 casts, 4 rels (~300 tok)
- `McpServerVersion.php` — Model — 4 fields, 2 casts, 1 rels (~131 tok)
- `Project.php` — Model — 5 fields, 2 casts, 4 rels (~239 tok)
- `TaskRun.php` — Model — 10 fields, 8 casts, 2 rels (~229 tok)
- `Template.php` — Model — 8 fields, 4 casts (~133 tok)
- `Tenant.php` — Model — 4 fields, 2 casts, 2 rels (~199 tok)
- `Tool.php` — Model — 7 fields, 6 casts, 1 rels (~172 tok)
- `ToolCall.php` — Model — 9 fields, 4 casts (~115 tok)
- `User.php` — Model — 3 fields, 1 rels (~214 tok)
- `Workflow.php` — Model — 8 fields, 4 rels (~255 tok)
- `WorkflowRun.php` — Model — 9 fields, 8 casts, 2 rels (~223 tok)
- `WorkflowVersion.php` — Model — 6 fields, 6 casts, 1 rels (~160 tok)

## backend/app/Providers/

- `AppServiceProvider.php` — Register any application services. (~97 tok)

## backend/app/Support/

- `Audit.php` — Tiny helper to record audit events. Phase 1 baseline; full export (~281 tok)

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
- `services.php` — Declares of (~278 tok)
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

## backend/database/seeders/

- `DatabaseSeeder.php` — Database seeder: DatabaseSeeder (~1733 tok)

## backend/docker/

- `entrypoint.sh` — Laravel 12 dev entrypoint (~1021 tok)

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

- `api.php` (~564 tok)
- `console.php` (~56 tok)
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
- `laravel.log` (~22010 tok)

## backend/tests/

- `TestCase.php` — Declares TestCase (~38 tok)

## backend/tests/Feature/

- `ExampleTest.php` — A basic test example. (~96 tok)

## backend/tests/Unit/

- `ExampleTest.php` — A basic test example. (~65 tok)

## frontend/

- `Dockerfile` — Docker container definition (~146 tok)
- `next-env.d.ts` — / <reference types="next" /> (~75 tok)
- `next.config.mjs` — Next.js configuration (~113 tok)
- `package-lock.json` — npm lock file (~81345 tok)
- `package.json` — Node.js package manifest (~325 tok)
- `postcss.config.mjs` (~22 tok)
- `tailwind.config.ts` — Tailwind CSS configuration (~183 tok)
- `tsconfig.json` — TypeScript configuration (~173 tok)

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

- `page.tsx` — AgentsPage — renders table, modal — uses useState, useQuery, useMemo, useMutation (~2380 tok)

## frontend/src/app/(app)/console/

- `page.tsx` — ConsoleUI (~65 tok)

## frontend/src/app/(app)/dashboard/

- `page.tsx` — DashboardUI — uses useQuery (~651 tok)

## frontend/src/app/(app)/mcp-servers/

- `page.tsx` — McpServersPage — renders modal — uses useState, useQuery, useMemo, useMutation (~2131 tok)

## frontend/src/app/(app)/platform/

- `page.tsx` — PlatformUI (~65 tok)

## frontend/src/app/(app)/runs/

- `page.tsx` — RunsPage — renders table — uses useQuery, useMemo (~1734 tok)

## frontend/src/app/(app)/workflows/

- `page.tsx` — WorkflowsPage — renders modal — uses useState, useQuery, useMemo, useMutation (~1997 tok)

## frontend/src/app/(auth)/login/

- `page.tsx` — tenants — renders form — uses useState (~5009 tok)

## frontend/src/components/app-shell/

- `AppShell.tsx` — NAV — uses useRouter, useState, useEffect, useMemo (~2151 tok)

## frontend/src/components/shared/

- `PageHeader.tsx` — PageHeader (~638 tok)

## frontend/src/components/ui/

- `EnterpriseAIMCPAdminConsole.tsx` — sidebarItems (~8598 tok)
- `EnterpriseAIMCPDashboard.tsx` — kpis — renders table (~6764 tok)
- `EnterpriseAIMCPLoginPage.tsx` — tenants — renders form — uses useState (~4689 tok)
- `EnterpriseAIMCPPlatformConsole.tsx` — navSections (~10632 tok)
- `EnterpriseAIMCPPlatformPage.tsx` — platformLayers (~7750 tok)

## frontend/src/lib/

- `api.ts` — Centralised API client for the Enterprise AI MCP Platform backend. (~1847 tok)
- `auth-context.tsx` — AuthContext (~882 tok)

## frontend/src/theme/

- `mui-theme.ts` — Futuristic enterprise dark theme — neon cyan + violet accents. (~388 tok)
