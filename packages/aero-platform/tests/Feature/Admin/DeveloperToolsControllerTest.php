<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-5 — DeveloperToolsController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class DeveloperToolsControllerTest extends TestCase
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

    public function test_dashboard_renders_inertia_component(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.developer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Admin/Developer/Dashboard'));
    }

    public function test_clear_cache_flushes_array_store(): void
    {
        config(['cache.stores.array' => ['driver' => 'array']]);
        Cache::store('array')->put('hello', 'world', 60);
        $this->assertSame('world', Cache::store('array')->get('hello'));

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.developer.cache.clear'), ['store' => 'array'])
            ->assertRedirect();

        $this->assertNull(Cache::store('array')->get('hello'));
    }

    public function test_unknown_cache_store_returns_422(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.developer.cache.clear'), ['store' => 'no-such-store'])
            ->assertStatus(422);
    }

    public function test_log_files_renders_inertia_component(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.developer.logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Admin/Developer/Logs'));
    }

    public function test_log_download_returns_file(): void
    {
        File::ensureDirectoryExists(storage_path('logs'));
        File::put(storage_path('logs/p5-test.log'), "line1\nline2\n");

        $response = $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.developer.logs.download', ['filename' => 'p5-test.log']));

        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));

        // Clean up
        File::delete(storage_path('logs/p5-test.log'));
    }

    public function test_log_tail_returns_json_lines(): void
    {
        File::ensureDirectoryExists(storage_path('logs'));
        File::put(storage_path('logs/p5-tail.log'), "line1\nline2\nline3\n");

        $response = $this->actingAs($this->admin, 'landlord')
            ->getJson(route('platform.admin.developer.logs.tail', ['filename' => 'p5-tail.log', 'lines' => 2]));

        $response->assertOk();
        $response->assertJsonStructure(['lines']);

        // Clean up
        File::delete(storage_path('logs/p5-tail.log'));
    }
}
