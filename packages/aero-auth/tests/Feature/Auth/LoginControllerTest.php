<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Feature\Auth;

use Aero\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class LoginControllerTest extends TestCase
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

    public function test_login_page_renders_correct_component(): void
    {
        $this->get(route('login'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Login')
                ->has('canResetPassword')
                ->has('status')
            );
    }

    public function test_login_requires_device_id(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post(route('login'), [
            'email'    => 'test@example.com',
            'password' => 'password',
            // device_id intentionally omitted
        ])->assertSessionHasErrors('device_id');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post(route('login'), [
            'email'     => 'test@example.com',
            'password'  => 'wrong-password',
            'device_id' => 'test-device-' . uniqid(),
        ])->assertSessionHasErrors('email');
    }

    public function test_login_requires_email(): void
    {
        $this->post(route('login'), [
            'password'  => 'password',
            'device_id' => 'test-device-' . uniqid(),
        ])->assertSessionHasErrors('email');
    }

    public function test_login_requires_password(): void
    {
        $this->post(route('login'), [
            'email'     => 'test@example.com',
            'device_id' => 'test-device-' . uniqid(),
        ])->assertSessionHasErrors('password');
    }

    public function test_logout_redirects_to_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('logout'))
            ->assertRedirect();

        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect();
    }
}
