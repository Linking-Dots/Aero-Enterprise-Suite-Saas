<?php

namespace Aero\HRM\Http\Controllers\Benefits;

use Aero\HRM\Http\Requests\Benefits\EnrollBenefitsRequest;
use Aero\HRM\Models\HrmBenefitEnrollment;
use Aero\HRM\Models\HrmBenefitEnrollmentPeriod;
use Aero\HRM\Services\Benefits\OpenEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class OpenEnrollmentController extends Controller
{
    public function __construct(private OpenEnrollmentService $svc) {}

    public function index(Request $r): Response
    {
        $employee = $r->user()->employee;
        $period = $this->svc->activePeriodFor($employee, now());

        return Inertia::render('HRM/Benefits/OpenEnrollment/Index', [
            'period' => $period?->only(['id', 'name', 'starts_at', 'ends_at', 'coverage_starts_at', 'coverage_ends_at']),
            'eligibleBenefits' => $period ? $this->svc->eligibleBenefits($employee, $period)->values() : [],
            'myElections' => $period
                ? HrmBenefitEnrollment::where('employee_id', $employee->id)
                    ->where('period_id', $period->id)
                    ->with('benefit:id,name,category,allows_dependents,dependent_cost,frequency')
                    ->get()
                : [],
        ]);
    }

    public function enroll(EnrollBenefitsRequest $r): RedirectResponse
    {
        $employee = $r->user()->employee;
        $period = HrmBenefitEnrollmentPeriod::findOrFail($r->input('period_id'));

        abort_unless(
            $period->status === HrmBenefitEnrollmentPeriod::STATUS_ACTIVE
                && now()->between($period->starts_at, $period->ends_at),
            422,
            'Enrollment window closed.'
        );

        $this->svc->elect($employee, $period, $r->validated('elections'));

        return redirect()->route('hrm.benefits.open-enrollment.index')
            ->with('success', 'Your elections have been recorded.');
    }
}
