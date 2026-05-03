<?php

namespace Aero\UI;

use Illuminate\Support\ServiceProvider;

/**
 * Aero UI Service Provider
 *
 * This package contains ALL frontend code for the aeos365:
 * - React components, pages, hooks, contexts
 * - The single app.blade.php view for Inertia
 * - CSS and theme configuration
 * - Vite configuration stubs
 *
 * All other Aero packages (core, platform, hrm, etc.) are backend-only
 * and reference this package's view for rendering.
 */
class AeroUIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register the views - this allows Inertia to use 'aero-ui::app'
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aero-ui');

        // Ensure Vite configuration exists in host app (auto-install)
        $this->ensureViteConfiguration();

        // Publish views for customization (optional)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/aero-ui'),
            ], 'aero-ui-views');

            // Publish vite.config.js and package.json (optional manual publish)
            $this->publishes([
                __DIR__.'/../stubs/vite.config.js.stub' => base_path('vite.config.js'),
                __DIR__.'/../stubs/package.json.stub' => base_path('package.json'),
            ], 'aero-ui-vite');
        }
    }

    /**
     * Ensure Vite configuration files exist in the host app.
     * Automatically installs vite.config.js and package.json from stubs if missing or outdated.
     */
    protected function ensureViteConfiguration(): void
    {
        // Skip during testing or if not a web request
        if ($this->app->runningUnitTests()) {
            return;
        }

        $viteConfigPath = base_path('vite.config.js');
        $packageJsonPath = base_path('package.json');
        $stubsPath = __DIR__.'/../stubs';

        // Check if vite.config.js needs to be installed
        if (! file_exists($viteConfigPath) || ! $this->isAeroUIViteConfig($viteConfigPath)) {
            $stubFile = $stubsPath.'/vite.config.js.stub';
            if (file_exists($stubFile)) {
                copy($stubFile, $viteConfigPath);
                if ($this->app->runningInConsole()) {
                    echo "[Aero UI] Installed vite.config.js\n";
                }
            }
        }

        // Check if package.json needs to be installed/updated
        if (! file_exists($packageJsonPath) || ! $this->hasAeroUIDependencies($packageJsonPath)) {
            $stubFile = $stubsPath.'/package.json.stub';
            if (file_exists($stubFile)) {
                copy($stubFile, $packageJsonPath);
                if ($this->app->runningInConsole()) {
                    echo "[Aero UI] Installed package.json - run 'npm install' to install dependencies\n";
                }
            }
        }
    }

    /**
     * Check if vite.config.js is configured for Aero UI.
     */
    protected function isAeroUIViteConfig(string $path): bool
    {
        $content = file_get_contents($path);

        return str_contains($content, 'vendor/aero/ui') || str_contains($content, 'aero/ui');
    }

    /**
     * Check if package.json has Aero UI dependencies.
     */
    protected function hasAeroUIDependencies(string $path): bool
    {
        $content = file_get_contents($path);
        $json = json_decode($content, true);

        if (! $json) {
            return false;
        }

        // Check for key dependencies that indicate Aero UI setup
        return isset($json['dependencies']['@heroui/react'])
            && isset($json['dependencies']['framer-motion'])
            && isset($json['dependencies']['@inertiajs/react']);
    }
}
