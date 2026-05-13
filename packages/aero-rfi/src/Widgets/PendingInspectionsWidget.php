<?php

declare(strict_types=1);

namespace Aero\Rfi\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Aero\Rfi\Models\Rfi;
use Illuminate\Support\Facades\Auth;

/**
 * Pending Inspections Widget
 *
 * Shows count of RFIs pending inspection/approval.
 * This is an ALERT widget - needs attention.
 *
 * Appears on: RFI Dashboard (/rfi/dashboard)
 */
class PendingInspectionsWidget extends AbstractDashboardWidget
{
    protected string $position = 'sidebar';

    protected int $order = 15;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = ['rfi.dashboard']; // HRMAC format: module.submodule

    protected array $dashboards = ['rfi'];

    public function getKey(): string
    {
        return 'rfi.pending_inspections';
    }

    public function getComponent(): string
    {
        return 'Widgets/RFI/PendingInspectionsWidget';
    }

    public function getTitle(): string
    {
        return 'Pending Inspections';
    }

    public function getDescription(): string
    {
        return 'RFIs awaiting inspection';
    }

    public function getModuleCode(): string
    {
        return 'rfi';
    }

    /**
     * Override isEnabled to check permission OR condition.
     * Super Administrators bypass ALL checks.
     */
    public function isEnabled(): bool
    {
        // Super Admin bypass - always enabled, bypasses ALL checks
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isModuleActive()) {
            return false;
        }

        // User must have RFI dashboard module access
        return $this->userHasModuleAccess();
    }

    public function getData(): array
    {
        return $this->safeResolve(function () {
            $user = Auth::user();
            if (! $user) {
                return $this->getEmptyState();
            }

            // Count pending inspections
            $pendingQuery = Rfi::whereIn('status', [
                Rfi::STATUS_PENDING,
                Rfi::STATUS_IN_PROGRESS,
            ])->whereNull('inspection_result');

            // Super Admin and users with manage access see all inspections
            // Otherwise filter by their assignments
            $isSuperAdmin = $this->isSuperAdmin();
            $canManageAll = $this->userHasModuleAccess('rfi', 'inspections', 'manage');

            if (! $isSuperAdmin && ! $canManageAll) {
                $pendingQuery->where('assigned_user_id', $user->id);
            }

            $pendingCount = $pendingQuery->count();

            // Urgent = submitted more than 24 hours ago
            $urgentCount = (clone $pendingQuery)
                ->where('created_at', '<=', now()->subHours(24))
                ->count();

            // Today's count
            $todayCount = Rfi::whereDate('date', today())
                ->whereIn('status', [Rfi::STATUS_PENDING, Rfi::STATUS_IN_PROGRESS])
                ->count();

            // Get recent 3 for preview
            $recentItems = (clone $pendingQuery)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get(['id', 'number', 'type', 'status', 'created_at', 'location']);

            return [
                'count' => $pendingCount,
                'urgent' => $urgentCount,
                'today' => $todayCount,
                'items' => $recentItems->map(fn ($item) => [
                    'id' => $item->id,
                    'number' => $item->number,
                    'type' => $item->type,
                    'location' => $item->location,
                    'age' => $item->created_at->diffForHumans(),
                ])->toArray(),
            ];
        }, $this->getEmptyState());
    }

    private function getEmptyState(): array
    {
        return [
            'count' => 0,
            'urgent' => 0,
            'today' => 0,
            'items' => [],
        ];
    }
}
