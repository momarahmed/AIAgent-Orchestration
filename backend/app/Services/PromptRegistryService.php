<?php

namespace App\Services;

use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Support\Audit;
use Illuminate\Support\Str;

/**
 * Prompt Registry (PRD §9.2, §24.4 — Phase 4)
 *
 * Treats prompts as first-class versioned assets:
 *   - create / publish / archive / activate-version / render
 *   - rendering substitutes {{var}} placeholders with provided variables
 *   - audit every change for compliance reporting
 */
class PromptRegistryService
{
    public function create(int $tenantId, ?int $projectId, string $name, string $body, array $opts = []): Prompt
    {
        $slug = $opts['slug'] ?? Str::slug($name);
        $prompt = Prompt::create([
            'tenant_id'   => $tenantId,
            'project_id'  => $projectId,
            'name'        => $name,
            'slug'        => $slug,
            'description' => $opts['description'] ?? null,
            'category'    => $opts['category'] ?? 'general',
            'current_version' => 1,
            'created_by'  => $opts['created_by'] ?? null,
        ]);

        $version = PromptVersion::create([
            'prompt_id'  => $prompt->id,
            'version'    => 1,
            'body'       => $body,
            'variables'  => $this->extractVariables($body),
            'metadata'   => $opts['metadata'] ?? [],
            'status'     => 'active',
            'changelog'  => 'Initial version',
            'created_by' => $opts['created_by'] ?? null,
        ]);

        Audit::record('prompt', 'created', 'prompt', $prompt->id, [
            'slug' => $slug, 'version' => 1,
        ], tenantId: $tenantId);

        return $prompt->fresh();
    }

    public function newVersion(Prompt $prompt, string $body, ?string $changelog = null, array $opts = []): PromptVersion
    {
        $nextVersion = (int) ($prompt->versions()->max('version') ?? 0) + 1;

        $version = PromptVersion::create([
            'prompt_id'  => $prompt->id,
            'version'    => $nextVersion,
            'body'       => $body,
            'variables'  => $this->extractVariables($body),
            'metadata'   => $opts['metadata'] ?? [],
            'status'     => $opts['activate'] ?? false ? 'active' : 'draft',
            'changelog'  => $changelog,
            'created_by' => $opts['created_by'] ?? null,
        ]);

        if ($opts['activate'] ?? false) {
            $prompt->update(['current_version' => $nextVersion]);
        }

        Audit::record('prompt', 'version_created', 'prompt', $prompt->id, [
            'version' => $nextVersion, 'changelog' => $changelog,
        ], tenantId: $prompt->tenant_id);

        return $version;
    }

    public function activate(Prompt $prompt, int $version): PromptVersion
    {
        $v = $prompt->versions()->where('version', $version)->firstOrFail();
        $prompt->versions()->where('status', 'active')->update(['status' => 'archived']);
        $v->update(['status' => 'active']);
        $prompt->update(['current_version' => $version]);

        Audit::record('prompt', 'activated', 'prompt', $prompt->id, ['version' => $version], tenantId: $prompt->tenant_id);
        return $v;
    }

    public function render(Prompt $prompt, array $variables = [], ?int $version = null): string
    {
        $v = $version
            ? $prompt->versions()->where('version', $version)->firstOrFail()
            : $prompt->versions()->where('version', $prompt->current_version)->firstOrFail();
        return $this->substitute($v->body, $variables);
    }

    public function diff(Prompt $prompt, int $a, int $b): array
    {
        $va = $prompt->versions()->where('version', $a)->firstOrFail();
        $vb = $prompt->versions()->where('version', $b)->firstOrFail();
        return [
            'a' => ['version' => $a, 'body' => $va->body, 'changelog' => $va->changelog],
            'b' => ['version' => $b, 'body' => $vb->body, 'changelog' => $vb->changelog],
            'changed_chars' => abs(strlen($va->body) - strlen($vb->body)),
            'identical' => $va->body === $vb->body,
        ];
    }

    public function substitute(string $body, array $vars): string
    {
        foreach ($vars as $k => $v) {
            $body = str_replace('{{' . $k . '}}', (string) $v, $body);
        }
        return $body;
    }

    protected function extractVariables(string $body): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/', $body, $m);
        return array_values(array_unique($m[1] ?? []));
    }
}
