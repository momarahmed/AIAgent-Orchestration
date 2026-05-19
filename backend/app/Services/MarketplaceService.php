<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\McpServer;
use App\Models\MarketplaceInstall;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceRating;
use App\Models\MarketplaceVersion;
use App\Models\Template;
use App\Models\Workflow;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Template Marketplace orchestrator.
 *
 * - Publish: ingest a Template -> create/refresh MarketplaceListing,
 *   run MetaAgentReviewService, sign with MarketplaceSignatureService,
 *   register a MarketplaceVersion.
 * - Install: enforce signature + risk policy via OpaPolicyService,
 *   instantiate the underlying assets via TemplateService.
 * - Rate / review: track ratings with moderation hooks.
 */
class MarketplaceService
{
    public function __construct(
        protected MetaAgentReviewService $review,
        protected MarketplaceSignatureService $signing,
        protected OpaPolicyService $opa,
        protected SecurityScannerService $scanner,
        protected TemplateService $templates,
    ) {}

    public function publish(Template $template, int $publisherId, array $opts = []): MarketplaceListing
    {
        return DB::transaction(function () use ($template, $publisherId, $opts) {
            $slug = $opts['slug'] ?? Str::slug($template->name) . '-' . $template->id;

            $listing = MarketplaceListing::updateOrCreate(
                ['template_id' => $template->id],
                [
                    'tenant_id'           => $template->tenant_id,
                    'publisher_id'        => $publisherId,
                    'slug'                => $slug,
                    'title'               => $opts['title'] ?? $template->name,
                    'description'         => $opts['description'] ?? $template->description,
                    'category'            => $opts['category'] ?? $template->asset_type ?? 'agent',
                    'visibility'          => $opts['visibility'] ?? 'internal',
                    'status'              => 'review',
                    'screenshots'         => $opts['screenshots'] ?? [],
                    'tags'                => $opts['tags'] ?? [],
                    'parameters_schema'   => $template->parameters_schema ?? [],
                    'required_connectors' => $opts['required_connectors'] ?? [],
                    'readme'              => $opts['readme'] ?? null,
                ]
            );

            $manifest = $this->buildManifest($template, $listing, $opts);
            $review   = $this->review->review($manifest, ['tenant_id' => $template->tenant_id]);

            $scan = $this->scanner->scanTemplate(json_encode($manifest), $template->tenant_id, $publisherId);
            $scanResult = [
                'scan_id'          => $scan->id,
                'blocks_promotion' => (bool) ($scan->blocks_promotion ?? false),
                'critical_count'   => (int) ($scan->critical_count ?? 0),
                'high_count'       => (int) ($scan->high_count ?? 0),
                'severity_summary' => $scan->severity_summary ?? null,
            ];

            $blocked = ($review['security']['blocked'] ?? false) || $scanResult['blocks_promotion'];
            $status  = $blocked
                ? 'review'
                : ($review['verdict'] === 'rejected' ? 'review' : 'published');

            $signatureBundle = $this->signing->sign($manifest, $template->tenant_id);
            $sbomHash        = $this->signing->sbomHash($manifest);

            $versionNumber = $opts['version'] ?? $this->nextSemver($listing);
            $version = MarketplaceVersion::create([
                'listing_id'        => $listing->id,
                'version'           => $versionNumber,
                'migration_notes'   => $opts['migration_notes'] ?? null,
                'manifest'          => $manifest,
                'parameters_schema' => $template->parameters_schema ?? [],
                'signature'         => $signatureBundle['signature'],
                'sbom_hash'         => $sbomHash,
                'status'            => $blocked ? 'pending' : 'approved',
                'review_log'        => [
                    'meta_agents' => $review,
                    'scanner'     => $scanResult,
                    'key_id'      => $signatureBundle['key_id'],
                ],
                'published_by'      => $publisherId,
                'published_at'      => $blocked ? null : now(),
            ]);

            $listing->update([
                'status'           => $status,
                'latest_version'   => $version->version,
                'latest_signature' => $signatureBundle['signature'],
                'sbom_hash'        => $sbomHash,
                'signed'           => true,
                'quality_review'   => $review,
            ]);

            Audit::record(
                'marketplace_publish',
                $blocked ? 'review_required' : 'published',
                'MarketplaceListing',
                $listing->id,
                ['version' => $version->version, 'review' => $review, 'blocked' => $blocked],
                null,
                $template->tenant_id,
            );

            return $listing->fresh();
        });
    }

    public function install(MarketplaceListing $listing, array $opts): MarketplaceInstall
    {
        $environment = $opts['environment'] ?? 'dev';
        $tenantId    = $opts['tenant_id'];

        $context = [
            'environment'   => $environment,
            'signed'        => (bool) $listing->signed,
            'visibility'    => $listing->visibility,
            'tenant_id'     => $tenantId,
            'rating_avg'    => (float) $listing->rating_avg,
            'category'      => $listing->category,
        ];

        if (! $listing->signed && in_array($environment, ['staging', 'prod'], true)) {
            throw new \RuntimeException('Unsigned marketplace templates cannot be installed in staging or prod (PRD §21.3).');
        }

        $opaDecision = $this->opa->evaluate('eamcp.marketplace.install', $context);
        if (! ($opaDecision['allowed'] ?? true)) {
            throw new \RuntimeException('OPA policy denied marketplace install: ' . ($opaDecision['reason'] ?? 'unknown'));
        }

        $version = $listing->versions()->where('version', $opts['version'] ?? $listing->latest_version)->latest('id')->first();
        if (! $version) {
            throw new \RuntimeException('Listing has no published version.');
        }

        $created = $this->materialize($listing, $version, $opts);

        $install = MarketplaceInstall::create([
            'listing_id'    => $listing->id,
            'version_id'    => $version->id,
            'tenant_id'     => $tenantId,
            'project_id'    => $opts['project_id'] ?? null,
            'installed_by'  => $opts['user_id'] ?? null,
            'environment'   => $environment,
            'parameters'    => $opts['parameters'] ?? [],
            'created_assets'=> $created,
            'status'        => 'installed',
        ]);

        $listing->increment('install_count');

        Audit::record(
            'marketplace_install',
            'installed',
            'MarketplaceInstall',
            $install->id,
            ['listing_slug' => $listing->slug, 'version' => $version->version, 'environment' => $environment],
            null,
            $tenantId,
        );

        return $install;
    }

    public function rate(MarketplaceListing $listing, int $userId, int $rating, ?string $review = null): MarketplaceRating
    {
        $rating = max(1, min(5, $rating));
        $entry = MarketplaceRating::updateOrCreate(
            ['listing_id' => $listing->id, 'user_id' => $userId],
            ['rating' => $rating, 'review' => $review, 'moderation_state' => 'approved'],
        );

        $stats = MarketplaceRating::where('listing_id', $listing->id)
            ->where('moderation_state', 'approved')
            ->selectRaw('AVG(rating) as avg, COUNT(*) as cnt')
            ->first();

        $listing->update([
            'rating_avg'   => round((float) $stats->avg, 2),
            'rating_count' => (int) $stats->cnt,
        ]);

        Audit::record(
            'marketplace_rating',
            'rated',
            'MarketplaceListing',
            $listing->id,
            ['rating' => $rating, 'user_id' => $userId],
            null,
            $listing->tenant_id,
        );

        return $entry;
    }

    public function nextSemver(MarketplaceListing $listing): string
    {
        if (! $listing->exists || ! $listing->latest_version) {
            return '0.1.0';
        }
        $parts = explode('.', $listing->latest_version);
        $parts = array_map('intval', $parts + [0, 0, 0]);
        $parts[2]++;
        return implode('.', array_slice($parts, 0, 3));
    }

    private function buildManifest(Template $template, MarketplaceListing $listing, array $opts): array
    {
        return [
            'slug'              => $listing->slug,
            'title'             => $listing->title,
            'description'       => $listing->description,
            'category'          => $listing->category,
            'asset_type'        => $template->asset_type,
            'payload'           => $template->payload,
            'parameters_schema' => $template->parameters_schema,
            'tags'              => $listing->tags ?? [],
            'required_connectors' => $listing->required_connectors ?? [],
            'readme'            => $listing->readme,
            'tests'             => $opts['tests'] ?? null,
            'approval_required' => $opts['approval_required'] ?? false,
            'meta' => [
                'publisher_id' => $listing->publisher_id,
                'tenant_id'    => $template->tenant_id,
                'created_at'   => $template->created_at?->toIso8601String(),
            ],
        ];
    }

    private function materialize(MarketplaceListing $listing, MarketplaceVersion $version, array $opts): array
    {
        $manifest   = $version->manifest;
        $tenantId   = $opts['tenant_id'];
        $projectId  = $opts['project_id'] ?? null;
        $parameters = $opts['parameters'] ?? [];

        $payload = $this->applyParameters($manifest['payload'] ?? [], $parameters);
        $assetType = $manifest['asset_type'] ?? 'agent';

        return match ($assetType) {
            'agent' => $this->createAgent($listing, $tenantId, $projectId, $payload),
            'mcp', 'mcp_server' => $this->createMcp($listing, $tenantId, $payload),
            'workflow' => $this->createWorkflow($listing, $tenantId, $projectId, $payload),
            default => ['skipped' => true, 'reason' => "unhandled asset type {$assetType}"],
        };
    }

    private function applyParameters(array $payload, array $parameters): array
    {
        $json = json_encode($payload);
        foreach ($parameters as $k => $v) {
            $json = str_replace('${' . $k . '}', is_scalar($v) ? (string) $v : json_encode($v), $json);
        }
        return json_decode($json, true) ?: $payload;
    }

    private function createAgent($listing, int $tenantId, ?int $projectId, array $payload): array
    {
        $name = ($payload['name'] ?? $listing->title) . ' (Marketplace)';
        $agent = Agent::create(array_merge([
            'tenant_id'  => $tenantId,
            'project_id' => $projectId,
            'name'       => $name,
            'slug'       => $this->uniqueSlug($name),
            'status'     => 'draft',
        ], array_intersect_key($payload, array_flip(['model', 'temperature', 'instructions', 'tools', 'config']))));
        return ['agent_id' => $agent->id];
    }

    private function createMcp($listing, int $tenantId, array $payload): array
    {
        $name = ($payload['name'] ?? $listing->title) . ' (Marketplace)';
        $mcp = McpServer::create(array_merge([
            'tenant_id'  => $tenantId,
            'name'       => $name,
            'slug'       => $this->uniqueSlug($name),
            'status'     => 'draft',
        ], array_intersect_key($payload, array_flip(['endpoint', 'transport', 'category', 'risk_classification', 'requires_sandbox', 'sandbox_config']))));
        return ['mcp_server_id' => $mcp->id];
    }

    private function createWorkflow($listing, int $tenantId, ?int $projectId, array $payload): array
    {
        $name = ($payload['name'] ?? $listing->title) . ' (Marketplace)';
        $wf = Workflow::create(array_merge([
            'tenant_id'  => $tenantId,
            'project_id' => $projectId,
            'name'       => $name,
            'slug'       => $this->uniqueSlug($name),
            'status'     => 'draft',
        ], array_intersect_key($payload, array_flip(['definition', 'description', 'schedule_cron']))));
        return ['workflow_id' => $wf->id];
    }

    private function uniqueSlug(string $base): string
    {
        return \Illuminate\Support\Str::slug($base) . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
    }
}
