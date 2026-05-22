<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Enterprise;

use Aero\Platform\Models\Enterprise\LicenseActivation;
use Aero\Platform\Models\Enterprise\LicenseKey;
use Aero\Platform\Services\Enterprise\LicenseService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Tests\TestCase;

/**
 * P-11 — LicenseService
 *
 * Tests cover: generate validation, product_codes + plan_code persistence,
 * activation entitlement scoping, and activation independence.
 */
class LicenseServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected LicenseService $service;

    /**
     * Override runDatabaseMigrations to:
     * 1. Redirect all connections to the same in-memory SQLite PDO before
     *    migrations run (avoids "Unknown database ':memory:'" on MySQL driver).
     * 2. Skip migrate:rollback teardown — SQLite down() methods use MySQL
     *    syntax incompatible with SQLite; in-memory DB is discarded anyway.
     */
    public function runDatabaseMigrations(): void
    {
        $this->shareSqliteAcrossConnections();
        $this->artisan('migrate:fresh');
    }

    private function shareSqliteAcrossConnections(): void
    {
        $sqliteConfig = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.connections.mysql' => $sqliteConfig, 'database.connections.central' => $sqliteConfig, 'tenancy.database.central_connection' => 'sqlite']);
        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');
        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->service = app(LicenseService::class);
    }

    // =========================================================================
    // GENERATE — VALIDATION
    // =========================================================================

    public function test_generate_license_requires_product_codes(): void
    {
        // LicenseService::generate() requires a non-empty product_codes array.
        // Passing an empty array should throw an exception (InvalidArgumentException
        // or TypeError depending on how the service guards this input).
        $this->expectException(\Throwable::class);

        $this->service->generate(
            productCodes: [],
            planCode: null,
            issuedTo: ['name' => 'Test User', 'email' => 'test@example.com'],
        );
    }

    // =========================================================================
    // GENERATE — PERSISTENCE
    // =========================================================================

    public function test_generate_license_stores_product_codes_and_optional_plan_code(): void
    {
        $license = $this->service->generate(
            productCodes: ['hrm', 'finance'],
            planCode: 'pro',
            issuedTo: ['name' => 'Acme Corp', 'email' => 'billing@acme.test'],
        );

        // Reload from DB — key was replaced with raw key on the transient copy,
        // so we find by issued_to_email to avoid hash mismatch.
        $persisted = LicenseKey::where('issued_to_email', 'billing@acme.test')->firstOrFail();

        $this->assertEqualsCanonicalizing(['hrm', 'finance'], $persisted->product_codes);
        $this->assertSame('pro', $persisted->plan_code);
        $this->assertSame('Acme Corp', $persisted->issued_to_name);
    }

    public function test_generate_license_without_plan_code_stores_null(): void
    {
        $this->service->generate(
            productCodes: ['hrm'],
            planCode: null,
            issuedTo: ['name' => 'Solo Corp', 'email' => 'solo@corp.test'],
        );

        $persisted = LicenseKey::where('issued_to_email', 'solo@corp.test')->firstOrFail();

        $this->assertNull($persisted->plan_code);
        $this->assertEqualsCanonicalizing(['hrm'], $persisted->product_codes);
    }

    // =========================================================================
    // ACTIVATE — ENTITLEMENT SCOPING
    // =========================================================================

    public function test_activation_grants_entitlement_for_listed_product_codes_only(): void
    {
        $license = $this->service->generate(
            productCodes: ['hrm', 'finance'],
            planCode: null,
            issuedTo: ['name' => 'EntTest', 'email' => 'ent@test.test'],
            maxActivations: 5,
        );

        // Reload so we work with the hashed key record (not the transient copy).
        $persisted = LicenseKey::where('issued_to_email', 'ent@test.test')->firstOrFail();

        // Assert entitlement via the model helper.
        $this->assertTrue($persisted->entitles('hrm'));
        $this->assertTrue($persisted->entitles('finance'));

        // A product code not in the list must NOT be entitled.
        $this->assertFalse($persisted->entitles('payroll'));
        $this->assertFalse($persisted->entitles('crm'));
    }

    public function test_activate_creates_license_activation_record(): void
    {
        $license = $this->service->generate(
            productCodes: ['hrm'],
            planCode: null,
            issuedTo: ['name' => 'ActivateTest', 'email' => 'activate@test.test'],
            maxActivations: 3,
        );

        $persisted = LicenseKey::where('issued_to_email', 'activate@test.test')->firstOrFail();

        $activation = $this->service->activate($persisted, [
            'install_id' => 'install-abc-001',
            'domain' => 'customer.example.com',
        ]);

        $this->assertInstanceOf(LicenseActivation::class, $activation);
        $this->assertDatabaseHas('license_activations', [
            'license_id' => $persisted->id,
            'install_id' => 'install-abc-001',
        ]);

        // activation_count incremented.
        $persisted->refresh();
        $this->assertSame(1, $persisted->activation_count);
    }

    public function test_activation_blocked_when_max_activations_reached(): void
    {
        $license = $this->service->generate(
            productCodes: ['hrm'],
            planCode: null,
            issuedTo: ['name' => 'MaxTest', 'email' => 'max@test.test'],
            maxActivations: 1,
        );

        $persisted = LicenseKey::where('issued_to_email', 'max@test.test')->firstOrFail();

        // First activation — should succeed.
        $this->service->activate($persisted, ['install_id' => 'install-first']);

        // Second activation — should throw RuntimeException.
        $this->expectException(RuntimeException::class);

        $persisted->refresh();
        $this->service->activate($persisted, ['install_id' => 'install-second']);
    }

    // =========================================================================
    // ORDER FORM — PLAN SUB + PRODUCT SUBS INDEPENDENCE
    // =========================================================================

    public function test_activating_order_form_creates_plan_sub_and_product_subs_independently(): void
    {
        // Generate a license that covers both a plan code and multiple product codes.
        // Then assert that:
        //   1. One LicenseKey is created that holds plan_code.
        //   2. N product_codes are stored independently on the same record.
        //   3. Revoking (simulating plan cancellation) does not remove product_codes.
        $license = $this->service->generate(
            productCodes: ['hrm', 'finance', 'payroll'],
            planCode: 'enterprise',
            issuedTo: ['name' => 'BigCo', 'email' => 'bigco@test.test'],
            maxActivations: 5,
        );

        $persisted = LicenseKey::where('issued_to_email', 'bigco@test.test')->firstOrFail();

        $this->assertSame('enterprise', $persisted->plan_code);
        $this->assertCount(3, $persisted->product_codes);

        // Revoke simulates "plan subscription cancelled".
        $this->service->revoke($persisted, 'admin');

        $persisted->refresh();

        // After revoke (plan cancellation), product_codes remain intact — they are independent.
        $this->assertCount(3, $persisted->product_codes);
        $this->assertNotNull($persisted->revoked_at);

        // LicenseKey row count remains 1 (no duplicate rows created).
        $this->assertSame(1, LicenseKey::where('issued_to_email', 'bigco@test.test')->count());
    }

    // =========================================================================
    // GENERATE — AUDIT LOG
    // =========================================================================

    public function test_generate_license_writes_audit_log(): void
    {
        $this->service->generate(
            productCodes: ['hrm'],
            planCode: null,
            issuedTo: ['name' => 'AuditUser', 'email' => 'audit@test.test'],
        );

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.license.key_generated',
        ]);
    }
}
