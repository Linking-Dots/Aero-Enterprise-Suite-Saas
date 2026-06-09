<?php

namespace Aero\Installation\Installation\Steps;

use Aero\Core\Services\InstallationState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Finalize Step
 *
 * Final verification and setup:
 * - Clear caches
 * - Warm essential caches
 * - Mark installation as complete
 * - Generate final report
 */
class FinalizeStep extends BaseInstallationStep
{
    public function name(): string
    {
        return 'finalize';
    }

    public function description(): string
    {
        return 'Finalize installation and verify readiness';
    }

    public function order(): int
    {
        return 12;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration', 'admin', 'modules'];
    }

    public function execute(): array
    {
        $results = [];

        // ================================================================
        // STEP 1: Mark installation as complete FIRST (critical ordering)
        // ================================================================
        // Service providers (AeroPlatformServiceProvider) check for
        // storage/app/aeos.installed to decide whether to register platform
        // and tenant routes. We MUST create the lock file BEFORE generating
        // any caches (route cache, config cache, etc.) so those routes are
        // included in the cached manifest.
        $this->log('Marking installation as complete');
        try {
            $this->markInstallationComplete();
            $this->createLockFile();
            $results['marked_complete'] = true;
        } catch (\Exception $e) {
            $this->warn('Failed to mark installation complete: '.$e->getMessage());
            $results['marked_complete'] = false;
        }

        // Verify HRMAC structure is complete
        $this->log('Verifying HRMAC structure');
        try {
            $hrmacStatus = $this->verifyHrmacStructure();
            $results['hrmac_verified'] = $hrmacStatus['valid'];
            if (! $hrmacStatus['valid']) {
                $this->warn('HRMAC verification found issues: '.implode(', ', $hrmacStatus['issues']));
            }
        } catch (\Exception $e) {
            $this->warn('HRMAC verification failed: '.$e->getMessage());
            $results['hrmac_verified'] = false;
        }

        // Clear all caches
        $this->log('Clearing application caches (preserving cache to protect installation orchestrator state)');
        try {
            // Artisan::call('cache:clear'); // Bypass to preserve orchestrator session
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            $results['cache_cleared'] = true;
        } catch (\Exception $e) {
            $this->warn('Cache clearing failed: '.$e->getMessage());
            $results['cache_cleared'] = false;
        }

        // Ensure views directory exists so optimize/view:cache won't crash
        $viewsPath = resource_path('views');
        if (! is_dir($viewsPath)) {
            mkdir($viewsPath, 0755, true);
        }

        // Optimize application
        $this->log('Optimizing application');
        try {
            Artisan::call('optimize');
            $results['optimized'] = true;
        } catch (\Exception $e) {
            $this->warn('Optimization failed: '.$e->getMessage());
            $results['optimized'] = false;

            // Fallback: run individual cache commands so route cache is still generated
            $this->log('Running fallback optimization (config:cache + route:cache)');
            try {
                Artisan::call('config:cache');
            } catch (\Exception $configEx) {
                $this->warn('Fallback config:cache failed: '.$configEx->getMessage());
            }
            try {
                Artisan::call('route:cache');
                $results['route_cached'] = true;
            } catch (\Exception $routeEx) {
                $this->warn('Fallback route:cache failed: '.$routeEx->getMessage());
                $results['route_cached'] = false;
            }
        }

        // Generate completion summary
        $results['completion_summary'] = $this->generateSummary();

        $this->log('Installation finalization completed');

        return $results;
    }

    public function validate(): bool
    {
        // Allow step to proceed if database connection is working
        // Installation history will be created during execution (fresh install)
        try {
            \DB::connection()->getPdo();

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Mark installation as complete in database
     */
    protected function markInstallationComplete(): void
    {
        try {
            if (! DB::table('installation_history')->exists()) {
                return;
            }

            DB::table('installation_status')->updateOrInsert(
                ['id' => 1],
                [
                    'status' => 'complete',
                    'completed_at' => now(),
                    'mode' => env('INSTALLATION_MODE', 'standalone'),
                ]
            );

        } catch (\Exception) {
            // Table might not exist yet
        }
    }

    /**
     * Verify HRMAC structure is complete
     */
    protected function verifyHrmacStructure(): array
    {
        $issues = [];

        // Check roles exist
        try {
            $roleCount = DB::table('roles')->count();
            if ($roleCount === 0) {
                $issues[] = 'No roles created';
            }
        } catch (\Exception) {
            $issues[] = 'Cannot verify roles table';
        }

        // Check role_module_access records exist
        try {
            $accessCount = DB::table('role_module_access')->count();
            if ($accessCount === 0) {
                $issues[] = 'No role-module access mappings created';
            }
        } catch (\Exception) {
            // Table may not exist in non-HRMAC setup
        }

        // Check modules are registered
        try {
            $moduleCount = DB::table('modules')->where('is_active', true)->count();
            if ($moduleCount === 0) {
                $issues[] = 'No modules registered';
            }

            // Verify Super Administrator has access to at least one module
            $superAdminRole = DB::table('roles')
                ->where('name', 'Super Administrator')
                ->first();

            if ($superAdminRole) {
                $superAdminAccess = DB::table('role_module_access')
                    ->where('role_id', $superAdminRole->id)
                    ->count();

                if ($superAdminAccess === 0) {
                    $issues[] = 'Super Administrator has no module access';
                }
            }
        } catch (\Exception) {
            // Table may not exist
        }

        // Check admin user exists
        try {
            $adminCount = DB::table('users')
                ->orWhere(function ($q) {
                    $q->from('landlord_users');
                })
                ->count();

            if ($adminCount === 0) {
                $issues[] = 'No admin user created';
            }
        } catch (\Exception) {
            // Table may not exist
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Generate installation completion summary
     */
    protected function generateSummary(): array
    {
        return [
            'application_name' => env('APP_NAME', 'aeos365'),
            'application_url' => env('APP_URL'),
            'installation_mode' => env('INSTALLATION_MODE', 'standalone'),
            'installed_at' => now(),
            'admin_email' => env('ADMIN_EMAIL', 'admin@aeros.test'),
            'database' => env('DB_DATABASE'),
            'next_steps' => [
                '1. Log in to admin panel at '.env('APP_URL'),
                '2. Configure additional settings',
                '3. Create first organization/tenant',
                '4. Display feature modules',
            ],
        ];
    }

    /**
     * Create installation lock files
     *
     * Writes the unified lock file (aeos.installed) and mode file (aeos.mode)
     * to storage/app. Both aero-core and aero-platform packages check for
     * aeos.installed to decide whether to register runtime routes.
     *
     * InstallationState::markInstalled() writes the file-based cache entry
     * AND the legacy flat file for backward compatibility.
     */
    protected function createLockFile(): void
    {
        File::ensureDirectoryExists(storage_path('app'));

        // Mark installed via InstallationState: populates the file-based cache
        // AND writes the legacy storage/app/aeos.installed flat file.
        InstallationState::markInstalled();

        // Also write the mode file used by service providers
        $mode = env('INSTALLATION_MODE', 'standalone');
        $modeFile = storage_path('app/aeos.mode');
        File::put($modeFile, $mode);
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
