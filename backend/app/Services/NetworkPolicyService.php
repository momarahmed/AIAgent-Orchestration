<?php

namespace App\Services;

use App\Models\McpServer;
use App\Models\NetworkAllowlist;
use Illuminate\Support\Facades\Log;

/**
 * Network policy enforcement (Phase 3).
 *
 * Manages per-MCP-server egress allowlists and validates that
 * outbound connections are permitted before MCP tool execution.
 */
class NetworkPolicyService
{
    /**
     * Check if a given host:port is allowed for an MCP server in an environment.
     */
    public function isAllowed(int $mcpServerId, string $host, ?int $port, string $environment = 'dev'): bool
    {
        $rules = NetworkAllowlist::where('mcp_server_id', $mcpServerId)
            ->where('environment', $environment)
            ->where('is_active', true)
            ->get();

        if ($rules->isEmpty()) {
            // No rules defined — allow all in dev, deny all in prod
            return $environment === 'dev';
        }

        foreach ($rules as $rule) {
            if ($this->matchesRule($rule, $host, $port)) {
                return true;
            }
        }

        Log::warning('network_policy.denied', [
            'mcp_server_id' => $mcpServerId,
            'host'          => $host,
            'port'          => $port,
            'environment'   => $environment,
        ]);

        return false;
    }

    /**
     * Get all allowlist entries for an MCP server.
     */
    public function getAllowlist(int $mcpServerId, ?string $environment = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = NetworkAllowlist::where('mcp_server_id', $mcpServerId);
        if ($environment) {
            $query->where('environment', $environment);
        }
        return $query->orderBy('environment')->orderBy('host')->get();
    }

    /**
     * Generate Kubernetes NetworkPolicy YAML for an MCP server.
     */
    public function generateK8sNetworkPolicy(McpServer $mcpServer, string $environment): array
    {
        $allowlist = $this->getAllowlist($mcpServer->id, $environment);

        $egressRules = [];
        foreach ($allowlist->where('direction', 'egress') as $rule) {
            $egressRule = [
                'to' => [['ipBlock' => ['cidr' => $this->resolveHostToCidr($rule->host)]]],
            ];

            if ($rule->port) {
                $egressRule['ports'] = [['port' => $rule->port, 'protocol' => strtoupper($rule->protocol)]];
            }

            $egressRules[] = $egressRule;
        }

        return [
            'apiVersion' => 'networking.k8s.io/v1',
            'kind'       => 'NetworkPolicy',
            'metadata'   => [
                'name'      => "mcp-{$mcpServer->slug}-netpol",
                'namespace' => "eamcp-{$environment}",
                'labels'    => [
                    'app.kubernetes.io/part-of' => 'eamcp',
                    'eamcp/mcp-server'          => $mcpServer->slug,
                ],
            ],
            'spec' => [
                'podSelector' => [
                    'matchLabels' => ['eamcp/mcp-server' => $mcpServer->slug],
                ],
                'policyTypes' => ['Egress'],
                'egress'      => $egressRules,
            ],
        ];
    }

    protected function matchesRule(NetworkAllowlist $rule, string $host, ?int $port): bool
    {
        $hostMatch = fnmatch($rule->host, $host) || $rule->host === $host || $rule->host === '*';

        if (! $hostMatch) {
            return false;
        }

        if ($rule->port && $port && $rule->port !== $port) {
            return false;
        }

        return true;
    }

    protected function resolveHostToCidr(string $host): string
    {
        if (preg_match('/^\d+\.\d+\.\d+\.\d+(\/\d+)?$/', $host)) {
            return str_contains($host, '/') ? $host : "{$host}/32";
        }
        // For hostname-based rules, resolve at deployment time
        return '0.0.0.0/0';
    }
}
