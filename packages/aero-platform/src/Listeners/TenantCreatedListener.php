<?php

namespace Aero\Platform\Listeners;

use Aero\Core\Services\Module\ModuleDiscoveryService;
use Aero\Core\Services\ModuleRegistry;
use Aero\Platform\Models\Module;
use Aero\Platform\Services\Tenant\TenantRoleSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Events\TenantCreated;

/**
 * TenantCreatedListener
 *
 * Listens for tenant creation events and:
 * 1. Runs module migrations on tenant database
 * 2. Syncs tenant-scoped modules to the tenant database
 * 3. Seeds essential module data for the tenant
 *
 * This ensures that when a new tenant is created, all installed modules
 * (aero-hrm, aero-crm, etc.) have their migrations run and module hierarchy
 * is properly seeded on the tenant database.
 *
 * The modules themselves are unaware of tenancy - this listener handles
 * discovering their migration paths and running them in the tenant context.
 */
class TenantCreatedListener implements ShouldQueue
{
    public function __construct(
        protected ModuleDiscoveryService $moduleDiscovery
    ) {}

    /**
     * Handle the event.
     *
     * NOTE: This listener runs when a Tenant model is created. However, during
     * registration, tenants are created as PENDING records before their database
     * exists. The actual database is created later by the ProvisionTenant job.
     *
     * This listener will:
     * - Skip processing for pending tenants (no database yet)
     * - Only run for tenants that already have a database (e.g., programmatically created)
     */
    public function handle(TenantCreated $event): void
    {
        $tenant = $event->tenant;

        Log::info("[TenantCreated] Event received for tenant: {$tenant->id}");

        // Check if this is a pending tenant from registration flow
        // Pending tenants don't have databases yet - ProvisionTenant job will handle setup
        if ($tenant->status === 'pending' || empty($tenant->database()->getName())) {
            Log::info("[TenantCreated] Skipping setup for pending tenant (no database yet): {$tenant->id}", [
                'status' => $tenant->status,
                'has_db_name' => ! empty($tenant->database()->getName()),
            ]);

            return;
        }

        // Verify database actually exists before attempting to initialize
        if (! $this->databaseExists($tenant)) {
            Log::info("[TenantCreated] Skipping setup - database does not exist yet: {$tenant->id}", [
                'expected_db' => $tenant->database()->getName(),
            ]);

            return;
        }

        Log::info("[TenantCreated] Setting up tenant database for: {$tenant->id}");

        try {
            // Initialize tenant context
            tenancy()->initialize($tenant);

            // Step 1: Run module migrations
            $this->runModuleMigrations($tenant);

            // Step 2: Sync tenant-scoped modules to tenant database
            $this->syncTenantModules($tenant);

            // Step 3: Seed essential tenant data
            $this->seedTenantData($tenant);

            // Step 4: Seed default HRMAC roles (Tenant Admin + Tenant User)
            $this->seedTenantRoles($tenant);

            Log::info("[TenantCreated] Tenant setup completed successfully for: {$tenant->id}");
        } catch (\Throwable $e) {
            Log::error("[TenantCreated] Failed to setup tenant: {$tenant->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        } finally {
            tenancy()->end();
        }
    }

    /**
     * Check if the tenant database exists.
     */
    protected function databaseExists($tenant): bool
    {
        try {
            $databaseName = $tenant->database()->getName();

            if (empty($databaseName)) {
                return false;
            }

            $result = DB::select(
                'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
                [$databaseName]
            );

            return ! empty($result);
        } catch (\Throwable $e) {
            Log::warning("[TenantCreated] Failed to check database existence: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Run module migrations for the tenant.
     */
    protected function runModuleMigrations($tenant): void
    {
        Log::info("[TenantCreated] Running module migrations for tenant: {$tenant->id}");

        $migrationPaths = $this->getModuleMigrationPaths();

        if (empty($migrationPaths)) {
            Log::info("[TenantCreated] No module migrations found for tenant: {$tenant->id}");

            return;
        }

        foreach ($migrationPaths as $moduleName => $paths) {
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    Log::info("[TenantCreated] Running migrations for module '{$moduleName}' from: {$path}");

                    Artisan::call('migrate', [
                        '--path' => $this->getRelativePath($path),
                        '--database' => 'tenant',
                        '--force' => true,
                    ]);

                    Log::info("[TenantCreated] Completed migrations for module '{$moduleName}'");
                }
            }
        }

        Log::info("[TenantCreated] All module migrations completed for tenant: {$tenant->id}");
    }

    /**
     * Sync tenant-scoped modules to the tenant database.
     * This populates the modules, sub_modules, module_components, and module_component_actions tables.
     */
    protected function syncTenantModules($tenant): void
    {
        // Check if module tables exist (they should after migrations)
        if (! Schema::hasTable('modules')) {
            Log::warning("[TenantCreated] Modules table does not exist, skipping module sync for tenant: {$tenant->id}");

            return;
        }

        Log::info("[TenantCreated] Syncing tenant modules for: {$tenant->id}");

        try {
            // Call aero:sync-module with fresh flag for clean seed
            // Scope is auto-detected (tenant context = tenant modules only)
            Artisan::call('aero:sync-module', [
                '--fresh' => true,
                '--force' => true,
            ]);

            $output = Artisan::output();
            Log::info("[TenantCreated] Module sync output for {$tenant->id}: {$output}");
        } catch (\Throwable $e) {
            Log::error("[TenantCreated] Failed to sync modules for tenant: {$tenant->id}", [
                'error' => $e->getMessage(),
            ]);
            // Don't throw - module sync failure shouldn't prevent tenant creation
        }
    }

    /**
     * Seed essential tenant data (roles, permissions, etc.)
     */
    protected function seedTenantData($tenant): void
    {
        Log::info("[TenantCreated] Seeding essential data for tenant: {$tenant->id}");

        try {
            // Run tenant database seeder if it exists
            if (class_exists('Database\\Seeders\\TenantDatabaseSeeder')) {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\TenantDatabaseSeeder',
                    '--force' => true,
                ]);
                Log::info("[TenantCreated] TenantDatabaseSeeder completed for tenant: {$tenant->id}");
            }
        } catch (\Throwable $e) {
            Log::warning("[TenantCreated] Failed to run TenantDatabaseSeeder for tenant: {$tenant->id}", [
                'error' => $e->getMessage(),
            ]);
            // Don't throw - seeder failure shouldn't prevent tenant creation
        }

        // Seed tenant_module subscriptions
        $this->seedTenantModules($tenant);
    }

    /**
     * Seed tenant_module subscriptions from tenant data.
     */
    protected function seedTenantModules($tenant): void
    {
        Log::info("[TenantCreated] Seeding tenant_module subscriptions for tenant: {$tenant->id}");

        try {
            // Get selected modules from tenant data (set during registration)
            $selectedModules = $tenant->data['selected_modules'] ?? ['core'];

            // Ensure core is always included
            if (! in_array('core', $selectedModules, true)) {
                $selectedModules[] = 'core';
            }

            foreach ($selectedModules as $moduleCode) {
                $module = Module::where('code', $moduleCode)->first();
                if ($module) {
                    DB::table('tenant_module')->insert([
                        'tenant_id' => $tenant->id,
                        'module_id' => $module->id,
                        'is_active' => true,
                        'subscribed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::info("[TenantCreated] Subscribed tenant {$tenant->id} to module: {$moduleCode}");
                } else {
                    Log::warning("[TenantCreated] Module not found: {$moduleCode}");
                }
            }

            Log::info("[TenantCreated] Tenant_module seeding completed for tenant: {$tenant->id}");
        } catch (\Throwable $e) {
            Log::warning("[TenantCreated] Failed to seed tenant_module for tenant: {$tenant->id}", [
                'error' => $e->getMessage(),
            ]);
            // Don't throw - module seeding failure shouldn't prevent tenant creation
        }
    }

    /**
     * Seed default HRMAC roles (Tenant Admin + Tenant User) for the tenant.
     * Failures are swallowed so a role-seeding issue never blocks tenant provisioning.
     */
    protected function seedTenantRoles($tenant): void
    {
        try {
            app(TenantRoleSeeder::class)->seedFor($tenant);
        } catch (\Throwable $e) {
            Log::error("[TenantCreated] Role seeding failed for tenant {$tenant->id}: ".$e->getMessage());
            // Do not rethrow — tenant is provisioned, admin can manually re-run via artisan
        }
    }

    /**
     * Get migration paths for all installed tenant-scoped modules.
     * Uses ModuleDiscoveryService for dynamic discovery.
     *
     * @return array<string, array<string>> Module name => migration paths
     */
    protected function getModuleMigrationPaths(): array
    {
        $paths = [];

        // Primary: Use ModuleDiscoveryService for dynamic discovery
        $modules = $this->moduleDiscovery->getModuleDefinitions();

        foreach ($modules as $moduleDef) {
            // Only process tenant-scoped modules
            $scope = $moduleDef['scope'] ?? 'tenant';
            if ($scope !== 'tenant') {
                continue;
            }

            $moduleCode = $moduleDef['code'] ?? null;
            if (! $moduleCode) {
                continue;
            }

            // Find migration paths for this module
            $moduleMigrationPaths = $this->findModuleMigrationPaths($moduleCode);
            if (! empty($moduleMigrationPaths)) {
                $paths[$moduleCode] = $moduleMigrationPaths;
            }
        }

        // Fallback: Check ModuleRegistry (from aero-core)
        if (empty($paths) && app()->bound(ModuleRegistry::class)) {
            $registry = app(ModuleRegistry::class);
            $modules = $registry->all();

            foreach ($modules as $moduleCode => $provider) {
                // Try to find migration path from provider's base path
                $providerClass = get_class($provider);
                $reflector = new \ReflectionClass($providerClass);
                $modulePath = dirname($reflector->getFileName(), 2);
                $migrationPath = $modulePath.'/database/migrations';
                if (is_dir($migrationPath)) {
                    $paths[$moduleCode][] = $migrationPath;
                }
            }
        }

        return $paths;
    }

    /**
     * Find migration paths for a specific module by code.
     *
     * @return array<string>
     */
    protected function findModuleMigrationPaths(string $moduleCode): array
    {
        $paths = [];

        // Check vendor path (installed via Composer)
        $vendorPath = base_path("vendor/aero/aero-{$moduleCode}/database/migrations");
        if (is_dir($vendorPath)) {
            $paths[] = realpath($vendorPath);
        }

        // Check packages path (monorepo development)
        $packagesPath = base_path("packages/aero-{$moduleCode}/database/migrations");
        if (is_dir($packagesPath)) {
            $paths[] = realpath($packagesPath);
        }

        // Check monorepo parent path
        $monoRepoPath = base_path("../../packages/aero-{$moduleCode}/database/migrations");
        if (is_dir($monoRepoPath)) {
            $paths[] = realpath($monoRepoPath);
        }

        // Check runtime modules path
        $runtimePath = base_path("modules/aero-{$moduleCode}/database/migrations");
        if (is_dir($runtimePath)) {
            $paths[] = realpath($runtimePath);
        }

        return array_unique(array_filter($paths));
    }

    /**
     * Convert absolute path to relative path for artisan migrate command.
     */
    protected function getRelativePath(string $absolutePath): string
    {
        $basePath = base_path();

        // Handle monorepo structure
        if (str_starts_with($absolutePath, dirname(dirname($basePath)))) {
            return str_replace(dirname(dirname($basePath)).'/', '../../', $absolutePath);
        }

        if (str_starts_with($absolutePath, $basePath)) {
            return str_replace($basePath.'/', '', $absolutePath);
        }

        return $absolutePath;
    }
}
