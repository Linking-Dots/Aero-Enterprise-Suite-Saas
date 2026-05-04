<?php

namespace Aero\TimeTracking\Providers;

use Illuminate\Support\ServiceProvider;

class TimeTrackingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/module.php',
            'time_tracking.module'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'time_tracking');
    }
}
