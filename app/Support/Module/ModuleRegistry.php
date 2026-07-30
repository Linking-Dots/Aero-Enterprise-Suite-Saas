<?php

namespace App\Support\Module;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Module Registry
 *
 * Central registry for all modules in the application.
 * Discovers, registers, and manages module lifecycle.
 */
class ModuleRegistry
{
    protected Collection $modules;
    protected string $modulesPath;
    protected array $loaded = [];

    public function __construct()
    {
        $this->modules = collect();
        $this->modulesPath = base_path('modules');
    }

    /**
     * Discover all modules in the modules directory
     */
    public function discover(): self
    {
        if (!File::isDirectory($this->modulesPath)) {
            return $this;
        }

        $directories = File::directories($this->modulesPath);

        foreach ($directories as $directory) {
            $manifestPath = $directory . '/module.json';
            
            if (File::exists($manifestPath)) {
                $this->registerFromManifest($manifestPath, $directory);
            }
        }

        return $this;
    }

    /**
     * Register a module from its manifest file
     */
    protected function registerFromManifest(string $manifestPath, string $modulePath): void
    {
        try {
            $manifest = new ModuleManifest($manifestPath);
            $module = new Module($manifest, $modulePath);
            
            $this->register($module);
        } catch (\Exception $e) {
            // Log error but don't stop discovery
            logger()->error("Failed to register module from {$manifestPath}: " . $e->getMessage());
        }
    }

    /**
     * Register a module
     */
    public function register(Module $module): self
    {
        $this->modules->put($module->getCode(), $module);
        return $this;
    }

    /**
     * Get a module by code
     */
    public function get(string $code): ?Module
    {
        return $this->modules->get($code);
    }

    /**
     * Get all registered modules
     */
    public function all(): Collection
    {
        return $this->modules;
    }

    /**
     * Get enabled modules
     */
    public function enabled(): Collection
    {
        return $this->modules->filter(fn($module) => $module->isEnabled());
    }

    /**
     * Get disabled modules
     */
    public function disabled(): Collection
    {
        return $this->modules->filter(fn($module) => !$module->isEnabled());
    }

    /**
     * Check if a module exists
     */
    public function has(string $code): bool
    {
        return $this->modules->has($code);
    }

    /**
     * Get module count
     */
    public function count(): int
    {
        return $this->modules->count();
    }

    /**
     * Get modules by type
     */
    public function byType(string $type): Collection
    {
        return $this->modules->filter(fn($module) => $module->getType() === $type);
    }

    /**
     * Get standalone modules
     */
    public function standalone(): Collection
    {
        return $this->modules->filter(fn($module) => $module->isStandalone());
    }

    /**
     * Find modules that provide a specific service
     */
    public function providingService(string $service): Collection
    {
        return $this->modules->filter(function($module) use ($service) {
            return in_array($service, $module->getProvidedServices());
        });
    }

    /**
     * Find modules that depend on a specific module
     */
    public function dependingOn(string $moduleCode): Collection
    {
        return $this->modules->filter(function($module) use ($moduleCode) {
            $dependencies = $module->getDependencies();
            return isset($dependencies[$moduleCode]);
        });
    }

    /**
     * Get modules sorted by dependency order
     * Returns modules in the order they should be loaded
     */
    public function sorted(): Collection
    {
        $sorted = collect();
        $visited = [];
        $temp = [];

        $visit = function($moduleCode) use (&$visit, &$sorted, &$visited, &$temp) {
            if (isset($temp[$moduleCode])) {
                throw new RuntimeException("Circular dependency detected for module: {$moduleCode}");
            }

            if (isset($visited[$moduleCode])) {
                return;
            }

            $temp[$moduleCode] = true;

            $module = $this->get($moduleCode);
            if ($module) {
                foreach ($module->getDependencies() as $depCode => $version) {
                    $visit($depCode);
                }

                unset($temp[$moduleCode]);
                $visited[$moduleCode] = true;
                $sorted->push($module);
            }
        };

        foreach ($this->modules->keys() as $moduleCode) {
            if (!isset($visited[$moduleCode])) {
                $visit($moduleCode);
            }
        }

        return $sorted;
    }

    /**
     * Mark module as loaded
     */
    public function markAsLoaded(string $code): void
    {
        $this->loaded[$code] = true;
    }

    /**
     * Check if module is loaded
     */
    public function isLoaded(string $code): bool
    {
        return isset($this->loaded[$code]);
    }

    /**
     * Get loaded modules
     */
    public function loaded(): array
    {
        return array_keys($this->loaded);
    }

    /**
     * Clear the registry
     */
    public function clear(): void
    {
        $this->modules = collect();
        $this->loaded = [];
    }

    /**
     * Export registry to array
     */
    public function toArray(): array
    {
        return $this->modules->map(fn($module) => $module->toArray())->all();
    }
}
