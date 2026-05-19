<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmBenefit;
use Illuminate\Database\Eloquent\Factories\Factory;

class HrmBenefitFactory extends Factory
{
    protected $model = HrmBenefit::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('BENEFIT_????')),
            'name' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement(['health', 'dental', 'vision', 'life', 'disability', 'pension', 'wellness', 'other']),
            'description' => $this->faker->sentence(),
            'provider' => $this->faker->company(),
            'employee_cost' => $this->faker->randomFloat(2, 0, 300),
            'employer_cost' => $this->faker->randomFloat(2, 0, 500),
            'frequency' => 'monthly',
            'allows_dependents' => false,
            'dependent_cost' => null,
            'eligibility_rules' => null,
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }

    public function withDependents(float $cost = 50.00): static
    {
        return $this->state(['allows_dependents' => true, 'dependent_cost' => $cost]);
    }
}
