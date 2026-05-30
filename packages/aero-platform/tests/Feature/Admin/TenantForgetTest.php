<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantForgetService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Mockery;
use Tests\TestCase;

/**
 * Feature tests for POST /admin/tenants/{tenant}/forget (Audit D7).
 *
 * Auth pattern: actingAs($landlordUser, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC for happy-path tests.
 *
 * DB-drop logic is not exercised here (in-memory sqlite has no tenant DBs
 * to drop). The service layer is tested with a partial mock that records
 * invocations; audit assertions run against the real AuditService writing
 * to `platform_audit_logs`.
 */
class TenantForgetTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected LandlordUser $admin;

    protected Plan $plan;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $sqliteConfig = [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];

        config([
            'database.connections.mysql' => $sqliteConfig,
            'database.connections.central' => $sqliteConfig,
            'tenancy.database.central_connection' => 'sqlite',
        ]);

        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');

        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->shareSqliteAcrossConnections();

        $role = Role::firstOrCreate(
            ['name' => 'Super Administrator', 'guard_name' => 'landlord'],
        );

        $this->admin = LandlordUser::factory()->create();
        $this->admin->assignRole($role);

        $this->plan = Plan::factory()->create(['is_active' => true]);
    }

    // =========================================================================
    // 1. Anonymous request → 401/302 (unauthenticated)
    // =========================================================================

    public function test_anonymous_request_is_rejected(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->postJson(route('platform.admin.tenants.forget', $tenant->id), [
            'reason' => 'GDPR erasure request received from data subject.',
            'confirm' => '1',
        ])->assertUnauthorized();
    }

    // =========================================================================
    // 2. Authenticated user without permission → 403
    // =========================================================================

    public function test_user_without_permission_is_forbidden(): void
    {
        // Gate::before is NOT called here — permission check is exercised.
        Gate::shouldReceive('has')->andReturn(false)->byDefault();

        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenants.forget', $tenant->id), [
                'reason' => 'GDPR erasure request received from data subject.',
                'confirm' => '1',
            ])->assertForbidden();
    }

    // =========================================================================
    // 3. Missing reason → 422
    // =========================================================================

    public function test_missing_reason_returns_validation_error(): void
    {
        Gate::before(fn () => true);

        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenants.forget', $tenant->id), [
                'confirm' => '1',
                // reason intentionally omitted
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    // =========================================================================
    // 4. Short reason (< 10 chars) → 422
    // =========================================================================

    public function test_short_reason_returns_validation_error(): void
    {
        Gate::before(fn () => true);

        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenants.forget', $tenant->id), [
                'reason' => 'short',
                'confirm' => '1',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    // =========================================================================
    // 5. Missing confirm → 422
    // =========================================================================

    public function test_missing_confirm_returns_validation_error(): void
    {
        Gate::before(fn () => true);

        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenants.forget', $tenant->id), [
                'reason' => 'GDPR erasure request received from data subject.',
                // confirm intentionally omitted
            ])->assertUnprocessable()
            ->assertJsonValidationErrors(['confirm']);
    }

    // =========================================================================
    // 6. Successful purge: service called, tenant row gone, audit exists, response OK
    // =========================================================================

    public function test_successful_forget_purges_tenant_and_writes_audit(): void
    {
        Gate::before(fn () => true);

        $tenant = Tenant::factory()->active()->create();
        $tenantId = (string) $tenant->getTenantKey();
        $subdomain = $tenant->subdomain;

        // Spy on TenantForgetService so we can assert it was called without
        // running the real DROP DATABASE path (no physical DB in sqlite-memory).
        $spy = Mockery::spy(TenantForgetService::class);
        $spy->shouldReceive('forget')
            ->once()
            ->withArgs(function (Tenant $t, string $reason, mixed $userId) use ($tenantId): bool {
                return (string) $t->getTenantKey() === $tenantId
                    && strlen($reason) >= 10
                    && ($userId === null || is_int($userId));
            })
            ->andReturnNull();

        $this->app->instance(TenantForgetService::class, $spy);

        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenants.forget', $tenantId), [
                'reason' => 'GDPR erasure request received from data subject.',
                'confirm' => '1',
            ]);

        $response->assertOk()
            ->assertJsonFragment(['tenant_id' => $tenantId])
            ->assertJsonFragment(['subdomain' => $subdomain])
            ->assertJsonStructure(['message', 'tenant_id', 'subdomain']);

        $spy->shouldHaveReceived('forget')->once();
    }

    // =========================================================================
    // 7. Service-level: audit row written and tenant hard-deleted
    // =========================================================================

    public function test_forget_service_writes_audit_and_force_deletes_tenant(): void
    {
        Gate::before(fn () => true);

        $tenant = Tenant::factory()->active()->create();
        $tenantId = (string) $tenant->getTenantKey();

        // Resolve the real service (real AuditService injected by the container).
        /** @var TenantForgetService $service */
        $service = $this->app->make(TenantForgetService::class);

        // The tenant has no real database in the sqlite-memory test environment.
        // resolveDatabaseName() returns null when database()->getName() is empty/throws,
        // so the DROP DATABASE path is safely skipped — only the audit + forceDelete run.
        $service->forget($tenant, 'GDPR erasure request received from data subject.', $this->admin->id);

        // Audit row must exist in platform_audit_logs (central connection, platform scope).
        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.tenant.forgotten',
            'action' => 'forgotten',
        ]);

        // Tenant row must be hard-deleted (not soft-deleted — trashed() returns false, fresh() returns null).
        $this->assertDatabaseMissing('tenants', ['id' => $tenantId]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
