<?php

namespace App\Services;

use App\Models\PromptInjectionLog;
use Illuminate\Support\Facades\Log;

/**
 * Prompt-injection detection (Phase 3 — Should-priority).
 *
 * Two-pass approach:
 *  1. Heuristic regex patterns (fast, always-on)
 *  2. LLM guardrail second pass on suspicious inputs (when configured)
 */
class PromptInjectionService
{
    protected array $heuristicPatterns = [
        '/ignore\s+(all\s+)?previous\s+instructions/i',
        '/ignore\s+(all\s+)?above\s+instructions/i',
        '/disregard\s+(all\s+)?prior\s+(instructions|context)/i',
        '/you\s+are\s+now\s+(a|an)\s+/i',
        '/system\s*:\s*(you|ignore|forget|override)/i',
        '/\bDAN\b.*\bjailbreak/i',
        '/pretend\s+(to\s+be|you\s+are)\s+/i',
        '/bypass\s+(the\s+)?(safety|content|filter|restriction)/i',
        '/\<\|.*?(system|endoftext|im_start).*?\|?\>/i',
        '/\[\s*SYSTEM\s*\]/i',
        '/do\s+not\s+follow\s+(the\s+)?(rules|guidelines|instructions)/i',
        '/repeat\s+(the\s+)?(system|initial|secret)\s+(prompt|instructions|message)/i',
        '/what\s+(is|are)\s+(your|the)\s+(system|initial|secret)\s+(prompt|instructions)/i',
        '/reveal\s+(your|the)\s+(system|hidden|secret)\s+(prompt|instructions)/i',
        '/output\s+(your|the)\s+(system|initial)\s+(prompt|message|instructions)/i',
    ];

    /**
     * @return array{blocked:bool,warnings:array,reasons:array}
     */
    public function scan(string $text, string $source = 'user_prompt', array $context = []): array
    {
        $blocked  = false;
        $warnings = [];
        $reasons  = [];

        // Pass 1: Heuristic patterns
        $heuristicResult = $this->heuristicScan($text);
        if ($heuristicResult['detected']) {
            $severity = $heuristicResult['match_count'] >= 3 ? 'high' : 'medium';

            if ($severity === 'high') {
                $blocked = true;
                $reasons = $heuristicResult['patterns'];
            } else {
                $warnings = $heuristicResult['patterns'];
            }

            $this->logDetection($source, 'heuristic', $severity, $blocked ? 'blocked' : 'flagged', $text, [
                'patterns_matched' => $heuristicResult['patterns'],
                'match_count'      => $heuristicResult['match_count'],
            ], $context);
        }

        // Pass 2: LLM guardrail (when configured and heuristics flagged something)
        if (! empty($warnings) && config('services.guardrail.enabled')) {
            $llmResult = $this->llmGuardrailScan($text);
            if ($llmResult['injection_likely']) {
                $blocked = true;
                $reasons = array_merge($reasons, ['LLM guardrail flagged input']);

                $this->logDetection($source, 'llm_guardrail', 'high', 'blocked', $text, [
                    'llm_response' => $llmResult,
                ], $context);
            }
        }

        return compact('blocked', 'warnings', 'reasons');
    }

    /**
     * Scan tool output before feeding it back into a prompt.
     */
    public function scanToolOutput(string $output, array $context = []): array
    {
        return $this->scan($output, 'tool_output', $context);
    }

    protected function heuristicScan(string $text): array
    {
        $matched = [];

        foreach ($this->heuristicPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $matched[] = $pattern;
            }
        }

        return [
            'detected'    => ! empty($matched),
            'patterns'    => $matched,
            'match_count' => count($matched),
        ];
    }

    protected function llmGuardrailScan(string $text): array
    {
        // Phase 3 stub: integrate with configured guardrail LLM
        // In production, call a dedicated classifier model or the provider's moderation API
        return ['injection_likely' => false, 'confidence' => 0.0];
    }

    protected function logDetection(
        string $source,
        string $method,
        string $severity,
        string $action,
        string $content,
        array $details,
        array $context,
    ): void {
        try {
            PromptInjectionLog::create([
                'tenant_id'         => $context['tenant_id'] ?? null,
                'user_id'           => $context['user_id'] ?? null,
                'source'            => $source,
                'detection_method'  => $method,
                'severity'          => $severity,
                'action_taken'      => $action,
                'suspicious_content'=> mb_substr($content, 0, 2000),
                'detection_details' => $details,
                'related_type'      => $context['related_type'] ?? null,
                'related_id'        => $context['related_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('prompt_injection.log_failed', ['error' => $e->getMessage()]);
        }
    }
}
