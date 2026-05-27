<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Tests\PackageTestCase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

/**
 * Feature tests for RoleController (CA-1).
 *
 * Note: RoleService internally uses Spatie\Permission\Models\Role.
 *
 * Run:
 *   php c:/laragon/www/aeos365/vendor/bin/phpunit \
 *     --configuration packages/aero-core/phpunit.xml \
 *     packages/aero-core/tests/Feature/Admin/RoleControllerTest.php
 */
class RoleControllerTest extends PackageTestCase
{
    // =========================================================================
    // Index
    // =========================================================================

    public function test_index_lists_roles(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->get(route('core.roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/Roles/Index', false)
                ->has('roles')
            );
    }

    public function test_index_redirects_unauthenticated_users(): void
    {
        $this->get(route('core.roles.index'))
            ->assertRedirect(route('login'));
    }

    // =========================================================================
    // Store
    // =========================================================================

    public function test_store_creates_new_role(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post(route('core.roles.store'), ['name' => 'Manager'])
            ->assertRedirect(route('core.roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'Manager']);
    }

    public function test_store_validates_required_name(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->actingAs($admin)
            ->post(route('core.roles.store'), [])
            ->assertSessionHasErrors('name');
    }

    public function test_store_rejects_duplicate_role_name(): void
    {
        $admin = $this->makeSuperAdmin();

        // 'super-admin' already exists from makeSuperAdmin()
        $this->actingAs($admin)
            ->post(route('core.roles.store'), ['name' => 'super-admin'])
            ->assertSessionHasErrors('name');
    }

    // =========================================================================
    // Update
    // =========================================================================

    /**
     * P1 DEFECT: The API route group (registered first) at PUT /roles/{id}
     * calls RoleController::updateRole, which does not exist.
     * The Inertia route at PUT /roles/{role} calls RoleController::update (correct).
     * Because the API route shadows the Inertia route, all PUT /roles/{id} requests
     * hit the non-existent method and return 500.
     *
     * This test documents the current broken behaviour.
     * Fix: Rename API route methods (updateRole→update, deleteRole→destroy, storeRole→store)
     * or separate API and Inertia routes by prefix.
     */
    public function test_update_route_shadowed_by_api_calling_nonexistent_method(): void
    {
        $admin = $this->makeSuperAdmin();
        $role  = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->put(route('core.roles.update', $role), ['name' => 'Senior Editor'])
            ->assertStatus(500); // P1: API route calls RoleController::updateRole (non-existent)
    }

    /**
     * Verify the guard at controller level via direct invocation — bypassing
     * the shadowed API route.
     */
    public function test_cannot_update_super_admin_role_direct(): void
    {
        $admin          = $this->makeSuperAdmin();
        $superAdminRole = Role::where('name', 'super-admin')->first();

        $controller = app(\Aero\Core\Http\Controllers\Admin\RoleController::class);
        $request    = new \Aero\Core\Http\Requests\UpdateRoleRequest();
        $request->merge(['name' => 'Hacked']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->update($request, $superAdminRole);
    }

    // =========================================================================
    // Destroy
    // =========================================================================

    /**
     * P1 DEFECT: Same API route shadowing issue — DELETE /roles/{id} hits
     * RoleController::deleteRole (non-existent) → 500.
     */
    public function test_destroy_route_shadowed_by_api_calling_nonexistent_method(): void
    {
        $admin = $this->makeSuperAdmin();
        $role  = Role::create(['name' => 'Temp', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->delete(route('core.roles.destroy', $role))
            ->assertStatus(500); // P1: API route calls RoleController::deleteRole (non-existent)
    }

    /**
     * Verify the guard at controller level via direct invocation.
     */
    public function test_cannot_delete_super_admin_role_direct(): void
    {
        $admin          = $this->makeSuperAdmin();
        $superAdminRole = Role::where('name', 'super-admin')->first();
        $request        = \Illuminate\Http\Request::create('/roles/'.$superAdminRole->id, 'DELETE');
        $request->setUserResolver(fn () => $admin);

        $controller = app(\Aero\Core\Http\Controllers\Admin\RoleController::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->destroy($superAdminRole, $request);
    }
}
