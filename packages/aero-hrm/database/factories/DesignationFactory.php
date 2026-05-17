<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\Department;
use Aero\HRM\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Aero\HRM\Models\Designation>
 */
class DesignationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Designation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->randomElement([
                'Software Engineer',
                'Senior Software Engineer',
                'HR Manager',
                'HR Executive',
                'Finance Manager',
                'Accountant',
                'Marketing Manager',
                'Sales Executive',
                'Product Manager',
                'Designer',
                'Team Lead',
                'Project Manager',
            ]),
            'department_id' => Department::factory(),
            'description' => $this->faker->sentence(),
            'hierarchy_level' => $this->faker->numberBetween(1, 5),
            'parent_id' => null,
        ];
    }
}
