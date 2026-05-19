<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 / 2 asset model — Tenants, Projects, Users, Agents, MCP Servers,
 * Workflows, Templates and their immutable Versions. See PRD Section 19.1.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('environment')->default('dev');
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        // Pivot: user belongs to many tenants with a role
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
        });

        // ---------------- Agents ----------------
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft|staging|production|archived
            $table->string('risk_level')->default('L1');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['project_id', 'slug']);
        });

        Schema::create('agent_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('role')->nullable();
            $table->text('system_instructions')->nullable();
            $table->json('model_config')->nullable();   // provider, model, temperature, fallback...
            $table->json('allowed_mcp_servers')->nullable();
            $table->json('allowed_tools')->nullable();
            $table->string('memory_scope')->default('session'); // none|session|project|tenant
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['agent_id', 'version']);
        });

        // ---------------- MCP Servers ----------------
        Schema::create('mcp_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('transport')->default('http'); // stdio|http|streamable-http|sse
            $table->string('runtime')->default('python');
            $table->string('endpoint')->nullable();
            $table->string('auth_method')->default('none'); // none|api_key|oauth2|bearer
            $table->json('secret_refs')->nullable();
            $table->string('status')->default('draft');
            $table->string('health')->default('unknown');
            $table->timestamp('last_health_check_at')->nullable();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['project_id', 'slug']);
        });

        Schema::create('mcp_server_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mcp_server_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('config')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['mcp_server_id', 'version']);
        });

        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mcp_server_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('input_schema')->nullable();
            $table->json('output_schema')->nullable();
            $table->string('risk_level')->default('L1'); // L0..L4
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['mcp_server_id', 'name']);
        });

        // ---------------- Workflows ----------------
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('status')->default('draft');
            $table->string('trigger_type')->default('manual'); // manual|schedule|webhook|event
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['project_id', 'slug']);
        });

        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('graph_json')->nullable();   // nodes + edges
            $table->json('variables')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['workflow_id', 'version']);
        });

        Schema::create('workflow_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('queued'); // queued|running|completed|failed|cancelled|awaiting_approval
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['workflow_id', 'status']);
        });

        Schema::create('task_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_run_id')->constrained()->cascadeOnDelete();
            $table->string('node_id');
            $table->string('node_type'); // trigger|agent|mcp_tool|approval|condition
            $table->string('status')->default('pending');
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['workflow_run_id', 'node_id']);
        });

        Schema::create('tool_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tool_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mcp_server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tool_name');
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();
        });

        // ---------------- Templates ----------------
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('asset_type'); // agent|mcp_server|workflow
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->json('parameters_schema')->nullable();
            $table->string('visibility')->default('private'); // private|tenant|public
            $table->timestamps();
            $table->softDeletes();
        });

        // ---------------- Audit Events ----------------
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type'); // create|update|delete|deploy|run|approve|...
            $table->string('subject_type')->nullable(); // agent|mcp_server|workflow|...
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('action');
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'subject_type', 'subject_id']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('tool_calls');
        Schema::dropIfExists('task_runs');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('workflow_versions');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('tools');
        Schema::dropIfExists('mcp_server_versions');
        Schema::dropIfExists('mcp_servers');
        Schema::dropIfExists('agent_versions');
        Schema::dropIfExists('agents');
        Schema::dropIfExists('tenant_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('tenants');
    }
};
