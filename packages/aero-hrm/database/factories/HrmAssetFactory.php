<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmAsset;
use Aero\HRM\Models\HrmAssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmAsset>
 */
class HrmAssetFactory extends Factory
{
    protected $model = HrmAsset::class;

    public function definition(): array
    {
        return [
            'tag' => 'ASSET-'.strtoupper($this->faker->unique()->bothify('??###')),
            'name' => $this->faker->words(3, true),
            'category_id' => HrmAssetCategory::factory(),
            'serial_number' => $this->faker->optional()->bothify('SN-######'),
            'vendor' => $this->faker->optional()->company(),
            'purchased_on' => $this->faker->optional()->date(),
            'purchase_cost' => $this->faker->randomFloat(2, 100, 5000),
            'status' => HrmAsset::STATUS_AVAILABLE,
            'photo_path' => null,
            'notes' => null,
        ];
    }

    public function allocated(): static
    {
        return $this->state(['status' => HrmAsset::STATUS_ALLOCATED]);
    }

    public function retired(): static
    {
        return $this->state(['status' => HrmAsset::STATUS_RETIRED]);
    }
}
