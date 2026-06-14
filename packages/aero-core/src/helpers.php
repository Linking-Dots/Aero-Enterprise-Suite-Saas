<?php

// CANONICAL MODE SOURCE: storage/app/aeos.mode (written by FinalizeStep during install).
// ModeDetector in aero-installation uses this file + class_exists fallback during installation only.
// The running application ALWAYS uses these helpers, never ModeDetector.

/**
 * Helper Functions for Aero Module System
 *
 * These functions provide runtime support for the module system,
 * bridging SaaS and Standalone modes.
 */

use Aero\Core\Services\RuntimeLoader;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

if (! function_exists('aero_mode')) {
    /**
     * Get the current Aero mode (saas or standalone).
     * Mode is file-based and immutable at runtime.
     *
     * @return string 'saas' or 'standalone'
     */
    function aero_mode(): string
    {
        static $mode = null;

        if ($mode !== null) {
            return $mode;
        }

        // Primary: explicit mode file
        $modePath = storage_path('app/aeos.mode');
        if (file_exists($modePath)) {
            $v = trim(file_get_contents($modePath));
            if (in_array($v, ['saas', 'standalone'], true)) {
                return $mode = $v;
            }
        }

        // Fallback: detect from DB structure — SaaS always has a 'tenants' table in central connection
        try {
            if (Schema::connection('central')->hasTable('tenants')) {
                Log::warning('aeos.mode file missing -- recovered as SaaS from DB schema');

                return $mode = 'saas';
            }
        } catch (Throwable) {
            // Central connection unavailable — definitely not SaaS
        }

        return $mode = 'standalone';
    }
}

if (! function_exists('is_saas_mode')) {
    /**
     * Check if running in SaaS mode.
     */
    function is_saas_mode(): bool
    {
        return aero_mode() === 'saas';
    }
}

if (! function_exists('is_standalone_mode')) {
    /**
     * Check if running in standalone mode.
     */
    function is_standalone_mode(): bool
    {
        return aero_mode() === 'standalone';
    }
}

if (! function_exists('central_connection')) {
    /**
     * Resolve the connection name for central/landlord data (Axis B B2).
     *
     * SaaS: 'central' — registered by the platform layer against the landlord
     *       database. Tenant data lives in per-tenant databases; central data
     *       (tenants, plans, subscriptions, platform_* tables) lives here.
     *
     * Standalone: the DEFAULT connection. No platform layer is present, so the
     *       'central' connection is never registered and there is exactly ONE
     *       database — "central" and "tenant" data coexist in it. Returning the
     *       default connection lets central-flavoured code (AuditService,
     *       CentralModel subclasses shipped in standalone packages) run without a
     *       'Database connection [central] not configured' fatal.
     *
     * This is the single resolver every central reference should route through
     * instead of the literal 'central' string.
     */
    function central_connection(): string
    {
        if (is_saas_mode() && config('database.connections.central') !== null) {
            return 'central';
        }

        return config('database.default');
    }
}

if (! function_exists('getRuntimeModules')) {
    /**
     * Get all runtime-loaded modules for injection into Blade templates.
     */
    function getRuntimeModules(): array
    {
        // Only in standalone mode
        if (! is_standalone_mode()) {
            return [];
        }

        $modules = [];
        $modulesPath = base_path('modules');

        if (! File::isDirectory($modulesPath)) {
            return [];
        }

        $directories = File::directories($modulesPath);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            $distPath = $directory.'/dist';

            // Check if module has built assets
            if (File::isDirectory($distPath)) {
                $moduleConfig = [
                    'name' => $moduleName,
                    'path' => $directory,
                ];

                // Look for JS bundle
                $jsFiles = [
                    "modules/{$moduleName}/dist/{$moduleName}.umd.js",
                    "modules/{$moduleName}/dist/{$moduleName}.js",
                ];

                foreach ($jsFiles as $jsFile) {
                    if (File::exists(public_path($jsFile))) {
                        $moduleConfig['js'] = $jsFile;
                        break;
                    }
                }

                // Look for CSS bundle
                $cssFiles = [
                    "modules/{$moduleName}/dist/{$moduleName}.css",
                    "modules/{$moduleName}/dist/style.css",
                ];

                foreach ($cssFiles as $cssFile) {
                    if (File::exists(public_path($cssFile))) {
                        $moduleConfig['css'] = $cssFile;
                        break;
                    }
                }

                if (isset($moduleConfig['js'])) {
                    $modules[] = $moduleConfig;
                }
            }
        }

        return $modules;
    }
}

if (! function_exists('isModuleActive')) {
    /**
     * Check if a module is active in the current environment.
     */
    function isModuleActive(string $moduleName): bool
    {
        // In SaaS mode, check Composer packages
        if (is_saas_mode()) {
            return class_exists("Aero\\{$moduleName}\\ServiceProvider") ||
                   class_exists("Aero\\{$moduleName}\\Aero{$moduleName}ServiceProvider");
        }

        // In Standalone mode, check runtime loader
        $loader = app(RuntimeLoader::class);

        return $loader->isModuleLoaded(strtolower($moduleName));
    }
}

if (! function_exists('getActiveModules')) {
    /**
     * Get all active modules in the current environment.
     */
    function getActiveModules(): array
    {
        if (is_saas_mode()) {
            // In SaaS mode, modules are composer packages
            return config('modules.installed', []);
        }

        // In Standalone mode, get from runtime loader
        $loader = app(RuntimeLoader::class);

        return array_keys($loader->getLoadedModules());
    }
}

if (! function_exists('isPlatformActive')) {
    /**
     * Check if aero-platform is active (SaaS mode).
     *
     * Platform package may be installed but not active if user selected Standalone mode.
     * Mode file is the authoritative source, not package presence.
     */
    function isPlatformActive(): bool
    {
        return is_saas_mode();
    }
}

if (! function_exists('getTenantId')) {
    /**
     * Get the current tenant ID (works in both modes).
     */
    function getTenantId(): int|string|null
    {
        // Try stancl/tenancy first
        if (function_exists('tenant')) {
            try {
                $tenant = tenant();

                return $tenant?->getTenantKey();
            } catch (Throwable $e) {
                // Ignore
            }
        }

        // Try session
        if (session()->has('tenant_id')) {
            return session('tenant_id');
        }

        // Standalone default
        if (is_standalone_mode()) {
            return config('aero.standalone_tenant_id', 1);
        }

        return null;
    }
}

if (! function_exists('moduleAsset')) {
    /**
     * Generate URL to a module asset.
     */
    function moduleAsset(string $moduleName, string $path): string
    {
        $normalizedPath = ltrim($path, '/');

        if (is_standalone_mode()) {
            return asset("modules/{$moduleName}/{$normalizedPath}");
        }

        // In SaaS mode, assets are in public/vendor/{module}
        return asset("vendor/{$moduleName}/{$normalizedPath}");
    }
}

if (! function_exists('moduleRoute')) {
    /**
     * Generate a route URL for a module.
     */
    function moduleRoute(string $name, array $parameters = [], bool $absolute = true): string
    {
        // In SaaS mode with tenancy, routes may be tenant-scoped
        if (isPlatformActive() && function_exists('tenant')) {
            try {
                $tenant = tenant();
                if ($tenant) {
                    return route($name, $parameters, $absolute);
                }
            } catch (Throwable $e) {
                // Continue to standard route generation
            }
        }

        return route($name, $parameters, $absolute);
    }
}
