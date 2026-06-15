<?php

namespace Aero\HRM\Services;

use Aero\Core\Support\TenantCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Database Performance Optimization Service
 * Implements query optimization, caching strategies, and performance monitoring
 */
class DatabaseOptimizationService
{
    /**
     * Get optimized user data with eager loading
     */
    public function getOptimizedUsers(array $filters = [])
    {
        $cacheKey = 'users_'.md5(serialize($filters));

        return TenantCache::remember($cacheKey, 300, function () use ($filters) {
            $query = DB::table('users')
                ->leftJoin('departments', 'users.department', '=', 'departments.id')
                ->leftJoin('designations', 'users.designation', '=', 'designations.id')
                ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select(
                    'users.*',
                    'departments.title as department_name',
                    'designations.title as designation_name',
                    'roles.name as role_name'
                );

            // active/inactive is managed via SoftDeletes (active = not soft-deleted).
            if (($filters['status'] ?? 'active') === 'inactive') {
                $query->whereNotNull('users.deleted_at');
            } else {
                $query->whereNull('users.deleted_at');
            }

            // Apply filters
            if (! empty($filters['department'])) {
                $query->where('users.department', $filters['department']);
            }

            return $query->get();
        });
    }

    /**
     * Get attendance statistics with optimized queries
     */
    public function getAttendanceStats($userId = null, $startDate = null, $endDate = null)
    {
        $cacheKey = "attendance_stats_{$userId}_{$startDate}_{$endDate}";

        return TenantCache::remember($cacheKey, 600, function () use ($userId, $startDate, $endDate) {
            $query = DB::table('attendances')
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total_punches'),
                    DB::raw('MIN(created_at) as first_punch'),
                    DB::raw('MAX(created_at) as last_punch'),
                    DB::raw('SUM(CASE WHEN type = "in" THEN 1 ELSE 0 END) as punch_ins'),
                    DB::raw('SUM(CASE WHEN type = "out" THEN 1 ELSE 0 END) as punch_outs')
                );

            if ($userId) {
                $query->where('user_id', $userId);
            }

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }

            return $query->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'desc')
                ->get();
        });
    }

    /**
     * Optimize database indexes
     */
    public function optimizeIndexes()
    {
        $optimizations = [];

        try {
            // Check for missing indexes on frequently queried columns
            $missingIndexes = [
                'users' => ['department', 'designation', 'deleted_at', 'email'],
                'attendances' => ['user_id', 'type', 'created_at'],
                'leaves' => ['user_id', 'status', 'from_date', 'to_date'],
                'daily_works' => ['user_id', 'date', 'status'],
                'model_has_roles' => ['model_id', 'role_id'],
                'model_has_permissions' => ['model_id', 'permission_id'],
            ];

            foreach ($missingIndexes as $table => $columns) {
                foreach ($columns as $column) {
                    $indexName = "idx_{$table}_{$column}";

                    // Check if index exists
                    $indexExists = DB::select('
                        SELECT COUNT(*) as count 
                        FROM INFORMATION_SCHEMA.STATISTICS 
                        WHERE table_schema = DATABASE() 
                        AND table_name = ? 
                        AND index_name = ?
                    ', [$table, $indexName]);

                    if ($indexExists[0]->count == 0) {
                        $optimizations[] = "ALTER TABLE `{$table}` ADD INDEX `{$indexName}` (`{$column}`)";
                    }
                }
            }

            return $optimizations;
        } catch (\Exception $e) {
            Log::error('Database optimization failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Clean up old data
     */
    public function cleanupOldData()
    {
        $cleaned = [];

        try {
            // Clean old logs (older than 3 months)
            $deletedLogs = DB::table('activity_log')
                ->where('created_at', '<', now()->subMonths(3))
                ->delete();
            $cleaned['activity_logs'] = $deletedLogs;

            // Clean old notifications (older than 1 month)
            $deletedNotifications = DB::table('notifications')
                ->where('created_at', '<', now()->subMonth())
                ->whereNotNull('read_at')
                ->delete();
            $cleaned['notifications'] = $deletedNotifications;

            // Optimize tables
            DB::statement('OPTIMIZE TABLE users, attendances, leaves, daily_works');
            $cleaned['optimized'] = true;

            return $cleaned;
        } catch (\Exception $e) {
            Log::error('Database cleanup failed: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }
}
