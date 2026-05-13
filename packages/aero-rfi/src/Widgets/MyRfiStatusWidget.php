<?php

declare(strict_types=1);

namespace Aero\Rfi\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Aero\Rfi\Models\Rfi;
use Illuminate\Support\Facades\Auth;

/**
 * My RFI Status Widget
 *
 * Shows today's RFI tasks for the current user.
 * This is an ACTION widget - user may need to take action.
 *
 * Appears on: RFI Dashboard (/rfi/dashboard)
 */
class MyRfiStatusWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 10;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ACTION;

    protected array $requiredPermissions = ['rfi.dashboard']; // HRMAC format: module.submodule

    protected array $dashboards = ['rfi'];

    public function getKey(): string
    {
        return 'rfi.my_status';
    }

    public function getComponent(): string
    {
        return 'Widgets/RFI/MyRfiStatusWidget';
    }

    public function getTitle(): string
    {
        return 'My RFI Status';
    }

    public function getDescription(): string
    {
        return "Today's inspection requests";
    }

    public function getModuleCode(): string
    {
        return 'rfi';
    }

    public function getData(): array
    {
        return $this->safeResolve(function () {
            $user = Auth::user();
            if (! $user) {
                return $this->getEmptyState();
            }

            $today = today();

            // Get user's today's RFIs (as incharge or assigned)
            $myTodayRfis = Rfi::where(function ($q) use ($user) {
                $q->where('incharge_user_id', $user->id)
                    ->orWhere('assigned_user_id', $user->id);
            })
                ->whereDate('date', $today)
                ->get();

            $stats = [
                'total' => $myTodayRfis->count(),
                'pending' => $myTodayRfis->whereIn('status', [Rfi::STATUS_PENDING, Rfi::STATUS_NEW])->count(),
                'in_progress' => $myTodayRfis->where('status', Rfi::STATUS_IN_PROGRESS)->count(),
                'completed' => $myTodayRfis->where('status', Rfi::STATUS_COMPLETED)->count(),
                'rejected' => $myTodayRfis->where('status', Rfi::STATUS_REJECTED)->count(),
            ];

            // Get the most recent/urgent items
            $recentItems = $myTodayRfis
                ->whereIn('status', [Rfi::STATUS_PENDING, Rfi::STATUS_IN_PROGRESS, Rfi::STATUS_NEW])
                ->sortByDesc('created_at')
                ->take(3)
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'number' => $item->number,
                    'type' => $item->type,
                    'status' => $item->status,
                    'location' => $item->location,
                ])
                ->values()
                ->toArray();

            return [
                'stats' => $stats,
                'items' => $recentItems,
                'hasWork' => $stats['pending'] > 0 || $stats['in_progress'] > 0,
            ];
        }, $this->getEmptyState());
    }

    private function getEmptyState(): array
    {
        return [
            'stats' => [
                'total' => 0,
                'pending' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'rejected' => 0,
            ],
            'items' => [],
            'hasWork' => false,
        ];
    }
}
