<?php

namespace Aero\HRM\Http\Controllers\Safety;

use Aero\Core\Models\User;
use Aero\HRM\Http\Requests\Safety\StoreInspectionRequest;
use Aero\HRM\Http\Requests\Safety\SubmitFindingsRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\HrmSafetyInspection;
use Aero\HRM\Services\Safety\SafetyInspectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmSafetyInspectionController extends Controller
{
    public function __construct(private SafetyInspectionService $svc) {}

    public function index(): Response
    {
        $inspections = HrmSafetyInspection::with(['inspector:id,name', 'department:id,name'])
            ->when(request('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('scheduled_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Safety/Inspections/Index', [
            'inspections' => $inspections,
            'filters' => request()->only(['status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Safety/Inspections/Create', [
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'inspectors' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreInspectionRequest $r): RedirectResponse
    {
        $inspection = $this->svc->schedule([
            ...$r->validated(),
            'inspector_id' => $r->validated('inspector_id') ?? $r->user()->id,
        ]);

        return redirect()->route('hrm.safety.inspections.show', $inspection)->with('success', 'Inspection scheduled.');
    }

    public function show(HrmSafetyInspection $inspection): Response
    {
        $inspection->load(['findings', 'inspector:id,name', 'department:id,name']);

        return Inertia::render('HRM/Safety/Inspections/Show', [
            'inspection' => $inspection,
        ]);
    }

    public function submitFindings(SubmitFindingsRequest $r, HrmSafetyInspection $inspection): RedirectResponse
    {
        $this->svc->submitFindings($inspection, $r->validated('findings'));

        return back()->with('success', 'Findings submitted.');
    }
}
