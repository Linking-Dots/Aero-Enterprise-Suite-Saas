<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmSafetyTraining;
use Aero\HRM\Models\HrmSafetyTrainingAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmSafetyTrainingAssignment>
 */
class HrmSafetyTrainingAssignmentFactory extends Factory
{
    protected $model = HrmSafetyTrainingAssignment::class;

    public function definition(): array
    {
        return [
            'training_id' => HrmSafetyTraining::factory(),
            'employee_id' => Employee::factory(),
            'assigned_by' => User::factory(),
            'status' => HrmSafetyTrainingAssignment::STATUS_ASSIGNED,
            'due_date' => now()->addDays(30)->toDateString(),
        ];
    }
}
