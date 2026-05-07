<?php

namespace Aero\Core\Manifests;

final class ProductManifest
{
    /**
     * @param  string  $id  Matches license server product ID (e.g. 'aero-hrm')
     * @param  string  $name  Human-readable name (e.g. 'Aero HRM Suite')
     * @param  string  $version  Current installed version (semver)
     * @param  string[]  $bundledModules  Module codes included in this product
     * @param  string  $licenseServer  URL of the license server for this product
     * @param  string  $updateServer  URL of the update server for this product
     * @param  string  $edition  'saas' | 'standalone' | 'both'
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $version,
        public readonly array $bundledModules,
        public readonly string $licenseServer,
        public readonly string $updateServer,
        public readonly string $edition,
    ) {}

    public static function fromConfig(array $config): self
    {
        $required = ['id', 'name', 'version', 'bundled_modules', 'license_server', 'update_server', 'edition'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \InvalidArgumentException("Product manifest missing required key: [{$key}]");
            }
        }

        return new self(
            id: $config['id'],
            name: $config['name'],
            version: $config['version'],
            bundledModules: $config['bundled_modules'],
            licenseServer: $config['license_server'],
            updateServer: $config['update_server'],
            edition: $config['edition'],
        );
    }

    public function supportsStandalone(): bool
    {
        return in_array($this->edition, ['standalone', 'both'], true);
    }

    public function supportsSaaS(): bool
    {
        return in_array($this->edition, ['saas', 'both'], true);
    }
}
