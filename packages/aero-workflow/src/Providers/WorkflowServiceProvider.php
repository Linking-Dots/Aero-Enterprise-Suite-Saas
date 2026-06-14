<?php

namespace Aero\Workflow\Providers;

use Illuminate\Support\ServiceProvider;

class WorkflowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/module.php',
            'workflow.module'
        );
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

    }
}
