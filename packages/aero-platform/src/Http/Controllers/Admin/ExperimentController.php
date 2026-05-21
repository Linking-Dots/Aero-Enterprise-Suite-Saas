<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\Integrations\FeatureFlagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P-7 — A/B Experiment Controller (Admin).
 *
 * Route prefix: /admin/p7/experiments
 * Route names:  platform.admin.feature-flags.experiments.*
 */
class ExperimentController extends Controller
{
    public function __construct(private readonly FeatureFlagService $svc) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/FeatureFlags/Experiments', [
            'experiments' => $this->svc->listExperiments($request->only(['q'])),
            'filters' => $request->only(['q']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'flag_id' => ['required', 'integer', 'exists:central.feature_flags,id'],
            'control_pct' => ['integer', 'min:0', 'max:100'],
            'variant_pct' => ['integer', 'min:0', 'max:100'],
        ]);

        $experiment = $this->svc->startExperiment($data);

        return response()->json(['message' => 'Experiment started.', 'data' => $experiment], 201);
    }

    public function stop(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'winner' => ['nullable', 'string', Rule::in(['control', 'variant'])],
        ]);

        $experiment = $this->svc->stopExperiment($id, $data['winner'] ?? null);

        return response()->json(['message' => 'Experiment stopped.', 'data' => $experiment]);
    }
}
