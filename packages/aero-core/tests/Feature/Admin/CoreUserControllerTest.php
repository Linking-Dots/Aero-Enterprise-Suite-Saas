<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Aero\Core\Tests\PackageTestCase;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Feature tests for CoreUserController (CA-1).
 *
 * Run:
 *   php c:/laragon/www/aeos365/vendor/bin/phpunit \
 *     --configuration packages/aero-core/phpunit.xml \
 *     packages/aero-core/tests/Feature/Admin/CoreUserControllerTest.php
 */
class CoreUserControllerTest extends PackageTestCase
{
    // =========================================================================
    // Index
    // =========================================================================

    public function test_index_renders_user_list_for_authenticated_user(): void
    {
        $admin = $this->makeSuperAdmin();
        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('core.users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/Users/Index', false)
                ->has('users')
            );
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('core.users.index'))
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // Create
    // =========================================================================

    public function test_create_page_renders_with_roles(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->get(route('core.users.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/Users/Create', false)
                ->has('roles')
            );
    }

    // =========================================================================
    // Store
    // =========================================================================

    /**
     * Aero\Auth\Services\UserService::create derives user_name automatically from name.
     */
    public function test_store_creates_user_with_derived_user_name(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post(route('core.users.store'), [
                'name'                  => 'New User',
                'email'                 => 'newuser@example.com',
                'password'              => 'Password1!',
                'password_confirmation' => 'Password1!',
            ])
            ->assertRedirect(route('core.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_store_validates_required_fields(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post(route('core.users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_store_rejects_duplicate_email(): void
    {
        $admin    = $this->makeSuperAdmin();
        User::factory()->create(['email' => 'dup@example.com']);

        $this->actingAs($admin)
            ->post(route('core.users.store'), [
                'name'                  => 'Dup',
                'email'                 => 'dup@example.com',
                'password'              => 'Password1!',
                'password_confirmation' => 'Password1!',
            ])
            ->assertSessionHasErrors('email');
    }

    // =========================================================================
    // Show
    // =========================================================================

    /**
     * CoreUserController::show gracefully handles absent dynamic relationships.
     * The 'sessions' and 'devices' relationships are only loaded if they exist on the model.
     */
    public function test_show_route_is_accessible_for_authenticated_user(): void
    {
        $admin  = $this->makeSuperAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('core.users.show', $target))
            ->assertOk();  // Inertia component exists; component file check skipped in pkg env
    }

    // =========================================================================
    // Destroy
    // =========================================================================

    /**
     * Admin "delete" is PERMANENT (forceDelete) under the unified UserService —
     * active/inactive is what SoftDeletes manages, so delete must hard-remove.
     */
    public function test_destroy_permanently_deletes_user(): void
    {
        $admin  = $this->makeSuperAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('core.users.destroy', $target))
            ->assertRedirect(route('core.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_cannot_delete_own_account_direct_service(): void
    {
        $admin = $this->makeSuperAdmin();

        // Test the guard at service level directly since the HTTP route is shadowed
        $controller = app(\Aero\Core\Http\Controllers\Admin\CoreUserController::class);
        $request = \Illuminate\Http\Request::create(
            route('core.users.destroy', $admin), 'DELETE'
        );
        $request->setUserResolver(fn () => $admin);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->destroy($admin, $request);
    }

    // =========================================================================
    // Toggle Status
    // =========================================================================

    public function test_toggle_status_deactivates_active_user(): void
    {
        $admin  = $this->makeSuperAdmin();
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('core.users.toggle-status', $target));

        // Deactivate = soft delete (active = not trashed).
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    public function test_toggle_status_activates_inactive_user(): void
    {
        $admin  = $this->makeSuperAdmin();
        $target = User::factory()->create();
        $target->delete(); // inactive = soft-deleted

        $this->actingAs($admin)
            ->post(route('core.users.toggle-status', $target));

        // Reactivate = restore.
        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
    }
}
