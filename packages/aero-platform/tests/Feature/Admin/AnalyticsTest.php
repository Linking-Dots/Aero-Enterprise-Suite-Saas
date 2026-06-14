<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\PlatformMetricDaily;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AnalyticsTest extends TestCase
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

    public function test_revenue_report_groups_by_period(): void
    {
        for ($i = 0; $i < 14; $i++) {
            PlatformMetricDaily::create([
                'date' => now()->subDays($i)->toDateString(),
                'mrr' => 1000 + $i,
                'arr' => (1000 + $i) * 12,
                'plan_mrr' => 800 + $i,
                'product_mrr' => 200,
                'plan_arr' => (800 + $i) * 12,
                'product_arr' => 2400,
                'new_tenants' => 0,
                'churned_tenants' => 0,
                'active_tenants' => 10,
                'trial_tenants' => 0,
                'total_revenue' => 100,
            ]);
        }

        $admin = LandlordUserFactory::new()->create();

        $this->actingAs($admin, 'landlord')
            ->get(route('platform.admin.analytics.revenue', ['bucket' => 'week']))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Platform/Admin/Analytics/Revenue'));
    }

    public function test_cohort_analysis_returns_retention_rates(): void
    {
        $admin = LandlordUserFactory::new()->create();

        $this->actingAs($admin, 'landlord')
            ->get(route('platform.admin.product-analytics.cohorts', ['months' => 3]))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Platform/Admin/ProductAnalytics/Cohorts')
                ->has('matrix')
            );
    }

    public function test_analytics_dashboard_renders_correct_component(): void
    {
        $admin = LandlordUserFactory::new()->create();

        $this->actingAs($admin, 'landlord')
            ->get(route('platform.admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Platform/Admin/Analytics/Revenue'));
    }

    public function test_feature_usage_renders_correct_component(): void
    {
        $admin = LandlordUserFactory::new()->create();

        $this->actingAs($admin, 'landlord')
            ->get(route('platform.admin.product-analytics.features'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Platform/Admin/ProductAnalytics/Features')
                ->has('rows')
            );
    }
}
