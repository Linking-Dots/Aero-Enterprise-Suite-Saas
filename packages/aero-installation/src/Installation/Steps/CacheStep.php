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
        $this->log('Generating config cache');
        try {
            Artisan::call('config:cache');
            $results['config_cached'] = true;
        } catch (\Exception $e) {
            $this->warn('Config cache failed: '.$e->getMessage());
            $results['config_cached'] = false;
        }

        // Generate route cache
        $this->log('Generating route cache');
        try {
            Artisan::call('route:cache');
            $results['route_cached'] = true;
        } catch (\Exception $e) {
            $this->warn('Route cache failed: '.$e->getMessage());
            $results['route_cached'] = false;
        }

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
