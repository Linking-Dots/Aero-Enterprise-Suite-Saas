<?php

namespace Aero\Platform\Console\Commands;

use Aero\HRMAC\Console\Commands\SyncModuleHierarchy;
use Aero\HRMAC\Models\Module;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup {--fresh : Run a fresh migration (drops all tables)} 
                                   {--seed : Run seeders only without migrations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the application with all required data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting aeos365 application setup...');

        // Check if --fresh flag is set
        if ($this->option('fresh')) {
            $this->warn('Fresh install option detected. This will drop all tables!');
            if ($this->confirm('Are you sure you want to continue?', true)) {
                $this->info('Running fresh migrations...');
                Artisan::call('migrate:fresh', ['--force' => true]);
                $this->info(Artisan::output());
            } else {
                $this->info('Operation cancelled.');

                return 1;
            }
        } elseif (! $this->option('seed')) {
            // Run migrations if not in seed-only mode
            $this->info('Running migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $this->info(Artisan::output());
        }

        // Run the setup seeder
        $this->info('Running setup seeder...');
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\SetupSeeder',
            '--force' => true,
        ]);
        $this->info(Artisan::output());

        // Sync platform modules using HRMAC
        $this->info('Synchronizing platform modules...');
        $this->syncPlatformModules();

        $this->info('✅ aeos365 application has been set up successfully!');

        // Output final information
        $this->info('Application is now ready to use.');
        $this->info('You can login with admin@example.com / password');

        // Run a development server if in local environment
        if (app()->environment('local')) {
            if ($this->confirm('Would you like to start a development server now?', false)) {
                $this->info('Starting development server...');
                $this->line('Press Ctrl+C to stop the server');
                Artisan::call('serve');
            }
        }

        return 0;
    }

    /**
     * Sync platform modules using HRMAC package.
     * Platform modules (scope: platform) are synced to the landlord database.
     */
    private function syncPlatformModules(): void
    {
        try {
            // Check if HRMAC package is available
            if (class_exists(SyncModuleHierarchy::class)) {
                $this->line('   Using HRMAC sync (scope: platform)...');

                Artisan::call('aero:sync-module', [
                    '--scope' => 'platform',  // Only platform-scoped modules
                    '--prune' => true,
                ]);

                $moduleCount = Module::where('scope', 'platform')->count();
                $this->line("   ✅ {$moduleCount} platform module(s) synchronized");
            } else {
                $this->warn('   HRMAC package not available, skipping module sync');
            }
        } catch (\Exception $e) {
            $this->error('   Platform module sync failed: '.$e->getMessage());
        }
    }
}
