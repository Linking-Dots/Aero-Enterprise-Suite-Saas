<?php

declare(strict_types=1);

namespace Aero\Platform\Database\Seeders;

use Aero\Auth\Models\User;
use Aero\Contracts\AeroMode;
use Aero\HRMAC\Models\Role as HrmacRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds a realistic platform-staff roster so the Users command centre has
 * something to operate on (a fresh install ships with a single admin).
 *
 * Idempotent — keyed on email. Passwords are random and never surfaced: these
 * accounts exist to demonstrate the staff surface (roles, 2FA posture, lock
 * state, login history), NOT to provide additional logins. The real admin
 * (admin@aeos365.com) is left untouched beyond ensuring its role.
 *
 * All roles use the `landlord` guard (platform context).
 */
class PlatformStaffSeeder extends Seeder
{
    public function run(): void
    {
        // HRMAC Role/model-access reads require a resolved HRMAC context; in a CLI
        // seeder there isn't one, so bypass the guard for this central seed.
        AeroMode::withoutTenantContextGuard(fn () => $this->seed());
    }

    private function seed(): void
    {
        // 1. Ensure the landlord roles exist (the two admin roles already ship via
        // PlatformHrmacSeeder; the three scoped roles are added here so staff can
        // be assigned realistic, least-privilege roles).
        $roles = [];
        foreach ([
            'Super Platform Admin',
            'Platform Admin',
            'Support Admin',
            'Billing Manager',
            'Auditor',
        ] as $name) {
            $roles[$name] = HrmacRole::firstOrCreate(['name' => $name], ['guard_name' => 'landlord']);
        }

        // 2. Staff roster. Each row: [name, email, role, 2fa?, lastLoginHoursAgo|null,
        // logins, joinedDaysAgo, state('active'|'inactive'|'locked')].
        $staff = [
            ['Sarah Chen',     'sarah.chen@aeos365.com',    'Platform Admin',  true,  2,    188, 240, 'active'],
            ['Marcus Reid',    'marcus.reid@aeos365.com',   'Platform Admin',  false, 26,   96,  180, 'active'],
            ['Priya Nair',     'priya.nair@aeos365.com',    'Billing Manager', true,  3,    74,  150, 'active'],
            ['Tom Okafor',     'tom.okafor@aeos365.com',    'Support Admin',   false, 5,    210, 200, 'active'],
            ['Elena Vasquez',  'elena.v@aeos365.com',       'Support Admin',   true,  22,   151, 120, 'active'],
            ['David Kim',      'david.kim@aeos365.com',     'Auditor',         true,  48,   33,  90,  'active'],
            ['Rachel Adams',   'rachel.adams@aeos365.com',  'Platform Admin',  true,  72,   120, 210, 'locked'],
            ['James Wright',   'james.wright@aeos365.com',  'Support Admin',   false, 288,  64,  160, 'inactive'],
            ['Nina Patel',     'nina.patel@aeos365.com',    'Billing Manager', false, null, 0,   2,   'active'],
        ];

        foreach ($staff as [$name, $email, $role, $tfa, $lastH, $logins, $joinedDays, $state]) {
            /** @var User $user */
            $user = User::withTrashed()->updateOrCreate(
                ['email' => $email],
                [
                    'name'                    => $name,
                    'user_name'               => Str::slug($name, '.'),
                    'password'                => Hash::make('Aa!'.Str::random(16)),
                    'email_verified_at'       => now()->subDays($joinedDays),
                    'two_factor_confirmed_at' => $tfa ? now()->subDays($joinedDays)->addDay() : null,
                    'two_factor_secret'       => $tfa ? encrypt(Str::random(32)) : null,
                    'last_login_at'           => $lastH === null ? null : now()->subHours($lastH),
                    'last_login_ip'           => $lastH === null ? null : '203.0.113.'.random_int(2, 250),
                    'login_count'             => $logins,
                    'account_locked_at'       => $state === 'locked' ? now()->subHours($lastH)->addMinutes(5) : null,
                    'locked_reason'           => $state === 'locked' ? '5 failed login attempts' : null,
                    'created_at'              => now()->subDays($joinedDays),
                ]
            );

            // last_login_at is guarded (not mass-assignable) — set it explicitly so
            // the login-recency metrics are real.
            $user->forceFill(['last_login_at' => $lastH === null ? null : now()->subHours($lastH)])->save();

            // Inactive = soft-deleted (SoftDeletes is the active/inactive source of truth).
            if ($state === 'inactive' && ! $user->trashed()) {
                $user->delete();
            } elseif ($state !== 'inactive' && $user->trashed()) {
                $user->restore();
            }

            $user->syncRoles([$roles[$role]]);
        }

        // 3. Guarantee the primary admin keeps Super Platform Admin.
        $admin = User::where('email', 'admin@aeos365.com')->first();
        if ($admin && ! $admin->hasRole('Super Platform Admin')) {
            $admin->assignRole($roles['Super Platform Admin']);
        }

        $this->command?->info('Seeded '.count($staff).' platform-staff users across '.count($roles).' roles.');
    }
}
