<?php

// packages/aero-core/src/Services/License/DomainBinding.php

namespace Aero\Core\Services\License;

class DomainBinding
{
    private string $bindingFilePath;

    public function __construct()
    {
        $this->bindingFilePath = storage_path('app/aeos.domain');
    }

    public function currentDomainHash(): string
    {
        return hash('sha256', strtolower($this->resolveHost()));
    }

    public function bind(): void
    {
        file_put_contents($this->bindingFilePath, $this->currentDomainHash());
    }

    public function matches(): bool
    {
        if (! file_exists($this->bindingFilePath)) {
            return true; // Not yet bound
        }

        $bound = trim(file_get_contents($this->bindingFilePath));

        return hash_equals($bound, $this->currentDomainHash());
    }

    public function boundHash(): ?string
    {
        return file_exists($this->bindingFilePath)
            ? trim(file_get_contents($this->bindingFilePath))
            : null;
    }

    private function resolveHost(): string
    {
        if (php_sapi_name() === 'cli') {
            return parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost';
        }

        return request()->getHost();
    }
}
