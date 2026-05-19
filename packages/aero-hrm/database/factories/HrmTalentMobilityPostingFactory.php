<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\HrmTalentMobilityPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrmTalentMobilityPosting>
 */
class HrmTalentMobilityPostingFactory extends Factory
{
    protected $model = HrmTalentMobilityPosting::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['transfer', 'project', 'secondment', 'promotion']),
            'department_id' => null,
            'role_id' => null,
            'closes_at' => now()->addDays(30)->toDateString(),
            'status' => HrmTalentMobilityPosting::STATUS_OPEN,
            'created_by' => User::factory(),
        ];
    }
}
