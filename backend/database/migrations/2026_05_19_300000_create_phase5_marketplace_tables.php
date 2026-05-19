<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — Marketplace, Scale & Productization
 *
 * - Template Marketplace (internal + external) with signing, RAG search,
 *   ratings, install lifecycle, version history, publisher console.
 * - Advanced analytics snapshots (usage/cost/reliability/template adoption).
 * - Portfolio cost governance (hierarchical budgets, chargeback).
 * - Multi-compliance audit export packs (SOC2/ISO27001/GDPR).
 * - Continuous security scanning lifecycle (SBOM diffs, vulnerability tracking).
 * - GitOps environments (Argo CD / Flux registration + drift state).
 * - AutoGen / Tesslate legacy import jobs.
 * - i18n / Localization storage (Arabic = first non-English locale).
 */
return new class extends Migration {
    public function up(): void
    {
        // ─── TEMPLATE MARKETPLACE ─────────────────────────────────────

        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('publisher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('agent'); // agent | mcp | workflow | policy | prompt | deployment
            $table->string('visibility')->default('internal'); // internal | trusted | external
            $table->string('status')->default('draft');        // draft | review | approved | published | suspended | deprecated
            $table->json('screenshots')->nullable();
            $table->json('tags')->nullable();
            $table->json('parameters_schema')->nullable();
            $table->json('required_connectors')->nullable();
            $table->json('readme')->nullable();
            $table->string('latest_version')->default('0.1.0');
            $table->string('latest_signature')->nullable();
            $table->string('sbom_hash')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('install_count')->default(0);
            $table->json('quality_review')->nullable(); // meta-agent verdicts
            $table->boolean('signed')->default(false);
            $table->timestamps();
            $table->index(['visibility', 'status']);
            $table->index('category');
        });

        Schema::create('marketplace_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->string('version');
            $table->text('migration_notes')->nullable();
            $table->json('manifest');
            $table->json('parameters_schema')->nullable();
            $table->string('signature')->nullable();
            $table->string('sbom_hash')->nullable();
            $table->string('status')->default('approved'); // pending | approved | rejected
            $table->json('review_log')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['listing_id', 'version']);
        });

        Schema::create('marketplace_installs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->foreignId('version_id')->nullable()->constrained('marketplace_versions')->nullOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('environment')->default('dev'); // dev|test|staging|prod
            $table->json('parameters')->nullable();
            $table->json('created_assets')->nullable();   // ids/types of resulting agents, mcp, workflows
            $table->string('status')->default('installed'); // installed | uninstalled | failed
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('marketplace_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('marketplace_listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('review')->nullable();
            $table->string('moderation_state')->default('approved'); // pending | approved | rejected
            $table->timestamps();
            $table->unique(['listing_id', 'user_id']);
        });

        Schema::create('marketplace_signing_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key_id')->unique();
            $table->string('algorithm')->default('HMAC-SHA256');
            $table->text('public_key')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─── ANALYTICS SNAPSHOTS ──────────────────────────────────────

        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->date('day');
            $table->string('kind'); // usage | cost | reliability | template_adoption | quality
            $table->string('metric');
            $table->json('dimensions')->nullable();
            $table->decimal('value', 18, 4)->default(0);
            $table->timestamps();
            $table->index(['tenant_id', 'kind', 'day']);
            $table->index(['kind', 'metric', 'day']);
        });

        Schema::create('agent_quality_scores', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type'); // agent | mcp_server | workflow
            $table->unsignedBigInteger('subject_id');
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->decimal('latency_ms_p95', 12, 2)->default(0);
            $table->decimal('cost_per_run_usd', 12, 4)->default(0);
            $table->decimal('user_rating', 3, 2)->default(0);
            $table->decimal('composite_score', 5, 2)->default(0);
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });

        // ─── PORTFOLIO COST GOVERNANCE ───────────────────────────────

        Schema::create('portfolio_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type'); // organization | tenant | project | agent
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->string('name');
            $table->decimal('monthly_limit_usd', 14, 2);
            $table->decimal('current_spend_usd', 14, 2)->default(0);
            $table->string('action_on_exceed')->default('alert'); // alert | throttle | switch_to_local | block
            $table->json('notify')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('portfolio_budgets')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['scope_type', 'scope_id']);
        });

        Schema::create('chargeback_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_usd', 14, 2)->default(0);
            $table->json('breakdown'); // by_project, by_agent, by_workflow, by_model
            $table->string('status')->default('generated'); // generated | issued | paid
            $table->string('storage_path')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'period_start']);
        });

        Schema::create('cost_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->string('recommendation_type'); // model_swap | cache_more | reduce_tokens | local_llm
            $table->text('message');
            $table->decimal('estimated_savings_usd', 12, 2)->default(0);
            $table->string('status')->default('open'); // open | accepted | dismissed | applied
            $table->timestamps();
        });

        // ─── MULTI-COMPLIANCE AUDIT EXPORTS ──────────────────────────

        Schema::create('compliance_frameworks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // SOC2 | ISO27001 | GDPR | HIPAA | NIST
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('controls'); // map of control_id => description
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('compliance_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('frameworks'); // ['SOC2', 'ISO27001']
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('generating'); // generating | ready | failed | expired
            $table->json('control_mappings')->nullable();
            $table->json('evidence_summary')->nullable();
            $table->string('format')->default('zip'); // zip | pdf | csv | jsonl
            $table->string('storage_path')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        // ─── CONTINUOUS SECURITY SCANNING ─────────────────────────────

        Schema::create('sbom_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('image_ref');
            $table->string('sbom_hash')->unique();
            $table->json('components');
            $table->unsignedInteger('total_packages')->default(0);
            $table->timestamps();
        });

        Schema::create('sbom_diffs', function (Blueprint $table) {
            $table->id();
            $table->string('image_ref');
            $table->foreignId('previous_snapshot_id')->nullable()->constrained('sbom_snapshots')->nullOnDelete();
            $table->foreignId('current_snapshot_id')->constrained('sbom_snapshots')->cascadeOnDelete();
            $table->json('diff'); // added/removed/changed
            $table->string('risk_level')->default('low'); // low | medium | high | critical
            $table->boolean('alert_sent')->default(false);
            $table->timestamps();
            $table->index(['image_ref', 'risk_level']);
        });

        Schema::create('vulnerability_findings', function (Blueprint $table) {
            $table->id();
            $table->string('cve');
            $table->string('image_ref');
            $table->string('package');
            $table->string('installed_version')->nullable();
            $table->string('fixed_version')->nullable();
            $table->string('severity')->default('medium'); // critical|high|medium|low|info
            $table->string('state')->default('open'); // open | accepted_risk | fixed | false_positive
            $table->text('description')->nullable();
            $table->date('deadline')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('history')->nullable(); // state transitions
            $table->timestamps();
            $table->index(['cve', 'image_ref']);
            $table->index(['severity', 'state']);
        });

        // ─── GITOPS ENVIRONMENTS ──────────────────────────────────────

        Schema::create('gitops_environments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('engine')->default('argocd'); // argocd | flux
            $table->string('repo_url');
            $table->string('branch')->default('main');
            $table->string('path');
            $table->string('cluster')->nullable();
            $table->string('namespace')->nullable();
            $table->string('environment_class')->default('dev'); // dev|test|staging|prod
            $table->boolean('auto_sync')->default(false);
            $table->string('drift_state')->default('synced'); // synced | drift_detected | out_of_sync
            $table->timestamp('last_sync_at')->nullable();
            $table->string('last_commit_sha', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('gitops_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('environment_id')->constrained('gitops_environments')->cascadeOnDelete();
            $table->string('commit_sha', 64);
            $table->string('triggered_by')->default('manual'); // manual | auto | drift
            $table->string('status')->default('running'); // running | succeeded | failed | rolled_back
            $table->json('drift_resources')->nullable();
            $table->json('result')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['environment_id', 'status']);
        });

        Schema::create('region_health', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->string('role')->default('primary'); // primary | secondary
            $table->string('status')->default('healthy'); // healthy | degraded | failover_active
            $table->decimal('replication_lag_seconds', 10, 2)->default(0);
            $table->json('measurements')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
            $table->unique('region');
        });

        // ─── LEGACY IMPORTS ──────────────────────────────────────────

        Schema::create('legacy_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_format'); // autogen | tesslate | n8n | flowise | dify | crewai
            $table->json('source_payload');
            $table->json('output_payload')->nullable();
            $table->decimal('confidence_score', 5, 2)->default(0);
            $table->json('review_checklist')->nullable();
            $table->string('status')->default('pending'); // pending | converted | review_required | failed
            $table->timestamps();
            $table->index(['source_format', 'status']);
        });

        // ─── LOCALIZATION ─────────────────────────────────────────────

        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // en, ar, fr, ...
            $table->string('name');
            $table->boolean('rtl')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('locale_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locale_id')->constrained()->cascadeOnDelete();
            $table->string('namespace')->default('common'); // common | marketplace | analytics | ...
            $table->string('key');
            $table->text('value');
            $table->timestamps();
            $table->unique(['locale_id', 'namespace', 'key']);
            $table->index(['namespace', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locale_translations');
        Schema::dropIfExists('locales');
        Schema::dropIfExists('legacy_imports');
        Schema::dropIfExists('region_health');
        Schema::dropIfExists('gitops_syncs');
        Schema::dropIfExists('gitops_environments');
        Schema::dropIfExists('vulnerability_findings');
        Schema::dropIfExists('sbom_diffs');
        Schema::dropIfExists('sbom_snapshots');
        Schema::dropIfExists('compliance_exports');
        Schema::dropIfExists('compliance_frameworks');
        Schema::dropIfExists('cost_recommendations');
        Schema::dropIfExists('chargeback_reports');
        Schema::dropIfExists('portfolio_budgets');
        Schema::dropIfExists('agent_quality_scores');
        Schema::dropIfExists('analytics_snapshots');
        Schema::dropIfExists('marketplace_signing_keys');
        Schema::dropIfExists('marketplace_ratings');
        Schema::dropIfExists('marketplace_installs');
        Schema::dropIfExists('marketplace_versions');
        Schema::dropIfExists('marketplace_listings');
    }
};
