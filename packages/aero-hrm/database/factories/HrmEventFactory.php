<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\HrmEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HrmEvent>
 */
class HrmEventFactory extends Factory
{
    protected $model = HrmEvent::class;

    public function definition(): array
    {
        return [
            'slug' => Str::slug($this->faker->words(3, true)).'-'.Str::random(4),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'location' => $this->faker->city(),
            'starts_at' => now()->addDays(7),
            'ends_at' => now()->addDays(7)->addHours(4),
            'status' => HrmEvent::STATUS_DRAFT,
            'is_public' => false,
            'capacity' => null,
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state([
            'status' => HrmEvent::STATUS_DRAFT,
        ]);
    }

    public function published(): static
    {
        return $this->state([
            'status' => HrmEvent::STATUS_PUBLISHED,
            'published_at' => now(),
            'is_public' => true,
        ]);
    }
}
