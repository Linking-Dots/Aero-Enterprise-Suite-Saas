<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmExpenseClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmExpenseClaim>
 */
class HrmExpenseClaimFactory extends Factory
{
    protected $model = HrmExpenseClaim::class;

    public function definition(): array
    {
        return [
            'reference' => 'EXP-'.$this->faker->unique()->numerify('########'),
            'employee_id' => Employee::factory(),
            'claim_date' => now()->toDateString(),
            'title' => $this->faker->sentence(3),
            'currency' => 'USD',
            'total_amount' => $this->faker->randomFloat(2, 50, 2000),
            'status' => HrmExpenseClaim::STATUS_SUBMITTED,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => HrmExpenseClaim::STATUS_DRAFT]);
    }

    public function submitted(): static
    {
        return $this->state(['status' => HrmExpenseClaim::STATUS_SUBMITTED]);
    }

    public function approved(): static
    {
        return $this->state(['status' => HrmExpenseClaim::STATUS_APPROVED]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => HrmExpenseClaim::STATUS_REJECTED]);
    }
}
