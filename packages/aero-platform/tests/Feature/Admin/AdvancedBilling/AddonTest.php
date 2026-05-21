<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin\AdvancedBilling;

use Aero\Platform\Models\LandlordUser;
use Aero\Platform\Models\PlatformAddon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AddonTest extends TestCase
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
        $this->admin = LandlordUser::factory()->create();
    }

    public function test_archive_sets_status_archived(): void
    {
        $addon = PlatformAddon::create([
            'code' => 'extra-storage',
            'name' => 'Extra Storage',
            'price' => 9.99,
            'billing_period' => 'monthly',
            'status' => PlatformAddon::STATUS_ACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson("/billing/addons/{$addon->id}/archive")
            ->assertOk();

        $this->assertDatabaseHas('platform_addons', [
            'id' => $addon->id,
            'status' => PlatformAddon::STATUS_ARCHIVED,
        ]);
    }

    public function test_price_and_period_are_validated(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson('/billing/addons', [
                'code' => 'bad-addon',
                'name' => 'Bad Addon',
                'price' => -5,
                'billing_period' => 'invalid_period',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['price', 'billing_period']);
    }
}
