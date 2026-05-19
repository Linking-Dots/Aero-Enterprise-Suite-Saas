<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmBenefit;
use Aero\HRM\Models\HrmBenefitEnrollment;
use Aero\HRM\Models\HrmBenefitEnrollmentPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrmBenefitEnrollmentFactory extends Factory
{
    protected $model = HrmBenefitEnrollment::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period_id' => HrmBenefitEnrollmentPeriod::factory(),
            'benefit_id' => HrmBenefit::factory(),
            'status' => HrmBenefitEnrollment::STATUS_ENROLLED,
            'dependents_count' => 0,
            'employee_cost_snapshot' => $this->faker->randomFloat(2, 0, 200),
            'employer_cost_snapshot' => $this->faker->randomFloat(2, 0, 400),
            'waiver_reason' => null,
            'elected_at' => now(),
        ];
    }

    public function waived(string $reason = 'Spouse coverage'): static
    {
        return $this->state([
            'status' => HrmBenefitEnrollment::STATUS_WAIVED,
            'waiver_reason' => $reason,
        ]);
    }
}
