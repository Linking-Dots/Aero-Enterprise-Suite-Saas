<?php

declare(strict_types=1);

namespace Aero\Platform\Widgets;

use Aero\Platform\Contracts\AbstractPlatformWidget;
use Aero\Platform\Contracts\PlatformWidgetCategory;
use Aero\Auth\Models\User;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Platform Stats Widget for Admin Dashboard
 *
 * Displays comprehensive platform statistics including:
 * - Total tenants (by status)
 * - Total users across all tenants
 * - Revenue metrics (MRR, ARR)
 * - Subscription analytics
 *
 * This is a SUMMARY widget - provides platform-wide data overview.
 */
class PlatformStatsWidget extends AbstractPlatformWidget
{
    protected string $position = 'stats_row';

    protected int $order = 1;

    protected int|string $span = 'full';

    protected PlatformWidgetCategory $category = PlatformWidgetCategory::SUMMARY;

    protected array $requiredPermissions = [];

    public function getKey(): string
    {
        return 'platform.stats';
    }

    public function getComponent(): string
    {
        return 'Widgets/Platform/PlatformStatsWidget';
    }

    public function getTitle(): string
    {
        return 'Platform Statistics';
    }

    public function getDescription(): string
    {
        return 'Overview of platform metrics and KPIs';
    }

    public function getModuleCode(): string
    {
        return 'platform';
    }

    /**
     * Stats widget is always enabled for platform admins.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get widget data for frontend.
     * Cached for 5 minutes to reduce database load.
     */
    public function getData(): array
    {
        return Cache::remember('platform.dashboard.stats', 300, function () {
            return $this->calculateStats();
        });
    }

    /**
     * Calculate platform statistics.
     */
    protected function calculateStats(): array
    {
        // Tenant statistics by status
        $tenantStats = $this->getTenantStats();

        // Revenue statistics
        $revenueStats = $this->getRevenueStats();

        // User statistics
        $userStats = $this->getUserStats();

        // Growth metrics
        $growthStats = $this->getGrowthStats();

        return [
            'tenants' => $tenantStats,
            'revenue' => $revenueStats,
            'users' => $userStats,
            'growth' => $growthStats,
        ];
    }

    /**
     * Get tenant statistics by status.
     */
    protected function getTenantStats(): array
    {
        $total = Tenant::count();
        $active = Tenant::where('status', Tenant::STATUS_ACTIVE)->count();
        $pending = Tenant::where('status', Tenant::STATUS_PENDING)->count();
        $provisioning = Tenant::where('status', Tenant::STATUS_PROVISIONING)->count();
        $suspended = Tenant::where('status', Tenant::STATUS_SUSPENDED)->count();
        $failed = Tenant::where('status', Tenant::STATUS_FAILED)->count();
        $archived = Tenant::where('status', Tenant::STATUS_ARCHIVED)->count();

        // Trial tenants
        $onTrial = Tenant::where('status', Tenant::STATUS_ACTIVE)
            ->whereHas('subscription', fn ($q) => $q->where('status', Subscription::STATUS_TRIALING)->where('trial_ends_at', '>', now()))
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'pending' => $pending,
            'provisioning' => $provisioning,
            'suspended' => $suspended,
            'failed' => $failed,
            'archived' => $archived,
            'onTrial' => $onTrial,
        ];
    }

    /**
     * Get revenue statistics.
     */
    protected function getRevenueStats(): array
    {
        // Active subscriptions
        $activeSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)->count();

        // Monthly Recurring Revenue (MRR)
        $mrr = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('billing_cycle', 'monthly')
            ->sum('amount');

        // Yearly subscriptions converted to monthly
        $yearlyAsMonthly = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('billing_cycle', 'yearly')
            ->sum(DB::raw('amount / 12'));

        $totalMrr = (float) $mrr + (float) $yearlyAsMonthly;

        // Annual Recurring Revenue
        $arr = $totalMrr * 12;

        // Average Revenue Per Tenant
        $arpt = $activeSubscriptions > 0 ? $totalMrr / $activeSubscriptions : 0;

        return [
            'mrr' => round($totalMrr, 2),
            'arr' => round($arr, 2),
            'arpt' => round($arpt, 2),
            'activeSubscriptions' => $activeSubscriptions,
        ];
    }

    /**
     * Get user statistics.
     */
    protected function getUserStats(): array
    {
        // Platform admin users
        $adminUsers = User::count();
        $activeAdmins = User::active()->count();

        return [
            'adminUsers' => $adminUsers,
            'activeAdmins' => $activeAdmins,
        ];
    }

    /**
     * Get growth statistics.
     */
    protected function getGrowthStats(): array
    {
        $now = Carbon::now();

        // New tenants this month
        $newThisMonth = Tenant::whereMonth('created_at', $now->month)
            ->whereYear('created_at', $now->year)
            ->count();

        // New tenants this week
        $newThisWeek = Tenant::where('created_at', '>=', $now->startOfWeek())
            ->count();

        // Churn calculation (cancelled in last 30 days / active at start)
        $cancelledLast30Days = Subscription::where('status', Subscription::STATUS_CANCELLED)
            ->where('cancelled_at', '>=', $now->subDays(30))
            ->count();

        $activeAt30DaysAgo = Subscription::where('starts_at', '<=', $now->subDays(30))
            ->where(function ($q) use ($now) {
                $q->whereNull('cancelled_at')
                    ->orWhere('cancelled_at', '>', $now->subDays(30));
            })
            ->count();

        $churnRate = $activeAt30DaysAgo > 0
            ? round(($cancelledLast30Days / $activeAt30DaysAgo) * 100, 2)
            : 0;

        return [
            'newThisMonth' => $newThisMonth,
            'newThisWeek' => $newThisWeek,
            'churnRate' => $churnRate,
        ];
    }
}
