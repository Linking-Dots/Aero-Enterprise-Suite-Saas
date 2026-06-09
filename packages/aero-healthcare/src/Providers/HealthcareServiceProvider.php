<?php

namespace Aero\Healthcare\Providers;

use Illuminate\Support\ServiceProvider;

class HealthcareServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register healthcare services
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');


        // Publish configuration
        $this->publishes([
            __DIR__.'/../../config/healthcare.php' => config_path('healthcare.php'),
        ], 'config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'migrations');
    }
}
