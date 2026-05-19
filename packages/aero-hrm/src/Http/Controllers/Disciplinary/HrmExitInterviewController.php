<?php

namespace Aero\HRM\Http\Controllers\Disciplinary;

use Aero\HRM\Http\Requests\Disciplinary\RecordExitInterviewRequest;
use Aero\HRM\Http\Requests\Disciplinary\StoreExitInterviewRequest;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmExitInterview;
use Aero\HRM\Services\Disciplinary\ExitInterviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmExitInterviewController extends Controller
{
    public function __construct(private ExitInterviewService $svc) {}

    public function index(): Response
    {
        $interviews = HrmExitInterview::with(['employee', 'interviewer'])
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('scheduled_for')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Disciplinary/ExitInterviews/Index', [
            'interviews' => $interviews,
            'filters' => request()->only(['status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Disciplinary/ExitInterviews/Create', [
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreExitInterviewRequest $r): RedirectResponse
    {
        $this->svc->schedule($r->validated());

        return redirect()->route('hrm.exit-interviews.index')->with('success', 'Exit interview scheduled.');
    }

    public function show(HrmExitInterview $interview): Response
    {
        $interview->load(['employee', 'interviewer']);

        return Inertia::render('HRM/Disciplinary/ExitInterviews/Show', [
            'interview' => $interview,
        ]);
    }

    public function record(RecordExitInterviewRequest $r, HrmExitInterview $interview): RedirectResponse
    {
        $this->svc->record($interview, $r->validated());

        return back()->with('success', 'Exit interview completed.');
    }
}
