<?php

declare(strict_types=1);

namespace Aero\Core\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers Octane request-flush callbacks for aero-core singletons.
 *
 * These singletons hold per-request state that must be cleared between
 * Octane requests to prevent data leaking from one request to the next.
 */
class AeroOctaneServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! class_exists('Laravel\Octane\Octane')) {
            return;
        }

        \Laravel\Octane\Octane::flush([
            // ModuleRegistry caches tenant module state per-request
            \Aero\Core\Services\ModuleRegistry::class,
        ]);
    }
}
