<?php

namespace Aero\HRM\Http\Controllers\Disciplinary;

use Aero\HRM\Http\Requests\Disciplinary\AcknowledgeWarningRequest;
use Aero\HRM\Http\Requests\Disciplinary\StoreWarningRequest;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmDisciplinaryActionType;
use Aero\HRM\Models\HrmWarning;
use Aero\HRM\Services\Disciplinary\WarningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmWarningController extends Controller
{
    public function __construct(private WarningService $svc) {}

    public function index(): Response
    {
        $warnings = HrmWarning::with(['employee', 'issuedBy', 'actionType'])
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(request('employee_id'), fn ($q, $id) => $q->where('employee_id', $id))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('HRM/Disciplinary/Warnings/Index', [
            'warnings' => $warnings,
            'filters' => request()->only(['status', 'employee_id']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Disciplinary/Warnings/Create', [
            'actionTypes' => HrmDisciplinaryActionType::where('active', true)->orderBy('name')->get(['id', 'name', 'severity']),
            'employees' => Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreWarningRequest $r): RedirectResponse
    {
        $this->svc->issue([
            ...$r->validated(),
            'issued_by' => $r->user()->id,
        ]);

        return back()->with('success', 'Warning issued.');
    }

    public function acknowledge(AcknowledgeWarningRequest $r, HrmWarning $warning): RedirectResponse
    {
        $warning->load('employee');
        abort_unless($warning->employee->user_id === $r->user()->id, 403, 'You can only acknowledge your own warnings.');
        $this->svc->acknowledge($warning, $r->input('response'));

        return back()->with('success', 'Warning acknowledged.');
    }
}
