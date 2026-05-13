<?php

namespace Aero\Platform\Services;

use Aero\Contracts\TenantScopeInterface;
use Aero\Core\Traits\ParsesHostDomain;
use Aero\Platform\Models\Tenant;

/**
 * SaaSTenantScope
 *
 * Implementation of TenantScopeInterface for SaaS/Multi-Tenant mode.
 * Uses stancl/tenancy to determine the current tenant context.
 *
 * This implementation is bound when aero-platform is installed.
 */
class SaaSTenantScope implements TenantScopeInterface
{
    use ParsesHostDomain;

    /**
     * {@inheritdoc}
     */
    public function getCurrentTenantId(): int|string|null
    {
        if (! function_exists('tenant')) {
            return null;
        }

        try {
            $tenant = tenant();

            if (! $tenant) {
                return null;
            }

            return $tenant->getTenantKey();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentTenant(): mixed
    {
        if (! function_exists('tenant')) {
            return null;
        }

        try {
            return tenant();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function inTenantContext(): bool
    {
        return $this->getCurrentTenantId() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function inCentralContext(): bool
    {
        // Check if we're on a central domain using auto-detection
        $request = request();

        if ($request) {
            return $this->isHostOnCentralDomain($request->getHost());
        }

        // Fallback: If no request context, check if tenant is initialized
        return ! $this->inTenantContext();
    }

    /**
     * {@inheritdoc}
     */
    public function getMode(): string
    {
        return 'saas';
    }

    /**
     * {@inheritdoc}
     */
    public function isSaaSMode(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isStandaloneMode(): bool
    {
        return false;
    }

    /**
     * Get the current tenant model.
     */
    public function getTenant(): ?Tenant
    {
        $tenant = $this->getCurrentTenant();

        return $tenant instanceof Tenant ? $tenant : null;
    }

    /**
     * Check if the current tenant has an active subscription for a module.
     *
     * @param  string  $moduleCode  The module code (e.g., 'hrm')
     */
    public function hasModuleSubscription(string $moduleCode): bool
    {
        $tenant = $this->getTenant();

        if (! $tenant) {
            return false;
        }

        // Check if tenant has active subscription that includes this module
        return $tenant->hasActiveSubscriptionFor($moduleCode);
    }
}
