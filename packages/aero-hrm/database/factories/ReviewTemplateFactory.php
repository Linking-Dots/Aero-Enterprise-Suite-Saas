<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\ReviewTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewTemplateFactory extends Factory
{
    protected $model = ReviewTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'sections' => [],
            'rating_scale' => [
                'min' => 1,
                'max' => 5,
                'labels' => [],
            ],
            'active' => true,
        ];
    }
}
