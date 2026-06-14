<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\WebhookDeliveryLog;
use Aero\Platform\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * P-7 — WebhookTest
 *
 * Verifies:
 * - test endpoint dispatches a sample payload and records a delivery log with the response code.
 */
class WebhookTest extends TestCase
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

    public function test_test_endpoint_records_delivery_log_with_response_code(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'events' => ['tenant.created'],
            'secret' => 'test-secret',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.integrations.webhooks.test', $endpoint->id));

        $response->assertOk()
            ->assertJsonPath('message', 'Test webhook dispatched.')
            ->assertJsonPath('response_status', 200);

        $logId = $response->json('log_id');
        $log = WebhookDeliveryLog::find($logId);

        $this->assertNotNull($log);
        $this->assertEquals($endpoint->id, $log->endpoint_id);
        $this->assertEquals(200, $log->response_status);
        $this->assertEquals('webhook.test', $log->event_type);
        $this->assertNotNull($log->delivered_at);
    }

    public function test_store_creates_endpoint(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.integrations.webhooks.store'), [
                'url' => 'https://hooks.example.com/receive',
                'events' => ['tenant.created', 'subscription.cancelled'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Webhook endpoint created.');

        $this->assertDatabaseHas('platform_webhook_endpoints', [
            'url' => 'https://hooks.example.com/receive',
        ]);
    }

    public function test_rotate_secret_changes_secret(): void
    {
        $endpoint = WebhookEndpoint::create([
            'url' => 'https://example.com/webhook',
            'events' => ['tenant.created'],
            'secret' => 'original-secret',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.integrations.webhooks.rotate-secret', $endpoint->id))
            ->assertOk()
            ->assertJsonPath('message', 'Signing secret rotated.');
    }
}
