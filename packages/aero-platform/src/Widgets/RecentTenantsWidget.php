<?php

declare(strict_types=1);

namespace Aero\Platform\Widgets;

use Aero\Platform\Contracts\AbstractPlatformWidget;
use Aero\Platform\Contracts\PlatformWidgetCategory;
use Aero\Platform\Models\Tenant;

/**
 * Recent Tenants Widget for Admin Dashboard
 *
 * Displays the most recently registered tenants with their
 * status, plan, and creation date.
 *
 * This is a DISPLAY widget - informational listing.
 */
class RecentTenantsWidget extends AbstractPlatformWidget
{
    protected string $position = 'main_left';

    protected int $order = 10;

    protected int|string $span = 2;

    protected PlatformWidgetCategory $category = PlatformWidgetCategory::DISPLAY;

    protected array $requiredPermissions = [];

    public function getKey(): string
    {
        return 'platform.recent_tenants';
    }

    public function getComponent(): string
    {
        return 'Widgets/Platform/RecentTenantsWidget';
    }

    public function getTitle(): string
    {
        return 'Recent Tenants';
    }

    public function getDescription(): string
    {
        return 'Recently registered organizations';
    }

    public function getModuleCode(): string
    {
        return 'platform';
    }

    /**
     * Widget is enabled for platform admins.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get widget data for frontend.
     */
    public function getData(): array
    {
        $recentTenants = Tenant::with(['currentSubscription.plan', 'domains'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function (Tenant $tenant) {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name ?? 'Unnamed Tenant',
                    'subdomain' => $tenant->subdomain ?? $tenant->id,
                    'domain' => $tenant->domains->first()?->domain ?? null,
                    'status' => $tenant->status,
                    'plan' => $tenant->plan?->name ?? 'No Plan',
                    'planColor' => $this->getPlanColor($tenant->plan?->code),
                    'createdAt' => $tenant->created_at?->diffForHumans() ?? 'Unknown',
                    'createdAtFull' => $tenant->created_at?->format('M d, Y H:i') ?? null,
                    'isOnTrial' => $tenant->subscription('default')?->onTrial() ?? false,
                    'trialEndsAt' => $tenant->subscription('default')?->trial_ends_at?->diffForHumans() ?? null,
                ];
            })
            ->toArray();

        return [
            'tenants' => $recentTenants,
        ];
    }

    /**
     * Get color for plan display.
     */
    protected function getPlanColor(?string $planCode): string
    {
        return match ($planCode) {
            'enterprise' => '#f59e0b',
            'professional' => '#8b5cf6',
            'growth' => '#0ea5e9',
            'starter' => '#94a3b8',
            default => '#6b7280',
        };
    }
}
