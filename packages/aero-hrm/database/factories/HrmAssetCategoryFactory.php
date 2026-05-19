<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmAssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmAssetCategory>
 */
class HrmAssetCategoryFactory extends Factory
{
    protected $model = HrmAssetCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'description' => $this->faker->optional()->sentence(),
            'active' => true,
        ];
    }
}
