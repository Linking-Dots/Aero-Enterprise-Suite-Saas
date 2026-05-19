<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmDisciplinaryActionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrmDisciplinaryActionTypeFactory extends Factory
{
    protected $model = HrmDisciplinaryActionType::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'description' => $this->faker->sentence(),
            'escalates_after_count' => null,
            'escalates_to_type' => null,
            'active' => true,
        ];
    }

    public function withEscalation(int $count = 3, string $toType = 'Written Warning'): static
    {
        return $this->state(['escalates_after_count' => $count, 'escalates_to_type' => $toType]);
    }
}
