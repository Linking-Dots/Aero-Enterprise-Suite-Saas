<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\SalaryStructure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryStructure>
 */
class SalaryStructureFactory extends Factory
{
    protected $model = SalaryStructure::class;

    public function definition(): array
    {
        return [
            'name'          => $this->faker->words(2, true).' Structure',
            'basic'         => $this->faker->randomFloat(2, 20000, 200000),
            'component_ids' => [],
            'active'        => true,
        ];
    }
}
