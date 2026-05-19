<?php

namespace App\Services;

use App\Models\ActivepiecesConnection;
use App\Models\Tool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Activepieces Integration Bridge (Phase 3).
 *
 * Two integration directions:
 *  1. Workflow Node — call a governed Activepieces flow as a single workflow node
 *  2. MCP Tool Exposure — selected Activepieces pieces are wrapped as MCP tools
 *
 * PRD Section 24.5.5: "Reduces the need to build hundreds of SaaS connectors."
 */
class ActivepiecesBridge
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.activepieces.url', 'http://activepieces:8080'), '/');
    }

    /**
     * Execute an Activepieces flow as a workflow node.
     */
    public function executeFlow(string $flowId, array $inputs = [], ?int $tenantId = null): array
    {
        $connection = ActivepiecesConnection::where('flow_id', $flowId)->first();

        if ($connection && $connection->status !== 'active') {
            return ['success' => false, 'error' => 'Connection is disabled'];
        }

        try {
            $response = Http::timeout(60)
                ->withHeaders($this->authHeaders($tenantId))
                ->post("{$this->baseUrl}/v1/webhooks/{$flowId}", $inputs);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data'    => $response->json(),
                    'flow_id' => $flowId,
                ];
            }

            return [
                'success' => false,
                'error'   => $response->body(),
                'status'  => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('activepieces.flow_failed', ['flow_id' => $flowId, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * List available Activepieces pieces that can be exposed as MCP tools.
     */
    public function listAvailablePieces(): array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders($this->authHeaders())
                ->get("{$this->baseUrl}/v1/pieces");

            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable $e) {
            Log::warning('activepieces.list_pieces_failed', ['error' => $e->getMessage()]);
        }

        return $this->fallbackPieceList();
    }

    /**
     * Register an Activepieces piece as a governed MCP tool.
     */
    public function registerAsMcpTool(
        int $mcpServerId,
        string $pieceName,
        string $actionName,
        string $riskLevel = 'L1',
    ): Tool {
        return Tool::create([
            'mcp_server_id' => $mcpServerId,
            'name'          => "ap_{$pieceName}_{$actionName}",
            'description'   => "Activepieces: {$pieceName} → {$actionName}",
            'risk_level'    => $riskLevel,
            'input_schema'  => [
                'type'       => 'object',
                'properties' => [
                    'inputs' => ['type' => 'object', 'description' => 'Activepieces action inputs'],
                ],
            ],
            'output_schema' => ['type' => 'object'],
        ]);
    }

    /**
     * Get governed connections for a tenant.
     */
    public function getConnections(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return ActivepiecesConnection::where('tenant_id', $tenantId)
            ->orderBy('piece_name')
            ->get();
    }

    protected function authHeaders(?int $tenantId = null): array
    {
        $token = config('services.activepieces.api_key', '');
        return [
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json',
        ];
    }

    /**
     * Fallback piece list for dev / when Activepieces is not running.
     */
    protected function fallbackPieceList(): array
    {
        return [
            ['name' => 'slack',      'displayName' => 'Slack',       'description' => 'Send messages and manage channels'],
            ['name' => 'teams',      'displayName' => 'Microsoft Teams', 'description' => 'Post to channels and chats'],
            ['name' => 'salesforce', 'displayName' => 'Salesforce',  'description' => 'CRM operations'],
            ['name' => 'jira',       'displayName' => 'Jira',        'description' => 'Issue tracking and project management'],
            ['name' => 'github',     'displayName' => 'GitHub',      'description' => 'Repository and PR management'],
            ['name' => 'gmail',      'displayName' => 'Gmail',       'description' => 'Email operations'],
            ['name' => 'google-sheets', 'displayName' => 'Google Sheets', 'description' => 'Spreadsheet operations'],
            ['name' => 'notion',     'displayName' => 'Notion',      'description' => 'Knowledge base management'],
            ['name' => 'discord',    'displayName' => 'Discord',     'description' => 'Discord bot and webhook integration'],
            ['name' => 'hubspot',    'displayName' => 'HubSpot',     'description' => 'Marketing and CRM operations'],
        ];
    }
}
