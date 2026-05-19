<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prompt;
use App\Models\PromptVersion;
use App\Services\PromptEvaluationService;
use App\Services\PromptRegistryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromptController extends Controller
{
    public function __construct(
        protected PromptRegistryService $registry,
        protected PromptEvaluationService $evaluator,
    ) {}

    public function index(Request $r): JsonResponse
    {
        $q = Prompt::query()->with('currentVersion');
        if ($r->query('tenant_id')) $q->where('tenant_id', $r->query('tenant_id'));
        if ($r->query('project_id')) $q->where('project_id', $r->query('project_id'));
        return response()->json(['data' => $q->orderByDesc('updated_at')->paginate(50)->items()]);
    }

    public function show(Prompt $prompt): JsonResponse
    {
        $prompt->load('versions', 'evaluations');
        return response()->json($prompt);
    }

    public function store(Request $r): JsonResponse
    {
        $data = $r->validate([
            'tenant_id'   => 'required|integer|exists:tenants,id',
            'project_id'  => 'nullable|integer|exists:projects,id',
            'name'        => 'required|string',
            'body'        => 'required|string',
            'category'    => 'nullable|string',
            'description' => 'nullable|string',
            'slug'        => 'nullable|string',
        ]);
        $prompt = $this->registry->create(
            $data['tenant_id'],
            $data['project_id'] ?? null,
            $data['name'],
            $data['body'],
            array_merge($data, ['created_by' => $r->user()?->id])
        );
        return response()->json($prompt, 201);
    }

    public function newVersion(Request $r, Prompt $prompt): JsonResponse
    {
        $data = $r->validate(['body' => 'required|string', 'changelog' => 'nullable|string', 'activate' => 'boolean']);
        $v = $this->registry->newVersion($prompt, $data['body'], $data['changelog'] ?? null, [
            'activate' => $data['activate'] ?? false,
            'created_by' => $r->user()?->id,
        ]);
        return response()->json($v, 201);
    }

    public function activate(Prompt $prompt, int $version): JsonResponse
    {
        return response()->json($this->registry->activate($prompt, $version));
    }

    public function diff(Prompt $prompt, int $a, int $b): JsonResponse
    {
        return response()->json($this->registry->diff($prompt, $a, $b));
    }

    public function render(Request $r, Prompt $prompt): JsonResponse
    {
        $vars = $r->input('variables', []);
        return response()->json(['rendered' => $this->registry->render($prompt, $vars, $r->input('version'))]);
    }

    public function evaluate(Request $r, Prompt $prompt): JsonResponse
    {
        $data = $r->validate([
            'version'  => 'nullable|integer',
            'models'   => 'required|array|min:1',
            'dataset'  => 'nullable|array',
            'dataset_name' => 'nullable|string',
        ]);
        $version = $data['version']
            ? $prompt->versions()->where('version', $data['version'])->firstOrFail()
            : $prompt->versions()->where('version', $prompt->current_version)->firstOrFail();

        $evals = $this->evaluator->run($version, $data['models'], $data['dataset'] ?? null, $data['dataset_name'] ?? 'default');
        return response()->json(['evaluations' => $evals]);
    }

    public function leaderboard(Prompt $prompt): JsonResponse
    {
        return response()->json(['data' => $this->evaluator->leaderboard($prompt)]);
    }
}
