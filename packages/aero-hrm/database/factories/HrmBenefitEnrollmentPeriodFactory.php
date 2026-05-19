<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\HrmBenefitEnrollmentPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrmBenefitEnrollmentPeriodFactory extends Factory
{
    protected $model = HrmBenefitEnrollmentPeriod::class;

    public function definition(): array
    {
        $starts = now()->subDays(5);
        $ends = now()->addDays(25);

        return [
            'name' => $this->faker->year().' Open Enrollment',
            'starts_at' => $starts,
            'ends_at' => $ends,
            'coverage_starts_at' => $ends->copy()->addDay(),
            'coverage_ends_at' => $ends->copy()->addYear(),
            'audience_filter' => null,
            'status' => HrmBenefitEnrollmentPeriod::STATUS_DRAFT,
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => HrmBenefitEnrollmentPeriod::STATUS_DRAFT]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => HrmBenefitEnrollmentPeriod::STATUS_ACTIVE,
            'activated_at' => now(),
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addDays(25),
        ]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => HrmBenefitEnrollmentPeriod::STATUS_CLOSED,
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(30),
        ]);
    }
}
