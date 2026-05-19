<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceInstall;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceRating;
use App\Models\Template;
use App\Services\MarketplaceService;
use App\Services\RagSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Marketplace endpoints (Phase 5).
 *
 * MKT-001..MKT-008 per PRD § Phase 5 / Section 17.
 */
class MarketplaceController extends Controller
{
    public function __construct(
        protected MarketplaceService $marketplace,
        protected RagSearchService $rag,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;

        $query = MarketplaceListing::query()
            ->where('status', 'published');

        if ($tenantId) {
            $query->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                    ->orWhereIn('visibility', ['external', 'trusted']);
            });
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }
        if ($visibility = $request->query('visibility')) {
            $query->where('visibility', $visibility);
        }
        $query->orderByDesc('install_count')->orderByDesc('rating_avg');

        return response()->json($query->paginate((int) $request->query('per_page', 20)));
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);
        $results = $this->rag->search(
            (string) $request->input('q'),
            $request->user()?->tenant_id,
            $request->query('category'),
            (int) $request->query('limit', 20),
        );
        return response()->json(['query' => $request->input('q'), 'results' => $results]);
    }

    public function show(MarketplaceListing $listing): JsonResponse
    {
        $listing->load('versions', 'ratings.user', 'publisher');
        return response()->json($listing);
    }

    public function publish(Request $request): JsonResponse
    {
        $data = $request->validate([
            'template_id'          => 'required|integer|exists:templates,id',
            'title'                => 'nullable|string|max:255',
            'description'          => 'nullable|string',
            'category'             => 'nullable|string|max:50',
            'visibility'           => 'nullable|string|in:internal,trusted,external',
            'screenshots'          => 'nullable|array',
            'tags'                 => 'nullable|array',
            'required_connectors'  => 'nullable|array',
            'readme'               => 'nullable|array',
            'migration_notes'      => 'nullable|string',
            'version'              => 'nullable|string|max:30',
            'approval_required'    => 'nullable|boolean',
        ]);

        $template = Template::findOrFail($data['template_id']);
        $listing  = $this->marketplace->publish($template, (int) $request->user()->id, $data);

        return response()->json($listing, 201);
    }

    public function install(MarketplaceListing $listing, Request $request): JsonResponse
    {
        $data = $request->validate([
            'environment'   => 'nullable|string|in:dev,test,staging,prod',
            'project_id'    => 'nullable|integer|exists:projects,id',
            'version'       => 'nullable|string',
            'parameters'    => 'nullable|array',
        ]);

        $install = $this->marketplace->install($listing, array_merge($data, [
            'tenant_id' => $request->user()->tenant_id,
            'user_id'   => $request->user()->id,
        ]));

        return response()->json($install, 201);
    }

    public function rate(MarketplaceListing $listing, Request $request): JsonResponse
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:2000',
        ]);
        $entry = $this->marketplace->rate(
            $listing,
            (int) $request->user()->id,
            (int) $data['rating'],
            $data['review'] ?? null,
        );
        return response()->json($entry, 201);
    }

    public function installs(Request $request): JsonResponse
    {
        $q = MarketplaceInstall::query()->where('tenant_id', $request->user()?->tenant_id);
        if ($listingId = $request->query('listing_id')) {
            $q->where('listing_id', $listingId);
        }
        return response()->json($q->latest()->paginate(20));
    }

    public function publisherSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $listings = MarketplaceListing::where('publisher_id', $user->id)->get();

        return response()->json([
            'listings_count'      => $listings->count(),
            'total_installs'      => (int) $listings->sum('install_count'),
            'avg_rating'          => round((float) $listings->avg('rating_avg'), 2),
            'pending_review'      => $listings->where('status', 'review')->count(),
            'listings'            => $listings->map(fn ($l) => [
                'id'             => $l->id,
                'slug'           => $l->slug,
                'title'          => $l->title,
                'status'         => $l->status,
                'install_count'  => $l->install_count,
                'rating_avg'     => (float) $l->rating_avg,
                'latest_version' => $l->latest_version,
                'signed'         => (bool) $l->signed,
            ]),
        ]);
    }
}
