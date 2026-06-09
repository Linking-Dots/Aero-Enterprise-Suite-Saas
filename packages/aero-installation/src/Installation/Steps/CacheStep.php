<?php

namespace Aero\Installation\Installation\Steps;

use Illuminate\Support\Facades\Artisan;

/**
 * Cache Step
 *
 * Warms application caches for optimal performance
 */
class CacheStep extends BaseInstallationStep
{
    public function name(): string
    {
        return 'cache';
    }

    public function description(): string
    {
        return 'Warm application caches';
    }

    public function order(): int
    {
        return 11;
    }

    public function dependencies(): array
    {
        return ['config', 'migration', 'settings'];
    }

    public function execute(): array
    {
        $results = [];

        // Clear old caches
        $this->log('Clearing old caches');
        try {
            Artisan::call('cache:clear');
            $results['cache_cleared'] = true;
        } catch (\Exception $e) {
            $this->warn('Cache clear failed: '.$e->getMessage());
            $results['cache_cleared'] = false;
        }

        // Generate config cache
        // NOTE: We intentionally skip route:cache and config:cache here. At this point
        // storage/app/aeos.installed does not exist yet, so service
        // providers (AeroPlatformServiceProvider) skip registering
        // platform/tenant routes and config bindings. Caching now would permanently
        // omit/corrupt them. They are deferred to FinalizeStep after lock files exist.
        $results['config_cached'] = 'deferred';

        return $results;
    }

    public function validate(): bool
    {
        // Check that cache directories are writable
        $cachePaths = [
            storage_path('framework/cache'),
            storage_path('framework/views'),
        ];

        foreach ($cachePaths as $path) {
            if (! is_writable($path ?? '')) {
                return false;
            }
        }

        return true;
    }

    public function canSkip(): bool
    {
        return true;
    }
}
