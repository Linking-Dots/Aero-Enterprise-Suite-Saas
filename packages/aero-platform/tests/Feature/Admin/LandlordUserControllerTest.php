<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature\Admin;
use Aero\Platform\Database\Factories\LandlordUserFactory;

use Aero\HRMAC\Models\Role;
use Aero\Auth\Models\User;
use Aero\Platform\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

/**
 * P-4 — LandlordUserController (Admin)
 *
 * Auth pattern: actingAs($admin, 'landlord') where $admin is a super-admin
 * landlord so the `hrmac:` route middleware authorises. Connection sharing,
 * platform context and DatabaseMigrations come from the package TestCase.
 */
class LandlordUserControllerTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->admin = $this->superAdminLandlord();
    }

    public function test_index_renders_users_list(): void
    {
        LandlordUserFactory::new()->count(3)->create();

        $this->actingAs($this->admin, 'landlord')
            ->get(route('platform.admin.users.index'))
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
            ->post(route('platform.admin.users.store'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'secret1234',
                'active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);
    }

    public function test_store_fails_with_duplicate_email(): void
    {
        LandlordUserFactory::new()->create(['email' => 'duplicate@example.com']);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.users.store'), [
                'name' => 'Another User',
                'email' => 'duplicate@example.com',
                'password' => 'secret1234',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_update_changes_user_data(): void
    {
        $user = LandlordUserFactory::new()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin, 'landlord')
            ->put(route('platform.admin.users.update', $user), [
                'name' => 'New Name',
                'email' => $user->email,
            ])
            ->assertRedirect();

        $this->assertEquals('New Name', $user->fresh()->name);
    }

    public function test_toggle_status_soft_deletes_user(): void
    {
        $user = LandlordUserFactory::new()->create();

        $this->actingAs($this->admin, 'landlord')
            ->patch(route('platform.admin.users.toggle-status', $user))
            ->assertRedirect();

        // Active/inactive is managed via SoftDeletes: deactivate = soft delete.
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_destroy_permanently_deletes_user(): void
    {
        $user = LandlordUserFactory::new()->create();

        $this->actingAs($this->admin, 'landlord')
            ->delete(route('platform.admin.users.destroy', $user))
            ->assertRedirect();

        // Admin "delete" is permanent (forceDelete) under the unified UserService.
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_store_assigns_roles_to_user(): void
    {
        $role = Role::create([
            'name' => 'Editor',
            'guard_name' => 'web',
        ]);

        $this->actingAs($this->admin, 'landlord')
            ->post(route('platform.admin.users.store'), [
                'name' => 'Role User',
                'email' => 'roleuser@example.com',
                'password' => 'secret1234',
                'role_ids' => [$role->id],
            ])
            ->assertRedirect();

        $user = User::where('email', 'roleuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->roles()->where('id', $role->id)->exists());
    }
}
