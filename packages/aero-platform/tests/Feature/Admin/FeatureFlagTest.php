<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\Platform\Models\FeatureFlag;
use Aero\Platform\Models\FeatureFlagTenantOverride;
use Aero\Auth\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-7 — FeatureFlagTest
 *
 * Verifies:
 * - When a tenant override exists, it takes precedence over the global flag state.
 * - CRUD operations work correctly.
 */
class FeatureFlagTest extends TestCase
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

    public function test_tenant_override_takes_precedence_over_global_flag(): void
    {
        // Global flag is disabled
        $flag = FeatureFlag::create([
            'code' => 'new_dashboard',
            'name' => 'New Dashboard',
            'is_active' => false,
            'rollout_pct' => 0,
            'is_archived' => false,
        ]);

        // Tenant-specific override enables it for tenant-001
        $override = FeatureFlagTenantOverride::create([
            'flag_id' => $flag->id,
            'tenant_id' => 'tenant-001',
            'is_active' => true,
        ]);

        // The override row exists and says active = true
        $resolvedOverride = FeatureFlagTenantOverride::where('flag_id', $flag->id)
            ->where('tenant_id', 'tenant-001')
            ->first();

        $this->assertNotNull($resolvedOverride);
        $this->assertTrue($resolvedOverride->is_active);

        // Global flag is still false — override wins
        $this->assertFalse($flag->fresh()->is_active);
        $this->assertTrue($resolvedOverride->is_active);
    }

    public function test_store_creates_flag(): void
    {
        $response = $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.feature-flags.store'), [
                'code' => 'beta_feature',
                'name' => 'Beta Feature',
                'is_active' => false,
                'rollout_pct' => 10,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Feature flag created.');

        $this->assertDatabaseHas('feature_flags', ['code' => 'beta_feature']);
    }

    public function test_toggle_flips_is_active(): void
    {
        $flag = FeatureFlag::create([
            'code' => 'toggleable',
            'name' => 'Toggleable',
            'is_active' => false,
            'rollout_pct' => 0,
            'is_archived' => false,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.feature-flags.toggle', $flag->id))
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue($flag->fresh()->is_active);
    }

    public function test_archive_sets_is_archived_and_disables(): void
    {
        $flag = FeatureFlag::create([
            'code' => 'to_archive',
            'name' => 'To Archive',
            'is_active' => true,
            'rollout_pct' => 50,
            'is_archived' => false,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.feature-flags.archive', $flag->id))
            ->assertOk()
            ->assertJsonPath('data.is_archived', true);

        $flag->refresh();
        $this->assertTrue($flag->is_archived);
        $this->assertFalse($flag->is_active);
    }

    public function test_set_override_via_api(): void
    {
        $flag = FeatureFlag::create([
            'code' => 'override_test',
            'name' => 'Override Test',
            'is_active' => false,
            'rollout_pct' => 0,
            'is_archived' => false,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->postJson(route('platform.admin.feature-flags.overrides.store', $flag->id), [
                'tenant_id' => 'tenant-xyz',
                'is_active' => true,
            ])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Override set.');

        $this->assertDatabaseHas('feature_flag_tenant_overrides', [
            'flag_id' => $flag->id,
            'tenant_id' => 'tenant-xyz',
            'is_active' => 1,
        ]);
    }
}
