<?php

namespace Aero\Core\Installation\Steps;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Seeding Step
 *
 * Runs database seeders in dependency order
 */
class SeedingStep extends BaseInstallationStep
{
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
        return 6;
    }

    public function dependencies(): array
    {
        return ['config', 'database', 'migration', 'admin', 'modules'];
    }

    public function execute(): array
    {
        $this->log('Starting database seeding');

        $modeFile = storage_path('app/aeos.mode');
        $mode     = file_exists($modeFile) ? trim(file_get_contents($modeFile)) : env('INSTALLATION_MODE', 'standalone');

        try {
            if ($mode === 'saas') {
                // Directly call platform seeders — no app-level DatabaseSeeder exists in SaaS
                $seededClasses = [];

                $platformSeeder = 'Aero\\Platform\\Database\\Seeders\\PlatformDatabaseSeeder';
                if (class_exists($platformSeeder)) {
                    app($platformSeeder)->setCommand($this->createNullCommand())->run();
                    $seededClasses[] = $platformSeeder;
                }

                $productSeeder = 'Aero\\Platform\\Database\\Seeders\\ProductSeeder';
                if (class_exists($productSeeder)) {
                    app($productSeeder)->setCommand($this->createNullCommand())->run();
                    $seededClasses[] = $productSeeder;
                }

                return [
                    'seeding_status' => 'success',
                    'mode'           => 'saas',
                    'seeded'         => $seededClasses,
                ];
            }

            // Standalone: fall back to app DatabaseSeeder if it exists
            Artisan::call('db:seed', ['--force' => true]);

            return [
                'seeding_status' => 'success',
                'mode'           => 'standalone',
                'seeded_tables'  => ['roles', 'permissions', 'settings', 'modules'],
            ];

        } catch (\Exception $e) {
            $this->warn('Seeding partially completed: ' . $e->getMessage());

            return [
                'seeding_status' => 'completed_with_warnings',
                'warning'        => $e->getMessage(),
            ];
        }
    }

    /** Returns a minimal command stub so seeders can call $this->command->info() without crashing. */
    private function createNullCommand(): \Illuminate\Console\Command
    {
        return new class extends \Illuminate\Console\Command {
            protected $name = 'install:seed-stub';
            public function info($string, $verbosity = null): void {}
            public function line($string, $style = null, $verbosity = null): void {}
            public function handle(): void {}
        };
    }

    public function validate(): bool
    {
        // Check that some seed data exists
        try {
            $hasRoles = DB::table('roles')->exists();
            $hasPermissions = DB::table('permissions')->exists();

            return $hasRoles && $hasPermissions;

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
