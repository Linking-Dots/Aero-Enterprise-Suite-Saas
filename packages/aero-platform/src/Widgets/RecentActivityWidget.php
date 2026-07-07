<?php

declare(strict_types=1);

namespace Aero\Platform\Widgets;

use Aero\Platform\Contracts\AbstractPlatformWidget;
use Aero\Platform\Contracts\PlatformWidgetCategory;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Recent Activity Widget for Admin Dashboard
 *
 * Displays recent platform activities:
 * - Tenant registrations
 * - Subscription changes
 * - System events
 * - Failed provisioning
 *
 * This is a FEED widget - shows recent events.
 */
class RecentActivityWidget extends AbstractPlatformWidget
{
    protected string $position = 'main_left';

    protected int $order = 30;

    protected int|string $span = 1;

    protected PlatformWidgetCategory $category = PlatformWidgetCategory::ACTIVITY;

    protected array $requiredPermissions = [];

    public function getKey(): string
    {
        return 'platform.recent_activity';
    }

    public function getComponent(): string
    {
        return 'Widgets/Platform/RecentActivityWidget';
    }

    public function getTitle(): string
    {
        return 'Recent Activity';
    }

    public function getDescription(): string
    {
        return 'Timeline of platform events';
    }

    public function getModuleCode(): string
    {
        return 'platform';
    }

    /**
     * Activity widget is always enabled.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get widget data for frontend.
     * Cached for 2 minutes.
     */
    public function getData(): array
    {
        return Cache::remember('platform.dashboard.recent_activity', 120, function () {
            return $this->getRecentActivities();
        });
    }

    /**
     * Get recent activities from various sources.
     */
    protected function getRecentActivities(): array
    {
        $activities = collect();

        // Get recent tenant registrations
        $activities = $activities->merge($this->getRecentTenantRegistrations());

        // Get recent subscription changes
        $activities = $activities->merge($this->getRecentSubscriptionChanges());

        // Get recent provisioning failures
        $activities = $activities->merge($this->getRecentProvisioningFailures());

        // Get audit log entries if table exists
        $activities = $activities->merge($this->getAuditLogEntries());

        // Sort by timestamp and take latest 10
        return [
            'activities' => $activities
                ->sortByDesc('timestamp')
                ->take(10)
                ->values()
                ->toArray(),
            'totalToday' => $this->getTodayActivityCount(),
        ];
    }

    /**
     * Get recent tenant registrations.
     */
    protected function getRecentTenantRegistrations(): Collection
    {
        return Tenant::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($tenant) {
                return [
                    'type' => 'tenant_created',
                    'icon' => 'BuildingOffice2Icon',
                    'color' => 'success',
                    'message' => "New tenant \"{$tenant->name}\" registered",
                    'time' => $tenant->created_at->diffForHumans(),
                    'timestamp' => $tenant->created_at->toIso8601String(),
                    'href' => route('platform.admin.tenants.show', $tenant->id),
                ];
            });
    }

    /**
     * Get recent subscription changes.
     */
    protected function getRecentSubscriptionChanges(): Collection
    {
        $activities = collect();

        // Upgrades (new active subscriptions)
        $newSubscriptions = Subscription::query()
            ->with('tenant', 'plan')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        foreach ($newSubscriptions as $sub) {
            $activities->push([
                'type' => 'subscription_started',
                'icon' => 'ArrowTrendingUpIcon',
                'color' => 'primary',
                'message' => "{$sub->tenant?->name} subscribed to {$sub->plan?->name} plan",
                'time' => $sub->created_at->diffForHumans(),
                'timestamp' => $sub->created_at->toIso8601String(),
                'href' => route('platform.admin.tenants.show', $sub->tenant_id),
            ]);
        }

        // Cancellations
        $cancelledSubscriptions = Subscription::query()
            ->with('tenant', 'plan')
            ->where('status', Subscription::STATUS_CANCELLED)
            ->whereNotNull('cancelled_at')
            ->where('cancelled_at', '>=', now()->subDays(7))
            ->orderByDesc('cancelled_at')
            ->limit(3)
            ->get();

        foreach ($cancelledSubscriptions as $sub) {
            $activities->push([
                'type' => 'subscription_cancelled',
                'icon' => 'XCircleIcon',
                'color' => 'danger',
                'message' => "{$sub->tenant?->name} cancelled their subscription",
                'time' => $sub->cancelled_at->diffForHumans(),
                'timestamp' => $sub->cancelled_at->toIso8601String(),
                'href' => route('platform.admin.tenants.show', $sub->tenant_id),
            ]);
        }

        return $activities;
    }

    /**
     * Get recent provisioning failures.
     */
    protected function getRecentProvisioningFailures(): Collection
    {
        return Tenant::query()
            ->where('status', Tenant::STATUS_FAILED)
            ->where('updated_at', '>=', now()->subDays(7))
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get()
            ->map(function ($tenant) {
                return [
                    'type' => 'provisioning_failed',
                    'icon' => 'ExclamationTriangleIcon',
                    'color' => 'danger',
                    'message' => "Provisioning failed for \"{$tenant->name}\"",
                    'time' => $tenant->updated_at->diffForHumans(),
                    'timestamp' => $tenant->updated_at->toIso8601String(),
                    'href' => route('platform.admin.tenants.show', $tenant->id),
                ];
            });
    }

    /**
     * Get entries from audit log if available.
     */
    protected function getAuditLogEntries(): Collection
    {
        try {
            // Check if audit_logs table exists
            if (! \Schema::hasTable('audit_logs')) {
                return collect();
            }

            return DB::table('audit_logs')
                ->where('created_at', '>=', now()->subDays(3))
                ->whereIn('event_type', [
                    'tenant.suspended',
                    'tenant.activated',
                    'payment.failed',
                    'system.maintenance',
                ])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(function ($log) {
                    return [
                        'type' => $log->event_type ?? 'system',
                        'icon' => $this->getIconForEventType($log->event_type ?? ''),
                        'color' => $this->getColorForEventType($log->event_type ?? ''),
                        'message' => $log->message ?? $log->description ?? 'System event',
                        'time' => Carbon::parse($log->created_at)->diffForHumans(),
                        'timestamp' => $log->created_at,
                        'href' => null,
                    ];
                });
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get icon for event type.
     */
    protected function getIconForEventType(string $eventType): string
    {
        return match ($eventType) {
            'tenant.suspended' => 'PauseIcon',
            'tenant.activated' => 'PlayIcon',
            'payment.failed' => 'CreditCardIcon',
            'system.maintenance' => 'WrenchScrewdriverIcon',
            default => 'InformationCircleIcon',
        };
    }

    /**
     * Get color for event type.
     */
    protected function getColorForEventType(string $eventType): string
    {
        return match ($eventType) {
            'tenant.suspended', 'payment.failed' => 'danger',
            'tenant.activated' => 'success',
            'system.maintenance' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Get total activity count for today.
     */
    protected function getTodayActivityCount(): int
    {
        $count = 0;

        // New tenants today
        $count += Tenant::whereDate('created_at', today())->count();

        // New subscriptions today
        $count += Subscription::whereDate('created_at', today())->count();

        return $count;
    }
}
