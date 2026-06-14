<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\ModuleSyncFilterInterface;
use Illuminate\Support\Collection;

/**
 * SaaS data-decision (Audit D15): when syncing a tenant's module hierarchy, restrict
 * the discovered modules to those the tenant has paid for (baseline ∪ subscribed).
 *
 * This is the platform's concern, not HRMAC's. It is bound to
 * {@see ModuleSyncFilterInterface} by the platform service provider, so HRMAC's
 * context-free sync command applies it without knowing anything about tenants or
 * subscriptions. In standalone (no aero-platform), nothing is bound and every
 * discovered module syncs.
 */
class TenantSubscriptionModuleFilter implements ModuleSyncFilterInterface
{
    public function filter(Collection $modules, string $scope): Collection
    {
        // Only tenant syncs are subscription-gated. Platform/all sync everything.
        if ($scope !== 'tenant' || ! function_exists('tenancy') || ! tenancy()->initialized) {
            return $modules;
        }

        $tenant = tenant();
        if ($tenant === null || ! method_exists($tenant, 'getSubscribedProductModulesAttribute')) {
            return $modules;
        }

        $allowed = $tenant->subscribed_product_modules;

        return $modules
            ->filter(fn (array $def) => in_array($def['code'] ?? null, $allowed, true))
            ->values();
    }
}
