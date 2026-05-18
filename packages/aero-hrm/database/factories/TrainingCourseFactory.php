<?php

declare(strict_types=1);

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\TrainingCategory;
use Aero\HRM\Models\TrainingCourse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrainingCourse>
 */
class TrainingCourseFactory extends Factory
{
    protected $model = TrainingCourse::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(4, false);

        return [
            'category_id' => TrainingCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => $this->faker->optional()->sentence(),
            'description' => null,
            'delivery_mode' => 'in_person',
            'duration_minutes' => 60,
            'learning_objectives' => null,
            'prerequisites' => null,
            'tags' => null,
            'is_mandatory' => false,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
