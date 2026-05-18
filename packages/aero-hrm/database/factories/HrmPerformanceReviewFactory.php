<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmPerformanceReview;
use Aero\HRM\Models\ReviewCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrmPerformanceReviewFactory extends Factory
{
    protected $model = HrmPerformanceReview::class;

    public function definition(): array
    {
        return [
            'cycle_id' => ReviewCycle::factory(),
            'employee_id' => 1,
            'manager_id' => null,
            'status' => HrmPerformanceReview::STATUS_DRAFT,
            'self_answers' => null,
            'manager_answers' => null,
            'final_rating' => null,
            'final_comment' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => HrmPerformanceReview::STATUS_DRAFT]);
    }

    public function managerSubmitted(): static
    {
        return $this->state([
            'status' => HrmPerformanceReview::STATUS_MANAGER_SUBMITTED,
            'self_answers' => ['q1' => 'self answer'],
            'manager_answers' => ['q1' => 'manager answer'],
        ]);
    }

    public function finalized(): static
    {
        return $this->state([
            'status' => HrmPerformanceReview::STATUS_FINALIZED,
            'final_rating' => $this->faker->randomFloat(2, 1, 5),
            'finalized_at' => now(),
        ]);
    }
}
