<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\CalibrationSession;
use Aero\HRM\Models\ReviewCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalibrationSessionFactory extends Factory
{
    protected $model = CalibrationSession::class;

    public function definition(): array
    {
        return [
            'cycle_id' => ReviewCycle::factory(),
            'name' => $this->faker->words(3, true),
            'grid' => null,
            'status' => CalibrationSession::STATUS_DRAFT,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => CalibrationSession::STATUS_ACTIVE]);
    }
}
