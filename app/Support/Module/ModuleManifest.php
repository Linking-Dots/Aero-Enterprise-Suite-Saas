<?php

namespace App\Support\Module;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

/**
 * Module Manifest
 *
 * Represents the module.json metadata file for a module.
 * Contains module information, dependencies, features, and configuration.
 */
class ModuleManifest
{
    protected array $data;
    protected string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->load();
    }

    /**
     * Load manifest from module.json file
     */
    protected function load(): void
    {
        if (!File::exists($this->path)) {
            throw new InvalidArgumentException("Module manifest not found at: {$this->path}");
        }

        $content = File::get($this->path);
        $this->data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Invalid JSON in module manifest: " . json_last_error_msg());
        }

        $this->validate();
    }

    /**
     * Validate required manifest fields
     */
    protected function validate(): void
    {
        $required = ['name', 'code', 'version', 'description'];

        foreach ($required as $field) {
            if (!isset($this->data[$field])) {
                throw new InvalidArgumentException("Module manifest missing required field: {$field}");
            }
        }
    }

    /**
     * Get module name
     */
    public function getName(): string
    {
        return $this->data['name'];
    }

    /**
     * Get module code (unique identifier)
     */
    public function getCode(): string
    {
        return $this->data['code'];
    }

    /**
     * Get module version
     */
    public function getVersion(): string
    {
        return $this->data['version'];
    }

    /**
     * Get module description
     */
    public function getDescription(): string
    {
        return $this->data['description'];
    }

    /**
     * Get module type (business, utility, integration, etc.)
     */
    public function getType(): string
    {
        return $this->data['type'] ?? 'business';
    }

    /**
     * Check if module can run standalone
     */
    public function isStandalone(): bool
    {
        return $this->data['standalone'] ?? false;
    }

    /**
     * Get module authors
     */
    public function getAuthors(): array
    {
        return $this->data['authors'] ?? [];
    }

    /**
     * Get module dependencies
     */
    public function getDependencies(): array
    {
        return $this->data['dependencies'] ?? [];
    }

    /**
     * Get services provided by this module
     */
    public function getProvidedServices(): array
    {
        return $this->data['provides']['services'] ?? [];
    }

    /**
     * Get APIs provided by this module
     */
    public function getProvidedApis(): array
    {
        return $this->data['provides']['apis'] ?? [];
    }

    /**
     * Get module requirements (PHP, Laravel, etc.)
     */
    public function getRequirements(): array
    {
        return $this->data['requires'] ?? [];
    }

    /**
     * Get module features
     */
    public function getFeatures(): array
    {
        return $this->data['features'] ?? [];
    }

    /**
     * Get features for specific plan
     */
    public function getFeaturesForPlan(string $plan): array
    {
        return $this->data['plans'][$plan] ?? [];
    }

    /**
     * Get all plans
     */
    public function getPlans(): array
    {
        return $this->data['plans'] ?? [];
    }

    /**
     * Get database configuration
     */
    public function getDatabaseConfig(): array
    {
        return $this->data['database'] ?? [];
    }

    /**
     * Get migrations path
     */
    public function getMigrationsPath(): string
    {
        return $this->data['database']['migrations_path'] ?? 'Database/Migrations';
    }

    /**
     * Get seeders path
     */
    public function getSeedersPath(): string
    {
        return $this->data['database']['seeders_path'] ?? 'Database/Seeders';
    }

    /**
     * Get routes configuration
     */
    public function getRoutes(): array
    {
        return $this->data['routes'] ?? [];
    }

    /**
     * Get web routes file
     */
    public function getWebRoutes(): ?string
    {
        return $this->data['routes']['web'] ?? null;
    }

    /**
     * Get API routes file
     */
    public function getApiRoutes(): ?string
    {
        return $this->data['routes']['api'] ?? null;
    }

    /**
     * Get tenant routes file
     */
    public function getTenantRoutes(): ?string
    {
        return $this->data['routes']['tenant'] ?? null;
    }

    /**
     * Get assets configuration
     */
    public function getAssets(): array
    {
        return $this->data['assets'] ?? [];
    }

    /**
     * Get JavaScript entry file
     */
    public function getJsEntry(): ?string
    {
        return $this->data['assets']['js'] ?? null;
    }

    /**
     * Get CSS entry file
     */
    public function getCssEntry(): ?string
    {
        return $this->data['assets']['css'] ?? null;
    }

    /**
     * Get config files to publish
     */
    public function getPublishableConfigs(): array
    {
        return $this->data['config']['publish'] ?? [];
    }

    /**
     * Get all manifest data
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get manifest data as collection
     */
    public function toCollection(): Collection
    {
        return collect($this->data);
    }

    /**
     * Check if manifest has specific key
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get specific manifest value
     */
    public function get(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    /**
     * Save manifest to file
     */
    public function save(): bool
    {
        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return File::put($this->path, $json) !== false;
    }

    /**
     * Update manifest data
     */
    public function set(string $key, $value): self
    {
        data_set($this->data, $key, $value);
        return $this;
    }
}
