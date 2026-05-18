<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Performance;

use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Models\Goal;
use Aero\HRM\Services\Performance\GoalLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HrmGoalController extends Controller
{
    /**
     * List goals.
     * Gate: hrm.performance.goals.view
     */
    public function index(Request $request): Response
    {
        $this->authorize('hrm.performance.goals.view');

        $goals = Goal::with(['employee'])
            ->when($request->status, function ($q, string $status): void {
                $q->where('status', $status);
            })
            ->when($request->employee_id, function ($q, int $id): void {
                $q->where('employee_id', $id);
            })
            ->latest()
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        return Inertia::render('HRM/Performance/Goals/Index', [
            'goals' => $goals,
            'filters' => $request->only(['status', 'employee_id', 'per_page']),
        ]);
    }

    /**
     * Store a new SMART goal.
     * Gate: hrm.performance.goals.edit
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('hrm.performance.goals.edit');

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'title' => ['required', 'string', 'max:200'],
            'specific' => ['required', 'string', 'max:500'],
            'measurable' => ['required', 'string', 'max:500'],
            'achievable' => ['nullable', 'string', 'max:500'],
            'relevant' => ['nullable', 'string', 'max:500'],
            'time_bound' => ['required', 'date', 'after:today'],
        ]);

        DB::transaction(function () use ($validated): void {
            Goal::create($validated);
        });

        return back()->with('success', 'Goal created.');
    }

    /**
     * Update goal progress / fields.
     * Gate: hrm.performance.goals.edit
     */
    public function update(Request $request, Goal $goal): RedirectResponse
    {
        $this->authorize('hrm.performance.goals.edit');

        $validated = $request->validate([
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'title' => ['sometimes', 'string', 'max:200'],
            'specific' => ['sometimes', 'string', 'max:500'],
            'measurable' => ['sometimes', 'string', 'max:500'],
            'achievable' => ['nullable', 'string', 'max:500'],
            'relevant' => ['nullable', 'string', 'max:500'],
            'time_bound' => ['sometimes', 'date'],
        ]);

        DB::transaction(function () use ($goal, $validated): void {
            $goal->update($validated);
        });

        return back()->with('success', 'Goal updated.');
    }

    /**
     * Close a goal with a final status.
     * Gate: hrm.performance.goals.edit
     */
    public function close(Request $request, Goal $goal, GoalLifecycleService $svc): RedirectResponse
    {
        $this->authorize('hrm.performance.goals.edit');

        $validated = $request->validate([
            'final_status' => ['required', 'string', 'in:achieved,missed,closed'],
        ]);

        $svc->close($goal, $validated['final_status']);

        return back()->with('success', 'Goal closed.');
    }
}
