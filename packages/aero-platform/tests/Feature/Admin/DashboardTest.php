<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\PlatformMetricDaily;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseMigrations {
        runDatabaseMigrations as baseRunDatabaseMigrations;
    }

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();
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
        $this->shareSqliteAcrossConnections();
        Gate::before(fn () => true);
    }

    public function test_dashboard_returns_mrr_arr_correctly(): void
    {
        PlatformMetricDaily::create([
            'date' => today()->toDateString(),
            'mrr' => 12500,
            'arr' => 150000,
            'plan_mrr' => 10000,
            'product_mrr' => 2500,
            'plan_arr' => 120000,
            'product_arr' => 30000,
            'new_tenants' => 0,
            'churned_tenants' => 0,
            'active_tenants' => 10,
            'trial_tenants' => 2,
            'total_revenue' => 12500,
        ]);

        $user = LandlordUser::factory()->create();

        $this->actingAs($user, 'landlord')
            ->get(route('platform.admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Platform/Admin/Dashboard/Index')
                ->where('stats.mrr', 12500.0)
                ->where('stats.arr', 150000.0)
            );
    }

    public function test_dashboard_stats_endpoint_returns_json(): void
    {
        $user = LandlordUser::factory()->create();

        $this->actingAs($user, 'landlord')
            ->getJson(route('platform.admin.dashboard.stats'))
            ->assertOk()
            ->assertJsonStructure(['mrr', 'arr', 'active_tenants', 'churn_rate_pct']);
    }

    public function test_system_health_endpoint_returns_json(): void
    {
        $user = LandlordUser::factory()->create();

        $this->actingAs($user, 'landlord')
            ->getJson(route('platform.admin.dashboard.health'))
            ->assertOk()
            ->assertJsonStructure(['database', 'cache', 'queue', 'storage']);
    }
}
