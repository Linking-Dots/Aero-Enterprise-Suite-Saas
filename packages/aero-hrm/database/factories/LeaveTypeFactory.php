<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'code' => strtoupper($this->faker->unique()->lexify('LT???')),
            'color' => $this->faker->hexColor(),
            'days_per_year' => $this->faker->randomFloat(2, 5, 30),
            'is_paid' => $this->faker->boolean(80),
            'requires_approval' => true,
            'carry_forward' => $this->faker->boolean(),
            'encashable' => false,
            'max_carry_forward' => null,
            'is_active' => true,
        ];
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => false,
        ]);
    }

    public function noApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => false,
        ]);
    }
}
