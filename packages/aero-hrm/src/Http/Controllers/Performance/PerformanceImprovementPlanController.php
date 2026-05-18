<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Performance;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\PerformanceImprovementPlan;
use Aero\HRM\Services\Performance\PIPService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceImprovementPlanController extends Controller
{
    /**
     * List all PIPs with employee eager-loaded.
     * Gate: hrm.performance.improvement_plans.view
     */
    public function index(Request $request): Response
    {
        $this->authorize('hrm.performance.improvement_plans.view');

        $pips = PerformanceImprovementPlan::with(['employee'])
            ->when($request->search, function ($q, string $search): void {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($request->status, function ($q, string $status): void {
                $q->where('status', $status);
            })
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('HRM/Performance/PIP/Index', [
            'pips' => $pips,
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    /**
     * Show the create PIP form.
     * Gate: hrm.performance.improvement_plans.create
     */
    public function create(): Response
    {
        $this->authorize('hrm.performance.improvement_plans.create');

        $employees = Employee::where('status', 'active')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code']);

        return Inertia::render('HRM/Performance/PIP/Create', [
            'employees' => $employees,
        ]);
    }

    /**
     * Store a new PIP.
     * Gate: hrm.performance.improvement_plans.create
     */
    public function store(Request $request, PIPService $svc): RedirectResponse
    {
        $this->authorize('hrm.performance.improvement_plans.create');

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:200'],
            'objectives' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['nullable', 'string', 'in:draft,active,completed,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $svc->create($validated);

        return redirect()->route('hrm.performance.pip.index')
            ->with('success', 'Performance Improvement Plan created.');
    }
}
