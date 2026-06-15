<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Core\Models\User;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * Plan Entitlement Service
 *
 * Enforces plan limits and module access at runtime.
 * Used by middleware and feature gates.
 */
class PlanEntitlementService
{
    /**
     * Check if tenant has reached user limit.
     */
    public function hasReachedUserLimit(string $tenantId): bool
    {
        // Standalone mode has no user limits
        if (config('aero.mode') === 'standalone') {
            return false;
        }

        $subscription = $this->getActiveSubscription($tenantId);

        if (! $subscription || ! $subscription->plan) {
            // No active subscription in SaaS mode = deny access
            return true;
        }

        $plan = $subscription->plan;
        $maxUsers = $plan->max_users ?? $plan->limits['max_users'] ?? 0;

        // 0 means unlimited
        if ($maxUsers === 0) {
            return false;
        }

        // Count active tenant users (active = not soft-deleted)
        $userCount = User::where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->count();

        return $userCount >= $maxUsers;
    }

    /**
     * Check if tenant has reached storage limit.
     */
    public function hasReachedStorageLimit(string $tenantId): bool
    {
        // Standalone mode has no storage limits
        if (config('aero.mode') === 'standalone') {
            return false;
        }

        $subscription = $this->getActiveSubscription($tenantId);

        if (! $subscription || ! $subscription->plan) {
            // No active subscription in SaaS mode = deny access
            return true;
        }

        $plan = $subscription->plan;
        $maxStorageGb = $plan->max_storage_gb ?? $plan->limits['max_storage_gb'] ?? 0;

        // 0 means unlimited
        if ($maxStorageGb === 0) {
            return false;
        }

        $tenant = \Aero\Platform\Models\Tenant::find($tenantId);
        if (! $tenant) {
            return true;
        }

        // Use the latest tenant stat record (updated by MonitorStorageUsageJob)
        $latestStat = \Aero\Platform\Models\TenantStat::where('tenant_id', $tenantId)
            ->orderByDesc('date')
            ->first();

        if ($latestStat && $latestStat->storage_used_mb > 0) {
            $storageUsedGb = $latestStat->storage_used_mb / 1024;

            return $storageUsedGb >= $maxStorageGb;
        }

        // Fallback to tenant metadata if stats are not yet available
        $metadata = $tenant->metadata ?? [];
        $storageUsedGb = $metadata['storage_usage_gb'] ?? 0;

        return $storageUsedGb >= $maxStorageGb;
    }

    /**
     * Check if tenant has module access.
     *
     * Plans and products/modules are separate concerns:
     * - Plans control limits (users, storage).
     * - Module access is determined by the tenant_module pivot
     *   and independent module subscriptions (subscription_modules).
     */
    public function hasModuleAccess(string $tenantId, string $moduleCode): bool
    {
        // Standalone mode bypasses subscription checks
        if (config('aero.mode') === 'standalone') {
            return true;
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }

        return $tenant->hasActiveSubscription($moduleCode);
    }

    /**
     * Get remaining user slots.
     */
    public function getRemainingUserSlots(string $tenantId): ?int
    {
        // Standalone mode has unlimited user slots
        if (config('aero.mode') === 'standalone') {
            return null; // Unlimited
        }

        $subscription = $this->getActiveSubscription($tenantId);

        if (! $subscription || ! $subscription->plan) {
            return null; // Unlimited
        }

        $plan = $subscription->plan;
        $maxUsers = $plan->max_users ?? $plan->limits['max_users'] ?? 0;

        if ($maxUsers === 0) {
            return null; // Unlimited
        }

        $userCount = User::where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->count();

        return max(0, $maxUsers - $userCount);
    }

    /**
     * Get active subscription for tenant (cached).
     */
    protected function getActiveSubscription(string $tenantId): ?Subscription
    {
        return Cache::remember(
            "tenant:{$tenantId}:active_subscription",
            now()->addMinutes(5),
            fn () => Subscription::where('billable_type', Tenant::class)
                ->where('billable_id', $tenantId)
                ->with('plan.modules')
                ->active()
                ->first()
        );
    }

    /**
     * Clear entitlement cache for tenant.
     */
    public function clearCache(string $tenantId): void
    {
        Cache::forget("tenant:{$tenantId}:active_subscription");
    }
}
