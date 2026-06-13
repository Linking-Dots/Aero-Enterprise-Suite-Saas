<?php

declare(strict_types=1);

namespace Aero\Core\Tests;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Core\Models\User;
use Aero\HRMAC\HRMACServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

/**
 * Shared base test case for all aero-core Feature tests.
 *
 * - Bootstraps AeroCoreServiceProvider + HRMACServiceProvider
 * - Uses in-memory SQLite
 * - Creates supplemental tables (audit_logs, announcements, modules)
 * - Provides makeSuperAdmin() helper for HRMAC bypass
 */
abstract class PackageTestCase extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Testbench Setup
    // =========================================================================

    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            \Aero\Kernel\AeroKernelServiceProvider::class,
            AeroCoreServiceProvider::class,
            HRMACServiceProvider::class,
        ];
    }

    /**
     * Load aero-auth's migrations into the test schema.
     *
     * Auth is now core's identity foundation: it owns the `user_sessions`,
     * `user_devices`, `user_impersonations` (etc.) tables that core models relate to
     * (e.g. User::devices()/sessions(), eager-loaded by CoreUserController::show()).
     * Auth's ServiceProvider is intentionally NOT registered here — core tests only need
     * the tables, not auth's runtime bindings/routes — so we load just its migration path.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname(__DIR__, 2).'/aero-auth/database/migrations');
    }

    protected function getEnvironmentSetUp($app): void
    {
        $sqliteConfig = [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ];

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $sqliteConfig);
        // Map 'central' to the same in-memory DB so AuditService doesn't crash
        $app['config']->set('database.connections.central', $sqliteConfig);

        $app['config']->set('aero.installed', true);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');

        // App encryption key — controllers/services that encrypt (EncryptedField,
        // sessions, signed URLs) need it. Without it the encrypter throws
        // MissingAppKeyException on boot. Fixed test key (32 bytes) for determinism.
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        // SystemSetting uses spatie/medialibrary (HasMedia). getMediaModel() reads
        // config('media-library.media_model'); without it the return-type is null.
        $app['config']->set('media-library.media_model', \Spatie\MediaLibrary\MediaCollections\Models\Media::class);

        // aero-core controllers/middleware reference the dual-guard system's 'landlord'
        // guard (auth('landlord'), guard-scoped redirects, etc.). aero-platform registers
        // it at runtime in SaaS; the package test env doesn't load aero-platform, so define
        // it here (provider points at the core User model — tests don't authenticate as a
        // real landlord, they just need the guard to resolve so routes don't error).
        $app['config']->set('auth.guards.landlord', ['driver' => 'session', 'provider' => 'landlord_users']);
        $app['config']->set('auth.providers.landlord_users', ['driver' => 'eloquent', 'model' => User::class]);

        // Point Inertia at the stub app view so responses don't 500 on blade render
        $app['config']->set('inertia.root_view', 'app');
        // The actual page JSX lives in aero-ui (not resolvable in a package test env),
        // so don't assert the page FILE exists — assertInertia still checks component
        // name + props from the response.
        $app['config']->set('inertia.testing.ensure_pages_exist', false);

        // Spatie Permission needs a non-null permission model class.
        // We don't use Spatie permissions for auth, but RoleService uses Spatie Role.
        $app['config']->set('permission.models.permission', \Spatie\Permission\Models\Permission::class);
        $app['config']->set('permission.models.role', \Spatie\Permission\Models\Role::class);
    }

    /**
     * Define routes before the application boots.
     * This ensures 'login' is available when auth middleware resolves redirectTo().
     */
    protected function defineRoutes($router): void
    {
        $router->get('/login', fn () => response('login page', 200))->name('login');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Register the stub view path so Inertia can render app.blade.php
        $this->app['view']->addLocation(__DIR__.'/stubs/views');

        // Allow super-admins to pass all Gate checks (incl. StoreUserRequest::authorize)
        Gate::before(function ($user) {
            if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                return true;
            }
        });

        $this->createSupplementalTables();
    }

    /**
     * Create tables that are needed by tested features but are not included in
     * core package migrations (e.g. modules, announcements, audit_logs).
     */
    private function createSupplementalTables(): void
    {
        // modules — AdminDashboardService::getTenantStats uses Module::count()
        if (! Schema::hasTable('modules')) {
            Schema::create('modules', function ($table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(0);
                $table->timestamps();
            });
        }

        // sub_modules — HRMAC userCanAccessSubModule looks up sub_modules table
        if (! Schema::hasTable('sub_modules')) {
            Schema::create('sub_modules', function ($table) {
                $table->id();
                $table->unsignedBigInteger('module_id');
                $table->string('code');
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->integer('priority')->default(0);
                $table->string('route')->nullable();
                $table->timestamps();
            });
        }

        // announcements — DashboardController queries Announcement::active()
        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function ($table) {
                $table->id();
                $table->string('title');
                $table->longText('body');
                $table->string('type')->default('info');
                $table->string('status')->default('draft');
                $table->timestamp('published_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->string('audience')->default('all');
                $table->boolean('is_pinned')->default(false);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // audit_logs — AuditService writes on every business action
        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function ($table) {
                $table->id();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('actor_name')->nullable();
                $table->string('actor_ip', 45)->nullable();
                $table->string('event_type', 100);
                $table->string('action', 100);
                $table->text('description')->nullable();
                $table->string('subject_type')->nullable();
                $table->string('subject_id', 36)->nullable();
                $table->string('subject_label')->nullable();
                $table->json('before_state')->nullable();
                $table->json('after_state')->nullable();
                $table->json('changed_fields')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('anonymized_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // permissions + role_has_permissions — needed by Spatie-backed RoleService
        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function ($table) {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_primary');
            });
        }

        // role_module_access — HRMAC role access checks
        if (! Schema::hasTable('role_module_access')) {
            Schema::create('role_module_access', function ($table) {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('module_id')->nullable();
                $table->unsignedBigInteger('sub_module_id')->nullable();
                $table->unsignedBigInteger('component_id')->nullable();
                $table->unsignedBigInteger('action_id')->nullable();
                $table->string('access_scope')->default('all');
                $table->timestamps();
            });
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a user with the super-admin role, which bypasses all HRMAC checks.
     */
    protected function makeSuperAdmin(): User
    {
        $roleId = DB::table('roles')->where('name', 'super-admin')->value('id');
        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name'         => 'super-admin',
                'guard_name'   => 'web',
                'is_active'    => true,
                'is_protected' => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }

        $user = User::factory()->create();

        DB::table('model_has_roles')->insertOrIgnore([
            'role_id'    => $roleId,
            'model_type' => $user->getMorphClass(),
            'model_id'   => $user->id,
        ]);

        return $user;
    }
}
