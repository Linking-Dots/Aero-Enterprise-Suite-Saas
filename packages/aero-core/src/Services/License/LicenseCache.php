<?php

// packages/aero-core/src/Services/License/LicenseCache.php

namespace Aero\Core\Services\License;

class LicenseCache
{
    private string $cacheFilePath;

    public function __construct()
    {
        $this->cacheFilePath = storage_path('app/aeos.license-cache');
    }

    public function store(array $result): void
    {
        $data = array_merge($result, ['cached_at' => time()]);
        file_put_contents($this->cacheFilePath, json_encode($data));
    }

    public function get(int $ttlSeconds = 86400): ?array
    {
        if (! file_exists($this->cacheFilePath)) {
            return null;
        }

        $data = json_decode(file_get_contents($this->cacheFilePath), true);
        if (! is_array($data) || ! isset($data['cached_at'])) {
            return null;
        }

        if (abs(time() - $data['cached_at']) > $ttlSeconds) {
            return null;
        }

        return $data;
    }

    public function lastSuccessAt(): ?int
    {
        $data = $this->get(PHP_INT_MAX);

        return ($data && ($data['status'] ?? '') === 'valid') ? $data['cached_at'] : null;
    }

    public function clear(): void
    {
        if (file_exists($this->cacheFilePath)) {
            unlink($this->cacheFilePath);
        }
    }
}
