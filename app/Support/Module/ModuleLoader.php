<?php

namespace App\Support\Module;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use RuntimeException;

/**
 * Module Loader
 *
 * Handles loading and bootstrapping of modules.
 * Loads module service providers, routes, and assets.
 */
class ModuleLoader
{
    protected Application $app;
    protected ModuleRegistry $registry;

    public function __construct(Application $app, ModuleRegistry $registry)
    {
        $this->app = $app;
        $this->registry = $registry;
    }

    /**
     * Load all enabled modules
     */
    public function loadAll(): void
    {
        $modules = $this->registry->enabled()->all();

        foreach ($modules as $module) {
            $this->load($module);
        }
    }

    /**
     * Load a specific module
     */
    public function load(Module $module): void
    {
        // Check if already loaded
        if ($this->registry->isLoaded($module->getCode())) {
            return;
        }

        // Load dependencies first
        $this->loadDependencies($module);

        // Load module service provider
        $this->loadServiceProvider($module);

        // Mark as loaded
        $this->registry->markAsLoaded($module->getCode());
    }

    /**
     * Load module dependencies
     */
    protected function loadDependencies(Module $module): void
    {
        foreach ($module->getDependencies() as $depCode => $version) {
            $dependency = $this->registry->get($depCode);

            if (!$dependency) {
                throw new RuntimeException(
                    "Module dependency '{$depCode}' not found for module '{$module->getCode()}'"
                );
            }

            if (!$this->versionMatches($dependency->getVersion(), $version)) {
                throw new RuntimeException(
                    "Module dependency version mismatch: {$depCode} requires {$version}, found {$dependency->getVersion()}"
                );
            }

            // Load dependency if not already loaded
            if (!$this->registry->isLoaded($depCode)) {
                $this->load($dependency);
            }
        }
    }

    /**
     * Load module service provider
     */
    protected function loadServiceProvider(Module $module): void
    {
        $providerClass = $module->getServiceProviderClass();

        if (!class_exists($providerClass)) {
            // Try to autoload the class
            $providerFile = $module->getPath() . '/Providers/' . class_basename($providerClass) . '.php';
            
            if (file_exists($providerFile)) {
                require_once $providerFile;
            }
        }

        if (class_exists($providerClass)) {
            $this->app->register($providerClass);
        }
    }

    /**
     * Check if version matches constraint
     */
    protected function versionMatches(string $version, string $constraint): bool
    {
        // Remove 'v' prefix if present
        $version = ltrim($version, 'v');
        $constraint = ltrim($constraint, 'v');

        // Handle ^ constraint (compatible version)
        if (str_starts_with($constraint, '^')) {
            $constraint = ltrim($constraint, '^');
            return version_compare($version, $constraint, '>=');
        }

        // Handle ~ constraint (minor version)
        if (str_starts_with($constraint, '~')) {
            $constraint = ltrim($constraint, '~');
            $parts = explode('.', $constraint);
            $majorMinor = implode('.', array_slice($parts, 0, 2));
            return version_compare($version, $majorMinor, '>=');
        }

        // Handle >= constraint
        if (str_starts_with($constraint, '>=')) {
            $constraint = ltrim($constraint, '>=');
            return version_compare($version, trim($constraint), '>=');
        }

        // Handle > constraint
        if (str_starts_with($constraint, '>')) {
            $constraint = ltrim($constraint, '>');
            return version_compare($version, trim($constraint), '>');
        }

        // Exact match
        return version_compare($version, $constraint, '=');
    }

    /**
     * Reload a module (useful for hot-reload in development)
     */
    public function reload(Module $module): void
    {
        // This is a simplified implementation
        // Full hot-reload would require more complex logic
        $this->load($module);
    }

    /**
     * Load modules for a specific tenant
     */
    public function loadForTenant($tenant): void
    {
        $tenantModules = $this->getTenantModules($tenant);

        foreach ($tenantModules as $moduleCode) {
            $module = $this->registry->get($moduleCode);
            
            if ($module && $module->isEnabled()) {
                $this->load($module);
            }
        }
    }

    /**
     * Get enabled modules for a tenant
     */
    protected function getTenantModules($tenant): array
    {
        // Get modules from tenant subscription plan
        $planModules = [];
        if ($tenant->plan_id) {
            $plan = \App\Models\Plan::find($tenant->plan_id);
            if ($plan) {
                $planModules = $plan->modules()
                    ->where('is_active', true)
                    ->pluck('code')
                    ->toArray();
            }
        }

        // Get custom modules enabled for tenant
        $customModules = $tenant->modules ?? [];

        // Merge and return unique modules
        return array_unique(array_merge($planModules, $customModules));
    }
}
