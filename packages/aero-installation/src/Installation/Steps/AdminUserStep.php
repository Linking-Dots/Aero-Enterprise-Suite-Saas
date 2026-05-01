<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Admin User Step
 *
 * Creates the initial admin/landlord user if it doesn't exist
 */
class AdminUserStep extends BaseInstallationStep
{
    protected string $mode;

    public function __construct(string $mode = 'standalone')
    {
        $this->mode = $mode;
    }

    public function name(): string
    {
        return 'admin';
    }

    public function description(): string
    {
        return 'Create initial admin user account';
    }

    public function order(): int
    {
        return 8;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration'];
    }

    public function execute(): array
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@aeros.test');
        $adminPassword = env('ADMIN_PASSWORD', 'Admin@12345');
        $adminName = env('ADMIN_NAME', 'Administrator');

        if (empty($adminEmail) || empty($adminPassword)) {
            throw new \Exception('ADMIN_EMAIL and ADMIN_PASSWORD environment variables are required');
        }

        // In SaaS mode, use landlord_users table; in standalone, use users table
        $userTable = $this->mode === 'saas' ? 'landlord_users' : 'users';

        // Check if admin already exists
        $existingAdmin = $this->getUserByEmail($adminEmail, $userTable);
        if ($existingAdmin) {
            $this->log("Admin user already exists: {$adminEmail} in table: {$userTable}");

            return [
                'admin_created' => false,
                'admin_exists' => true,
                'email' => $adminEmail,
                'table' => $userTable,
            ];
        }

        // Create admin user
        try {
            $adminId = DB::table($userTable)->insertGetId([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->log("Admin user created: {$adminEmail} (ID: {$adminId}) in table: {$userTable}");

            // Assign admin role (if roles table exists)
            $this->assignAdminRole($adminId, $userTable);

            return [
                'admin_created' => true,
                'admin_id' => $adminId,
                'email' => $adminEmail,
                'table' => $userTable,
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to create admin user: '.$e->getMessage());
        }
    }

    public function validate(): bool
    {
        // Allow step to proceed if database connection is working
        // In SaaS mode, users table may not exist in platform DB (landlord_users instead)
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get user by email
     */
    protected function getUserByEmail(string $email, string $table = 'users'): ?array
    {
        try {
            $user = DB::table($table)
                ->where('email', $email)
                ->first();

            return $user ? (array) $user : null;

        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Assign Super Administrator role to user
     * Super Administrator role bypasses all HRMAC checks
     */
    protected function assignAdminRole(int $userId, string $userTable = 'users'): void
    {
        try {
            // Check if roles table exists
            if (! DB::table('roles')->exists()) {
                return;
            }

            // Look for Super Administrator role (bypasses HRMAC)
            $superAdminRole = DB::table('roles')
                ->where('name', 'Super Administrator')
                ->first();

            if (! $superAdminRole) {
                // Fallback to admin role if Super Administrator doesn't exist
                $superAdminRole = DB::table('roles')
                    ->where('name', 'Super Admin')
                    ->first();
            }

            if (! $superAdminRole) {
                // Create Super Administrator role if it doesn't exist
                $superAdminRoleId = DB::table('roles')->insertGetId([
                    'name' => 'Super Administrator',
                    'guard_name' => 'web',
                    'description' => 'Full system access - bypasses all HRMAC checks',
                    'default_dashboard' => 'dashboard',
                    'priority' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $superAdminRole = (object) ['id' => $superAdminRoleId];
            }

            // Assign role (if model_has_roles table exists)
            try {
                DB::table('model_has_roles')->insert([
                    'role_id' => $superAdminRole->id,
                    'model_type' => 'App\Models\User',
                    'model_id' => $userId,
                ]);
                $this->log("Assigned Super Administrator role to user ID: {$userId} in table: {$userTable}");
            } catch (\Exception) {
                // Table might not exist yet
            }

        } catch (\Exception) {
            // Roles not set up yet
        }
    }

    public function canSkip(): bool
    {
        return false;
    }

    public function isRetriable(): bool
    {
        return true;
    }
}
