<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;

use Aero\HRMAC\Models\Role;
use Aero\Platform\Models\LandlordUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * P-4 — LandlordUserController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord').
 * Gate::before(fn () => true) bypasses HRMAC middleware for all tests.
 * Uses DatabaseMigrations + shareSqliteAcrossConnections() pattern.
 */
class LandlordUserControllerTest extends TestCase
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

    public function test_index_renders_users_list(): void
    {
        LandlordUser::factory()->count(3)->create();

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.p4users.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Platform/Admin/Users/Index')
                    ->has('users')
                    ->has('roles')
                    ->has('filters')
            );
    }

    public function test_store_creates_new_landlord_user(): void
    {
        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.p4users.store'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'secret1234',
                'active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('landlord_users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);
    }

    public function test_store_fails_with_duplicate_email(): void
    {
        LandlordUser::factory()->create(['email' => 'duplicate@example.com']);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.p4users.store'), [
                'name' => 'Another User',
                'email' => 'duplicate@example.com',
                'password' => 'secret1234',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_update_changes_user_data(): void
    {
        $user = LandlordUser::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.p4users.update', $user), [
                'name' => 'New Name',
                'email' => $user->email,
            ])
            ->assertRedirect();

        $this->assertEquals('New Name', $user->fresh()->name);
    }

    public function test_toggle_status_flips_active_flag(): void
    {
        $user = LandlordUser::factory()->create(['active' => true]);

        $this->actingAs($this->admin, 'landlord')
            ->patch(route('platform.admin.p4users.toggle-status', $user))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->active);
    }

    public function test_destroy_deletes_user(): void
    {
        $user = LandlordUser::factory()->create();

        $this->actingAs($this->admin, 'landlord')
            ->delete(route('platform.admin.p4users.destroy', $user))
            ->assertRedirect();

        $this->assertSoftDeleted('landlord_users', ['id' => $user->id]);
    }

    public function test_store_assigns_roles_to_user(): void
    {
        $role = Role::create([
            'name' => 'Editor',
            'guard_name' => 'web',
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.p4users.store'), [
                'name' => 'Role User',
                'email' => 'roleuser@example.com',
                'password' => 'secret1234',
                'role_ids' => [$role->id],
            ])
            ->assertRedirect();

        $user = LandlordUser::where('email', 'roleuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->roles()->where('id', $role->id)->exists());
    }
}
