<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\HrmAnnouncement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmAnnouncement>
 */
class HrmAnnouncementFactory extends Factory
{
    protected $model = HrmAnnouncement::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
            'is_global' => true,
            'target_department_ids' => null,
            'target_role_ids' => null,
            'created_by' => User::factory(),
            'published_at' => now(),
        ];
    }
}
