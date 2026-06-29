<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\Artisan;

/**
 * Seeding Step
 *
 * Runs database seeders in dependency order
 * In SaaS mode: only seeds platform modules data
 * In standalone mode: seeds core + other packages data
 */
class SeedingStep extends BaseInstallationStep
{
    protected string $mode = 'standalone';

    public function __construct(string $mode = 'standalone')
    {
        $this->mode = $mode;
    }

    public function name(): string
    {
        return 'seeding';
    }

    public function description(): string
    {
        return 'Seed database with essential data';
    }

    public function order(): int
    {
        return 9;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration', 'admin', 'modules'];
    }

    public function execute(): array
    {
        $this->log('Starting database seeding in '.$this->mode.' mode');

        try {
            // Wrap seeding in transaction to ensure consistency
            return \DB::transaction(function () {
                if ($this->mode === 'saas') {
                    // In SaaS mode, only seed platform data (roles and role module access)
                    $this->seedPlatformData();
                } else {
                    // In standalone mode, seed core + other packages data
                    // This includes RoleSeeder and RoleModuleAccessSeeder from core
                    Artisan::call('db:seed', [
                        '--force' => true,
                    ]);
                }

                return [
                    'seeding_status' => 'success',
                    'mode' => $this->mode,
                    'seeded_tables' => $this->mode === 'saas'
                        ? ['roles', 'role_module_access', 'platform_modules']
                        : ['roles', 'role_module_access', 'modules', 'core_packages'],
                ];
            });

        } catch (\Exception $e) {
            $this->error('Seeding failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Seed platform-specific data for SaaS mode
     * Seeds roles and role module access (HRMAC system)
     */
    protected function seedPlatformData(): void
    {
        $this->log('Seeding platform data for SaaS mode');
        
        // Run core package seeders for roles and role module access with mode parameter
        // HRMAC handles permissions via role module access, no PermissionSeeder needed
        // Settings are collected from UI, no SettingsSeeder needed.
        //
        // PlatformHrmacSeeder is the LANDLORD RBAC seeder: it creates the
        // 'Super Platform Admin' (landlord guard) + 'Platform Admin' roles and grants
        // them HRMAC access to the platform module. Without it the central/landlord DB
        // has no platform super-admin role and the installer's admin user cannot access
        // the platform admin. It must run after the module hierarchy is synced
        // (ModuleDiscoveryStep, which sets module scope='platform').
        // Catalog (plans/products/module pricing) is seeded by PlanSeedingStep via the
        // platform PlatformDatabaseSeeder; this step seeds only landlord RBAC data.
        $platformSeeders = [
            'Aero\\Core\\Database\\Seeders\\RoleSeeder',
            'Aero\\Core\\Database\\Seeders\\RoleModuleAccessSeeder',
            'Aero\\Platform\\Database\\Seeders\\PlatformHrmacSeeder',
        ];

        foreach ($platformSeeders as $seeder) {
            try {
                // Pass mode parameter to seeders that support it
                if ($seeder === 'Aero\\Core\\Database\\Seeders\\RoleModuleAccessSeeder') {
                    // Instantiate with mode parameter
                    $seederInstance = new $seeder($this->mode);
                    $seederInstance->run();
                } else {
                    // Regular seeder call
                    Artisan::call('db:seed', [
                        '--class' => $seeder,
                        '--force' => true,
                    ]);
                }
                $this->log('Seeded: '.$seeder);
            } catch (\Exception $e) {
                $this->warn('Failed to seed '.$seeder.': '.$e->getMessage());
            }
        }
    }

    public function validate(): bool
    {
        // Allow step to proceed if database connection is working
        // Roles will be created during execution (fresh install)
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public function canSkip(): bool
    {
        return true; // Seeding can be skipped if necessary
    }

    public function isRetriable(): bool
    {
        return true;
    }
}
