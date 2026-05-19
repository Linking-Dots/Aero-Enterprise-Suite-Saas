<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmGrievance;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrmGrievanceFactory extends Factory
{
    protected $model = HrmGrievance::class;

    public function definition(): array
    {
        return [
            'reference' => 'GRV-'.date('Y').'-'.str_pad($this->faker->unique()->numberBetween(1, 99999), 6, '0', STR_PAD_LEFT),
            'filed_by' => Employee::factory(),
            'against_employee_id' => null,
            'category' => $this->faker->randomElement(['harassment', 'discrimination', 'workplace_safety', 'policy_violation', 'interpersonal', 'other']),
            'subject' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(),
            'confidentiality' => 'standard',
            'status' => HrmGrievance::STATUS_FILED,
        ];
    }

    public function underInvestigation(): static
    {
        return $this->state(['status' => HrmGrievance::STATUS_UNDER_INVESTIGATION]);
    }
}
