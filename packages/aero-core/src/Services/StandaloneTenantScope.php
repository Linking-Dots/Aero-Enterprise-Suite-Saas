<?php

namespace Aero\Core\Services;

use Aero\Contracts\TenantScopeInterface;

/**
 * StandaloneTenantScope
 *
 * Default implementation of TenantScopeInterface for Standalone mode.
 * Always returns tenant_id = 1 (or configured value).
 *
 * This is the fallback when aero-platform is NOT installed.
 */
class StandaloneTenantScope implements TenantScopeInterface
{
    /**
     * The default tenant ID for standalone mode.
     */
    protected int $defaultTenantId;

    public function __construct()
    {
        $this->defaultTenantId = (int) config('aero.standalone_tenant_id', 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentTenantId(): int|string|null
    {
        return $this->defaultTenantId;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentTenant(): mixed
    {
        // In standalone mode, there's no tenant model
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function inTenantContext(): bool
    {
        // Standalone always has a tenant context (the single tenant)
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function inCentralContext(): bool
    {
        // Standalone never has a central context
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMode(): string
    {
        return 'standalone';
    }

    /**
     * {@inheritdoc}
     */
    public function isSaaSMode(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isStandaloneMode(): bool
    {
        return true;
    }
}
