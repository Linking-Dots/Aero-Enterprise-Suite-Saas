<?php

namespace Aero\HRM\Database\Factories;

use Aero\Core\Models\User;
use Aero\HRM\Models\HrmSafetyIncident;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HrmSafetyIncident>
 */
class HrmSafetyIncidentFactory extends Factory
{
    protected $model = HrmSafetyIncident::class;

    public function definition(): array
    {
        return [
            'reference' => 'INC-'.date('Y').'-'.strtoupper(Str::random(6)),
            'occurred_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'type' => $this->faker->randomElement([
                HrmSafetyIncident::TYPE_INJURY,
                HrmSafetyIncident::TYPE_NEAR_MISS,
                HrmSafetyIncident::TYPE_PROPERTY_DAMAGE,
            ]),
            'severity' => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'description' => $this->faker->sentence(),
            'status' => HrmSafetyIncident::STATUS_REPORTED,
            'reported_by' => User::factory(),
        ];
    }
}
