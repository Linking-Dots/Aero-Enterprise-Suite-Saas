<?php

namespace Aero\Platform\Console\Commands;

use Aero\Core\Services\InstallationState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CleanupFailedInstallation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:cleanup {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up failed platform installation artifacts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Platform Installation Cleanup Tool');
        $this->line('=====================================');

        // Check if actually installed
        if (InstallationState::isInstalled()) {
            $this->warn('⚠️  Platform appears to be successfully installed.');

            if (! $this->option('force') && ! $this->confirm('Are you sure you want to cleanup? This may break the installation.', false)) {
                $this->info('Cleanup cancelled.');

                return 0;
            }
        }

        $cleaned = [];

        // 1. Release installation lock
        if ($this->cleanupLock()) {
            $cleaned[] = 'Installation lock';
        }

        // 2. Remove persisted config files
        $configsCleaned = $this->cleanupPersistedConfigs();
        if ($configsCleaned > 0) {
            $cleaned[] = "$configsCleaned config file(s)";
        }

        // 3. Remove progress tracking
        if ($this->cleanupProgressTracking()) {
            $cleaned[] = 'Progress tracking';
        }

        // 4. Remove .env backup
        if ($this->cleanupEnvBackup()) {
            $cleaned[] = '.env backup';
        }

        // 5. Optionally rollback migrations
        if ($this->shouldRollbackMigrations()) {
            if ($this->rollbackMigrations()) {
                $cleaned[] = 'Database migrations';
            }
        }

        // 6. Remove installation marker
        if ($this->removeInstalledMarker()) {
            $cleaned[] = 'Installation marker';
        }

        // Summary
        $this->newLine();
        if (count($cleaned) > 0) {
            $this->info('✅ Cleaned up:');
            foreach ($cleaned as $item) {
                $this->line("   • $item");
            }
            $this->newLine();
            $this->info('💡 You can now retry the installation.');
        } else {
            $this->info('No cleanup needed. Installation artifacts not found.');
        }

        return 0;
    }

    /**
     * Clean up installation lock
     */
    private function cleanupLock(): bool
    {
        $lockFile = storage_path('installation.lock');

        if (File::exists($lockFile)) {
            try {
                $lockData = json_decode(File::get($lockFile), true);
                $this->line("Found lock from: {$lockData['user']} at {$lockData['created_at']}");

                File::delete($lockFile);

                return true;
            } catch (\Exception $e) {
                $this->error("Failed to remove lock: {$e->getMessage()}");

                return false;
            }
        }

        return false;
    }

    /**
     * Clean up persisted config files
     */
    private function cleanupPersistedConfigs(): int
    {
        $configFiles = [
            'installation_db_config.json',
            'installation_platform_config.json',
            'installation_admin_config.json',
        ];

        $cleaned = 0;
        foreach ($configFiles as $file) {
            $path = storage_path($file);
            if (File::exists($path)) {
                File::delete($path);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Clean up progress tracking
     */
    private function cleanupProgressTracking(): bool
    {
        $progressFile = storage_path('installation_progress.json');

        if (File::exists($progressFile)) {
            File::delete($progressFile);

            return true;
        }

        return false;
    }

    /**
     * Clean up .env backup
     */
    private function cleanupEnvBackup(): bool
    {
        $backupFile = storage_path('.env.backup');

        if (File::exists($backupFile)) {
            if ($this->confirm('Found .env backup. Do you want to restore it?', true)) {
                try {
                    File::copy($backupFile, base_path('.env'));
                    $this->info('✅ Restored .env from backup');
                } catch (\Exception $e) {
                    $this->error("Failed to restore .env: {$e->getMessage()}");
                }
            }

            File::delete($backupFile);

            return true;
        }

        return false;
    }

    /**
     * Check if migrations should be rolled back
     */
    private function shouldRollbackMigrations(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        try {
            // Check if migrations table exists
            if (! Schema::hasTable('migrations')) {
                return false;
            }

            $migrationCount = DB::table('migrations')->count();

            if ($migrationCount > 0) {
                return $this->confirm(
                    "Found {$migrationCount} migrations in database. Rollback migrations?",
                    true
                );
            }
        } catch (\Exception $e) {
            // Database not accessible or not configured
            return false;
        }

        return false;
    }

    /**
     * Rollback migrations
     */
    private function rollbackMigrations(): bool
    {
        try {
            $this->info('Rolling back migrations...');
            Artisan::call('migrate:rollback', ['--force' => true]);
            $output = Artisan::output();
            $this->line($output);

            return true;
        } catch (\Exception $e) {
            $this->error("Failed to rollback migrations: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Remove installed marker
     */
    private function removeInstalledMarker(): bool
    {
        $installedFile = storage_path('app/aeos.installed');

        if (File::exists($installedFile)) {
            if ($this->option('force') || $this->confirm('Remove installation marker?', true)) {
                File::delete($installedFile);
                InstallationState::clear();

                return true;
            }
        }

        return false;
    }
}
