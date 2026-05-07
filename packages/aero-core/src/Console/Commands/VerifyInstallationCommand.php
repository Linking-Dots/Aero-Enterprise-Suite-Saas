<?php

namespace Aero\Core\Console\Commands;

use Aero\Core\Services\InstallationState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyInstallationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aero:verify-installation {--verbose=false}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Verify that the platform installation is complete and correct';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $verbose = $this->option('verbose');

        $this->info('=== Installation Verification Report ===');
        $this->newLine();

        $checks = [];

        // 1. Check installation lock file
        $checks['Installation Lock'] = InstallationState::isInstalled() ? '✓ Complete' : '✗ Incomplete';
        $this->line("  1. Installation Lock: {$checks['Installation Lock']}");

        // 2. Check database tables
        $requiredTables = [
            'users',
            'migrations',
            'installation_history',
            'module_installations',
        ];

        $missingTables = [];
        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $missingTables[] = $table;
            }
        }

        if (empty($missingTables)) {
            $checks['Database Tables'] = '✓ All present';
            $this->line("  2. Database Tables: {$checks['Database Tables']}");
        } else {
            $checks['Database Tables'] = '✗ Missing: '.implode(', ', $missingTables);
            $this->line("  2. Database Tables: <fg=red>{$checks['Database Tables']}</>");
        }

        // 3. Check migrations metadata
        if (Schema::hasColumn('migrations', 'installation_tag')) {
            $taggedCount = DB::table('migrations')
                ->whereNotNull('installation_tag')
                ->count();
            $totalCount = DB::table('migrations')->count();
            $checks['Migrations Tagged'] = "{$taggedCount}/{$totalCount}";

            $color = $taggedCount === $totalCount ? 'green' : 'yellow';
            $this->line("  3. Migrations Tagged: <fg={$color}>{$checks['Migrations Tagged']}</>");
        } else {
            $checks['Migrations Tagged'] = '✗ Metadata column missing';
            $this->line("  3. Migrations Tagged: <fg=red>{$checks['Migrations Tagged']}</>");
        }

        // 4. Check installation history
        if (Schema::hasTable('installation_history')) {
            $historyCount = DB::table('installation_history')->count();
            $checks['Installation History'] = $historyCount > 0 ? "✓ {$historyCount} records" : '⚠ No records';

            $color = $historyCount > 0 ? 'green' : 'yellow';
            $this->line("  4. Installation History: <fg={$color}>{$checks['Installation History']}</>");
        } else {
            $checks['Installation History'] = '✗ Table missing';
            $this->line("  4. Installation History: <fg=red>{$checks['Installation History']}</>");
        }

        // 5. Check module installations
        if (Schema::hasTable('module_installations')) {
            $moduleCount = DB::table('module_installations')->count();
            $activeModules = DB::table('module_installations')
                ->where('status', 'active')
                ->count();
            $checks['Modules'] = $moduleCount > 0 ? "✓ {$moduleCount} total ({$activeModules} active)" : '⚠ No modules';

            $color = $activeModules > 0 ? 'green' : 'yellow';
            $this->line("  5. Modules: <fg={$color}>{$checks['Modules']}</>");
        } else {
            $checks['Modules'] = '✗ Table missing';
            $this->line("  5. Modules: <fg=red>{$checks['Modules']}</>");
        }

        // 6. Check admin user
        $adminUser = DB::table('users')
            ->whereRaw("email LIKE '%@%' OR role_id IN (SELECT id FROM roles WHERE name = 'admin')")
            ->first();

        $checks['Admin User'] = $adminUser ? '✓ Exists' : '✗ Not found';
        $this->line("  6. Admin User: {$checks['Admin User']}");

        // 7. Check critical migrations
        $criticalTags = ['core:foundation', 'core:auth', 'platform:tenancy', 'platform:modules'];
        if (Schema::hasColumn('migrations', 'installation_tag')) {
            $criticalMigrations = DB::table('migrations')
                ->whereIn('installation_tag', $criticalTags)
                ->count();
            $checks['Critical Migrations'] = $criticalMigrations === count($criticalTags) ? '✓ All present' : "✗ {$criticalMigrations}/".count($criticalTags);

            $color = $criticalMigrations == count($criticalTags) ? 'green' : 'red';
            $this->line("  7. Critical Migrations: <fg={$color}>{$checks['Critical Migrations']}</>");
        }

        // 8. Check environment
        $mode = env('AERO_MODE', 'unknown');
        $checks['Installation Mode'] = $mode;
        $this->line("  8. Installation Mode: {$checks['Installation Mode']}");

        $this->newLine();
        $this->info('=== Summary ===');

        $passed = 0;
        $failed = 0;
        $warnings = 0;

        foreach ($checks as $name => $result) {
            if (strpos($result, '✓') === 0) {
                $passed++;
            } elseif (strpos($result, '✗') === 0) {
                $failed++;
            } else {
                $warnings++;
            }
        }

        $this->line("  <fg=green>Passed:</> {$passed}");
        $this->line("  <fg=yellow>Warnings:</> {$warnings}");
        $this->line("  <fg=red>Failed:</> {$failed}");

        $this->newLine();

        if ($failed === 0) {
            $this->info('<fg=green>✓ Installation verification complete. Platform is ready.</> Use "php artisan aero:tag-migrations" to tag existing migrations.');

            return 0;
        } else {
            $this->error('✗ Installation verification failed. Please address the issues above.');

            return 1;
        }
    }
}
