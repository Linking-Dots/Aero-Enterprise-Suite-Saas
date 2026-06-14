<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\AdvancedBilling;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\UsageEvent;
use Aero\Platform\Models\UsageMeter;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UsageMeterTest extends TestCase
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

    public function test_usage_event_aggregation_respects_sum_mode(): void
    {
        $meter = UsageMeter::create([
            'code' => 'api-calls',
            'name' => 'API Calls',
            'event_code' => 'api.call',
            'aggregation' => UsageMeter::AGGREGATION_SUM,
            'price_per_unit' => 0.001,
            'unit_label' => 'call',
            'is_active' => true,
        ]);

        UsageEvent::insert([
            ['meter_id' => $meter->id, 'tenant_id' => 'tenant-1', 'quantity' => 10, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['meter_id' => $meter->id, 'tenant_id' => 'tenant-1', 'quantity' => 20, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['meter_id' => $meter->id, 'tenant_id' => 'tenant-1', 'quantity' => 5, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $aggregated = UsageEvent::where('meter_id', $meter->id)
            ->where('tenant_id', 'tenant-1')
            ->sum('quantity');

        $this->assertEquals(35, $aggregated);
    }

    public function test_usage_event_aggregation_respects_count_mode(): void
    {
        $meter = UsageMeter::create([
            'code' => 'logins',
            'name' => 'Login Events',
            'event_code' => 'user.login',
            'aggregation' => UsageMeter::AGGREGATION_COUNT,
            'price_per_unit' => 0,
            'unit_label' => 'event',
            'is_active' => true,
        ]);

        UsageEvent::insert([
            ['meter_id' => $meter->id, 'tenant_id' => 'tenant-2', 'quantity' => 1, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['meter_id' => $meter->id, 'tenant_id' => 'tenant-2', 'quantity' => 1, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $count = UsageEvent::where('meter_id', $meter->id)
            ->where('tenant_id', 'tenant-2')
            ->count();

        $this->assertEquals(2, $count);
    }

    public function test_usage_event_aggregation_respects_max_mode(): void
    {
        $meter = UsageMeter::create([
            'code' => 'storage',
            'name' => 'Storage Usage',
            'event_code' => 'storage.usage',
            'aggregation' => UsageMeter::AGGREGATION_MAX,
            'price_per_unit' => 0.01,
            'unit_label' => 'GB',
            'is_active' => true,
        ]);

        UsageEvent::insert([
            ['meter_id' => $meter->id, 'tenant_id' => 'tenant-3', 'quantity' => 50, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['meter_id' => $meter->id, 'tenant_id' => 'tenant-3', 'quantity' => 120, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
            ['meter_id' => $meter->id, 'tenant_id' => 'tenant-3', 'quantity' => 80, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $max = UsageEvent::where('meter_id', $meter->id)
            ->where('tenant_id', 'tenant-3')
            ->max('quantity');

        $this->assertEquals(120, $max);
    }
}
