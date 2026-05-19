<?php

namespace App\Http\Middleware;

use App\Services\PromptInjectionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Scans prompt/input fields for injection patterns.
 * Applied to routes that accept user-generated prompts (chat, agent, workflow).
 */
class PromptInjectionFilter
{
    public function __construct(protected PromptInjectionService $detector) {}

    public function handle(Request $request, Closure $next): Response
    {
        $fieldsToCheck = ['prompt', 'message', 'system_instructions', 'input', 'query'];
        $textToScan = '';

        foreach ($fieldsToCheck as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $textToScan .= $value . "\n";
            }
        }

        if (empty(trim($textToScan))) {
            return $next($request);
        }

        $result = $this->detector->scan($textToScan, 'user_prompt', [
            'user_id'   => $request->user()?->id,
            'tenant_id' => $request->user()?->tenants?->first()?->id,
            'ip'        => $request->ip(),
        ]);

        if ($result['blocked']) {
            return response()->json([
                'error'   => 'Prompt Injection Detected',
                'message' => 'Your input was flagged as potentially containing prompt injection. Please review and try again.',
                'details' => $result['reasons'] ?? [],
            ], 422);
        }

        if (! empty($result['warnings'])) {
            $request->attributes->set('prompt_injection_warnings', $result['warnings']);
        }

        return $next($request);
    }
}
