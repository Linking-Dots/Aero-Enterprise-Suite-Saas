<?php

declare(strict_types=1);

namespace Aero\Platform\Tests;

use Aero\Contracts\AeroMode;
use Aero\HRMAC\Models\Role as HrmacRole;
use Aero\Kernel\ValueObjects\RequestContext;
use Aero\Platform\Models\LandlordUser;
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
 *  1. CONNECTION SHARING (createApplication): platform-tier migrations are routed
 *     to the `central` connection by the tier migrator override. We point
 *     `central`/`mysql` at the same in-memory SQLite PDO as the default
 *     connection BEFORE the migrate trait runs, so the platform tables land in
 *     the one database every query can see. Done in createApplication() so it
 *     runs before migrate and is not shadowed by the DatabaseMigrations trait.
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

        $this->shareSqliteAcrossConnections($app);

        return $app;
    }

    /**
     * Migrate without the trait's teardown `migrate:rollback`. The suite runs on
     * a throwaway in-memory database (rebuilt by migrate:fresh each test), so a
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
    protected function superAdminLandlord(array $attributes = []): LandlordUser
    {
        AeroMode::withoutTenantContextGuard(function () {
            HrmacRole::firstOrCreate(
                ['name' => 'Super Platform Admin'],
                ['guard_name' => 'landlord'],
            );
        });

        $user = LandlordUser::factory()->create($attributes);
        $user->assignRole('Super Platform Admin');

        return $user;
    }

    /**
     * Point the `central` and legacy `mysql` connections at the same in-memory
     * SQLite PDO as the default connection, so tier-routed central migrations
     * land in the one database every test query can see.
     */
    protected function shareSqliteAcrossConnections($app = null): void
    {
        $app ??= $this->app;

        // FK enforcement OFF: migrate:fresh between tests drops every package's
        // tables, and cross-package FKs (e.g. HRM training_*) make a FK-enforced
        // drop fail on ordering. The default connection also runs with FKs off in
        // the host's phpunit, so this keeps the shared connections consistent.
        $sqliteConfig = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ];

        config([
            'database.connections.mysql' => $sqliteConfig,
            'database.connections.central' => $sqliteConfig,
            'tenancy.database.central_connection' => 'sqlite',
        ]);

        $db = $app['db'];
        $db->purge('mysql');
        $db->purge('central');

        $pdo = $db->connection('sqlite')->getPdo();
        $db->connection('mysql')->setPdo($pdo);
        $db->connection('central')->setPdo($pdo);
    }
}
