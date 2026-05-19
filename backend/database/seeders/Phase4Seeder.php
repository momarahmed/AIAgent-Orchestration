<?php

namespace Database\Seeders;

use App\Models\A2APartner;
use App\Models\BridgeConnection;
use App\Models\EventSubscription;
use App\Models\KnowledgeGraphEdge;
use App\Models\KnowledgeGraphNode;
use App\Models\MemoryCollection;
use App\Models\ModelRecord;
use App\Models\ModelRoutingRule;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Phase 4 seed data — Model catalog, routing rules, default prompts,
 * sample memory collections, A2A partner stub, bridges (disabled),
 * and seed knowledge-graph entries for demos.
 */
class Phase4Seeder extends Seeder
{
    public function run(): void
    {
        // ─── Model catalog ───────────────────────────────────────────
        $models = [
            ['slug' => 'openai:gpt-4o',        'provider' => 'openai',     'name' => 'GPT-4o',         'family' => 'gpt',    'capabilities' => ['chat', 'tools', 'vision'], 'context_window' => 128000, 'cost_per_1k_in' => 0.0025, 'cost_per_1k_out' => 0.010, 'latency_p50_ms' => 800, 'is_local' => false],
            ['slug' => 'openai:gpt-4o-mini',   'provider' => 'openai',     'name' => 'GPT-4o mini',    'family' => 'gpt',    'capabilities' => ['chat', 'tools'],         'context_window' => 128000, 'cost_per_1k_in' => 0.00015, 'cost_per_1k_out' => 0.0006, 'latency_p50_ms' => 500, 'is_local' => false],
            ['slug' => 'claude:claude-sonnet-4-20250514', 'provider' => 'claude', 'name' => 'Claude Sonnet 4', 'family' => 'claude', 'capabilities' => ['chat', 'tools', 'vision'], 'context_window' => 200000, 'cost_per_1k_in' => 0.003, 'cost_per_1k_out' => 0.015, 'latency_p50_ms' => 900, 'is_local' => false],
            ['slug' => 'claude:claude-3-haiku-20240307',  'provider' => 'claude', 'name' => 'Claude 3 Haiku',  'family' => 'claude', 'capabilities' => ['chat', 'tools'], 'context_window' => 200000, 'cost_per_1k_in' => 0.00025, 'cost_per_1k_out' => 0.00125, 'latency_p50_ms' => 400, 'is_local' => false],
            ['slug' => 'google_adk:gemini-1.5-pro',   'provider' => 'google_adk', 'name' => 'Gemini 1.5 Pro',   'family' => 'gemini', 'capabilities' => ['chat', 'tools', 'vision'], 'context_window' => 1000000, 'cost_per_1k_in' => 0.00125, 'cost_per_1k_out' => 0.005, 'latency_p50_ms' => 1200, 'is_local' => false],
            ['slug' => 'google_adk:gemini-1.5-flash', 'provider' => 'google_adk', 'name' => 'Gemini 1.5 Flash', 'family' => 'gemini', 'capabilities' => ['chat', 'tools'],         'context_window' => 1000000, 'cost_per_1k_in' => 0.000075, 'cost_per_1k_out' => 0.0003, 'latency_p50_ms' => 600, 'is_local' => false],
            ['slug' => 'ollama:llama3.2:1b',  'provider' => 'ollama', 'name' => 'Llama 3.2 1B (local)', 'family' => 'llama',   'capabilities' => ['chat'],          'context_window' => 32000, 'cost_per_1k_in' => 0, 'cost_per_1k_out' => 0, 'latency_p50_ms' => 250, 'is_local' => true],
            ['slug' => 'ollama:qwen2.5:3b',   'provider' => 'ollama', 'name' => 'Qwen 2.5 3B (local)',  'family' => 'qwen',    'capabilities' => ['chat'],          'context_window' => 32000, 'cost_per_1k_in' => 0, 'cost_per_1k_out' => 0, 'latency_p50_ms' => 350, 'is_local' => true],
        ];
        foreach ($models as $m) ModelRecord::updateOrCreate(['slug' => $m['slug']], $m);

        // ─── Default routing rules (global) ─────────────────────────
        $rules = [
            [
                'name' => 'Prefer cheap GPT-4o-mini for default chat',
                'priority' => 100,
                'match' => ['capability' => 'chat'],
                'route' => ['primary_model_slug' => 'openai:gpt-4o-mini', 'fallback' => ['claude:claude-3-haiku-20240307', 'ollama:llama3.2:1b']],
                'is_active' => true,
            ],
            [
                'name' => 'Route data-residency-tagged traffic to local LLM',
                'priority' => 50,
                'match' => ['residency' => 'local-only'],
                'route' => ['primary_model_slug' => 'ollama:llama3.2:1b', 'fallback' => []],
                'is_active' => true,
            ],
            [
                'name' => 'Use Claude Sonnet for high-stakes reasoning',
                'priority' => 30,
                'match' => ['tag' => 'reasoning'],
                'route' => ['primary_model_slug' => 'claude:claude-sonnet-4-20250514', 'fallback' => ['openai:gpt-4o']],
                'is_active' => true,
            ],
        ];
        foreach ($rules as $r) ModelRoutingRule::updateOrCreate(['name' => $r['name']], $r);

        $primary = Tenant::where('slug', 'esri-saudi')->first() ?: Tenant::first();
        if (! $primary) return;

        // ─── Default prompts ────────────────────────────────────────
        $defaults = [
            ['name' => 'Default System Prompt', 'slug' => 'default-system', 'category' => 'agent_system', 'body' => 'You are a helpful enterprise AI agent. Be concise and accurate.'],
            ['name' => 'GIS Investigation', 'slug' => 'gis-investigation', 'category' => 'meta_agent', 'body' => "Investigate the following GIS issue and produce a remediation plan:\n\n{{question}}"],
            ['name' => 'Tool Selection', 'slug' => 'tool-selection', 'category' => 'tool_select', 'body' => "Given the task '{{task}}', pick the best tools from: {{tools}}. Reply as JSON with tool names."],
        ];
        foreach ($defaults as $d) {
            $p = Prompt::firstOrCreate(
                ['tenant_id' => $primary->id, 'slug' => $d['slug']],
                [
                    'name' => $d['name'],
                    'category' => $d['category'],
                    'current_version' => 1,
                ]
            );
            PromptVersion::firstOrCreate(
                ['prompt_id' => $p->id, 'version' => 1],
                ['body' => $d['body'], 'variables' => [], 'status' => 'active', 'changelog' => 'Initial seed']
            );
        }

        // ─── Default memory collections ─────────────────────────────
        MemoryCollection::firstOrCreate(
            ['tenant_id' => $primary->id, 'slug' => 'project-notes'],
            ['name' => 'Project Notes', 'scope' => 'project', 'vector_namespace' => "t{$primary->id}_project_notes", 'embedding_model' => 'text-embedding-3-small', 'embedding_dim' => 1536]
        );

        // ─── Sample A2A partner (disabled by default) ───────────────
        A2APartner::firstOrCreate(
            ['partner_id' => 'demo-partner'],
            [
                'tenant_id'    => $primary->id,
                'name'         => 'Demo External Partner',
                'framework'    => 'langgraph',
                'endpoint_url' => 'https://demo-partner.example.com/a2a',
                'auth_type'    => 'hmac',
                'status'       => 'pending',
                'capabilities' => ['investigation', 'incident_response'],
            ]
        );

        // ─── Bridge connections (all disabled until tenant enables) ─
        foreach (['dify', 'flowise', 'sim', 'crewai'] as $fw) {
            BridgeConnection::firstOrCreate(
                ['tenant_id' => $primary->id, 'framework' => $fw, 'name' => ucfirst($fw) . ' Bridge'],
                [
                    'endpoint_url'      => null,
                    'enabled'           => false,
                    'requires_approval' => $fw !== 'crewai',
                    'config'            => [],
                ]
            );
        }

        // ─── Default event subscriptions ────────────────────────────
        EventSubscription::firstOrCreate(
            ['name' => 'Audit-log mirror', 'topic' => 'agent.events'],
            [
                'tenant_id'      => null,
                'filter'         => [],
                'handler_type'   => 'webhook',
                'handler_config' => ['webhook_url' => 'http://otel-collector:4318/v1/logs'],
                'is_active'      => false,
            ]
        );

        // ─── Seed knowledge-graph capability nodes ──────────────────
        $kgNodes = [
            ['type' => 'capability', 'label' => 'gis_query'],
            ['type' => 'capability', 'label' => 'database_query'],
            ['type' => 'capability', 'label' => 'incident_response'],
            ['type' => 'capability', 'label' => 'document_rag'],
        ];
        $nodeMap = [];
        foreach ($kgNodes as $n) {
            $nodeMap[$n['label']] = KnowledgeGraphNode::firstOrCreate(
                ['tenant_id' => $primary->id, 'node_type' => $n['type'], 'ref_type' => null, 'ref_id' => null, 'label' => $n['label']],
                ['properties' => []]
            );
        }
        if (isset($nodeMap['gis_query']) && isset($nodeMap['database_query'])) {
            KnowledgeGraphEdge::firstOrCreate([
                'from_node_id' => $nodeMap['gis_query']->id,
                'to_node_id'   => $nodeMap['database_query']->id,
                'relation'     => 'depends_on',
            ]);
        }
    }
}
