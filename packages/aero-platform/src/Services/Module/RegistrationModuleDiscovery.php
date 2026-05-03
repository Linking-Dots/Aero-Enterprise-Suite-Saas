<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Module;

/** @phpstan-ignore-next-line */
use Illuminate\Support\Collection;
/** @phpstan-ignore-next-line */
use Illuminate\Support\Facades\File;

/**
 * Registration Module Discovery Service
 *
 * Discovers installed Aero packages from composer.json and maps them to module
 * definitions suitable for syncing to the central database modules table.
 *
 * Uses the same algorithm as the aero-installation package's ModuleDiscoveryStep
 * but scoped for the public registration flow:
 * - Always includes 'core'
 * - Excludes infrastructure packages (platform, ui, installation)
 * - Returns metadata matching the Module model schema
 */
class RegistrationModuleDiscovery
{
    /**
     * Discover all product modules from composer.json.
     * Only includes packages where extra.aero.category === 'product'.
     *
     * @return Collection<int, array>
     */
    public function discover(): Collection
    {
        $modules = new Collection();

        // Always include core
        $modules->push($this->makeCoreModule());

        // Discover aero packages from composer.json
        $composerJson = $this->readComposerJson();
        $aeroPackages = array_filter(
            array_keys($composerJson['require'] ?? []),
            fn (string $package) => str_starts_with($package, 'aero/')
        );

        foreach ($aeroPackages as $package) {
            $code = str_replace('aero/', '', $package);

            // Skip core (already added)
            if ($code === 'core') {
                continue;
            }

            // Only include product packages (read category from package's own composer.json)
            if ($this->getPackageCategory($code) !== 'product') {
                continue;
            }

            $modules->push($this->makeModuleFromPackage($code, $package));
        }

        return $modules->values();
    }

    /**
     * Read the category from a package's own composer.json.
     * Checks vendor/aero/{code}/composer.json and packages/aero-{code}/composer.json.
     */
    protected function getPackageCategory(string $code): ?string
    {
        $paths = [
            base_path("vendor/aero/{$code}/composer.json"),
            base_path("packages/aero-{$code}/composer.json"),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                try {
                    $content = File::get($path);
                    $data = json_decode($content, true);
                    if (is_array($data) && isset($data['extra']['aero']['category'])) {
                        return $data['extra']['aero']['category'];
                    }
                } catch (\Throwable $e) {
                    // Fall through to next path
                }
            }
        }

        return null;
    }

    /**
     * Build a map keyed by module code for quick lookups.
     *
     * @return array<string, array>
     */
    public function discoverMap(): array
    {
        return $this->discover()->keyBy('code')->toArray();
    }

  

    /**
     * Create the core module definition.
     */
    protected function makeCoreModule(): array
    {
        return [
            'code' => 'core',
            'name' => 'Core',
            'description' => 'Core platform module providing foundation services, authentication, and shared infrastructure.',
            'icon' => null,
            'route_prefix' => null,
            'category' => 'core_system',
            'priority' => 0,
            'is_active' => true,
            'is_core' => true,
            'version' => '1.0.0',
            'scope' => 'platform',
            'min_plan' => null,
            'license_type' => 'platform',
            'dependencies' => [],
        ];
    }

    /**
     * Create a module definition from a discovered composer package.
     */
    protected function makeModuleFromPackage(string $code, string $packageName): array
    {
        // Attempt to load richer metadata from the package's config/module.php
        $config = $this->loadModuleConfig($code);

        $name = $config['name'] ?? ucfirst(str_replace(['-', '_'], ' ', $code));
        $description = $config['description'] ?? "{$name} module";
        $icon = $config['icon'] ?? null;
        $routePrefix = $config['route_prefix'] ?? "/{$code}";
        $category = $config['category'] ?? 'other';
        $priority = $config['priority'] ?? 10;
        $version = $config['version'] ?? '1.0.0';
        $scope = $config['scope'] ?? 'tenant';

        return [
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'route_prefix' => $routePrefix,
            'category' => $category,
            'priority' => $priority,
            'is_active' => true,
            'is_core' => false,
            'version' => $version,
            'scope' => $scope,
            'min_plan' => $config['min_plan'] ?? null,
            'license_type' => $config['license_type'] ?? 'tenant',
            'dependencies' => $config['dependencies'] ?? [],
        ];
    }

    /**
     * Load module config from package config/module.php if available.
     */
    protected function loadModuleConfig(string $code): ?array
    {
        $paths = [
            base_path("vendor/aero/{$code}/config/module.php"),
            base_path("packages/aero-{$code}/config/module.php"),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                try {
                    $config = require $path;
                    if (is_array($config)) {
                        return $config;
                    }
                } catch (\Throwable $e) {
                    // Fall through to next path
                }
            }
        }

        return null;
    }

    /**
     * Read and parse the root composer.json.
     */
    protected function readComposerJson(): array
    {
        $path = base_path('composer.json');

        if (! File::exists($path)) {
            return [];
        }

        $content = File::get($path);
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }
}

