<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class QuotaTest extends TestCase
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

    public function test_can_set_quota_override(): void
    {
        $admin = LandlordUserFactory::new()->create();
        $tenant = Tenant::factory()->create();

        $this->actingAs($admin, 'landlord')
            ->post(route('platform.admin.quotas.override', $tenant->id), [
                'resource' => 'storage_gb',
                'limit_value' => 200,
                'reason' => 'Customer requested upgrade',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenant_quota_overrides', [
            'tenant_id' => $tenant->id,
            'resource' => 'storage_gb',
            'limit_value' => 200,
        ]);
    }

    public function test_cannot_set_override_below_current_usage(): void
    {
        $admin = LandlordUserFactory::new()->create();
        $tenant = Tenant::factory()->create();

        \DB::connection('central')->table('tenant_stats')->insert([
            'tenant_id' => $tenant->id,
            'date' => today(),
            'storage_mb' => 500,
            'api_requests' => 0,
            'active_users' => 0,
        ]);

        $this->actingAs($admin, 'landlord')
            ->post(route('platform.admin.quotas.override', $tenant->id), [
                'resource' => 'storage_gb',
                'limit_value' => 100,
            ])
            ->assertStatus(422);
    }

    public function test_quota_index_renders_correct_component(): void
    {
        $admin = LandlordUserFactory::new()->create();

        $this->actingAs($admin, 'landlord')
            ->get(route('platform.admin.quotas.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Platform/Admin/Quotas/Index')
                ->has('overrides')
                ->has('analytics')
            );
    }

    public function test_quota_settings_renders_correct_component(): void
    {
        $admin = LandlordUserFactory::new()->create();

        $this->actingAs($admin, 'landlord')
            ->get(route('platform.admin.quotas.settings'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->component('Platform/Admin/Quotas/Settings')
                ->has('settings')
            );
    }
}
