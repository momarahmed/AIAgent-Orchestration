<?php

namespace App\Services;

use App\Models\BridgeConnection;
use App\Models\BridgeExecution;
use App\Services\Bridges\BridgeAdapter;
use App\Services\Bridges\CrewAiBridge;
use App\Services\Bridges\DifyBridge;
use App\Services\Bridges\FlowiseBridge;
use App\Services\Bridges\SimBridge;

/**
 * Bridge Registry (PRD §24.5 — Phase 4).
 *
 * Single entry-point for workflow nodes / meta-agents that need to call
 * an external framework. Looks up the matching BridgeAdapter and
 * delegates the call, ensuring every framework hop goes through the
 * shared audit + cost tracking path.
 */
class BridgeRegistry
{
    /** @var array<string, BridgeAdapter> */
    protected array $adapters;

    public function __construct(DifyBridge $dify, FlowiseBridge $flowise, SimBridge $sim, CrewAiBridge $crewai)
    {
        $this->adapters = [
            'dify'    => $dify,
            'flowise' => $flowise,
            'sim'     => $sim,
            'crewai'  => $crewai,
        ];
    }

    public function call(BridgeConnection $connection, string $action, array $input, ?int $workflowRunId = null): BridgeExecution
    {
        $adapter = $this->adapters[$connection->framework] ?? null;
        if (! $adapter) {
            throw new \InvalidArgumentException("Unknown bridge framework: {$connection->framework}");
        }
        return $adapter->call($connection, $action, $input, $workflowRunId);
    }

    public function adapterFor(string $framework): ?BridgeAdapter
    {
        return $this->adapters[$framework] ?? null;
    }

    /** @return string[] */
    public function frameworks(): array
    {
        return array_keys($this->adapters);
    }
}
