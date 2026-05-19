<?php

namespace Aero\HRM\Http\Controllers\Safety;

use Aero\HRM\Http\Requests\Safety\InvestigateIncidentRequest;
use Aero\HRM\Http\Requests\Safety\StoreIncidentRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\HrmSafetyIncident;
use Aero\HRM\Services\Safety\SafetyIncidentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmSafetyIncidentController extends Controller
{
    public function __construct(private SafetyIncidentService $svc) {}

    public function index(): Response
    {
        $incidents = HrmSafetyIncident::with(['reportedBy:id,name', 'department:id,name'])
            ->when(request('type'), fn ($q, $v) => $q->where('type', $v))
            ->when(request('severity'), fn ($q, $v) => $q->where('severity', $v))
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->when(request('q'), fn ($q, $v) => $q->where('description', 'like', "%{$v}%")->orWhere('reference', 'like', "%{$v}%"))
            ->latest('occurred_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Safety/Incidents/Index', [
            'incidents' => $incidents,
            'filters' => request()->only(['type', 'severity', 'status', 'q']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Safety/Incidents/Create', [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreIncidentRequest $r): RedirectResponse
    {
        $incident = $this->svc->report([
            ...$r->validated(),
            'reported_by' => $r->user()->id,
        ]);

        return redirect()->route('hrm.safety.incidents.show', $incident)->with('success', 'Incident reported.');
    }

    public function show(HrmSafetyIncident $incident): Response
    {
        $incident->load(['investigations.investigator:id,name', 'reportedBy:id,name']);

        return Inertia::render('HRM/Safety/Incidents/Show', [
            'incident' => $incident,
        ]);
    }

    public function investigate(InvestigateIncidentRequest $r, HrmSafetyIncident $incident): RedirectResponse
    {
        $this->svc->addInvestigation($incident, [
            ...$r->validated(),
            'investigator_id' => $r->user()->id,
        ]);

        return back()->with('success', 'Investigation recorded.');
    }

    public function close(HrmSafetyIncident $incident): RedirectResponse
    {
        $this->svc->close($incident, request()->user()->id);

        return back()->with('success', 'Incident closed.');
    }
}
