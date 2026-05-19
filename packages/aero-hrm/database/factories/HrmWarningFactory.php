<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\Employee;
use Aero\HRM\Models\HrmWarning;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrmWarningFactory extends Factory
{
    protected $model = HrmWarning::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'issued_by' => User::factory(),
            'action_type_id' => null,
            'subject' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
            'status' => HrmWarning::STATUS_ISSUED,
            'issued_at' => now()->subHour(),
        ];
    }

    public function acknowledged(): static
    {
        return $this->state([
            'status' => HrmWarning::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);
    }
}
