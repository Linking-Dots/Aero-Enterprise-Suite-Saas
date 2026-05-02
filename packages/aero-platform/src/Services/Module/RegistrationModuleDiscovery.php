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
     * Foundation packages that should never be exposed as selectable products.
     */
    protected const EXCLUDED_PACKAGES = ['platform', 'ui', 'installation'];

    /**
     * Discover all product modules from composer.json.
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

            // Skip excluded foundation packages
            if (in_array($code, self::EXCLUDED_PACKAGES, true)) {
                continue;
            }

            // Skip core (already added)
            if ($code === 'core') {
                continue;
            }

            $modules->push($this->makeModuleFromPackage($code, $package));
        }

        return $modules->values();
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
     * Sync discovered modules into the central database modules table.
     * Upserts records so the table always reflects installed packages.
     */
    public function syncToDatabase(): void
    {
        foreach ($this->discover() as $module) {
            \Aero\Platform\Models\Module::updateOrCreate(
                ['code' => $module['code']],
                [
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'icon' => $module['icon'] ?? null,
                    'route_prefix' => $module['route_prefix'] ?? null,
                    'category' => $module['category'],
                    'priority' => $module['priority'],
                    'is_active' => $module['is_active'],
                    'is_core' => $module['is_core'],
                    'version' => $module['version'],
                    'scope' => $module['scope'] ?? 'tenant',
                ]
            );
        }
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
