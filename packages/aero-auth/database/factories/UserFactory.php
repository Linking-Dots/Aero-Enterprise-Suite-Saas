<?php

namespace Aero\Auth\Database\Factories;

use Aero\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<User>
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->name();

        return [
            'name' => $name,
            'user_name' => Str::slug($name.' '.$this->faker->unique()->numberBetween(1000, 9999), '_'),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->e164PhoneNumber(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'notification_preferences' => [],
        ];
    }

    /**
     * Indicate that the user has not verified their email.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
