<?php

namespace Aero\HRM\Http\Controllers\Benefits;

use Aero\HRM\Models\HrmBenefit;
use Aero\HRM\Models\HrmBenefitEnrollment;
use Aero\HRM\Models\HrmBenefitEnrollmentPeriod;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class BenefitEnrollmentController extends Controller
{
    public function index(Request $r): Response
    {
        $filters = $r->only(['period_id', 'benefit_id', 'status', 'search']);

        return Inertia::render('HRM/Benefits/Enrollments/Index', [
            'enrollments' => HrmBenefitEnrollment::query()
                ->with(['employee:id,first_name,last_name', 'benefit:id,name', 'period:id,name'])
                ->when($filters['period_id'] ?? null, fn ($q, $v) => $q->where('period_id', $v))
                ->when($filters['benefit_id'] ?? null, fn ($q, $v) => $q->where('benefit_id', $v))
                ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
                ->when($filters['search'] ?? null, fn ($q, $s) => $q->whereHas('employee', fn ($e) => $e->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")))
                ->orderByDesc('elected_at')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $filters,
            'periods' => HrmBenefitEnrollmentPeriod::orderByDesc('starts_at')->get(['id', 'name']),
            'benefits' => HrmBenefit::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
