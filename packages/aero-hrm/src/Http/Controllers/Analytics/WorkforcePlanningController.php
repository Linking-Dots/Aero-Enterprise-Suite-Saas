<?php

declare(strict_types=1);

namespace Aero\HRM\Http\Controllers\Analytics;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Controllers\Controller;
use Aero\HRM\Http\Requests\Analytics\UpdateWorkforcePlanRequest;
use Aero\HRM\Models\WorkforcePlan;
use Aero\HRM\Services\Analytics\WorkforcePlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorkforcePlanningController extends Controller
{
    public function __construct(
        private readonly WorkforcePlanService $service,
        private readonly AuditServiceInterface $audit,
    ) {}

    /**
     * Workforce planning index — plan vs actual for the current fiscal year.
     */
    public function index(): Response
    {
        Gate::authorize('hrmac', 'hrm.workforce-planning.workforce-plans.view');

        $year = (int) now()->format('Y');
        $plans = $this->service->planVsActual($year);

        // Also surface available fiscal years
        $years = WorkforcePlan::selectRaw('DISTINCT fiscal_year')
            ->orderByDesc('fiscal_year')
            ->pluck('fiscal_year');

        return Inertia::render('HRM/Analytics/WorkforcePlanning/Index', [
            'year' => $year,
            'plans' => $plans,
            'years' => $years,
        ]);
    }

    /**
     * Bulk upsert workforce plans for one or more departments/years.
     */
    public function update(UpdateWorkforcePlanRequest $request): RedirectResponse
    {
        Gate::authorize('hrmac', 'hrm.workforce-planning.workforce-plans.update');

        $plans = $request->validated('plans');

        DB::transaction(function () use ($plans, $request): void {
            foreach ($plans as $planData) {
                $plan = WorkforcePlan::updateOrCreate(
                    [
                        'fiscal_year' => $planData['fiscal_year'],
                        'department_id' => $planData['department_id'],
                    ],
                    [
                        'target_headcount' => $planData['target_headcount'],
                        'target_hires' => $planData['target_hires'] ?? 0,
                        'target_attrition' => $planData['target_attrition'] ?? 0,
                        'notes' => $planData['notes'] ?? null,
                        'updated_by' => $request->user()->id,
                    ]
                );

                $this->audit->log(
                    event: 'workforce_plan.updated',
                    action: 'update',
                    subject: $plan,
                    description: "Workforce plan updated for dept {$plan->department_id} FY{$plan->fiscal_year}",
                );
            }
        });

        return back()->with('success', 'Workforce plans saved successfully.');
    }
}
