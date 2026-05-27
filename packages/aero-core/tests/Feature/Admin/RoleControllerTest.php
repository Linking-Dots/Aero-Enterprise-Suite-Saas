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
     * Route collision fixed: API routes now use /api/roles/* prefix.
     * Inertia PUT /roles/{role} correctly calls RoleController::update.
     */
    public function test_update_role_succeeds(): void
    {
        $admin = $this->makeSuperAdmin();
        $role  = Role::create(['name' => 'Editor', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->put(route('core.roles.update', $role), ['name' => 'Senior Editor'])
            ->assertRedirect(route('core.roles.index'));

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Senior Editor']);
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
     * Route collision fixed: API routes use /api/roles/* prefix.
     * DELETE /roles/{role} correctly calls RoleController::destroy.
     */
    public function test_destroy_role_succeeds(): void
    {
        $admin = $this->makeSuperAdmin();
        $role  = Role::create(['name' => 'Temp', 'guard_name' => 'web']);

        $this->actingAs($admin)
            ->delete(route('core.roles.destroy', $role))
            ->assertRedirect(route('core.roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
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
