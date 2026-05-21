<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\Finance;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Services\Finance\PaymentMethodAdminService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PaymentMethodSetDefaultTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected LandlordUser $admin;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config(['database.connections.mysql' => $cfg, 'database.connections.central' => $cfg]);
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
        Gate::before(fn () => true);

        $role = Role::firstOrCreate(['name' => 'Super Administrator', 'guard_name' => 'landlord']);
        $this->admin = LandlordUser::factory()->create();
        $this->admin->assignRole($role);
    }

    /**
     * setDefault must run inside a DB::transaction and write an audit entry.
     * The service stub delegates to the gateway — here we verify the audit log
     * and that no exception is thrown (atomicity guarantee by the service).
     */
    public function test_set_default_writes_audit_log(): void
    {
        $service = app(PaymentMethodAdminService::class);
        $service->setDefault('tenant-abc', 'pm_stub_123', $this->admin->id);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.payment_method.set_default',
        ]);
    }

    public function test_set_default_endpoint_responds_ok(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.payment-methods.set-default', [
                'tenantId' => 'tenant-123',
                'id' => 'pm_test_456',
            ]))
            ->assertOk();
    }

    public function test_add_payment_method_writes_audit_log(): void
    {
        $service = app(PaymentMethodAdminService::class);
        $service->add('tenant-xyz', ['type' => 'card', 'token' => 'tok_test'], $this->admin->id);

        $this->assertDatabaseHas('platform_audit_logs', [
            'event_type' => 'platform.payment_method.added',
        ]);
    }
}
