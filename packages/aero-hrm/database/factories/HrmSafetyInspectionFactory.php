<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\HrmSafetyInspection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HrmSafetyInspection>
 */
class HrmSafetyInspectionFactory extends Factory
{
    protected $model = HrmSafetyInspection::class;

    public function definition(): array
    {
        return [
            'reference' => 'INSP-'.date('Y').'-'.strtoupper(Str::random(6)),
            'title' => $this->faker->sentence(),
            'scheduled_date' => $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'inspector_id' => User::factory(),
            'status' => HrmSafetyInspection::STATUS_SCHEDULED,
        ];
    }
}
