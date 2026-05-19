<?php

namespace Aero\HRM\Services\Benefits;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Jobs\NotifyEligibleEmployeesJob;
use Aero\HRM\Models\HrmBenefitEnrollmentPeriod;
use Illuminate\Support\Facades\DB;

final class EnrollmentPeriodService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function activate(HrmBenefitEnrollmentPeriod $period): void
    {
        abort_unless($period->status === HrmBenefitEnrollmentPeriod::STATUS_DRAFT, 422, 'Period already active or closed.');
        abort_if($period->benefits()->count() === 0, 422, 'Attach at least one benefit first.');

        DB::transaction(function () use ($period) {
            $period->update(['status' => HrmBenefitEnrollmentPeriod::STATUS_ACTIVE, 'activated_at' => now()]);
            $this->audit->log(event: 'ENROLLMENT_PERIOD_ACTIVATED', action: 'activate', subject: $period, description: "Activated enrollment period: {$period->name}");
        });

        dispatch(new NotifyEligibleEmployeesJob($period));
    }
}
