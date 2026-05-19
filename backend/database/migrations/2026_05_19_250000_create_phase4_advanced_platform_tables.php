<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Advanced Multi-Agent Platform
 *
 * Tables:
 *   ─ Model Control Plane (PRD §9.2 / §17 / §24.4) ─
 *      models, prompts, prompt_versions, prompt_evaluations, model_routing_rules
 *   ─ Memory / Knowledge Layer (PRD §9.2) ─
 *      memory_items, memory_collections, knowledge_graph_nodes, knowledge_graph_edges
 *   ─ A2A Gateway (PRD §13.2 / §13.3) ─
 *      a2a_partners, a2a_messages
 *   ─ Event Bus (PRD §13) ─
 *      event_subscriptions, event_consumer_offsets, event_log
 *   ─ Meta-Agent layer (PRD §12.2) ─
 *      meta_agent_runs, meta_agent_actions, migration_imports
 *   ─ Bridges (PRD §24.5) ─
 *      bridge_connections, bridge_executions
 *   ─ Collaboration (UX-009) ─
 *      asset_comments
 *   ─ Observability replay (OBS-006) ─
 *      run_replays
 */
return new class extends Migration {
    public function up(): void
    {
        // ─── MODEL CONTROL PLANE ──────────────────────────────────────

        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('provider'); // openai|claude|google_adk|ollama|vllm|external
            $table->string('name');
            $table->string('family')->nullable();      // gpt|claude|gemini|llama|mistral
            $table->json('capabilities')->nullable();  // ["chat","tools","vision","embedding"]
            $table->unsignedInteger('context_window')->default(8000);
            $table->decimal('cost_per_1k_in', 10, 6)->default(0);
            $table->decimal('cost_per_1k_out', 10, 6)->default(0);
            $table->unsignedInteger('latency_p50_ms')->default(1000);
            $table->boolean('is_local')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['provider', 'is_active']);
        });

        Schema::create('model_routing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('priority')->default(100);
            $table->json('match');     // {capability, max_cost_per_1k, max_latency_ms, residency, tag}
            $table->json('route');     // {primary_model_slug, fallback: [slug, ...]}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'is_active', 'priority']);
        });

        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // agent_system|tool_select|meta_agent|user_template
            $table->unsignedInteger('current_version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->text('body');
            $table->json('variables')->nullable();    // declared {{vars}}
            $table->json('metadata')->nullable();
            $table->string('status')->default('draft'); // draft|active|archived
            $table->text('changelog')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['prompt_id', 'version']);
        });

        Schema::create('prompt_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prompt_version_id')->constrained()->cascadeOnDelete();
            $table->string('model_slug');
            $table->string('dataset')->default('default');
            $table->decimal('quality_score', 5, 2)->default(0);     // 0..100
            $table->decimal('cost_score', 5, 2)->default(0);
            $table->decimal('latency_score', 5, 2)->default(0);
            $table->unsignedInteger('sample_count')->default(0);
            $table->json('breakdown')->nullable();
            $table->timestamps();
            $table->index(['prompt_id', 'model_slug']);
        });

        // ─── MEMORY / KNOWLEDGE LAYER ─────────────────────────────────

        Schema::create('memory_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('scope')->default('project'); // none|session|project|tenant
            $table->string('vector_namespace');          // qdrant collection name
            $table->string('embedding_model')->default('text-embedding-3-small');
            $table->unsignedInteger('embedding_dim')->default(1536);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('memory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memory_collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type'); // chat|tool_output|document|run|note
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('vector_id')->nullable(); // ID in Qdrant
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['memory_collection_id', 'session_id']);
            $table->index(['tenant_id', 'project_id']);
        });

        Schema::create('knowledge_graph_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('node_type'); // agent|mcp_server|tool|template|workflow|capability
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('label');
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'node_type']);
        });

        Schema::create('knowledge_graph_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_node_id')->constrained('knowledge_graph_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('knowledge_graph_nodes')->cascadeOnDelete();
            $table->string('relation'); // depends_on|uses|implements|imports
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->unique(['from_node_id', 'to_node_id', 'relation'], 'kg_edge_unique');
        });

        // ─── A2A GATEWAY ──────────────────────────────────────────────

        Schema::create('a2a_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('partner_id')->unique();    // namespaced external id
            $table->string('name');
            $table->string('framework')->nullable();   // langgraph|crewai|autogen|google_adk|openai|custom
            $table->string('endpoint_url');
            $table->string('auth_type')->default('hmac'); // hmac|bearer|oauth2|mtls
            $table->string('secret_ref')->nullable();
            $table->string('status')->default('pending'); // pending|active|disabled|quarantined
            $table->boolean('quarantined')->default(false);
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('a2a_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id')->unique();
            $table->uuid('conversation_id')->index();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('workflow_runs')->nullOnDelete();
            $table->string('from_agent'); // free-form locator: tenant:agent_slug or partner_id:agent
            $table->string('to_agent');
            $table->string('from_scope')->default('internal'); // internal|external
            $table->string('to_scope')->default('internal');
            $table->string('message_type')->default('request'); // request|response|notify|error
            $table->string('priority')->default('normal');      // low|normal|high|urgent
            $table->string('direction'); // inbound|outbound
            $table->string('status')->default('pending'); // pending|delivered|failed|blocked
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->index(['tenant_id', 'created_at']);
        });

        // ─── EVENT BUS ────────────────────────────────────────────────

        Schema::create('event_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('topic'); // agent.events|tool.events|workflow.events|run.status|deployment.events|approval.events
            $table->json('filter')->nullable(); // match criteria on event payload
            $table->string('handler_type'); // workflow|webhook|agent
            $table->json('handler_config'); // {workflow_id} | {webhook_url} | {agent_id}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['topic', 'is_active']);
        });

        Schema::create('event_consumer_offsets', function (Blueprint $table) {
            $table->id();
            $table->string('consumer_group');
            $table->string('topic');
            $table->unsignedInteger('partition')->default(0);
            $table->unsignedBigInteger('offset')->default(0);
            $table->timestamps();
            $table->unique(['consumer_group', 'topic', 'partition'], 'event_offset_unique');
        });

        Schema::create('event_log', function (Blueprint $table) {
            $table->id();
            $table->string('topic')->index();
            $table->string('event_type')->index();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('event_id')->unique();
            $table->uuid('trace_id')->nullable()->index();
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->unsignedBigInteger('partition_offset')->nullable();
            $table->string('status')->default('emitted'); // emitted|consumed|failed
            $table->timestamp('emitted_at')->useCurrent();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });

        // ─── META-AGENT LAYER ─────────────────────────────────────────

        Schema::create('meta_agent_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('meta_agent'); // platform_architect|agent_builder|mcp_builder|workflow_builder|template_manager|qa|security|devops|documentation|migration|governance
            $table->text('prompt');
            $table->string('status')->default('queued'); // queued|running|awaiting_approval|completed|failed|cancelled
            $table->json('plan')->nullable();        // decomposed steps
            $table->json('artifacts')->nullable();   // produced agent_ids, mcp_server_ids, workflow_ids, template_ids
            $table->json('approvals')->nullable();   // approval_ids awaited/granted
            $table->decimal('cost_usd', 10, 4)->default(0);
            $table->unsignedInteger('token_usage')->default(0);
            $table->uuid('trace_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'meta_agent', 'status']);
        });

        Schema::create('meta_agent_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_agent_run_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('action');      // design|generate|test|scan|deploy|notify|wait_approval
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->string('status')->default('pending'); // pending|running|succeeded|failed|skipped
            $table->boolean('approval_required')->default(false);
            $table->foreignId('approval_id')->nullable()->constrained('approvals')->nullOnDelete();
            $table->text('reasoning')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('migration_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_format'); // n8n|flowise|dify|json|yaml
            $table->string('filename')->nullable();
            $table->json('raw_source');
            $table->json('translated')->nullable();
            $table->decimal('confidence', 5, 2)->default(0);
            $table->json('review_checklist')->nullable();
            $table->string('status')->default('parsed'); // parsed|imported|failed
            $table->foreignId('workflow_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ─── BRIDGES ──────────────────────────────────────────────────

        Schema::create('bridge_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('framework'); // dify|flowise|sim|crewai
            $table->string('name');
            $table->string('endpoint_url')->nullable();
            $table->string('secret_ref')->nullable();
            $table->json('config')->nullable();
            $table->boolean('enabled')->default(false); // feature-flagged per tenant
            $table->boolean('requires_approval')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'framework', 'enabled']);
        });

        Schema::create('bridge_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bridge_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_run_id')->nullable()->constrained('workflow_runs')->nullOnDelete();
            $table->string('action'); // call_flow|run_app|exec_crew|import
            $table->json('input')->nullable();
            $table->json('output')->nullable();
            $table->string('status')->default('queued'); // queued|running|succeeded|failed
            $table->unsignedInteger('duration_ms')->nullable();
            $table->decimal('cost_usd', 10, 4)->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });

        // ─── COLLABORATION (UX-009) ───────────────────────────────────

        Schema::create('asset_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('asset_type'); // agent|mcp_server|workflow|template|run|prompt
            $table->unsignedBigInteger('asset_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('asset_comments')->nullOnDelete();
            $table->text('body');
            $table->json('mentions')->nullable();     // [user_id, ...]
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['asset_type', 'asset_id']);
        });

        // ─── REPLAY (OBS-006) ────────────────────────────────────────

        Schema::create('run_replays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_run_id')->constrained('workflow_runs')->cascadeOnDelete();
            $table->foreignId('replay_run_id')->nullable()->constrained('workflow_runs')->nullOnDelete();
            $table->foreignId('replayed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('pinned_versions')->nullable(); // {agent_version_id, mcp_version_id, prompt_version_id, model_slug}
            $table->string('status')->default('queued'); // queued|running|completed|failed
            $table->json('diff')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        // ─── AGENT EXTENSIONS ─────────────────────────────────────────
        // Memory scope + meta-agent flag on agents.
        if (! Schema::hasColumn('agents', 'memory_scope')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('memory_scope')->default('session')->after('environment_config'); // none|session|project|tenant
                $table->boolean('is_meta')->default(false)->after('memory_scope');
                $table->string('meta_agent_kind')->nullable()->after('is_meta');
                $table->string('preferred_model_slug')->nullable()->after('meta_agent_kind');
                $table->foreignId('prompt_id')->nullable()->after('preferred_model_slug')->constrained()->nullOnDelete();
            });
        }

        // Optional trace_id on workflow_runs for OBS distributed tracing
        if (! Schema::hasColumn('workflow_runs', 'trace_id')) {
            Schema::table('workflow_runs', function (Blueprint $table) {
                $table->uuid('trace_id')->nullable()->after('status')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workflow_runs', 'trace_id')) {
            Schema::table('workflow_runs', function (Blueprint $table) {
                $table->dropColumn('trace_id');
            });
        }
        if (Schema::hasColumn('agents', 'memory_scope')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropConstrainedForeignId('prompt_id');
                $table->dropColumn(['memory_scope', 'is_meta', 'meta_agent_kind', 'preferred_model_slug']);
            });
        }

        Schema::dropIfExists('run_replays');
        Schema::dropIfExists('asset_comments');
        Schema::dropIfExists('bridge_executions');
        Schema::dropIfExists('bridge_connections');
        Schema::dropIfExists('migration_imports');
        Schema::dropIfExists('meta_agent_actions');
        Schema::dropIfExists('meta_agent_runs');
        Schema::dropIfExists('event_log');
        Schema::dropIfExists('event_consumer_offsets');
        Schema::dropIfExists('event_subscriptions');
        Schema::dropIfExists('a2a_messages');
        Schema::dropIfExists('a2a_partners');
        Schema::dropIfExists('knowledge_graph_edges');
        Schema::dropIfExists('knowledge_graph_nodes');
        Schema::dropIfExists('memory_items');
        Schema::dropIfExists('memory_collections');
        Schema::dropIfExists('prompt_evaluations');
        Schema::dropIfExists('prompt_versions');
        Schema::dropIfExists('prompts');
        Schema::dropIfExists('model_routing_rules');
        Schema::dropIfExists('models');
    }
};
