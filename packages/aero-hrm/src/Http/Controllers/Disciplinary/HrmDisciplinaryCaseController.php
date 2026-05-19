<?php

namespace Aero\HRM\Http\Controllers\Disciplinary;

use Aero\HRM\Http\Requests\Disciplinary\CloseCaseRequest;
use Aero\HRM\Http\Requests\Disciplinary\RespondCaseRequest;
use Aero\HRM\Http\Requests\Disciplinary\StoreCaseRequest;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmDisciplinaryActionType;
use Aero\HRM\Models\HrmDisciplinaryCase;
use Aero\HRM\Services\Disciplinary\DisciplinaryCaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmDisciplinaryCaseController extends Controller
{
    public function __construct(private DisciplinaryCaseService $svc) {}

    public function index(): Response
    {
        $cases = HrmDisciplinaryCase::with(['employee', 'actionType', 'openedBy'])
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(request('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Disciplinary/Cases/Index', [
            'cases' => $cases,
            'filters' => request()->only(['status', 'employee_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Disciplinary/Cases/Create', [
            'actionTypes' => HrmDisciplinaryActionType::where('active', true)->orderBy('name')->get(['id', 'name', 'severity']),
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreCaseRequest $r): RedirectResponse
    {
        $case = $this->svc->open([
            ...$r->validated(),
            'opened_by' => $r->user()->id,
        ]);

        return redirect()->route('hrm.disciplinary.cases.show', $case)->with('success', 'Disciplinary case opened.');
    }

    public function show(HrmDisciplinaryCase $case): Response
    {
        $case->load(['employee', 'actionType', 'openedBy', 'closedBy', 'documents.uploader']);
        $timeline = $case->events()->with('actor')->orderBy('occurred_at')->get();

        return Inertia::render('HRM/Disciplinary/Cases/Show', [
            'case' => $case,
            'timeline' => $timeline,
        ]);
    }

    public function respond(RespondCaseRequest $r, HrmDisciplinaryCase $case): RedirectResponse
    {
        $this->svc->respond($case, $r->validated('response'), $r->user()->id);

        return back()->with('success', 'Response recorded.');
    }

    public function close(CloseCaseRequest $r, HrmDisciplinaryCase $case): RedirectResponse
    {
        $this->svc->close($case, $r->validated('outcome'), $r->validated('closure_notes'), $r->user()->id);

        return back()->with('success', 'Case closed.');
    }
}
