<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Feature\Auth;

use Aero\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
            \Aero\Auth\AeroAuthServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    public function test_index_requires_authentication(): void
    {
        $this->get(route('auth.two-factor.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('auth.two-factor.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/TwoFactor/Index')
            );
    }

    public function test_setup_requires_authentication(): void
    {
        $this->postJson(route('auth.two-factor.setup'))
            ->assertUnauthorized();
    }

    public function test_setup_returns_qr_code_and_secret(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('auth.two-factor.setup'));

        $response->assertOk()
            ->assertJsonStructure(['qr_code_url', 'secret']);
    }

    public function test_confirm_requires_valid_totp_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('auth.two-factor.confirm'), ['code' => '000000'])
            ->assertJsonPath('success', false);
    }

    public function test_disable_requires_current_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $this->actingAs($user)
            ->postJson(route('auth.two-factor.disable'), ['password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_challenge_renders_for_guest(): void
    {
        $this->get(route('auth.two-factor.challenge'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/TwoFactor/Challenge')
            );
    }

    public function test_verify_returns_422_for_invalid_code(): void
    {
        $this->postJson(route('auth.two-factor.verify'), ['code' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_regenerate_codes_requires_authentication(): void
    {
        $this->postJson(route('auth.two-factor.regenerate-codes'))
            ->assertUnauthorized();
    }
}
