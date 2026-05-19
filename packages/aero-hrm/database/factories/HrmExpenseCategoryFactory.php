<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmExpenseCategory>
 */
class HrmExpenseCategoryFactory extends Factory
{
    protected $model = HrmExpenseCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->optional()->sentence(),
            'active' => true,
        ];
    }
}
