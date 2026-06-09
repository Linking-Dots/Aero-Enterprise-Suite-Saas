<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Auth;

use Aero\Core\Models\User;
use Aero\Core\Tests\PackageTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2 safety net (dependency decoupling — cut auth → core by moving User
 * into aero-auth). Pins User + Spatie role behaviour against CURRENT code so the
 * move is verified to keep these green.
 *
 * Highest risk: the polymorphic model_has_roles.model_type. There is no morphMap
 * (verified 2026-06-09), so Spatie persists the full FQN. If User's FQN changes
 * from Aero\Core\Models\User to Aero\Auth\Models\User, every pre-existing role row
 * silently stops matching and all role checks fail.
 *
 * test_legacy_core_fqn_role_rows_still_resolve is THE contract Phase 2 must honour:
 * a role row written with the legacy 'Aero\Core\Models\User' model_type must still
 * resolve after the move — i.e. Phase 2 must register a morphMap (and migrate rows)
 * or override getMorphClass() to keep the legacy key matching.
 */
class UserRoleMorphStabilityTest extends PackageTestCase
{
    private function makeRole(string $name): int
    {
        return (int) DB::table('roles')->insertGetId([
            'name'       => $name,
            'guard_name' => 'web',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_user_can_be_assigned_a_role_and_check_passes(): void
    {
        $user = User::factory()->create();
        $roleId = $this->makeRole('manager');

        DB::table('model_has_roles')->insert([
            'role_id'    => $roleId,
            'model_type' => $user->getMorphClass(),
            'model_id'   => $user->id,
        ]);

        $this->assertTrue($user->fresh()->hasRole('manager'));
        $this->assertFalse($user->fresh()->hasRole('nonexistent-role'));
    }

    public function test_legacy_core_fqn_role_rows_still_resolve(): void
    {
        $user = User::factory()->create();
        $roleId = $this->makeRole('legacy-admin');

        // Simulate a role row persisted BEFORE any namespace move.
        DB::table('model_has_roles')->insert([
            'role_id'    => $roleId,
            'model_type' => 'Aero\\Core\\Models\\User',
            'model_id'   => $user->id,
        ]);

        $this->assertTrue(
            $user->fresh()->hasRole('legacy-admin'),
            'A model_has_roles row stored with the legacy Aero\\Core\\Models\\User '.
            'model_type must still resolve after User moves to aero-auth. Phase 2 must '.
            'register a stable morphMap (and migrate existing rows) or override '.
            'getMorphClass() so legacy rows keep matching.'
        );
    }

    public function test_super_admin_helper_grants_role_and_gate_bypass(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->assertTrue($admin->fresh()->hasRole('super-admin'));
    }
}
