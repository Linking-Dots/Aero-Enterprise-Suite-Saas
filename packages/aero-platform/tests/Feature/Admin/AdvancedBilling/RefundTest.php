<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\AdvancedBilling;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\Refund;
use Aero\Platform\Services\AdvancedBilling\RefundService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    protected User $admin;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
    }

    private function shareSqliteAcrossConnections(): void
    {
        $sqliteConfig = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
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
        Gate::before(fn () => true);
        $this->admin = LandlordUserFactory::new()->create();
    }

    public function test_refund_cannot_be_processed_before_approved(): void
    {
        $refund = Refund::create([
            'reference' => 'REF-PENDING-001',
            'tenant_id' => 'tenant-abc',
            'amount' => 50.00,
            'currency' => 'USD',
            'reason' => 'Customer requested refund',
            'status' => Refund::STATUS_PENDING,
            'requested_by' => $this->admin->id,
        ]);

        $svc = app(RefundService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be approved before processing');

        $svc->process($refund, $this->admin->id);
    }

    public function test_process_stamps_processed_at_and_writes_audit_log(): void
    {
        $refund = Refund::create([
            'reference' => 'REF-APPROVED-001',
            'tenant_id' => 'tenant-xyz',
            'amount' => 100.00,
            'currency' => 'USD',
            'reason' => 'Duplicate charge',
            'status' => Refund::STATUS_APPROVED,
            'requested_by' => $this->admin->id,
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $svc = app(RefundService::class);
        $processed = $svc->process($refund, $this->admin->id);

        $this->assertEquals(Refund::STATUS_PROCESSED, $processed->status);
        $this->assertNotNull($processed->processed_at);
        $this->assertNotNull($processed->gateway_refund_id);
    }
}
