<?php

namespace App\Services;

use App\Models\McpServer;
use Illuminate\Support\Facades\Log;

/**
 * Tool Sandbox (Phase 3).
 *
 * Manages strict Kubernetes sandbox environments for risky tools:
 * - Browser/CUA, Code/DevOps, and any tool with risk level >= L3
 * - No host filesystem mount, no privileged containers
 * - Network policy enforces egress allowlist per tool
 * - Per-call ephemeral sandbox for risky operations
 */
class ToolSandboxService
{
    protected array $defaultSandboxConfig = [
        'read_only_root' => true,
        'no_host_mount'  => true,
        'no_privileged'  => true,
        'cpu_limit'      => '500m',
        'memory_limit'   => '512Mi',
        'timeout_seconds'=> 300,
        'capabilities'   => ['drop' => ['ALL']],
    ];

    /**
     * Determine if a tool execution requires sandboxing.
     */
    public function requiresSandbox(McpServer $mcpServer, string $toolRiskLevel): bool
    {
        if ($mcpServer->requires_sandbox) {
            return true;
        }

        $riskOrder = ['L0' => 0, 'L1' => 1, 'L2' => 2, 'L3' => 3, 'L4' => 4];
        return ($riskOrder[$toolRiskLevel] ?? 0) >= 3;
    }

    /**
     * Generate sandbox pod spec for Kubernetes execution.
     */
    public function generatePodSpec(McpServer $mcpServer, string $toolName, array $inputs = []): array
    {
        $config = array_merge(
            $this->defaultSandboxConfig,
            $mcpServer->sandbox_config ?? [],
        );

        return [
            'apiVersion' => 'v1',
            'kind'       => 'Pod',
            'metadata'   => [
                'generateName' => "sandbox-{$mcpServer->slug}-",
                'namespace'    => 'eamcp-sandbox',
                'labels'       => [
                    'eamcp/sandbox'    => 'true',
                    'eamcp/mcp-server' => $mcpServer->slug,
                    'eamcp/tool'       => $toolName,
                ],
                'annotations' => [
                    'eamcp/ephemeral' => 'true',
                    'eamcp/timeout'   => (string) $config['timeout_seconds'],
                ],
            ],
            'spec' => [
                'restartPolicy'         => 'Never',
                'activeDeadlineSeconds' => $config['timeout_seconds'],
                'automountServiceAccountToken' => false,
                'securityContext' => [
                    'runAsNonRoot' => true,
                    'runAsUser'    => 1000,
                    'fsGroup'      => 1000,
                    'seccompProfile' => ['type' => 'RuntimeDefault'],
                ],
                'containers' => [
                    [
                        'name'  => 'sandbox',
                        'image' => $this->resolveImage($mcpServer),
                        'resources' => [
                            'limits' => [
                                'cpu'    => $config['cpu_limit'],
                                'memory' => $config['memory_limit'],
                            ],
                        ],
                        'securityContext' => [
                            'readOnlyRootFilesystem'   => $config['read_only_root'],
                            'allowPrivilegeEscalation' => false,
                            'capabilities'             => $config['capabilities'],
                        ],
                        'volumeMounts' => [
                            ['name' => 'tmp', 'mountPath' => '/tmp'],
                        ],
                    ],
                ],
                'volumes' => [
                    ['name' => 'tmp', 'emptyDir' => ['sizeLimit' => '100Mi']],
                ],
            ],
        ];
    }

    /**
     * Validate that a sandbox configuration meets security requirements.
     */
    public function validateConfig(array $config): array
    {
        $issues = [];

        if (empty($config['read_only_root']) || $config['read_only_root'] !== true) {
            $issues[] = 'read_only_root must be true for sandbox containers';
        }
        if (empty($config['no_privileged']) || $config['no_privileged'] !== true) {
            $issues[] = 'no_privileged must be true for sandbox containers';
        }
        if (empty($config['no_host_mount']) || $config['no_host_mount'] !== true) {
            $issues[] = 'no_host_mount must be true for sandbox containers';
        }

        return $issues;
    }

    /**
     * Simulate sandbox execution for local dev (Phase 3 dev mode).
     */
    public function executeInLocalSandbox(McpServer $mcpServer, string $toolName, array $inputs): array
    {
        Log::info('sandbox.local_exec', [
            'mcp_server' => $mcpServer->slug,
            'tool'       => $toolName,
        ]);

        return [
            'sandbox'    => 'local_simulation',
            'mcp_server' => $mcpServer->slug,
            'tool'       => $toolName,
            'status'     => 'completed',
            'output'     => ['message' => "Sandbox execution simulated for {$toolName}"],
        ];
    }

    protected function resolveImage(McpServer $mcpServer): string
    {
        $registry = config('services.registry.url', 'ghcr.io/eamcp');
        return "{$registry}/mcp-{$mcpServer->slug}:latest";
    }
}
