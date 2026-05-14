<?php

namespace Aero\Core\Installation\Steps;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Admin User Step
 *
 * Creates the initial admin/landlord user.
 * In SaaS mode: creates a LandlordUser in the `landlord_users` table.
 * In Standalone mode: creates a tenant User in the `users` table.
 *
 * Admin credentials come from the persisted wizard config
 * (storage/framework/installation_config.json), falling back to env vars.
 */
class AdminUserStep extends BaseInstallationStep
{
    /** Path matches UnifiedInstallationController::CONFIG_PATH */
    private const CONFIG_PATH = 'storage/framework/installation_config.json';

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
        return 5;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration'];
    }

    public function execute(): array
    {
        $mode = $this->detectMode();
        $config = $this->getPersistedConfig();
        $adminConfig = $config['admin'] ?? [];

        // Resolve credentials: wizard form > env vars > hardcoded defaults
        $adminEmail    = $adminConfig['email']         ?? env('ADMIN_EMAIL', 'admin@aeos365.test');
        $adminName     = trim(($adminConfig['first_name'] ?? '') . ' ' . ($adminConfig['last_name'] ?? ''))
                         ?: env('ADMIN_NAME', 'Platform Admin');
        $adminUsername = strtolower(str_replace([' ', '@', '.'], ['_', '_', '_'], $adminName));
        $passwordHash  = $adminConfig['password_hash'] ?? Hash::make(env('ADMIN_PASSWORD', 'Admin@12345!'));

        $table = ($mode === 'saas') ? 'landlord_users' : 'users';

        // Check if admin already exists
        $existing = DB::table($table)->where('email', $adminEmail)->first();

        if ($existing) {
            // Update password and name in case they changed
            DB::table($table)->where('email', $adminEmail)->update([
                'name'              => $adminName,
                'user_name'         => $adminUsername,
                'password'          => $passwordHash,
                'email_verified_at' => now(),
                'updated_at'        => now(),
            ]);

            $this->log("Admin user updated: {$adminEmail} (table={$table})");

            return [
                'admin_created' => false,
                'admin_exists'  => true,
                'email'         => $adminEmail,
                'table'         => $table,
            ];
        }

        try {
            $adminId = DB::table($table)->insertGetId([
                'name'              => $adminName,
                'user_name'         => $adminUsername,
                'email'             => $adminEmail,
                'password'          => $passwordHash,
                'active'            => true,
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $this->log("Admin user created: {$adminEmail} (ID: {$adminId}, table={$table})");

            // Assign Super Administrator role (best-effort, skipped if table not ready)
            $this->assignAdminRole($adminId, $table, $mode);

            return [
                'admin_created' => true,
                'admin_id'      => $adminId,
                'email'         => $adminEmail,
                'table'         => $table,
            ];

        } catch (\Exception $e) {
            throw new \Exception('Failed to create admin user: ' . $e->getMessage());
        }
    }

    public function validate(): bool
    {
        $mode   = $this->detectMode();
        $config = $this->getPersistedConfig();
        $email  = $config['admin']['email'] ?? env('ADMIN_EMAIL', 'admin@aeos365.test');
        $table  = ($mode === 'saas') ? 'landlord_users' : 'users';

        try {
            return DB::table($table)->where('email', $email)->exists();
        } catch (\Exception) {
            return false;
        }
    }

    // -----------------------------------------------------------------------

    private function detectMode(): string
    {
        // Check file-based mode flag first (most reliable)
        $modeFile = storage_path('app/aeos.mode');
        if (file_exists($modeFile)) {
            return trim(file_get_contents($modeFile));
        }

        return env('INSTALLATION_MODE', 'standalone');
    }

    private function getPersistedConfig(): array
    {
        $path = base_path(self::CONFIG_PATH);
        if (!file_exists($path)) {
            return [];
        }
        try {
            return json_decode(file_get_contents($path), true) ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    private function assignAdminRole(int $userId, string $table, string $mode): void
    {
        try {
            $guardName = ($mode === 'saas') ? 'landlord' : 'web';
            $roleName  = 'Super Administrator';

            if (!DB::getSchemaBuilder()->hasTable('roles')) {
                return;
            }

            $role = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', $guardName)
                ->first();

            if (!$role) {
                $roleId = DB::table('roles')->insertGetId([
                    'name'         => $roleName,
                    'guard_name'   => $guardName,
                    'description'  => 'Full system access',
                    'is_protected' => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            } else {
                $roleId = $role->id;
            }

            // model_has_roles uses the Eloquent model FQCN
            $modelType = ($mode === 'saas')
                ? 'Aero\\Platform\\Models\\LandlordUser'
                : 'Aero\\Core\\Models\\User';

            if (!DB::getSchemaBuilder()->hasTable('model_has_roles')) {
                return;
            }

            $exists = DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_type', $modelType)
                ->where('model_id', $userId)
                ->exists();

            if (!$exists) {
                DB::table('model_has_roles')->insert([
                    'role_id'    => $roleId,
                    'model_type' => $modelType,
                    'model_id'   => $userId,
                ]);
            }
        } catch (\Exception) {
            // Roles not set up yet — silently skip
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
