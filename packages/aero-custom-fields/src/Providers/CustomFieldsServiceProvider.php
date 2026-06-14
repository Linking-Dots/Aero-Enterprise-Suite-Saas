<?php

namespace Aero\CustomFields\Providers;

use Illuminate\Support\ServiceProvider;

class CustomFieldsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/module.php',
            'custom_fields.module'
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
