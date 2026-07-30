<?php

namespace App\Console\Commands\Module;

use App\Support\Module\ModuleRegistry;
use Illuminate\Console\Command;

class ModuleListCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'module:list
                            {--enabled : Show only enabled modules}
                            {--disabled : Show only disabled modules}
                            {--standalone : Show only standalone modules}';

    /**
     * The console command description.
     */
    protected $description = 'List all registered modules';

    /**
     * Execute the console command.
     */
    public function handle(ModuleRegistry $registry): int
    {
        $registry->discover();

        // Filter modules based on options
        $modules = $registry->all();

        if ($this->option('enabled')) {
            $modules = $registry->enabled();
        } elseif ($this->option('disabled')) {
            $modules = $registry->disabled();
        } elseif ($this->option('standalone')) {
            $modules = $registry->standalone();
        }

        if ($modules->isEmpty()) {
            $this->warn('No modules found.');
            return 0;
        }

        $this->info("Total modules: {$modules->count()}");
        $this->newLine();

        $this->table(
            ['Code', 'Name', 'Version', 'Type', 'Enabled', 'Standalone', 'Dependencies'],
            $modules->map(function ($module) {
                return [
                    $module->getCode(),
                    $module->getName(),
                    $module->getVersion(),
                    $module->getType(),
                    $module->isEnabled() ? '✓' : '✗',
                    $module->isStandalone() ? '✓' : '✗',
                    count($module->getDependencies()),
                ];
            })->toArray()
        );

        return 0;
    }
}
