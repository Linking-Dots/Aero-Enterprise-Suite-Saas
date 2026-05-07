<?php

namespace Aero\Core\Services;

use Aero\Core\Manifests\ProductManifest;

class ProductManifestLoader
{
    private ?ProductManifest $cached = null;

    public function load(): ProductManifest
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $config = config('product');

        if (empty($config)) {
            $config = $this->defaultPlatformManifest();
        }

        return $this->cached = ProductManifest::fromConfig($config);
    }

    private function defaultPlatformManifest(): array
    {
        return [
            'id' => 'aeos-platform',
            'name' => 'AEOS365 Platform',
            'version' => config('app.version', '1.0.0'),
            'bundled_modules' => ['*'],
            'license_server' => config('license.server_url', 'https://licenses.aerosuite.com'),
            'update_server' => env('UPDATE_SERVER_URL', 'https://updates.aerosuite.com'),
            'edition' => 'both',
        ];
    }
}
