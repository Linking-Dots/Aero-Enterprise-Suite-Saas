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

        // Clear stale COMPILED caches only (config/route/view).
        //
        // Do NOT call `cache:clear`: it flushes the default application cache store,
        // which during the web install holds the installation orchestrator's own
        // cross-poll state (UnifiedInstallationController caches the orchestrator
        // there, keyed by session id). Flushing it mid-run destroys that state, so the
        // next poll can't find the orchestrator, falls back to the last 'completed'
        // step-progress row and reports the install finished — skipping FinalizeStep
        // and never writing the aeos.installed lock file. FinalizeStep already avoids
        // cache:clear for this exact reason; mirror that here.
        $this->log('Clearing stale compiled caches (config/route/view)');
        try {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
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
