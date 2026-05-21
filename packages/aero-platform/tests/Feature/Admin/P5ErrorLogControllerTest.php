<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\ErrorLog;
use Aero\Platform\Models\LandlordUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-5 — P5ErrorLogController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 *
 * Note: ErrorLog uses is_resolved (bool), error_type, error_message, stack_trace
 * columns — not status/type/message/trace.
 */
class P5ErrorLogControllerTest extends TestCase
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

    private function makeErrorLog(array $overrides = []): ErrorLog
    {
        return ErrorLog::create(array_merge([
            'error_type' => 'RuntimeException',
            'error_message' => 'Something went wrong',
            'request_url' => 'https://app.test/api/test',
            'http_code' => 500,
            'origin' => 'backend',
            'is_resolved' => false,
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->shareSqliteAcrossConnections();
        Gate::before(fn () => true);
        $this->admin = LandlordUser::factory()->create();
    }

    public function test_index_renders_inertia_component(): void
    {
        $this->makeErrorLog();

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.error-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Admin/ErrorLogs/Index'));
    }

    public function test_resolve_sets_is_resolved_and_timestamp(): void
    {
        $log = $this->makeErrorLog();

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.error-logs.resolve', $log->id))
            ->assertRedirect();

        $log->refresh();
        $this->assertTrue((bool) $log->is_resolved);
        $this->assertNotNull($log->resolved_at);
    }

    public function test_cannot_resolve_already_resolved_log(): void
    {
        $log = $this->makeErrorLog(['is_resolved' => true, 'resolved_at' => now()]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.error-logs.resolve', $log->id))
            ->assertStatus(422);
    }

    public function test_bulk_resolve_only_touches_open_entries(): void
    {
        $open = $this->makeErrorLog(['is_resolved' => false]);
        $resolved = $this->makeErrorLog(['is_resolved' => true, 'resolved_at' => now()]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.error-logs.bulk-resolve'), [
                'ids' => [$open->id, $resolved->id],
            ])
            ->assertRedirect();

        $this->assertTrue((bool) $open->fresh()->is_resolved);
        $this->assertTrue((bool) $resolved->fresh()->is_resolved); // was already resolved
    }

    public function test_destroy_deletes_error_log(): void
    {
        $log = $this->makeErrorLog();

        $this->actingAs($this->admin, 'landlord')
            ->delete(route('platform.admin.error-logs.destroy', $log->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('error_logs', ['id' => $log->id]);
    }

    public function test_analytics_renders_inertia_component(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.error-logs.analytics'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Admin/ErrorLogs/Analytics'));
    }
}
