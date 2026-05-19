<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmDisciplinaryActionType;
use Aero\HRM\Models\HrmDisciplinaryCase;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrmDisciplinaryCaseFactory extends Factory
{
    protected $model = HrmDisciplinaryCase::class;

    public function definition(): array
    {
        return [
            'reference' => 'CASE-'.date('Y').'-'.str_pad($this->faker->unique()->numberBetween(1, 99999), 6, '0', STR_PAD_LEFT),
            'employee_id' => Employee::factory(),
            'action_type_id' => HrmDisciplinaryActionType::factory(),
            'opened_by' => User::factory(),
            'incident_date' => now()->subDays(rand(1, 30))->toDateString(),
            'subject' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(),
            'status' => HrmDisciplinaryCase::STATUS_OPEN,
            'outcome' => 'none',
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => HrmDisciplinaryCase::STATUS_OPEN]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => HrmDisciplinaryCase::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => User::factory(),
            'outcome' => 'verbal',
        ]);
    }
}
