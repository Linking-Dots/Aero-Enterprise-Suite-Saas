<?php

namespace Aero\Core\ValueObjects;

final readonly class RequestContext
{
    /**
     * @param string $scope  One of: 'tenant' | 'platform' | 'standalone'
     * @param string $guard  One of: 'web' | 'landlord'
     */
    public function __construct(
        public string $scope,
        public string $guard,
    ) {}

    public function isPlatform(): bool
    {
        return $this->scope === 'platform';
    }

    public function isTenant(): bool
    {
        return $this->scope === 'tenant';
    }

    public function isStandalone(): bool
    {
        return $this->scope === 'standalone';
    }
}
