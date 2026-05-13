<?php

declare(strict_types=1);

namespace Aero\Core\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Aero\Core\Models\User;
use Aero\HRMAC\Models\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * System Stats Widget for Core Dashboard
 *
 * Displays comprehensive tenant statistics including:
 * - Total users (active/inactive)
 * - Roles count
 * - Departments count
 * - Designations count
 * - New users this month
 *
 * This is a SUMMARY widget - provides data overview.
 * Uses query optimization and 5-minute caching for performance.
 */
class SystemStatsWidget extends AbstractDashboardWidget
{
    protected string $position = 'stats_row';

    protected int $order = 1;

    protected int|string $span = 'full';

    protected CoreWidgetCategory $category = CoreWidgetCategory::SUMMARY;

    protected array $requiredPermissions = ['dashboard.view_stats'];

    public function getKey(): string
    {
        return 'core.system_stats';
    }

    public function getComponent(): string
    {
        return 'Widgets/Core/SystemStatsWidget';
    }

    public function getTitle(): string
    {
        return 'System Statistics';
    }

    public function getDescription(): string
    {
        return 'Overview of tenant data and resources';
    }

    public function getModuleCode(): string
    {
        return 'core';
    }

    /**
     * Stats widget is always enabled.
     */
    public function isEnabled(): bool
    {
        return true;
    }

    /**
     * Get widget data for frontend.
     *
     * Uses optimized single query for user stats and 5-minute caching.
     */
    public function getData(): array
    {
        // Cache stats for 5 minutes to reduce database load
        return Cache::remember('dashboard.system_stats.'.auth()->id(), 300, function () {
            return $this->fetchStats();
        });
    }

    /**
     * Fetch system statistics from database.
     */
    protected function fetchStats(): array
    {
        try {
            // OPTIMIZED: Single query with conditional aggregation for user stats
            $userStats = User::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 ELSE 0 END) as new_this_month
            ', [now()->month, now()->year])->first();

            $totalUsers = (int) ($userStats->total ?? 0);
            $activeUsers = (int) ($userStats->active ?? 0);
            $inactiveUsers = (int) ($userStats->inactive ?? 0);
            $newUsersThisMonth = (int) ($userStats->new_this_month ?? 0);
        } catch (\Throwable $e) {
            Log::warning('SystemStatsWidget: Failed to fetch user statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            $totalUsers = $activeUsers = $inactiveUsers = $newUsersThisMonth = 0;
        }

        // Role statistics with error handling
        try {
            $totalRoles = (int) Role::count();
        } catch (\Throwable $e) {
            Log::warning('SystemStatsWidget: Failed to fetch role count', ['error' => $e->getMessage()]);
            $totalRoles = 0;
        }

        // Department and designation statistics (HRM module)
        $departmentsCount = $this->getTableCount('departments');
        $designationsCount = $this->getTableCount('designations');

        // Online users (active sessions in last 15 minutes)
        $onlineUsers = $this->getOnlineUsersCount($activeUsers);

        return $this->formatStatsData(
            $totalUsers,
            $activeUsers,
            $inactiveUsers,
            $onlineUsers,
            $totalRoles,
            $departmentsCount,
            $newUsersThisMonth,
            $designationsCount
        );
    }

    /**
     * Get count from a table if it exists and has proper structure.
     */
    protected function getTableCount(string $tableName): int
    {
        try {
            if (Schema::hasTable($tableName)) {
                return DB::table($tableName)->count();
            }
        } catch (\Throwable $e) {
            Log::warning("SystemStatsWidget: Failed to count {$tableName} table", [
                'error' => $e->getMessage(),
                'table' => $tableName,
            ]);
        }

        return 0;
    }

    /**
     * Get online users count from sessions table.
     */
    protected function getOnlineUsersCount(int $fallbackActiveUsers): int
    {
        try {
            if (Schema::hasTable('sessions')) {
                return DB::table('sessions')
                    ->where('last_activity', '>', now()->subMinutes(15)->timestamp)
                    ->count();
            }
        } catch (\Throwable $e) {
            Log::warning('SystemStatsWidget: Failed to count online users', ['error' => $e->getMessage()]);

            // Fallback: estimate 40% of active users as online
            return $fallbackActiveUsers > 0 ? (int) ceil($fallbackActiveUsers * 0.4) : 0;
        }

        return 0;
    }

    /**
     * Format statistics data for frontend.
     */
    protected function formatStatsData(
        int $totalUsers,
        int $activeUsers,
        int $inactiveUsers,
        int $onlineUsers,
        int $totalRoles,
        int $departmentsCount,
        int $newUsersThisMonth,
        int $designationsCount
    ): array {
        return [
            'stats' => [
                [
                    'key' => 'total_users',
                    'label' => 'Total Users',
                    'value' => $totalUsers,
                    'icon' => 'UsersIcon',
                    'color' => 'primary',
                    'description' => 'All registered users',
                ],
                [
                    'key' => 'active_users',
                    'label' => 'Active Users',
                    'value' => $activeUsers,
                    'icon' => 'CheckCircleIcon',
                    'color' => 'success',
                    'description' => 'Currently active',
                ],
                [
                    'key' => 'online_users',
                    'label' => 'Online Now',
                    'value' => $onlineUsers,
                    'icon' => 'SignalIcon',
                    'color' => 'success',
                    'description' => 'Last 15 minutes',
                ],
                [
                    'key' => 'inactive_users',
                    'label' => 'Inactive Users',
                    'value' => $inactiveUsers,
                    'icon' => 'XCircleIcon',
                    'color' => 'danger',
                    'description' => 'Disabled accounts',
                ],
                [
                    'key' => 'total_roles',
                    'label' => 'Roles',
                    'value' => $totalRoles,
                    'icon' => 'ShieldCheckIcon',
                    'color' => 'secondary',
                    'description' => 'System roles',
                ],
                [
                    'key' => 'departments',
                    'label' => 'Departments',
                    'value' => $departmentsCount,
                    'icon' => 'BuildingOfficeIcon',
                    'color' => 'warning',
                    'description' => 'Org units',
                ],
            ],
            'newUsersThisMonth' => $newUsersThisMonth,
            'totalDesignations' => $designationsCount,
        ];
    }
}
