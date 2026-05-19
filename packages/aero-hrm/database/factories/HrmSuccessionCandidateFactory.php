<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\Designation;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmSuccessionCandidate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmSuccessionCandidate>
 */
class HrmSuccessionCandidateFactory extends Factory
{
    protected $model = HrmSuccessionCandidate::class;

    public function definition(): array
    {
        return [
            'role_id' => Designation::factory(),
            'employee_id' => Employee::factory(),
            'readiness' => HrmSuccessionCandidate::READINESS_READY_NOW,
            'notes' => null,
            'nominated_by' => User::factory(),
        ];
    }
}
