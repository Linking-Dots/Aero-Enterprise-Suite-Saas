<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin\Enterprise;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\Enterprise\StoreRunbookRequest;
use Aero\Platform\Http\Requests\Enterprise\SetRtoRpoRequest;
use Aero\Platform\Models\Enterprise\DrRunbook;
use Aero\Platform\Services\Enterprise\DisasterRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DisasterRecoveryController extends Controller
{
    public function __construct(private readonly DisasterRecoveryService $svc) {}

    public function runbooks(): Response
    {
        return Inertia::render('Platform/Admin/DisasterRecovery/Index', [
            'runbooks' => $this->svc->listRunbooks(),
        ]);
    }

    public function storeRunbook(StoreRunbookRequest $request): JsonResponse
    {
        $runbook = $this->svc->createRunbook(
            $request->validated(),
            (string) Auth::guard('landlord')->id()
        );

        return response()->json(['message' => 'Runbook created.', 'id' => $runbook->id], 201);
    }

    public function executeRunbook(DrRunbook $runbook): JsonResponse
    {
        $execution = $this->svc->executeRunbook($runbook, (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'Runbook executed.', 'execution_id' => $execution->id]);
    }

    public function rtoRpo(): JsonResponse
    {
        return response()->json(['rto_rpo' => \Aero\Platform\Models\Enterprise\RtoRpoConfig::all()]);
    }

    public function setRtoRpo(SetRtoRpoRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $config = $this->svc->setRtoRpo(
            $validated['service'],
            $validated['rto_minutes'],
            $validated['rpo_minutes'],
            (string) Auth::guard('landlord')->id()
        );

        return response()->json(['message' => 'RTO/RPO configured.', 'id' => $config->id]);
    }

    public function drDrills(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'runbook_id' => ['nullable', 'string'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);
        $drill = $this->svc->scheduleDrDrill($validated, (string) Auth::guard('landlord')->id());

        return response()->json(['message' => 'DR drill scheduled.', 'id' => $drill->id], 201);
    }
}
