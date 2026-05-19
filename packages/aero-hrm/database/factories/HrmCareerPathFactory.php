<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmCareerPath;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HrmCareerPath>
 */
class HrmCareerPathFactory extends Factory
{
    protected $model = HrmCareerPath::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'description' => $this->faker->paragraph(),
            'target_role_id' => null,
            'is_active' => true,
        ];
    }
}
