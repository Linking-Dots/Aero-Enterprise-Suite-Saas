<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration Step
 *
 * Runs all pending migrations in order:
 * 1. Core foundation migrations
 * 2. Authentication migrations
 * 3. Tenancy migrations (SaaS mode)
 * 4. Module-specific migrations
 * 5. Settings migrations
 *
 * Uses installation-migration-order config for ordering
 */
class MigrationStep extends BaseInstallationStep
{
    protected string $mode;

    public function __construct(string $mode = 'standalone')
    {
        $this->mode = $mode;
    }

    public function name(): string
    {
        return 'migration';
    }

    public function description(): string
    {
        return 'Run database migrations in dependency order';
    }

    public function order(): int
    {
        return 4;
    }

    public function dependencies(): array
    {
        return ['config', 'database'];
    }

    public function execute(): array
    {
        $migrated = [];
        $output = [];

        if ($this->mode === 'saas') {
            // SaaS Mode: Run only platform package migrations
            $this->log('Running platform package migrations (SaaS mode)');
            try {
                // Use migrate:fresh for fresh installation to ensure clean state
                // This drops all tables and re-runs migrations
                $exitCode = Artisan::call('migrate:fresh', [
                    '--path' => 'vendor/aero/platform/database/migrations',
                    '--force' => true,
                ]);

                if ($exitCode === 0) {
                    $migrated['platform'] = 'success';
                    $output['platform'] = 'success';
                    $this->log('Platform package migrations completed successfully');
                } else {
                    throw new \Exception("Platform package migrations failed with exit code: {$exitCode}");
                }
            } catch (\Exception $e) {
                $output['platform'] = 'failed';
                throw new \Exception("Critical platform migrations failed: ".$e->getMessage());
            }

            // Verify migrations ran successfully
            $this->verifyMigrationsRan('vendor/aero/platform/database/migrations');
        } else {
            // Standalone Mode: Run core + other packages migrations (excluding platform)
            $this->log('Running core package migrations (Standalone mode)');
            try {
                // Use migrate:fresh for fresh installation to ensure clean state
                $exitCode = Artisan::call('migrate:fresh', [
                    '--path' => 'vendor/aero/core/database/migrations',
                    '--force' => true,
                ]);

                if ($exitCode === 0) {
                    $migrated['core'] = 'success';
                    $output['core'] = 'success';
                    $this->log('Core package migrations completed successfully');
                } else {
                    throw new \Exception("Core package migrations failed with exit code: {$exitCode}");
                }
            } catch (\Exception $e) {
                $output['core'] = 'failed';
                throw new \Exception("Critical core migrations failed: ".$e->getMessage());
            }

            // Run other package migrations (excluding platform)
            $this->log('Running other package migrations (Standalone mode)');
            try {
                $exitCode = Artisan::call('migrate', [
                    '--force' => true,
                ]);

                if ($exitCode === 0) {
                    $migrated['other_packages'] = 'success';
                    $output['other_packages'] = 'success';
                    $this->log('Other package migrations completed successfully');
                } else {
                    throw new \Exception("Other package migrations failed with exit code: {$exitCode}");
                }
            } catch (\Exception $e) {
                $output['other_packages'] = 'failed';
                throw new \Exception("Other package migrations failed: ".$e->getMessage());
            }

            // Verify migrations ran successfully
            $this->verifyMigrationsRan('vendor/aero/core/database/migrations');
        }

        return [
            'migrations_run' => count($migrated),
            'tags_processed' => count($output),
            'by_tag' => $output,
            'total_migrated' => $migrated,
        ];
    }

    /**
     * Verify migrations ran successfully by checking migration files vs executed migrations
     */
    protected function verifyMigrationsRan(string $path): void
    {
        $this->log('Verifying migrations ran successfully for path: '.$path);
        
        // Get all migration files in the specified path
        $migrationPath = base_path($path);
        if (! is_dir($migrationPath)) {
            throw new \Exception("Migration path not found: {$migrationPath}");
        }

        $migrationFiles = glob($migrationPath.'/*.php');
        $totalMigrations = count($migrationFiles);
        
        $this->log("Found {$totalMigrations} migration files in {$path}");

        // Extract migration names from files
        $migrationNames = [];
        foreach ($migrationFiles as $file) {
            $migrationNames[] = basename($file, '.php');
        }

        // Get executed migration names from database
        $executedMigrations = DB::table('migrations')->pluck('migration')->toArray();
        $executedCount = count($executedMigrations);
        
        $this->log("Total executed migrations in database: {$executedCount}");

        // Check which migrations from this path were executed
        $executedFromPath = array_intersect($migrationNames, $executedMigrations);
        $executedFromPathCount = count($executedFromPath);
        
        $this->log("Migrations from {$path} that were executed: {$executedFromPathCount}/{$totalMigrations}");

        // Verify all migrations in the path were executed
        if ($executedFromPathCount < $totalMigrations) {
            $missingMigrations = array_diff($migrationNames, $executedMigrations);
            $this->error('Not all migrations were executed. Missing: '.implode(', ', $missingMigrations));
            throw new \Exception('Migration verification failed: '.($totalMigrations - $executedFromPathCount).' migrations were not executed');
        }

        $this->log("Migration verification passed: all {$totalMigrations} migrations executed");
    }

    public function validate(): bool
    {
        // For fresh installation, always allow migrations to run
        // The validate() method is called before execute() to check preconditions
        // Since we're setting up a fresh database, we don't need to check if migrations already exist
        try {
            // Just check if database connection is working
            DB::connection()->getPdo();
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get migrations by tag
     *
     * Queries migrations table for entries with specific installation_tag
     */
    protected function getMigrationsByTag(string $tag): array
    {
        try {
            return DB::table('migrations')
                ->where('installation_tag', $tag)
                ->where('batch', 0) // Pending migrations
                ->pluck('migration')
                ->toArray();
        } catch (\Exception) {
            return [];
        }
    }

    public function canSkip(): bool
    {
        return false;
    }

    public function isRetriable(): bool
    {
        return true; // Can retry migrations
    }

    public function maxAttempts(): int
    {
        return 3;
    }

    public function rollback(): void
    {
        try {
            $this->log('Rolling back migrations');
            Artisan::call('migrate:rollback', [
                '--step' => 1,
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            $this->warn('Rollback failed: '.$e->getMessage());
        }
    }
}
