<?php

namespace Aero\HRM\Http\Controllers\Benefits;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Http\Requests\Benefits\StoreEnrollmentPeriodRequest;
use Aero\HRM\Models\Department;
use Aero\HRM\Models\HrmBenefit;
use Aero\HRM\Models\HrmBenefitEnrollmentPeriod;
use Aero\HRM\Services\Benefits\EnrollmentPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class EnrollmentPeriodController extends Controller
{
    public function __construct(
        private EnrollmentPeriodService $svc,
        private AuditServiceInterface $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('HRM/Benefits/EnrollmentPeriods/Index', [
            'periods' => HrmBenefitEnrollmentPeriod::with('creator:id,name')
                ->withCount('benefits', 'enrollments')
                ->orderByDesc('starts_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('HRM/Benefits/EnrollmentPeriods/Create', [
            'benefits' => HrmBenefit::where('active', true)->orderBy('name')->get(['id', 'name', 'category']),
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreEnrollmentPeriodRequest $r): RedirectResponse
    {
        $period = DB::transaction(function () use ($r) {
            $p = HrmBenefitEnrollmentPeriod::create([
                ...$r->safe()->except('benefit_ids'),
                'created_by' => $r->user()->id,
            ]);
            $p->benefits()->sync($r->validated('benefit_ids'));

            return $p;
        });

        $this->audit->log(event: 'ENROLLMENT_PERIOD_CREATED', action: 'create', subject: $period, description: "Created enrollment period: {$period->name}");

        return redirect()->route('hrm.benefits.enrollment-periods.show', $period)
            ->with('success', 'Enrollment period created.');
    }

    public function show(HrmBenefitEnrollmentPeriod $period): Response
    {
        return Inertia::render('HRM/Benefits/EnrollmentPeriods/Show', [
            'period' => $period->load('benefits', 'creator:id,name'),
            'stats' => [
                'enrolled' => $period->enrollments()->where('status', 'enrolled')->count(),
                'waived' => $period->enrollments()->where('status', 'waived')->count(),
                'employees' => $period->enrollments()->distinct('employee_id')->count('employee_id'),
            ],
        ]);
    }

    public function activate(HrmBenefitEnrollmentPeriod $period): RedirectResponse
    {
        $this->svc->activate($period);

        return back()->with('success', 'Period activated.');
    }
}
