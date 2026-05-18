<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\TrainingCourse;
use Aero\HRM\Models\TrainingSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    protected $model = TrainingSession::class;

    public function definition(): array
    {
        return [
            'course_id' => TrainingCourse::factory(),
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(7)->addHours(2),
            'location' => $this->faker->optional()->city(),
            'meeting_link' => null,
            'capacity' => 20,
            'instructor_id' => null,
            'status' => TrainingSession::STATUS_SCHEDULED,
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
