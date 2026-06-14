<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\PlatformAccessLog;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-5 — AccessLogController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class AccessLogControllerTest extends TestCase
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

    public function test_index_renders_inertia_component(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.access-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Admin/AccessLogs/Index')
                ->where('pii', false)
            );
    }

    public function test_pii_tab_returns_only_pii_field_accesses(): void
    {
        PlatformAccessLog::create([
            'actor_id' => 1,
            'actor_name' => 'Admin',
            'actor_ip' => '127.0.0.1',
            'resource_type' => 'Employee',
            'resource_id' => 1,
            'subject_label' => 'John Doe',
            'fields_accessed' => json_encode(['national_id']),
            'created_at' => now(),
        ]);
        PlatformAccessLog::create([
            'actor_id' => 1,
            'actor_name' => 'Admin',
            'actor_ip' => '127.0.0.1',
            'resource_type' => 'Employee',
            'resource_id' => 2,
            'subject_label' => 'Jane Doe',
            'fields_accessed' => json_encode(['display_name']),
            'created_at' => now(),
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.access-logs.pii'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('pii', true)
            );
    }

    public function test_export_returns_csv(): void
    {
        PlatformAccessLog::create([
            'actor_id' => 1,
            'actor_name' => 'Admin',
            'actor_ip' => '127.0.0.1',
            'resource_type' => 'Employee',
            'resource_id' => 1,
            'subject_label' => 'John Doe',
            'fields_accessed' => json_encode(['national_id']),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.access-logs.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
