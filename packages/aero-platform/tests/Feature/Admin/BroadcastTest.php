<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\TenantBroadcast;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-7 — BroadcastTest
 *
 * Verifies:
 * - Publishing a targeted broadcast scopes correctly to target_tenant_ids.
 * - Dismiss-all soft-deletes the broadcast.
 */
class BroadcastTest extends TestCase
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

    public function test_targeted_broadcast_stores_correct_tenant_ids(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenant-comms.broadcasts.store'), [
                'title' => 'Maintenance Notice',
                'body' => '<p>System update tonight.</p>',
                'target' => 'specific',
                'target_tenant_ids' => ['tenant-a', 'tenant-b'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Broadcast created.');

        $id = $response->json('data.id');
        $broadcast = TenantBroadcast::find($id);

        $this->assertNotNull($broadcast);
        $this->assertEquals('specific', $broadcast->target);
        $this->assertContains('tenant-a', $broadcast->target_tenant_ids);
        $this->assertContains('tenant-b', $broadcast->target_tenant_ids);
        $this->assertNotContains('tenant-c', $broadcast->target_tenant_ids);
    }

    public function test_publish_sets_published_at(): void
    {
        $broadcast = TenantBroadcast::create([
            'title' => 'Unpublished',
            'body' => '<p>Hello.</p>',
            'target' => 'all',
            'created_by' => $this->admin->id,
        ]);

        $this->assertNull($broadcast->published_at);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenant-comms.broadcasts.publish', $broadcast->id))
            ->assertOk()
            ->assertJsonPath('message', 'Broadcast published.');

        $this->assertNotNull($broadcast->fresh()->published_at);
    }

    public function test_dismiss_all_soft_deletes_broadcast(): void
    {
        $broadcast = TenantBroadcast::create([
            'title' => 'To Dismiss',
            'body' => '<p>Bye.</p>',
            'target' => 'all',
            'published_at' => now(),
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.tenant-comms.broadcasts.dismiss-all', $broadcast->id))
            ->assertOk()
            ->assertJsonPath('message', 'Broadcast dismissed for all tenants.');

        $this->assertSoftDeleted('tenant_broadcasts', ['id' => $broadcast->id]);
    }
}
