<?php

declare(strict_types=1);

namespace Aero\Auth\Tests\Feature\Admin;

use Aero\Auth\AeroAuthServiceProvider;
use Aero\Auth\Http\Controllers\Admin\UserAdminController;
use Aero\Auth\Models\User;
use Aero\Core\AeroCoreServiceProvider;
use Aero\HRMAC\HRMACServiceProvider;
use Aero\Kernel\AeroKernelServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Phase 2, Task 5 of the shared access-control consolidation plan
 * (docs/superpowers/plans/2026-07-06-shared-auth-access-control-consolidation.md).
 *
 * Proves Aero\Auth\Http\Controllers\Admin\UserAdminController is genuinely
 * context-free: it lists whichever Eloquent model backs the AUTHENTICATED
 * guard's provider (here: the `web` guard's `users` provider ->
 * Aero\Auth\Models\User) with zero tenant/platform symbol in the
 * controller, and that its scope-gated capabilities (impersonation) 403
 * when the consuming route's `hrmac_user_impersonation` default is false
 * and succeed when it is true.
 *
 * Routes are registered locally in defineRoutes() — this is a proof-of-shape
 * test; the real route wiring (with HRMAC middleware) happens in a later
 * plan task.
 */
class UserAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            AeroKernelServiceProvider::class,
            HRMACServiceProvider::class,
            AeroCoreServiceProvider::class,
            AeroAuthServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $sqliteConfig = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ];

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $sqliteConfig);
        $app['config']->set('database.connections.central', $sqliteConfig);

        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        // Tenant context: the `web` guard's provider resolves the shared
        // Aero\Auth\Models\User (this is what AeroCoreServiceProvider's
        // identity-model alias also points 'auth.providers.users.model' at,
        // but we set it explicitly here so the test does not depend on
        // provider boot order).
        $app['config']->set('auth.defaults', ['guard' => 'web', 'passwords' => 'users']);
        $app['config']->set('auth.guards.web', ['driver' => 'session', 'provider' => 'users']);
        $app['config']->set('auth.providers.users', ['driver' => 'eloquent', 'model' => User::class]);

        // Platform context: the `landlord` guard — today the same unified
        // User model (Boss's Auth-Identity Unification), on a different
        // connection in production. Registered here because aero-platform
        // (which normally registers it) is intentionally NOT loaded — this
        // controller must not need aero-platform to resolve it.
        $app['config']->set('auth.guards.landlord', ['driver' => 'session', 'provider' => 'landlord_users']);
        $app['config']->set('auth.providers.landlord_users', ['driver' => 'eloquent', 'model' => User::class]);

        $app['config']->set('inertia.root_view', 'app');
        $app['config']->set('inertia.testing.ensure_pages_exist', false);

        $app['config']->set('permission.models.permission', Permission::class);
        $app['config']->set('permission.models.role', Role::class);

        $app['config']->set('media-library.media_model', Media::class);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn () => response('login page', 200))->name('login');

        Route::middleware(['web'])->group(function () use ($router) {
            $router->get('/test/users', [UserAdminController::class, 'index'])
                ->name('test.users.index');

            $router->post('/test/users/{id}/impersonate-blocked', [UserAdminController::class, 'impersonate'])
                ->defaults('hrmac_user_impersonation', false);

            $router->post('/test/users/{id}/impersonate-allowed', [UserAdminController::class, 'impersonate'])
                ->defaults('hrmac_user_impersonation', true);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['view']->addLocation(__DIR__.'/../../stubs/views');

        // Bypass HRMAC module-access checks inside the controller/policies
        // this test does not exercise — only the controller's own
        // route-default gating is under test here.
        Gate::before(fn ($user) => true);
    }

    public function test_index_lists_users_from_the_resolved_web_guard_model(): void
    {
        $actor = User::factory()->create();
        User::factory()->count(2)->create();

        $this->actingAs($actor, 'web')
            ->get('/test/users')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 3)
                ->has('stats')
            );
    }

    public function test_impersonate_is_forbidden_when_route_default_is_false(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($actor, 'web')
            ->post("/test/users/{$target->id}/impersonate-blocked")
            ->assertForbidden();
    }

    public function test_impersonate_succeeds_when_route_default_is_true(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($actor, 'web')
            ->post("/test/users/{$target->id}/impersonate-allowed")
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($target, 'web');
    }
}
