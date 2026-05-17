<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Aero\HRM\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Engineering',
                'Human Resources',
                'Finance',
                'Marketing',
                'Sales',
                'Operations',
                'Customer Support',
                'Product',
                'Design',
                'Legal',
            ]),
            'code' => strtoupper($this->faker->lexify('DEPT???')),
            'description' => $this->faker->sentence(),
            'parent_id' => null,
            'manager_id' => null,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the department is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
