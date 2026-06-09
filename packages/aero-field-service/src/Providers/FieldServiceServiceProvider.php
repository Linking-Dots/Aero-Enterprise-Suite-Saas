<?php

namespace Aero\FieldService\Providers;

use Illuminate\Support\ServiceProvider;

class FieldServiceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register field service services
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
            __DIR__.'/../../config/field-service.php' => config_path('field-service.php'),
        ], 'config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'migrations');
    }
}
