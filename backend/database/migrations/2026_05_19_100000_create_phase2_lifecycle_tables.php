<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Lifecycle & Reliability tables
 * - deployments  (env promotion + audit)
 * - approvals    (human-in-the-loop queue)
 * - test_suites + test_executions
 * - secret_refs  (vault-backed secret references)
 * - workflow_run signals/state for durable engine pause/resume
 * - extended risk fields on agents/workflows
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('agents', 'max_risk_level_without_approval')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('max_risk_level_without_approval')->default('L1')->after('risk_level');
                $table->json('environment_config')->nullable()->after('max_risk_level_without_approval');
            });
        }

        if (! Schema::hasColumn('workflows', 'risk_level')) {
            Schema::table('workflows', function (Blueprint $table) {
                $table->string('risk_level')->default('L1')->after('status');
                $table->json('schedule_config')->nullable()->after('trigger_type');
                $table->json('retry_policy')->nullable()->after('schedule_config');
            });
        }

        if (! Schema::hasColumn('workflow_runs', 'paused_at')) {
            Schema::table('workflow_runs', function (Blueprint $table) {
                $table->timestamp('paused_at')->nullable()->after('completed_at');
                $table->timestamp('resumed_at')->nullable()->after('paused_at');
                $table->json('checkpoint')->nullable()->after('resumed_at');
                $table->string('environment')->default('dev')->after('status');
                $table->unsignedInteger('attempt')->default(1)->after('environment');
            });
        }

        if (! Schema::hasColumn('task_runs', 'attempt')) {
            Schema::table('task_runs', function (Blueprint $table) {
                $table->unsignedInteger('attempt')->default(1)->after('status');
                $table->json('retry_policy')->nullable()->after('attempt');
            });
        }

        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('asset_type'); // agent|mcp_server|workflow
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('asset_version_id')->nullable();
            $table->string('environment'); // dev|test|staging|prod
            $table->string('status')->default('pending');
            // pending|validating|testing|scanning|building|deploying|smoke_test|awaiting_approval|approved|rejected|deployed|failed|rolled_back
            $table->json('pipeline')->nullable(); // step-by-step results
            $table->json('config')->nullable();   // env-specific config
            $table->json('secret_refs')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['asset_type', 'asset_id']);
            $table->index(['environment', 'status']);
        });

        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type'); // workflow_run|tool_call|deployment|template_import
            $table->unsignedBigInteger('subject_id');
            $table->string('reason')->nullable();
            $table->string('risk_level')->default('L1');
            $table->string('status')->default('pending'); // pending|approved|rejected|expired|cancelled
            $table->json('payload')->nullable(); // node_id, tool_name, inputs preview, etc.
            $table->json('approver_pool')->nullable(); // user_ids or role names
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_comment')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'subject_type']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('test_suites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('asset_type');
            $table->unsignedBigInteger('asset_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('cases')->nullable(); // array of test cases
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['asset_type', 'asset_id']);
        });

        Schema::create('test_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_suite_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('queued'); // queued|running|passed|failed|error
            $table->json('results')->nullable();
            $table->unsignedInteger('passed')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('secret_refs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('vault_path');
            $table->string('environment')->default('dev'); // dev|test|staging|prod
            $table->string('provider')->default('vault'); // vault|aws_sm|azure_kv|gcp_sm|env
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'environment', 'name']);
        });

        Schema::create('codegen_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind'); // mcp_server|agent|workflow
            $table->string('status')->default('queued'); // queued|running|completed|failed
            $table->text('prompt');
            $table->json('inputs')->nullable();
            $table->json('outputs')->nullable(); // generated files preview
            $table->string('repo_branch')->nullable();
            $table->string('pr_url')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codegen_jobs');
        Schema::dropIfExists('secret_refs');
        Schema::dropIfExists('test_executions');
        Schema::dropIfExists('test_suites');
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('deployments');

        if (Schema::hasColumn('task_runs', 'attempt')) {
            Schema::table('task_runs', function (Blueprint $table) {
                $table->dropColumn(['attempt', 'retry_policy']);
            });
        }
        if (Schema::hasColumn('workflow_runs', 'paused_at')) {
            Schema::table('workflow_runs', function (Blueprint $table) {
                $table->dropColumn(['paused_at', 'resumed_at', 'checkpoint', 'environment', 'attempt']);
            });
        }
        if (Schema::hasColumn('workflows', 'risk_level')) {
            Schema::table('workflows', function (Blueprint $table) {
                $table->dropColumn(['risk_level', 'schedule_config', 'retry_policy']);
            });
        }
        if (Schema::hasColumn('agents', 'max_risk_level_without_approval')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn(['max_risk_level_without_approval', 'environment_config']);
            });
        }
    }
};
