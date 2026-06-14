<?php

declare(strict_types=1);

namespace Aero\Platform\Tests;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Contracts\AeroMode;
use Aero\HRMAC\Models\Role as HrmacRole;
use Aero\Kernel\ValueObjects\RequestContext;
use Aero\Auth\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

/**
 * Base test case for the aero-platform package suite.
 *
 * The platform runs in the SaaS host's central/landlord context, so the package
 * tests boot the full host application via the host's `Tests\TestCase`.
 *
 * This base centralises the harness the platform suite needs (previously copied,
 * incorrectly, into each test):
 *
 *  1. CENTRAL CONNECTION (createApplication): the platform suite runs on REAL
 *     MySQL (the throwaway `aeos_platform_test` schema) — never SQLite — so FK
 *     constraints, migration DDL and the central connection behave exactly as in
 *     a live SaaS deployment. Platform-tier migrations are routed to the
 *     `central` connection by the tier migrator override; we point `central` at
 *     the same MySQL schema as the default connection BEFORE the migrate trait
 *     runs, so every table lands in the one database every query can see.
 *
 *  2. PLATFORM REQUEST CONTEXT (setUp): HRMAC's context guard fail-closes unless
 *     a platform RequestContext is bound. HTTP requests get this from the
 *     resolve.platform.context route middleware, but factory/seeder work in
 *     setUp runs before any request — so we bind it here for the pre-HTTP phase.
 *
 *  3. SUPER-ADMIN LANDLORD HELPER: platform admin routes are gated by `hrmac:`
 *     middleware, which only short-circuits for a recognised super-admin role
 *     (config hrmac.super_admin_roles). superAdminLandlord() creates a landlord
 *     holding the 'Super Platform Admin' role so admin routes authorise.
 *
 * (Install markers — aeos.installed / aeos.mode=saas — are created in
 *  tests/bootstrap.php so the provider registers platform routes at boot.)
 */
abstract class TestCase extends \Tests\TestCase
{
    use DatabaseMigrations;

    public function createApplication()
    {
        $app = parent::createApplication();

        $this->bindCentralConnection($app);

        return $app;
    }

    /**
     * Migrate without the trait's teardown `migrate:rollback`. The suite runs on
     * a throwaway MySQL schema rebuilt by migrate:fresh each test, so a
     * reverse-order rollback is both pointless and broken — some cross-package
     * down() drops fail on FK/table ordering against the shared schema.
     */
    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Pre-HTTP platform context for HRMAC-backed central queries (factory,
        // seeders, role assignment). Route requests rebind this via middleware.
        $this->app->instance(RequestContext::class, new RequestContext('platform', 'landlord'));

        // Inertia page assertions check the component NAME, not the built JS
        // bundle; the React page files are not present in the test runner.
        config(['inertia.testing.ensure_pages_exist' => false]);
    }

    /**
     * Create a landlord that authorises against `hrmac:` middleware by holding a
     * recognised super-admin role.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function superAdminLandlord(array $attributes = []): User
    {
        AeroMode::withoutTenantContextGuard(function () {
            HrmacRole::firstOrCreate(
                ['name' => 'Super Platform Admin'],
                ['guard_name' => 'landlord'],
            );
        });

        $user = LandlordUserFactory::new()->create($attributes);
        $user->assignRole('Super Platform Admin');

        return $user;
    }

    /**
     * Point the `central` connection at the same MySQL schema as the default
     * connection, so tier-routed central migrations and central-bound landlord
     * queries land in the one throwaway database (aeos_platform_test). Both are
     * real MySQL connections to the same schema, so committed DDL/DML from
     * migrate:fresh is visible across them.
     *
     * IMPORTANT: only PURGE central here — never pre-open it. An open, idle
     * `central` connection holds table metadata locks that deadlock
     * migrate:fresh's `db:wipe` DROP TABLE on the default connection (the DROP
     * waits forever on the idle connection). Leaving central lazy means it only
     * opens during migrate/queries, where each DDL/DML statement auto-commits
     * and releases its locks immediately.
     */
    protected function bindCentralConnection($app = null): void
    {
        $app ??= $this->app;

        $mysql = config('database.connections.mysql');

        config([
            'database.connections.central' => $mysql,
            'tenancy.database.central_connection' => 'central',
        ]);

        // Lazy: only purge so `central` re-resolves with the new config. Do NOT
        // pre-open it here — an open idle `central` connection holds metadata
        // locks that deadlock migrate:fresh's db:wipe DROP on the default
        // connection. central opens lazily during migrate/queries (DDL/DML
        // auto-commit, releasing locks immediately).
        $app['db']->purge('central');
    }
}
