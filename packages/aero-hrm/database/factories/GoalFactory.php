<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Goal;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'employee_id' => 1,
            'title' => $this->faker->sentence(4),
            'specific' => $this->faker->sentence(10),
            'measurable' => $this->faker->sentence(10),
            'achievable' => $this->faker->sentence(10),
            'relevant' => $this->faker->sentence(10),
            'time_bound' => $this->faker->dateTimeBetween('now', '+1 year'),
            'progress' => 0,
            'status' => Goal::STATUS_OPEN,
        ];
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => Goal::STATUS_IN_PROGRESS,
            'progress' => $this->faker->numberBetween(1, 99),
        ]);
    }

    public function achieved(): static
    {
        return $this->state([
            'status' => Goal::STATUS_ACHIEVED,
            'progress' => 100,
            'closed_at' => now(),
        ]);
    }
}
