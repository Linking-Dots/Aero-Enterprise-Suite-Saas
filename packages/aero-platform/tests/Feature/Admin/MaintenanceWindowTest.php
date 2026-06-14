<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\MaintenanceWindow;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-7 — MaintenanceWindowTest
 *
 * Verifies:
 * - Cancel transitions status to 'cancelled' and stamps cancelled_at.
 */
class MaintenanceWindowTest extends TestCase
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
        $cfg = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
        config([
            'database.connections.mysql' => $cfg,
            'database.connections.central' => $cfg,
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

    public function test_cancel_transitions_to_cancelled_and_stamps_cancelled_at(): void
    {
        $window = MaintenanceWindow::create([
            'title' => 'DB Migration',
            'message' => 'Brief downtime for database migration.',
            'starts_at' => now()->addHours(2),
            'ends_at' => now()->addHours(3),
            'status' => MaintenanceWindow::STATUS_SCHEDULED,
            'affected_tenants' => 'all',
        ]);

        $this->assertEquals(MaintenanceWindow::STATUS_SCHEDULED, $window->status);
        $this->assertNull($window->cancelled_at);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenant-comms.maintenance-windows.cancel', $window->id))
            ->assertOk()
            ->assertJsonPath('message', 'Maintenance window cancelled.');

        $window->refresh();
        $this->assertEquals(MaintenanceWindow::STATUS_CANCELLED, $window->status);
        $this->assertNotNull($window->cancelled_at);
    }

    public function test_store_creates_scheduled_window(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenant-comms.maintenance-windows.store'), [
                'title' => 'Planned Upgrade',
                'message' => 'System will be unavailable for 30 minutes.',
                'starts_at' => now()->addDay()->toIso8601String(),
                'ends_at' => now()->addDay()->addMinutes(30)->toIso8601String(),
                'affected_tenants' => 'all',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Maintenance window scheduled.')
            ->assertJsonPath('data.status', MaintenanceWindow::STATUS_SCHEDULED);

        $this->assertDatabaseHas('maintenance_windows', ['title' => 'Planned Upgrade']);
    }

    public function test_index_renders_inertia(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.tenant-comms.maintenance-windows.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Admin/Communications/Maintenance'));
    }
}
