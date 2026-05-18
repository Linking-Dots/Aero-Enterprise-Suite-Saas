<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\LeaveBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        return [
            'employee_id'      => \Aero\HRM\Models\Employee::factory(),
            'leave_type_id'    => \Aero\HRM\Models\LeaveType::factory(),
            'year'             => now()->year,
            'entitled'         => 20.00,
            'used'             => 0.00,
            'carried_forward'  => 0.00,
            'encashed'         => 0.00,
        ];
    }
}
