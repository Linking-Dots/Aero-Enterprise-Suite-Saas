<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Feedback360Request;
use Illuminate\Database\Eloquent\Factories\Factory;

class Feedback360RequestFactory extends Factory
{
    protected $model = Feedback360Request::class;

    public function definition(): array
    {
        return [
            'subject_employee_id' => 1,
            'requester_id' => 1,
            'respondent_ids' => [],
            'due_on' => $this->faker->dateTimeBetween('now', '+30 days'),
            'status' => Feedback360Request::STATUS_OPEN,
        ];
    }

    public function closed(): static
    {
        return $this->state(['status' => Feedback360Request::STATUS_CLOSED]);
    }
}
