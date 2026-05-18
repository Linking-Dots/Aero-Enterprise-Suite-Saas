<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingEnrollment>
 */
class TrainingEnrollmentFactory extends Factory
{
    protected $model = TrainingEnrollment::class;

    public function definition(): array
    {
        return [
            'session_id' => TrainingSession::factory(),
            'employee_id' => Employee::factory(),
            'status' => TrainingEnrollment::STATUS_ENROLLED,
            'source' => 'admin',
            'enrolled_by' => User::factory(),
        ];
    }
}
