<?php

namespace Aero\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class InstallationState
{
    private const CACHE_KEY = 'aeos.installed';

    /**
     * Check whether the application is installed.
     *
     * Uses a file-based cache so the result survives PHP-FPM restarts.
     * Falls back to DB schema check if the legacy flat file is absent.
     */
    public static function isInstalled(): bool
    {
        $legacyFile = storage_path('app/aeos.installed');

        // Fast path: file check requires no service container — safe during register() phase.
        if (file_exists($legacyFile)) {
            return true;
        }

        // Cache-backed schema check (only reachable when the file doesn't exist yet).
        // CacheServiceProvider is deferred, so wrap in try/catch for early-boot safety.
        try {
            return Cache::store('file')->rememberForever(self::CACHE_KEY, function () {
                try {
                    return Schema::hasTable('users') && Schema::hasTable('modules');
                } catch (\Throwable) {
                    return false;
                }
            });
        } catch (\Throwable) {
            // Cache not yet registered (very early boot) — fall back to direct schema check.
            try {
                return Schema::hasTable('users') && Schema::hasTable('modules');
            } catch (\Throwable) {
                return false;
            }
        }
    }

    /**
     * Mark the application as installed.
     * Writes both the cache entry and the legacy flat file.
     */
    public static function markInstalled(): void
    {
        Cache::store('file')->forever(self::CACHE_KEY, true);
        file_put_contents(storage_path('app/aeos.installed'), now()->toIso8601String());
    }

    /**
     * Clear the installation state cache (use during testing or uninstall).
     */
    public static function clear(): void
    {
        Cache::store('file')->forget(self::CACHE_KEY);
    }
}
