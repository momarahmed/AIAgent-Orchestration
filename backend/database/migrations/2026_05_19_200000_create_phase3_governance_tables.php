<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Enterprise Governance & Integrations tables
 *
 * - Full RBAC/ABAC (permissions, role-project scoping, attribute policies)
 * - OPA policy library (authored / versioned policies)
 * - Audit report exports
 * - Security scanner results
 * - Network allowlists per MCP server
 * - Provider budgets (per-tenant model cost caps)
 * - Prompt-injection detection log
 * - Activepieces bridge configuration
 */
return new class extends Migration {
    public function up(): void
    {
        // ─── RBAC / ABAC ───────────────────────────────────────────────

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        // Expand roles table for Phase 3 hierarchy
        if (! Schema::hasColumn('roles', 'level')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unsignedSmallInteger('level')->default(0)->after('label');
                $table->boolean('is_system')->default(false)->after('level');
                $table->text('description')->nullable()->after('is_system');
            });
        }

        // Project-scoped role assignments
        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        // Attribute-based access control policies
        Schema::create('abac_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('resource_type'); // agent|mcp_server|workflow|tool|deployment|project
            $table->string('action');        // create|read|update|delete|execute|deploy|approve
            $table->json('conditions');      // attribute conditions in JSON
            $table->string('effect')->default('allow'); // allow|deny
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'resource_type', 'action']);
        });

        // ─── OPA POLICY LIBRARY ────────────────────────────────────────

        Schema::create('opa_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');   // tool_risk|deployment|approval|network|template_import|tenant_isolation
            $table->text('description')->nullable();
            $table->text('rego_code');
            $table->string('package_path');
            $table->string('status')->default('draft'); // draft|active|disabled|archived
            $table->unsignedInteger('version')->default(1);
            $table->json('metadata')->nullable();
            $table->json('dry_run_result')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category', 'status']);
        });

        Schema::create('opa_policy_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opa_policy_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('rego_code');
            $table->string('status'); // draft|active|disabled
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['opa_policy_id', 'version']);
        });

        // ─── AUDIT EXPORTS / REPORTS ───────────────────────────────────

        Schema::create('audit_report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category'); // asset_change|deployment|approval|tool_call|template_io|security_policy
            $table->text('description')->nullable();
            $table->json('filters');   // default filter criteria
            $table->json('columns');   // columns to include
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('audit_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audit_report_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('format'); // csv|json|pdf
            $table->string('status')->default('queued'); // queued|generating|completed|failed
            $table->json('filters')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('record_count')->default(0);
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        // Append-only archive hash chain for tamper evidence
        if (! Schema::hasColumn('audit_events', 'hash')) {
            Schema::table('audit_events', function (Blueprint $table) {
                $table->string('hash', 64)->nullable()->after('user_agent');
                $table->string('prev_hash', 64)->nullable()->after('hash');
            });
        }

        // ─── SECURITY SCANNER ──────────────────────────────────────────

        Schema::create('security_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scan_type');   // trivy|syft|grype|secret_scan|code_scan
            $table->string('target_type'); // container|template|code|dependency
            $table->string('target_ref')->nullable();
            $table->string('status')->default('queued'); // queued|running|completed|failed
            $table->string('severity_summary')->nullable(); // e.g. "2 critical, 5 high, 12 medium"
            $table->json('findings')->nullable();
            $table->unsignedInteger('critical_count')->default(0);
            $table->unsignedInteger('high_count')->default(0);
            $table->unsignedInteger('medium_count')->default(0);
            $table->unsignedInteger('low_count')->default(0);
            $table->boolean('blocks_promotion')->default(false);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['deployment_id', 'scan_type']);
        });

        // ─── NETWORK ALLOWLISTS ────────────────────────────────────────

        Schema::create('network_allowlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mcp_server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('environment')->default('dev');
            $table->string('direction')->default('egress'); // egress|ingress
            $table->string('host');
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('protocol')->default('tcp');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['mcp_server_id', 'environment']);
        });

        // ─── PROVIDER BUDGETS ──────────────────────────────────────────

        Schema::create('provider_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('provider');     // openai|claude|google_adk
            $table->string('model')->nullable();
            $table->decimal('monthly_limit_usd', 10, 2);
            $table->decimal('current_spend_usd', 10, 2)->default(0);
            $table->string('period')->default('monthly');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('action_on_exceed')->default('reject'); // reject|warn|throttle
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'provider', 'period_start']);
        });

        // Per-call cost tracking on tool_calls
        if (! Schema::hasColumn('tool_calls', 'token_count')) {
            Schema::table('tool_calls', function (Blueprint $table) {
                $table->unsignedInteger('token_count')->default(0)->after('duration_ms');
                $table->decimal('cost_usd', 8, 6)->default(0)->after('token_count');
                $table->string('provider')->nullable()->after('cost_usd');
                $table->string('model')->nullable()->after('provider');
            });
        }

        // ─── PROMPT INJECTION DETECTION ────────────────────────────────

        Schema::create('prompt_injection_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source'); // user_prompt|tool_output|document_upload
            $table->string('detection_method'); // heuristic|llm_guardrail|regex
            $table->string('severity')->default('medium'); // low|medium|high|critical
            $table->string('action_taken')->default('flagged'); // flagged|blocked|warned
            $table->text('suspicious_content')->nullable();
            $table->json('detection_details')->nullable();
            $table->string('related_type')->nullable(); // workflow_run|task_run|chat
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'severity']);
        });

        // ─── ACTIVEPIECES BRIDGE ───────────────────────────────────────

        Schema::create('activepieces_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('piece_name');
            $table->string('flow_id')->nullable();
            $table->string('status')->default('active'); // active|disabled|error
            $table->json('config')->nullable();
            $table->json('secret_refs')->nullable();
            $table->string('risk_level')->default('L1');
            $table->boolean('requires_approval')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'piece_name']);
        });

        // Expand mcp_servers for Phase 3 sandbox & network config
        if (! Schema::hasColumn('mcp_servers', 'sandbox_config')) {
            Schema::table('mcp_servers', function (Blueprint $table) {
                $table->json('sandbox_config')->nullable()->after('health');
                $table->json('network_policy')->nullable()->after('sandbox_config');
                $table->boolean('requires_sandbox')->default(false)->after('network_policy');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mcp_servers', 'sandbox_config')) {
            Schema::table('mcp_servers', function (Blueprint $table) {
                $table->dropColumn(['sandbox_config', 'network_policy', 'requires_sandbox']);
            });
        }

        Schema::dropIfExists('activepieces_connections');
        Schema::dropIfExists('prompt_injection_logs');
        Schema::dropIfExists('provider_budgets');
        Schema::dropIfExists('network_allowlists');
        Schema::dropIfExists('security_scans');

        if (Schema::hasColumn('audit_events', 'hash')) {
            Schema::table('audit_events', function (Blueprint $table) {
                $table->dropColumn(['hash', 'prev_hash']);
            });
        }

        Schema::dropIfExists('audit_exports');
        Schema::dropIfExists('audit_report_templates');
        Schema::dropIfExists('opa_policy_versions');
        Schema::dropIfExists('opa_policies');
        Schema::dropIfExists('abac_policies');
        Schema::dropIfExists('project_user');

        if (Schema::hasColumn('roles', 'level')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn(['level', 'is_system', 'description']);
            });
        }

        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');

        if (Schema::hasColumn('tool_calls', 'token_count')) {
            Schema::table('tool_calls', function (Blueprint $table) {
                $table->dropColumn(['token_count', 'cost_usd', 'provider', 'model']);
            });
        }
    }
};
