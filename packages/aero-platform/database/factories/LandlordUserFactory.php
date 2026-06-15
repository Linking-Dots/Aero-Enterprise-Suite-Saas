<?php

namespace Aero\Platform\Database\Factories;

use Aero\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Aero\Auth\Models\User>
 */
class LandlordUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_name' => Str::slug($name),
            'name' => $name,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'password' => static::$password ??= Hash::make('password'),
            'profile_image' => null,
            'timezone' => 'UTC',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is inactive (inactive = soft-deleted; SoftDeletes is
     * the source of truth for active/inactive).
     */
    public function inactive(): static
    {
        return $this->afterCreating(fn (User $user) => $user->delete());
    }

    /**
     * Indicate that the user has two-factor authentication enabled.
     */
    public function withTwoFactorAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt(Str::random(32)),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode([
                Str::random(10),
                Str::random(10),
                Str::random(10),
                Str::random(10),
            ])),
        ]);
    }

    /**
     * Indicate that the user is a Platform Super Admin.
     */
    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Platform Super Admin');
        });
    }

    /**
     * Indicate that the user is a Platform Admin.
     */
    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Platform Admin');
        });
    }

    /**
     * Indicate that the user is Platform Support.
     */
    public function support(): static
    {
        return $this->afterCreating(function (User $user) {
            $user->assignRole('Platform Support');
        });
    }
}
