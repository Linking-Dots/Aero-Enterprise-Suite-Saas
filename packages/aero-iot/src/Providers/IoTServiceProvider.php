<?php

namespace Aero\IoT\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class IoTServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/iot.php', 'iot'
        );
    }

    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');


        // Register publishable assets
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/iot.php' => config_path('iot.php'),
            ], 'iot-config');

            $this->publishes([
                __DIR__.'/../../database/migrations' => database_path('migrations'),
            ], 'iot-migrations');

            $this->publishes([
                __DIR__.'/../../resources/js' => resource_path('js/iot'),
            ], 'iot-assets');
        }

        // Register morph map for polymorphic relations
        Relation::morphMap([
            'device' => \Aero\IoT\Models\Device::class,
            'sensor' => \Aero\IoT\Models\Sensor::class,
            'gateway' => \Aero\IoT\Models\IoTGateway::class,
        ]);

        // Register IoT event listeners
        $this->registerEventListeners();

        // Register IoT commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // IoT-specific Artisan commands would go here
                // \Aero\IoT\Console\Commands\ProcessTelemetryCommand::class,
                // \Aero\IoT\Console\Commands\CheckDeviceHealthCommand::class,
            ]);
        }
    }

    protected function registerEventListeners(): void
    {
        // Register IoT-specific event listeners
        // Example: Device status changes, sensor alerts, etc.

        // Event::listen(DeviceStatusChanged::class, NotifyDeviceStatusListener::class);
        // Event::listen(SensorAlertTriggered::class, ProcessSensorAlertListener::class);
    }
}
