<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\EventSubscription;
use App\Services\EventBus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventBusController extends Controller
{
    public function __construct(protected EventBus $bus) {}

    public function status(): JsonResponse
    {
        return response()->json([
            'driver'  => $this->bus->driver(),
            'healthy' => $this->bus->healthy(),
            'topics'  => EventBus::TOPICS,
        ]);
    }

    public function publish(Request $r): JsonResponse
    {
        $data = $r->validate([
            'topic'      => 'required|in:' . implode(',', EventBus::TOPICS),
            'event_type' => 'required|string',
            'payload'    => 'array',
            'headers'    => 'array',
        ]);
        $id = $this->bus->publish($data['topic'], $data['event_type'], $data['payload'] ?? [], $data['headers'] ?? []);
        return response()->json(['event_id' => $id], 202);
    }

    public function subscriptions(Request $r): JsonResponse
    {
        $q = EventSubscription::query();
        if ($r->query('tenant_id')) $q->where('tenant_id', $r->query('tenant_id'));
        if ($r->query('topic')) $q->where('topic', $r->query('topic'));
        return response()->json(['data' => $q->orderBy('topic')->get()]);
    }

    public function storeSubscription(Request $r): JsonResponse
    {
        $data = $r->validate([
            'tenant_id'      => 'nullable|integer|exists:tenants,id',
            'name'           => 'required|string',
            'topic'          => 'required|in:' . implode(',', EventBus::TOPICS),
            'filter'         => 'nullable|array',
            'handler_type'   => 'required|in:workflow,webhook,agent',
            'handler_config' => 'required|array',
            'is_active'      => 'boolean',
        ]);
        return response()->json(EventSubscription::create($data), 201);
    }

    public function log(Request $r): JsonResponse
    {
        $q = EventLog::query();
        if ($r->query('topic')) $q->where('topic', $r->query('topic'));
        if ($r->query('tenant_id')) $q->where('tenant_id', $r->query('tenant_id'));
        if ($r->query('event_type')) $q->where('event_type', $r->query('event_type'));
        return response()->json(['data' => $q->orderByDesc('emitted_at')->paginate(100)->items()]);
    }
}
