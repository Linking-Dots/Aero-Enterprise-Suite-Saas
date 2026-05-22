<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\SendNpsRequest;
use Aero\Platform\Models\Enterprise\SuccessPlaybook;
use Aero\Platform\Services\Enterprise\CustomerSuccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CustomerSuccessController extends Controller
{
    public function __construct(private readonly CustomerSuccessService $svc) {}

    public function health(): Response
    {
        $scores = $this->svc->getHealthScores();
        $churnRisk = $this->svc->churnRiskAnalysis();

        return Inertia::render('Platform/Admin/CustomerSuccess/Dashboard', [
            'health_scores' => $scores,
            'churn_risk' => $churnRisk,
        ]);
    }

    public function computeHealth(): JsonResponse
    {
        $this->svc->computeHealthScores();

        return response()->json(['message' => 'Health score computation triggered.']);
    }

    public function nps(): Response
    {
        $results = $this->svc->getNpsResults();

        return Inertia::render('Platform/Admin/CustomerSuccess/Dashboard', [
            'nps_results' => $results,
        ]);
    }

    public function sendNps(SendNpsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $response = $this->svc->sendNpsSurvey($validated['tenant_id'], $validated['user_email']);

        return response()->json(['message' => 'NPS survey sent.', 'id' => $response->id], 201);
    }

    public function csmAssignment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
            'csm_user_id' => ['required', 'string'],
        ]);

        $this->svc->assignCsm($validated['tenant_id'], $validated['csm_user_id']);

        return response()->json(['message' => 'CSM assigned.']);
    }

    public function playbooks(): Response
    {
        $playbooks = $this->svc->listPlaybooks();

        return Inertia::render('Platform/Admin/CustomerSuccess/Playbooks', [
            'playbooks' => $playbooks,
        ]);
    }

    public function executePlaybook(Request $request, SuccessPlaybook $playbook): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
        ]);

        $run = $this->svc->executePlaybook(
            $playbook,
            $validated['tenant_id'],
            (string) Auth::guard('landlord')->id()
        );

        return response()->json(['message' => 'Playbook executed.', 'run_id' => $run->id]);
    }
}
