<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Tool;
use App\Models\TestExecution;
use App\Models\TestSuite;
use App\Models\Workflow;
use App\Models\WorkflowVersion;

class TestRunnerService
{
    public function __construct(
        protected AgentRuntime $agents,
        protected McpGateway $mcp,
        protected DurableWorkflowEngine $workflows,
    ) {}

    public function execute(TestSuite $suite, ?int $userId = null, ?int $deploymentId = null): TestExecution
    {
        $execution = TestExecution::create([
            'test_suite_id' => $suite->id,
            'deployment_id' => $deploymentId,
            'status' => 'running',
            'started_at' => now(),
            'triggered_by' => $userId,
        ]);

        $start = microtime(true);
        $passed = 0; $failed = 0; $errors = 0;
        $results = [];
        foreach ($suite->cases ?? [] as $case) {
            try {
                $result = $this->runCase($suite, $case);
                $ok = (bool) ($result['pass'] ?? false);
                $ok ? $passed++ : $failed++;
                $results[] = array_merge(['name' => $case['name'] ?? 'unnamed', 'ok' => $ok], $result);
            } catch (\Throwable $e) {
                $errors++;
                $results[] = ['name' => $case['name'] ?? 'unnamed', 'ok' => false, 'error' => $e->getMessage()];
            }
        }

        $execution->update([
            'status' => $failed === 0 && $errors === 0 ? 'passed' : 'failed',
            'results' => $results,
            'passed' => $passed,
            'failed' => $failed,
            'errors' => $errors,
            'duration_ms' => (int) ((microtime(true) - $start) * 1000),
            'completed_at' => now(),
        ]);

        return $execution->fresh();
    }

    public function runForAsset(string $assetType, int $assetId, string $kind = 'unit'): array
    {
        $suite = TestSuite::where('asset_type', $assetType)->where('asset_id', $assetId)->first();
        if (! $suite) {
            return ['skipped' => true, 'reason' => 'no test suite registered'];
        }
        $execution = $this->execute($suite);
        return ['execution_id' => $execution->id, 'status' => $execution->status, 'passed' => $execution->passed, 'failed' => $execution->failed];
    }

    protected function runCase(TestSuite $suite, array $case): array
    {
        $expected = $case['expected'] ?? null;

        switch ($suite->asset_type) {
            case 'agent':
                $output = $this->agents->execute($suite->asset_id, $case['prompt'] ?? '', $case['context'] ?? []);
                $text = $output['response'] ?? '';
                $pass = $expected ? str_contains(strtolower($text), strtolower($expected)) : ! empty($text);
                return ['pass' => $pass, 'output' => $output];

            case 'mcp_tool':
                $tool = Tool::find($case['tool_id'] ?? $suite->asset_id);
                if (! $tool) return ['pass' => false, 'error' => 'tool not found'];
                $output = $this->mcp->executeTool($tool->id, $case['inputs'] ?? [], null, null);
                $pass = $expected ? json_encode($output) === json_encode($expected) : ! isset($output['error']);
                return ['pass' => $pass, 'output' => $output];

            case 'workflow':
                $wf = Workflow::with('currentVersion')->find($suite->asset_id);
                if (! $wf || ! $wf->currentVersion) return ['pass' => false, 'error' => 'workflow has no version'];
                $run = $this->workflows->run($wf, $wf->currentVersion, $case['input'] ?? [], null, 'dev');
                $pass = $run->status === 'completed';
                return ['pass' => $pass, 'run_id' => $run->id, 'status' => $run->status];

            default:
                return ['pass' => false, 'error' => "unknown asset_type {$suite->asset_type}"];
        }
    }
}
