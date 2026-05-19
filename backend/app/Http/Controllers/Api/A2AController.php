<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\A2AMessage;
use App\Models\A2APartner;
use App\Services\A2AGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A2A Gateway HTTP surface (PRD §13.2 — Phase 4).
 *
 * - GET/POST /api/a2a/partners    — Partner directory (admin).
 * - POST    /api/a2a/send         — Outbound (internal → external).
 * - POST    /api/a2a/inbound/{p}  — Inbound webhook for external partners.
 * - GET     /api/a2a/messages     — Audit log (auditor view).
 */
class A2AController extends Controller
{
    public function __construct(protected A2AGatewayService $gateway) {}

    public function partners(Request $r): JsonResponse
    {
        $q = A2APartner::query();
        if ($r->query('tenant_id')) $q->where('tenant_id', $r->query('tenant_id'));
        return response()->json(['data' => $q->orderByDesc('updated_at')->get()]);
    }

    public function storePartner(Request $r): JsonResponse
    {
        $data = $r->validate([
            'tenant_id'    => 'required|integer|exists:tenants,id',
            'partner_id'   => 'required|string|unique:a2a_partners,partner_id',
            'name'         => 'required|string',
            'framework'    => 'nullable|string',
            'endpoint_url' => 'required|url',
            'auth_type'    => 'in:hmac,bearer,oauth2,mtls',
            'secret_ref'   => 'nullable|string',
            'status'       => 'in:pending,active,disabled,quarantined',
            'capabilities' => 'nullable|array',
        ]);
        return response()->json(A2APartner::create(array_merge($data, [
            'created_by' => $r->user()?->id,
        ])), 201);
    }

    public function updatePartner(Request $r, A2APartner $partner): JsonResponse
    {
        $partner->update($r->only(['name', 'framework', 'endpoint_url', 'auth_type', 'secret_ref', 'status', 'capabilities', 'quarantined']));
        return response()->json($partner->fresh());
    }

    public function send(Request $r): JsonResponse
    {
        $data = $r->validate([
            'partner_id'      => 'required|exists:a2a_partners,id',
            'to_agent'        => 'nullable|string',
            'payload'         => 'required|array',
            'message_type'    => 'nullable|in:request,response,notify,error',
            'priority'        => 'nullable|in:low,normal,high,urgent',
            'workflow_run_id' => 'nullable|integer',
            'from_agent_id'   => 'nullable|integer|exists:agents,id',
        ]);
        $partner = A2APartner::findOrFail($data['partner_id']);
        $message = $this->gateway->send($partner, $data['payload'], $data);
        return response()->json($message, $message->status === 'blocked' ? 403 : 202);
    }

    public function inbound(Request $r, string $partnerId): JsonResponse
    {
        $partner = A2APartner::where('partner_id', $partnerId)->firstOrFail();
        $result  = $this->gateway->receive($partner, $r->all());
        return response()->json($result, $result['accepted'] ? 202 : 403);
    }

    public function messages(Request $r): JsonResponse
    {
        $q = A2AMessage::query();
        if ($r->query('tenant_id'))       $q->where('tenant_id', $r->query('tenant_id'));
        if ($r->query('conversation_id')) $q->where('conversation_id', $r->query('conversation_id'));
        if ($r->query('direction'))       $q->where('direction', $r->query('direction'));
        return response()->json(['data' => $q->orderByDesc('created_at')->paginate(100)->items()]);
    }
}
