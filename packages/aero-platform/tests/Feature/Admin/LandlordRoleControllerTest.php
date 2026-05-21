<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\LandlordRole;
use Aero\Platform\Models\LandlordUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-4 — LandlordRoleController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class LandlordRoleControllerTest extends TestCase
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

        $this->admin = LandlordUser::factory()->create();
    }

    public function test_index_renders_roles_list(): void
    {
        LandlordRole::create(['name' => 'Billing Manager', 'permissions' => [], 'is_system' => false]);

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.p4roles.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Platform/Admin/Roles/Index')
                    ->has('roles')
                    ->has('availablePermissions')
            );
    }

    public function test_store_creates_role(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.p4roles.store'), [
                'name' => 'Billing Manager',
                'description' => 'Manages billing',
                'permissions' => ['billing-management.billing-dashboard.view'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('landlord_roles', ['name' => 'Billing Manager']);
    }

    public function test_store_fails_with_duplicate_name(): void
    {
        LandlordRole::create(['name' => 'Duplicate Role', 'permissions' => [], 'is_system' => false]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.p4roles.store'), [
                'name' => 'Duplicate Role',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_cannot_delete_system_role(): void
    {
        $role = LandlordRole::create([
            'name' => LandlordRole::SYSTEM_SUPER_ADMIN,
            'permissions' => [],
            'is_system' => true,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->delete(route('platform.admin.p4roles.destroy', $role))
            ->assertStatus(422);

        $this->assertDatabaseHas('landlord_roles', ['id' => $role->id]);
    }

    public function test_non_system_role_can_be_deleted(): void
    {
        $role = LandlordRole::create([
            'name' => 'Deletable Role',
            'permissions' => [],
            'is_system' => false,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->delete(route('platform.admin.p4roles.destroy', $role))
            ->assertRedirect();

        $this->assertDatabaseMissing('landlord_roles', ['id' => $role->id]);
    }

    public function test_clone_produces_identical_permissions(): void
    {
        $permissions = [
            'system-settings.general-settings.view',
            'platform-users.landlord-user-list.view',
        ];

        $role = LandlordRole::create([
            'name' => 'Original',
            'permissions' => $permissions,
            'is_system' => false,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.p4roles.clone', $role), ['name' => 'Original (copy)'])
            ->assertRedirect();

        $clone = LandlordRole::where('name', 'Original (copy)')->first();
        $this->assertNotNull($clone);
        $this->assertSame($role->permissions, $clone->permissions);
        $this->assertFalse($clone->is_system);
    }

    public function test_update_permissions_persists(): void
    {
        $role = LandlordRole::create([
            'name' => 'Ops Team',
            'permissions' => [],
            'is_system' => false,
        ]);

        $newPermissions = [
            'module-management.module-list.view',
            'module-management.module-pricing.edit',
        ];

        $this->actingAs($this->admin, 'landlord')
            ->patch(route('platform.admin.p4roles.permissions', $role), [
                'permissions' => $newPermissions,
            ])
            ->assertRedirect();

        $this->assertEquals($newPermissions, $role->fresh()->permissions);
    }

    public function test_update_role_name_and_description(): void
    {
        $role = LandlordRole::create([
            'name' => 'Old Name',
            'description' => 'Old Desc',
            'permissions' => [],
            'is_system' => false,
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.p4roles.update', $role), [
                'name' => 'New Name',
                'description' => 'New Desc',
                'permissions' => [],
            ])
            ->assertRedirect();

        $this->assertEquals('New Name', $role->fresh()->name);
        $this->assertEquals('New Desc', $role->fresh()->description);
    }
}
