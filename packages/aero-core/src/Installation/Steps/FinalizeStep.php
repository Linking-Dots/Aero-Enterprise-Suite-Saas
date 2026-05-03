<?php

namespace Aero\Core\Installation\Steps;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

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
        return 10;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration', 'admin', 'modules'];
    }

    public function execute(): array
    {
        $results = [];

        // Clear all caches
        $this->log('Clearing application caches');
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            $results['cache_cleared'] = true;
        } catch (\Exception $e) {
            $this->warn('Cache clearing failed: '.$e->getMessage());
            $results['cache_cleared'] = false;
        }

        // Optimize application
        $this->log('Optimizing application');
        try {
            Artisan::call('optimize');
            $results['optimized'] = true;
        } catch (\Exception $e) {
            $this->warn('Optimization failed: '.$e->getMessage());
            $results['optimized'] = false;
        }

        // Mark installation as complete
        $this->log('Marking installation as complete');
        try {
            $this->markInstallationComplete();
            $results['marked_complete'] = true;
        } catch (\Exception $e) {
            $this->warn('Failed to mark installation complete: '.$e->getMessage());
            $results['marked_complete'] = false;
        }

        // Generate completion summary
        $results['completion_summary'] = $this->generateSummary();

        $this->log('Installation finalization completed');

        return $results;
    }

    public function validate(): bool
    {
        try {
            // Check that installation_complete flag is set
            $status = DB::table('installation_history')
                ->where('step_name', 'finalize')
                ->where('status', 'success')
                ->exists();

            return $status;

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

    public function canSkip(): bool
    {
        return false;
    }

    public function isRetriable(): bool
    {
        return true;
    }
}
