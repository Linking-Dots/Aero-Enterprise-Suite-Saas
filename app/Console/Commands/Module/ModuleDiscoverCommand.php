<?php

namespace App\Console\Commands\Module;

use App\Support\Module\ModuleRegistry;
use Illuminate\Console\Command;

class ModuleDiscoverCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'module:discover';

    /**
     * The console command description.
     */
    protected $description = 'Discover and register all modules';

    /**
     * Execute the console command.
     */
    public function handle(ModuleRegistry $registry): int
    {
        $this->info('Discovering modules...');

        $registry->discover();

        $modules = $registry->all();

        if ($modules->isEmpty()) {
            $this->warn('No modules found.');
            return 0;
        }

        $this->info("Found {$modules->count()} module(s):");
        $this->newLine();

        $this->table(
            ['Code', 'Name', 'Version', 'Type', 'Standalone'],
            $modules->map(function ($module) {
                return [
                    $module->getCode(),
                    $module->getName(),
                    $module->getVersion(),
                    $module->getType(),
                    $module->isStandalone() ? 'Yes' : 'No',
                ];
            })->toArray()
        );

        return 0;
    }
}
