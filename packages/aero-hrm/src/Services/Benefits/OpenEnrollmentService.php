<?php

namespace Aero\HRM\Services\Benefits;

use Aero\Contracts\AuditServiceInterface;
use Aero\HRM\Events\BenefitElectionsCommitted;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmBenefit;
use Aero\HRM\Models\HrmBenefitEnrollment;
use Aero\HRM\Models\HrmBenefitEnrollmentPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class OpenEnrollmentService
{
    public function __construct(
        private EligibilityService $eligibility,
        private AuditServiceInterface $audit,
    ) {}

    public function activePeriodFor(Employee $e, Carbon $today): ?HrmBenefitEnrollmentPeriod
    {
        return HrmBenefitEnrollmentPeriod::query()
            ->where('status', HrmBenefitEnrollmentPeriod::STATUS_ACTIVE)
            ->whereDate('starts_at', '<=', $today)
            ->whereDate('ends_at', '>=', $today)
            ->get()
            ->first(fn ($p) => $this->matchesAudience($e, $p->audience_filter));
    }

    public function eligibleBenefits(Employee $e, HrmBenefitEnrollmentPeriod $p): Collection
    {
        return $p->benefits->filter(fn (HrmBenefit $b) => $this->eligibility->isEligible($e, $b, now()));
    }

    public function elect(Employee $e, HrmBenefitEnrollmentPeriod $p, array $elections): void
    {
        $benefitIds = collect($elections)->pluck('benefit_id')->unique()->all();
        $benefitMap = HrmBenefit::whereIn('id', $benefitIds)->get()->keyBy('id');

        DB::transaction(function () use ($e, $p, $elections, $benefitMap) {
            foreach ($elections as $row) {
                $benefit = $benefitMap->get($row['benefit_id']);
                abort_unless($benefit !== null, 422, 'Benefit not found.');
                abort_unless($this->eligibility->isEligible($e, $benefit, now()), 422, 'Not eligible for this benefit.');

                $enrollment = HrmBenefitEnrollment::updateOrCreate(
                    ['employee_id' => $e->id, 'period_id' => $p->id, 'benefit_id' => $benefit->id],
                    [
                        'status' => $row['status'],
                        'dependents_count' => $row['dependents_count'] ?? 0,
                        'waiver_reason' => $row['waiver_reason'] ?? null,
                        'employee_cost_snapshot' => $benefit->employee_cost
                            + ($benefit->dependent_cost ?? 0) * ($row['dependents_count'] ?? 0),
                        'employer_cost_snapshot' => $benefit->employer_cost,
                        'elected_at' => now(),
                    ],
                );

                $eventCode = $row['status'] === HrmBenefitEnrollment::STATUS_ENROLLED
                    ? 'BENEFIT_ENROLLED'
                    : 'BENEFIT_WAIVED';

                $this->audit->log(
                    event: $eventCode,
                    action: 'elect',
                    subject: $enrollment,
                    description: "Employee {$e->id} elected benefit {$benefit->name}: {$row['status']}",
                );
            }

            event(new BenefitElectionsCommitted($e->id, $p->id));
        });
    }

    private function matchesAudience(Employee $e, ?array $filter): bool
    {
        if (! $filter) {
            return true;
        }
        if ($ids = $filter['department_ids'] ?? null) {
            if (! in_array($e->department_id, $ids, true)) {
                return false;
            }
        }
        if ($types = $filter['employment_types'] ?? null) {
            if (! in_array($e->employment_type, $types, true)) {
                return false;
            }
        }

        return true;
    }
}
