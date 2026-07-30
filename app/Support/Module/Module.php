<?php

namespace App\Support\Module;

/**
 * Module
 *
 * Represents a module package with its manifest and path.
 * Provides access to module metadata and configuration.
 */
class Module
{
    protected ModuleManifest $manifest;
    protected string $path;
    protected bool $enabled = true;

    public function __construct(ModuleManifest $manifest, string $path)
    {
        $this->manifest = $manifest;
        $this->path = $path;
    }

    /**
     * Get module code
     */
    public function getCode(): string
    {
        return $this->manifest->getCode();
    }

    /**
     * Get module name
     */
    public function getName(): string
    {
        return $this->manifest->getName();
    }

    /**
     * Get module description
     */
    public function getDescription(): string
    {
        return $this->manifest->getDescription();
    }

    /**
     * Get module version
     */
    public function getVersion(): string
    {
        return $this->manifest->getVersion();
    }

    /**
     * Get module type
     */
    public function getType(): string
    {
        return $this->manifest->getType();
    }

    /**
     * Check if module is standalone
     */
    public function isStandalone(): bool
    {
        return $this->manifest->isStandalone();
    }

    /**
     * Get module dependencies
     */
    public function getDependencies(): array
    {
        return $this->manifest->getDependencies();
    }

    /**
     * Get services provided by module
     */
    public function getProvidedServices(): array
    {
        return $this->manifest->getProvidedServices();
    }

    /**
     * Get APIs provided by module
     */
    public function getProvidedApis(): array
    {
        return $this->manifest->getProvidedApis();
    }

    /**
     * Get module path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get module manifest
     */
    public function getManifest(): ModuleManifest
    {
        return $this->manifest;
    }

    /**
     * Get full path to migrations directory
     */
    public function getMigrationsPath(): string
    {
        return $this->path . '/' . $this->manifest->getMigrationsPath();
    }

    /**
     * Get full path to seeders directory
     */
    public function getSeedersPath(): string
    {
        return $this->path . '/' . $this->manifest->getSeedersPath();
    }

    /**
     * Get full path to web routes file
     */
    public function getWebRoutesPath(): ?string
    {
        $file = $this->manifest->getWebRoutes();
        return $file ? $this->path . '/' . $file : null;
    }

    /**
     * Get full path to API routes file
     */
    public function getApiRoutesPath(): ?string
    {
        $file = $this->manifest->getApiRoutes();
        return $file ? $this->path . '/' . $file : null;
    }

    /**
     * Get full path to tenant routes file
     */
    public function getTenantRoutesPath(): ?string
    {
        $file = $this->manifest->getTenantRoutes();
        return $file ? $this->path . '/' . $file : null;
    }

    /**
     * Get service provider class name
     */
    public function getServiceProviderClass(): string
    {
        $namespace = $this->getNamespace();
        $name = str_replace(' ', '', $this->getName());
        return "{$namespace}\\Providers\\{$name}ServiceProvider";
    }

    /**
     * Get module namespace
     */
    public function getNamespace(): string
    {
        return "Modules\\" . str_replace(' ', '', $this->getName());
    }

    /**
     * Enable module
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable module
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if module is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if module has specific dependency
     */
    public function hasDependency(string $code): bool
    {
        return isset($this->getDependencies()[$code]);
    }

    /**
     * Check if module provides specific service
     */
    public function providesService(string $service): bool
    {
        return in_array($service, $this->getProvidedServices());
    }

    /**
     * Get module as array
     */
    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'version' => $this->getVersion(),
            'type' => $this->getType(),
            'standalone' => $this->isStandalone(),
            'enabled' => $this->isEnabled(),
            'path' => $this->getPath(),
            'dependencies' => $this->getDependencies(),
            'services' => $this->getProvidedServices(),
            'apis' => $this->getProvidedApis(),
        ];
    }

    /**
     * Convert module to JSON
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return "{$this->getName()} ({$this->getCode()}) v{$this->getVersion()}";
    }
}
