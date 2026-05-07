<?php

namespace Aero\Core\Services;

use Aero\Core\Models\InstalledAddon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AddonCatalogService
{
    private string $catalogCachePath;

    public function __construct()
    {
        $this->catalogCachePath = storage_path('app/aeos.addon-catalog');
    }

    /**
     * Get the product catalog.
     * 1. Try live API  2. File cache  3. Bundled fallback
     */
    public function getCatalog(): array
    {
        $live = $this->fetchLive();
        if ($live !== null) {
            $this->writeCache($live);

            return $live;
        }

        $cached = $this->readCache();
        if ($cached !== null) {
            return $cached;
        }

        return $this->bundledCatalog();
    }

    /**
     * Get products NOT already installed.
     */
    public function getAvailableAddons(): array
    {
        $installed     = InstalledAddon::pluck('product_code')->toArray();
        $initialModule = config('product.initial_module', config('product.id', 'hrm'));

        return array_values(array_filter(
            $this->getCatalog(),
            fn ($p) => ! in_array($p['code'], $installed, true)
                    && $p['code'] !== $initialModule
        ));
    }

    private function fetchLive(): ?array
    {
        $url = config('license.server_url').'/api/marketplace/catalog';
        try {
            $response = Http::timeout(5)->get($url);
            if ($response->successful()) {
                return $response->json('products', []);
            }
        } catch (\Throwable $e) {
            Log::debug('AddonCatalog: live fetch failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function readCache(): ?array
    {
        if (! file_exists($this->catalogCachePath)) {
            return null;
        }

        $data = json_decode(file_get_contents($this->catalogCachePath), true);

        return is_array($data) ? ($data['products'] ?? null) : null;
    }

    private function writeCache(array $products): void
    {
        file_put_contents($this->catalogCachePath, json_encode([
            'products'  => $products,
            'cached_at' => time(),
        ]));
    }

    private function bundledCatalog(): array
    {
        return [
            ['code' => 'hrm',        'module_code' => 'hrm',        'name' => 'HRM Suite',         'monthly_price' => 29.00, 'currency' => 'USD', 'description' => 'Human resource management'],
            ['code' => 'crm',        'module_code' => 'crm',        'name' => 'CRM Suite',          'monthly_price' => 39.00, 'currency' => 'USD', 'description' => 'Customer relationship management'],
            ['code' => 'project',    'module_code' => 'project',    'name' => 'Project Management', 'monthly_price' => 24.00, 'currency' => 'USD', 'description' => 'Projects, tasks, milestones'],
            ['code' => 'compliance', 'module_code' => 'compliance', 'name' => 'Compliance',         'monthly_price' => 34.00, 'currency' => 'USD', 'description' => 'Regulatory compliance tracking'],
        ];
    }
}
