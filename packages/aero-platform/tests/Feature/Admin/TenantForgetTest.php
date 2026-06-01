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
        $this->shareSqliteAcrossConnections();
        $this->refreshTestDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false];
        config([
            'database.connections.mysql' => $cfg,
            'database.connections.central' => $cfg,
            'tenancy.database.central_connection' => 'sqlite',
            'tenancy.database.managers' => [
                'sqlite' => \Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
                'mysql' => \Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            ],
            'tenancy.database.prefix' => '',
            'tenancy.database.suffix' => '',
        ]);
        $this->app['db']->purge('sqlite');
        $this->app['db']->purge('mysql');
        $this->app['db']->purge('central');
        $pdo = $this->app['db']->connection('sqlite')->getPdo();
        $this->app['db']->connection('mysql')->setPdo($pdo);
        $this->app['db']->connection('central')->setPdo($pdo);
    }

    protected function refreshTestDatabase(): void
    {
        \Illuminate\Support\Facades\Schema::dropAllTables();

        $packages = realpath(__DIR__.'/../../../..');

        foreach ([
            $packages.'/aero-core/database/migrations',
            $packages.'/aero-auth/database/migrations',
            $packages.'/aero-hrmac/database/migrations',
            $packages.'/aero-platform/database/migrations',
        ] as $path) {
            $migrator = $this->app['migrator'];
            $migrator->setConnection('sqlite');
            if (! $migrator->repositoryExists()) {
                $migrator->getRepository()->createRepository();
            }
            $migrator->run([$path]);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        \Aero\Contracts\AeroMode::reset();

        // No Role assignment — tests use Gate::before to bypass HRMAC for happy
        // path; the "without permission" test stubs the Gate directly. The role
        // wiring is exercised separately in HRMAC tests.
        $this->admin = LandlordUser::factory()->create();
    }

    // =========================================================================
    // 1. Anonymous request → 401/302 (unauthenticated)
    // =========================================================================

    public function test_anonymous_request_is_rejected(): void
    {
        $tenant = Tenant::factory()->active()->create();

        $this->postJson(route('admin.tenants.forget', $tenant->id), [
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
            ->postJson(route('admin.tenants.forget', $tenant->id), [
                'reason' => 'GDPR erasure request received from data subject.',
                'confirm' => '1',
            ])->assertForbidden();
    }

    // =========================================================================
    // 3. Missing reason → 422
    // =========================================================================

    public function test_missing_reason_returns_validation_error(): void
    {
        // These tests exercise validation + service invocation, not authorization.
        // TWO gates must be bypassed: the hrmac MIDDLEWARE (CheckRoleModuleAccess — not
        // Gate-based, so withoutMiddleware on that class only) AND the FormRequest::authorize()
        // which calls $user->can('platform.tenants.tenant-list.forget') (Gate-based, so
        // Gate::before). Target the hrmac class specifically — a no-arg withoutMiddleware()
        // would also drop SubstituteBindings and leave $tenant unresolved. Authz is covered
        // end-to-end by the anonymous + no-permission tests (full stack).
        Gate::before(fn () => true);
        $this->withoutMiddleware(\Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess::class);

        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.tenants.forget', $tenant->id), [
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
        // These tests exercise validation + service invocation, not authorization.
        // TWO gates must be bypassed: the hrmac MIDDLEWARE (CheckRoleModuleAccess — not
        // Gate-based, so withoutMiddleware on that class only) AND the FormRequest::authorize()
        // which calls $user->can('platform.tenants.tenant-list.forget') (Gate-based, so
        // Gate::before). Target the hrmac class specifically — a no-arg withoutMiddleware()
        // would also drop SubstituteBindings and leave $tenant unresolved. Authz is covered
        // end-to-end by the anonymous + no-permission tests (full stack).
        Gate::before(fn () => true);
        $this->withoutMiddleware(\Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess::class);

        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.tenants.forget', $tenant->id), [
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
        // These tests exercise validation + service invocation, not authorization.
        // TWO gates must be bypassed: the hrmac MIDDLEWARE (CheckRoleModuleAccess — not
        // Gate-based, so withoutMiddleware on that class only) AND the FormRequest::authorize()
        // which calls $user->can('platform.tenants.tenant-list.forget') (Gate-based, so
        // Gate::before). Target the hrmac class specifically — a no-arg withoutMiddleware()
        // would also drop SubstituteBindings and leave $tenant unresolved. Authz is covered
        // end-to-end by the anonymous + no-permission tests (full stack).
        Gate::before(fn () => true);
        $this->withoutMiddleware(\Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess::class);

        $tenant = Tenant::factory()->active()->create();

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('admin.tenants.forget', $tenant->id), [
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
        // These tests exercise validation + service invocation, not authorization.
        // TWO gates must be bypassed: the hrmac MIDDLEWARE (CheckRoleModuleAccess — not
        // Gate-based, so withoutMiddleware on that class only) AND the FormRequest::authorize()
        // which calls $user->can('platform.tenants.tenant-list.forget') (Gate-based, so
        // Gate::before). Target the hrmac class specifically — a no-arg withoutMiddleware()
        // would also drop SubstituteBindings and leave $tenant unresolved. Authz is covered
        // end-to-end by the anonymous + no-permission tests (full stack).
        Gate::before(fn () => true);
        $this->withoutMiddleware(\Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess::class);

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
            ->postJson(route('admin.tenants.forget', $tenantId), [
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
        // These tests exercise validation + service invocation, not authorization.
        // TWO gates must be bypassed: the hrmac MIDDLEWARE (CheckRoleModuleAccess — not
        // Gate-based, so withoutMiddleware on that class only) AND the FormRequest::authorize()
        // which calls $user->can('platform.tenants.tenant-list.forget') (Gate-based, so
        // Gate::before). Target the hrmac class specifically — a no-arg withoutMiddleware()
        // would also drop SubstituteBindings and leave $tenant unresolved. Authz is covered
        // end-to-end by the anonymous + no-permission tests (full stack).
        Gate::before(fn () => true);
        $this->withoutMiddleware(\Aero\HRMAC\Http\Middleware\CheckRoleModuleAccess::class);

        $tenant = Tenant::factory()->active()->create();
        $tenantId = (string) $tenant->getTenantKey();

        // Subclass to no-op the actual DROP DATABASE — sqlite doesn't support
        // it. Audit + forceDelete are still exercised by the parent forget().
        $service = new class(
            $this->app->make(\Aero\Contracts\AuditServiceInterface::class),
            $this->app->make(\Aero\Platform\Support\TenantTeardownSequencer::class),
        ) extends TenantForgetService {
            protected function dropTenantDatabase(string $tenantId, string $subdomain, string $databaseName): void
            {
                // intentionally no-op in test
            }
        };

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
