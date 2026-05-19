<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI Workflow Studio — FlowiseAI integration tables.
 *
 *  flowise_agents — local mirror of FlowiseAI chatflows (one row per agent).
 *                   Keyed on flowise_chatflow_id; tenant-scoped.
 *  flowise_runs   — execution history for /api/flowise/agents/{id}/run.
 *  flowise_syncs  — per-agent sync attempt log for the two-way sync surface
 *                   (push to Flowise, pull from Flowise).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('flowise_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name', 191);
            $table->string('slug', 191);
            $table->text('description')->nullable();

            // draft | active | paused | error | archived
            $table->string('status', 32)->default('draft');

            // Flowise side identifiers (empty until first push succeeds).
            $table->string('flowise_chatflow_id', 64)->nullable()->unique();
            $table->string('flowise_deployed_url', 500)->nullable();
            $table->string('flowise_api_endpoint', 500)->nullable();

            // Snapshots of the workflow graph + tool/model config so the local
            // app can render summaries without round-tripping to Flowise.
            $table->json('workflow_config')->nullable();
            $table->json('tools_config')->nullable();
            $table->json('model_config')->nullable();

            $table->string('last_run_status', 32)->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'project_id']);
        });

        Schema::create('flowise_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flowise_agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // queued | running | succeeded | failed | cancelled
            $table->string('status', 32)->default('queued');

            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->json('tool_calls')->nullable();
            $table->json('conversation')->nullable();

            $table->unsignedBigInteger('prompt_tokens')->nullable();
            $table->unsignedBigInteger('completion_tokens')->nullable();
            $table->unsignedBigInteger('total_tokens')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->text('error_message')->nullable();
            $table->string('session_id', 64)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['flowise_agent_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('flowise_syncs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flowise_agent_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // push (local→flowise) | pull (flowise→local) | full
            $table->string('direction', 16);
            // pending | success | failed
            $table->string('sync_status', 16)->default('pending');

            $table->string('flowise_chatflow_id', 64)->nullable();
            $table->json('payload')->nullable();
            $table->text('sync_error')->nullable();

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'sync_status']);
            $table->index('flowise_chatflow_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flowise_syncs');
        Schema::dropIfExists('flowise_runs');
        Schema::dropIfExists('flowise_agents');
    }
};
