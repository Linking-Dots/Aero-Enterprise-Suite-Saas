<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Performance;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\ReviewCycle;
use Aero\HRM\Models\ReviewTemplate;
use Aero\HRM\Services\Performance\ReviewCycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewCycleController extends Controller
{
    /**
     * List all review cycles.
     * Gate: hrm.performance.appraisal-cycles.view
     */
    public function index(Request $request): Response
    {
        $this->authorize('hrm.performance.appraisal-cycles.view');

        $cycles = ReviewCycle::with(['template'])
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('HRM/Performance/Cycles/Index', [
            'cycles' => $cycles,
            'filters' => $request->only(['per_page']),
        ]);
    }

    /**
     * Show create form.
     * Gate: hrm.performance.appraisal-cycles.create
     */
    public function create(): Response
    {
        $this->authorize('hrm.performance.appraisal-cycles.create');

        return Inertia::render('HRM/Performance/Cycles/Create', [
            'templates' => ReviewTemplate::where('active', true)->get(['id', 'name']),
        ]);
    }

    /**
     * Store a new review cycle.
     * Gate: hrm.performance.appraisal-cycles.create
     */
    public function store(Request $request, ReviewCycleService $svc): RedirectResponse
    {
        $this->authorize('hrm.performance.appraisal-cycles.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'template_id' => ['required', 'integer', 'exists:hrm_review_templates,id'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
        ]);

        ReviewCycle::create($validated);

        return redirect()->route('hrm.performance.cycles.index')
            ->with('success', 'Review cycle created.');
    }

    /**
     * Activate a review cycle (creates per-employee reviews).
     * Gate: hrm.performance.appraisal-cycles.update
     */
    public function activate(ReviewCycle $cycle, ReviewCycleService $svc): RedirectResponse
    {
        $this->authorize('hrm.performance.appraisal-cycles.update');

        $svc->activate($cycle);

        return back()->with('success', 'Review cycle activated.');
    }
}
