<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Feature\Auth;

use Aero\Auth\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class SessionControllerTest extends TestCase
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

    public function test_sessions_page_requires_authentication(): void
    {
        $this->get(route('core.security.sessions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_sessions_page_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.authentication.sessions.view');

        $this->actingAs($user)
            ->get(route('core.security.sessions.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/Sessions')
                ->has('sessions')
                ->has('current_session_id')
                ->has('max_sessions')
            );
    }

    public function test_sessions_page_returns_403_without_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('core.security.sessions.index'))
            ->assertForbidden();
    }
}
