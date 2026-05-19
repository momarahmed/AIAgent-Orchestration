<?php

namespace App\Services;

/**
 * Phase 4/5 meta-agents: Documentation, QA/Test, Security Review.
 *
 * On marketplace publish, each candidate template is fanned out to the three
 * meta-agents. In production these are LangGraph agents calling the configured
 * model providers; here we provide a deterministic, fast review that satisfies
 * the same contract (verdict + score + findings).
 *
 * PRD Section 24.5 (meta-agents) + Section 30 (marketplace risk mitigation).
 */
class MetaAgentReviewService
{
    public function review(array $manifest, array $context = []): array
    {
        $documentation = $this->documentationReview($manifest);
        $qa            = $this->qaReview($manifest);
        $security      = $this->securityReview($manifest, $context);

        $composite = round(
            ($documentation['score'] + $qa['score'] + $security['score']) / 3,
            2
        );

        $verdict = match (true) {
            $composite >= 80                              => 'approved',
            $composite >= 60 && $security['blocked'] === false => 'approved_with_warnings',
            default                                        => 'rejected',
        };

        return [
            'verdict'       => $verdict,
            'score'         => $composite,
            'documentation' => $documentation,
            'qa'            => $qa,
            'security'      => $security,
            'reviewed_at'   => now()->toIso8601String(),
        ];
    }

    private function documentationReview(array $manifest): array
    {
        $findings = [];
        $score    = 100;

        foreach (['title', 'description'] as $field) {
            if (empty($manifest[$field])) {
                $findings[] = "missing_{$field}";
                $score     -= 20;
            }
        }
        $readme = $manifest['readme'] ?? null;
        if (empty($readme) || (is_array($readme) && empty($readme['content']))) {
            $findings[] = 'missing_readme';
            $score     -= 15;
        }
        if (empty($manifest['parameters_schema'])) {
            $findings[] = 'no_parameter_documentation';
            $score     -= 10;
        }
        if (empty($manifest['tags']) && empty($manifest['category'])) {
            $findings[] = 'no_classification';
            $score     -= 5;
        }
        return [
            'agent'    => 'documentation-meta-agent',
            'score'    => max(0, $score),
            'findings' => $findings,
        ];
    }

    private function qaReview(array $manifest): array
    {
        $findings = [];
        $score    = 100;

        $payload = $manifest['payload'] ?? [];
        if (($manifest['asset_type'] ?? '') === 'workflow' && empty($payload['nodes'])) {
            $findings[] = 'workflow_has_no_nodes';
            $score     -= 40;
        }
        if (($manifest['asset_type'] ?? '') === 'agent' && empty($payload['model'])) {
            $findings[] = 'agent_missing_model';
            $score     -= 25;
        }
        if (empty($manifest['tests']) && empty($payload['tests'])) {
            $findings[] = 'no_attached_tests';
            $score     -= 10;
        }
        $requiredParams = $manifest['parameters_schema']['required'] ?? [];
        if (! empty($requiredParams) && empty($manifest['parameters_examples'] ?? null)) {
            $findings[] = 'no_parameter_examples';
            $score     -= 5;
        }
        return [
            'agent'    => 'qa-meta-agent',
            'score'    => max(0, $score),
            'findings' => $findings,
        ];
    }

    private function securityReview(array $manifest, array $context): array
    {
        $findings = [];
        $score    = 100;
        $blocked  = false;

        $manifestString = strtolower(json_encode($manifest));

        $dangerousPatterns = [
            'eval('             => 'dynamic_eval_usage',
            'exec('             => 'dynamic_exec_usage',
            'subprocess'        => 'subprocess_invocation',
            'system('           => 'os_system_call',
            'curl '             => 'unbounded_outbound_call',
            'aws_access_key_id' => 'embedded_aws_key',
            'private_key'       => 'embedded_private_key',
            'password='         => 'embedded_password',
        ];

        foreach ($dangerousPatterns as $needle => $tag) {
            if (str_contains($manifestString, $needle)) {
                $findings[] = $tag;
                $score     -= 25;
            }
        }

        $highRiskTags = ['cloud_write', 'production_data', 'pii'];
        $tags = $manifest['tags'] ?? [];
        if (! empty(array_intersect($tags, $highRiskTags)) && empty($manifest['approval_required'] ?? null)) {
            $findings[] = 'high_risk_no_approval_gate';
            $score     -= 15;
        }

        if (in_array('embedded_aws_key', $findings, true) || in_array('embedded_private_key', $findings, true)) {
            $blocked = true;
        }
        return [
            'agent'    => 'security-review-meta-agent',
            'score'    => max(0, $score),
            'findings' => $findings,
            'blocked'  => $blocked,
        ];
    }
}
