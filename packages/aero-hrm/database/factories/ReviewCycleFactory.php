<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\ReviewCycle;
use Aero\HRM\Models\ReviewTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewCycleFactory extends Factory
{
    protected $model = ReviewCycle::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-6 months', 'now');
        $end = $this->faker->dateTimeBetween($start, '+6 months');

        return [
            'name' => $this->faker->words(4, true),
            'template_id' => ReviewTemplate::factory(),
            'starts_on' => $start,
            'ends_on' => $end,
            'status' => ReviewCycle::STATUS_DRAFT,
            'employee_ids' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => ReviewCycle::STATUS_ACTIVE]);
    }

    public function closed(): static
    {
        return $this->state(['status' => ReviewCycle::STATUS_CLOSED]);
    }
}
