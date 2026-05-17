<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\LeaveApplication;
use Aero\HRM\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveApplication>
 */
class LeaveApplicationFactory extends Factory
{
    protected $model = LeaveApplication::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('2026-01-01', '2026-12-01');
        $end = (clone $start)->modify('+'.rand(1, 5).' days');

        return [
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start,
            'end_date' => $end,
            'total_days' => (int) $start->diff($end)->days + 1,
            'status' => 'pending',
            'reason' => $this->faker->sentence(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => LeaveApplication::STATUS_APPROVED,
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'           => LeaveApplication::STATUS_REJECTED,
            'rejection_reason' => 'Test rejection reason.',
            'rejected_by'      => User::factory(),
            'rejected_at'      => now(),
        ]);
    }
}
