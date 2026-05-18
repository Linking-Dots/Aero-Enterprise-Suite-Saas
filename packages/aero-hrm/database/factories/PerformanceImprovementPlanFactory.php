<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\PerformanceImprovementPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceImprovementPlanFactory extends Factory
{
    protected $model = PerformanceImprovementPlan::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+1 month');
        $end = $this->faker->dateTimeBetween($start, '+3 months');

        return [
            'employee_id' => 1,
            'title' => $this->faker->sentence(4),
            'objectives' => $this->faker->paragraph,
            'start_date' => $start,
            'end_date' => $end,
            'status' => PerformanceImprovementPlan::STATUS_DRAFT,
            'notes' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => PerformanceImprovementPlan::STATUS_ACTIVE]);
    }

    public function completed(): static
    {
        return $this->state(['status' => PerformanceImprovementPlan::STATUS_COMPLETED]);
    }
}
