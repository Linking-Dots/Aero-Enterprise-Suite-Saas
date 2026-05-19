<?php

namespace Aero\HRM\Database\Factories;

use Aero\HRM\Models\HrmEvent;
use Aero\HRM\Models\HrmEventRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HrmEventRegistration>
 */
class HrmEventRegistrationFactory extends Factory
{
    protected $model = HrmEventRegistration::class;

    public function definition(): array
    {
        return [
            'event_id' => HrmEvent::factory(),
            'session_id' => null,
            'employee_id' => null,
            'attendee_name' => $this->faker->name(),
            'attendee_email' => $this->faker->safeEmail(),
            'token' => Str::random(48),
            'status' => HrmEventRegistration::STATUS_REGISTERED,
            'registered_at' => now(),
            'cancelled_at' => null,
        ];
    }
}
