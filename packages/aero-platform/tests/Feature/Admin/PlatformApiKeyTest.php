<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Auth\Models\User;
use Aero\Platform\Models\PlatformApiKey;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * P-7 — PlatformApiKeyTest
 *
 * Verifies:
 * - Key is stored as bcrypt hash (raw value not retrievable after creation).
 * - Raw plaintext key is returned only in the create response.
 * - Revoke stamps revoked_at.
 */
class PlatformApiKeyTest extends TestCase
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

    public function test_store_returns_raw_key_once(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.integrations.api-keys.store'), [
                'name' => 'Test Key',
                'scopes' => ['read:tenants'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'API key created. Store the key now — it will not be shown again.')
            ->assertJsonStructure(['key', 'data']);

        $rawKey = $response->json('key');
        $this->assertStringStartsWith('sk_', $rawKey);
    }

    public function test_key_hash_stored_not_raw_key(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.integrations.api-keys.store'), [
                'name' => 'Hashed Key Test',
                'scopes' => ['read:tenants'],
            ]);

        $response->assertStatus(201);

        $id = $response->json('data.id');
        $rawKey = $response->json('key');

        $record = PlatformApiKey::find($id);
        $this->assertNotNull($record);
        $this->assertNotEquals($rawKey, $record->key_hash);
        $this->assertTrue(Hash::check($rawKey, $record->key_hash));
    }

    public function test_revoke_stamps_revoked_at(): void
    {
        $key = PlatformApiKey::create([
            'name' => 'To Revoke',
            'key_prefix' => 'sk_test1',
            'key_hash' => bcrypt('somekey'),
            'key' => 'sk_test1_placeholder',
            'scopes' => ['read:all'],
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.integrations.api-keys.revoke', $key->id))
            ->assertOk()
            ->assertJsonPath('message', 'API key revoked.');

        $key->refresh();
        $this->assertNotNull($key->revoked_at);
        $this->assertFalse($key->is_active);
    }

    public function test_index_renders_inertia(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.integrations.api-keys.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Platform/Admin/Integrations/ApiKeys'));
    }
}
