<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmExitInterview;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrmExitInterviewFactory extends Factory
{
    protected $model = HrmExitInterview::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'scheduled_for' => now()->addDays(7)->toDateString(),
            'interviewer_id' => User::factory(),
            'status' => HrmExitInterview::STATUS_SCHEDULED,
        ];
    }

    public function completed(): static
    {
        return $this->state([
            'status' => HrmExitInterview::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
