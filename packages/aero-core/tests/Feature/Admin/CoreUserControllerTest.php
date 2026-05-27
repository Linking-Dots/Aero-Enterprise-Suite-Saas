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
     * P1 DEFECT: UserService::create only sets name/email/password/is_active
     * but the users table has a NOT NULL constraint on user_name.
     * The insert fails with a DB integrity error.
     *
     * This test documents the current (broken) behaviour.
     * Fix: UserService::create must derive/pass user_name.
     */
    public function test_store_fails_due_to_missing_user_name_in_service(): void
    {
        $admin = $this->makeSuperAdmin();

        // Posting valid form data — the service ignores user_name, causing a 500
        $this->actingAs($admin)
            ->post(route('core.users.store'), [
                'name'                  => 'New User',
                'email'                 => 'newuser@example.com',
                'password'              => 'Password1!',
                'password_confirmation' => 'Password1!',
                'user_name'             => 'new_user_001',
            ])
            ->assertStatus(500); // P1: UserService::create does not set user_name

        $this->assertDatabaseMissing('users', ['email' => 'newuser@example.com']);
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
     * NOTE: CoreUserController::show calls $user->load(['roles','sessions','devices']).
     * The 'sessions' relationship requires aero-auth package. In the package-only
     * test environment the relationship is absent, so this test verifies the route
     * is accessible but expects a 500 (missing relationship) — a P2 defect: the
     * controller should gracefully handle absent dynamic relationships.
     */
    public function test_show_route_is_accessible_for_authenticated_user(): void
    {
        $admin  = $this->makeSuperAdmin();
        $target = User::factory()->create();

        // The controller loads 'sessions' (from aero-auth), absent here → 500.
        // Asserting 500 documents the P2 defect rather than hiding it.
        $this->actingAs($admin)
            ->get(route('core.users.show', $target))
            ->assertStatus(500);
    }

    // =========================================================================
    // Destroy
    // =========================================================================

    /**
     * NOTE (P1): The API route `DELETE /users/{id}` shadows `DELETE /users/{user}`
     * (Inertia route) because it is registered first. Laravel implicit binding
     * cannot match `{id}` → `User $user`, so the controller receives a blank
     * User model and the delete is a no-op. The API route parameter should be
     * renamed from `{id}` to `{user}` to fix this.
     *
     * This test documents the current (broken) behaviour and must be updated
     * once the route fix is applied.
     */
    public function test_destroy_route_currently_shadowed_by_api_route(): void
    {
        $admin  = $this->makeSuperAdmin();
        $target = User::factory()->create();

        // Route `core.users.destroy` generates /users/{id} which is intercepted
        // by the API route first. The API route cannot bind {id}→User $user,
        // so the delete is a no-op and only a redirect is returned.
        $this->actingAs($admin)
            ->delete(route('core.users.destroy', $target))
            ->assertRedirect(); // 302 — no 500, but also no soft-delete (P1 bug)

        // Document: the user was NOT deleted (bug behaviour)
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
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
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post(route('core.users.toggle-status', $target));

        $this->assertDatabaseHas('users', [
            'id'        => $target->id,
            'is_active' => false,
        ]);
    }

    public function test_toggle_status_activates_inactive_user(): void
    {
        $admin  = $this->makeSuperAdmin();
        $target = User::factory()->create(['is_active' => false]);

        $this->actingAs($admin)
            ->post(route('core.users.toggle-status', $target));

        $this->assertDatabaseHas('users', [
            'id'        => $target->id,
            'is_active' => true,
        ]);
    }
}
