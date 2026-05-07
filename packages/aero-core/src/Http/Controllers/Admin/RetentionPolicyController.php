<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\RetentionPolicyService;
use Aero\Core\Models\RetentionPolicy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RetentionPolicyController extends Controller
{
    public function __construct(
        private RetentionPolicyService $retentionPolicyService
    ) {}

    /**
     * Display retention policies page.
     */
    public function index(Request $request): InertiaResponse
    {
        $policies = $this->retentionPolicyService->getPolicies();
        $entityTypes = $this->retentionPolicyService->getEntityTypes();
        $actions = $this->retentionPolicyService->getActions();
        $schedules = $this->retentionPolicyService->getSchedules();

        return Inertia::render('Core/RetentionPolicies/Index', [
            'policies' => $policies,
            'entity_types' => $entityTypes,
            'actions' => $actions,
            'schedules' => $schedules,
        ]);
    }

    /**
     * Create a new retention policy.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'entity_type' => ['required', 'string'],
            'action' => ['required', 'in:delete,archive'],
            'retention_days' => ['required', 'integer', 'min:1'],
            'filters' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'schedule' => ['required', 'in:daily,weekly,monthly'],
            'notes' => ['nullable', 'string'],
        ]);

        $policy = $this->retentionPolicyService->createPolicy($request->all());

        return response()->json([
            'message' => 'Retention policy created successfully',
            'policy' => $policy,
        ]);
    }

    /**
     * Update a retention policy.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'action' => ['nullable', 'in:delete,archive'],
            'retention_days' => ['nullable', 'integer', 'min:1'],
            'filters' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'schedule' => ['nullable', 'in:daily,weekly,monthly'],
            'notes' => ['nullable', 'string'],
        ]);

        $policy = RetentionPolicy::findOrFail($id);
        $updatedPolicy = $this->retentionPolicyService->updatePolicy($policy, $request->all());

        return response()->json([
            'message' => 'Retention policy updated successfully',
            'policy' => $updatedPolicy,
        ]);
    }

    /**
     * Delete a retention policy.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $policy = RetentionPolicy::findOrFail($id);
        $this->retentionPolicyService->deletePolicy($policy);

        return response()->json([
            'message' => 'Retention policy deleted successfully',
        ]);
    }

    /**
     * Execute a retention policy manually.
     */
    public function execute(Request $request, $id): JsonResponse
    {
        $policy = RetentionPolicy::findOrFail($id);
        $results = $this->retentionPolicyService->executePolicy($policy);

        return response()->json([
            'message' => 'Retention policy executed',
            'results' => $results,
        ]);
    }
}
