<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\PayComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayComponent>
 */
class PayComponentFactory extends Factory
{
    protected $model = PayComponent::class;

    public function definition(): array
    {
        return [
            'code'      => strtoupper($this->faker->unique()->lexify('COMP????')),
            'name'      => $this->faker->words(3, true),
            'kind'      => $this->faker->randomElement(['earning', 'deduction']),
            'calc_type' => $this->faker->randomElement(['fixed', 'percent_of_basic', 'percent_of_gross', 'formula']),
            'value'     => $this->faker->randomFloat(4, 0, 5000),
            'taxable'   => true,
            'active'    => true,
        ];
    }
}
