<?php

namespace App\Services;

/**
 * Intent Router (PRD §27.1 anchor scenario — Phase 4).
 *
 * Maps a free-form natural-language prompt to the right entry point in
 * the platform:
 *   - meta-agent kind (platform_architect, agent_builder, mcp_builder…)
 *   - or "chat"  → falls through to the conversational ChatController.
 *
 * The classification logic is intentionally simple (keyword + regex)
 * and runs first; if no high-confidence match is found we delegate to
 * the Platform Architect, which is the safest default for "create
 * something new" requests. A future iteration can swap this for an
 * LLM-based router behind the same interface.
 */
class IntentRouterService
{
    /**
     * @return array{intent:string, target:string, confidence:float, reasoning:string}
     */
    public function classify(string $prompt): array
    {
        $p = mb_strtolower($prompt);

        $rules = [
            ['intent' => 'build_solution',   'target' => 'platform_architect', 'patterns' => ['/\bbuild me\b/', '/\bcreate (an?|the) (agent|workflow|mcp|server|platform)/', '/\binvestigate\b.*\bworkflow\b/', '/\bdesign (a|the)\b/']],
            ['intent' => 'create_agent',     'target' => 'agent_builder',      'patterns' => ['/\bagent for\b/', '/\bnew agent\b/']],
            ['intent' => 'create_mcp',       'target' => 'mcp_builder',        'patterns' => ['/\bmcp server\b/', '/\bwrap (our|the) api\b/', '/\bbuild me an? mcp\b/']],
            ['intent' => 'create_workflow',  'target' => 'workflow_builder',   'patterns' => ['/\bworkflow\b/', '/\bautomation\b/', '/\bdaily run\b/']],
            ['intent' => 'import_external',  'target' => 'migration',          'patterns' => ['/\bimport (my|the)? ?(n8n|flowise|dify)\b/', '/\bmigrate (my|the)\b/']],
            ['intent' => 'compliance',       'target' => 'governance',         'patterns' => ['/\bcompliance\b/', '/\baudit\b/', '/\bsoc ?2\b/']],
            ['intent' => 'document',         'target' => 'documentation',      'patterns' => ['/\bdocument(ation)?\b/', '/\bchangelog\b/']],
            ['intent' => 'security_review',  'target' => 'security',           'patterns' => ['/\bsecurity (review|scan)\b/', '/\bvulnerability\b/']],
            ['intent' => 'qa',               'target' => 'qa',                 'patterns' => ['/\btest (the|my)\b/', '/\bqa\b/']],
            ['intent' => 'deploy',           'target' => 'devops',             'patterns' => ['/\bdeploy\b/', '/\brollout\b/', '/\bpromote\b/']],
        ];

        foreach ($rules as $rule) {
            foreach ($rule['patterns'] as $pat) {
                if (preg_match($pat, $p)) {
                    return [
                        'intent'    => $rule['intent'],
                        'target'    => $rule['target'],
                        'confidence'=> 0.9,
                        'reasoning' => "Matched pattern '{$pat}'",
                    ];
                }
            }
        }

        // Conversational fallback when nothing matches.
        if (mb_strlen(trim($prompt)) < 80) {
            return ['intent' => 'chat', 'target' => 'chat', 'confidence' => 0.5, 'reasoning' => 'Short prompt — route to chat.'];
        }
        return ['intent' => 'build_solution', 'target' => 'platform_architect', 'confidence' => 0.6, 'reasoning' => 'Fallback: long prompt → architect.'];
    }
}
