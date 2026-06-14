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
        // Load persisted config if available
        $configPath = storage_path('framework/installation_config.json');
        $persistedConfig = [];
        if (file_exists($configPath)) {
            $persistedConfig = json_decode(file_get_contents($configPath), true) ?? [];
        }

        $adminConfig = $persistedConfig['admin'] ?? [];

        $adminEmail = $adminConfig['email'] ?? env('ADMIN_EMAIL');
        $adminName = ($adminConfig['first_name'] ?? env('ADMIN_FIRST_NAME', 'Admin')).' '.($adminConfig['last_name'] ?? env('ADMIN_LAST_NAME', 'User'));

        if (empty($adminEmail)) {
            throw new \Exception('Admin email not provided. Complete the Admin step in the installer before proceeding.');
        }

        if (!empty($adminConfig['password_hash'])) {
            $hashedPassword = $adminConfig['password_hash'];
        } elseif (!empty(env('ADMIN_PASSWORD'))) {
            $hashedPassword = Hash::make(env('ADMIN_PASSWORD'));
        } else {
            throw new \Exception('Admin password not provided. Complete the Admin step in the installer before proceeding.');
        }

        // Auth-identity unification (2C, mechanism B): landlords are now `users`
        // rows in the CENTRAL database — same table as tenant/standalone users,
        // different connection. SaaS writes the admin to central `users`;
        // standalone to the default `users`.
        $userTable = 'users';
        $connection = $this->mode === 'saas' ? 'central' : null;

        // Check if admin already exists
        $existingAdmin = $this->getUserByEmail($adminEmail, $userTable, $connection);
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
            $adminId = DB::connection($connection)->table($userTable)->insertGetId([
                'user_name' => str($adminEmail)->before('@')->slug()->value() ?: 'admin',
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => $hashedPassword,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->log("Admin user created: {$adminEmail} (ID: {$adminId}) in table: {$userTable}");

            // Assign admin role (if roles table exists)
            $this->assignAdminRole($adminId, $userTable, $connection);

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
    protected function getUserByEmail(string $email, string $table = 'users', ?string $connection = null): ?array
    {
        try {
            $user = DB::connection($connection)->table($table)
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
    protected function assignAdminRole(int $userId, string $userTable = 'users', ?string $connection = null): void
    {
        try {
            // Check if roles table exists
            if (! DB::connection($connection)->table('roles')->exists()) {
                return;
            }

            // Look for Super Administrator role (bypasses HRMAC)
            $superAdminRole = DB::connection($connection)->table('roles')
                ->where('name', 'Super Administrator')
                ->first();

            if (! $superAdminRole) {
                // Fallback to admin role if Super Administrator doesn't exist
                $superAdminRole = DB::connection($connection)->table('roles')
                    ->where('name', 'Super Admin')
                    ->first();
            }

            if (! $superAdminRole) {
                // Create Super Administrator role if it doesn't exist
                $superAdminRoleId = DB::connection($connection)->table('roles')->insertGetId([
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

            // Assign role (if model_has_roles table exists). model_has_roles stores a
            // polymorphic morph key. Auth-identity unification: landlords AND tenant
            // users are the one Aero\Auth\Models\User, registered in the morph map as
            // the stable 'user' key (AeroCoreServiceProvider::registerIdentityModelAliases)
            // — in EVERY mode. (Pre-unification this wrote a class FQN for saas, which is
            // now stale: there is no Aero\Platform\Models\User, and the central User
            // morphs to 'user' like everywhere else.)
            try {
                DB::connection($connection)->table('model_has_roles')->insert([
                    'role_id' => $superAdminRole->id,
                    'model_type' => 'user',
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
