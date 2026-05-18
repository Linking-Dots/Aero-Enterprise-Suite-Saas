<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\TrainingEnrollment;
use Aero\HRM\Models\TrainingFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingFeedback>
 */
class TrainingFeedbackFactory extends Factory
{
    protected $model = TrainingFeedback::class;

    public function definition(): array
    {
        return [
            'enrollment_id' => TrainingEnrollment::factory(),
            'content_rating' => $this->faker->numberBetween(1, 5),
            'instructor_rating' => $this->faker->numberBetween(1, 5),
            'overall_rating' => $this->faker->numberBetween(1, 5),
            'would_recommend' => $this->faker->boolean(70),
            'what_worked' => $this->faker->optional()->sentence(),
            'what_didnt_work' => null,
            'suggestions' => null,
            'submitted_at' => now(),
        ];
    }
}
