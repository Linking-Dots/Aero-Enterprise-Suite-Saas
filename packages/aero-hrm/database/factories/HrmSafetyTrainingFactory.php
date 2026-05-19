<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmSafetyTraining;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmSafetyTraining>
 */
class HrmSafetyTrainingFactory extends Factory
{
    protected $model = HrmSafetyTraining::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['induction', 'refresher', 'equipment', 'emergency']),
            'duration_minutes' => 60,
            'mandatory' => false,
            'active' => true,
        ];
    }
}
