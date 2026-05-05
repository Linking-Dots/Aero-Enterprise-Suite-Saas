<?php

namespace Aero\Core\Services;

use Aero\Core\Models\Activity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityService
{
    /**
     * Log an activity event.
     */
    public function log(array $data): Activity
    {
        $activity = Activity::create([
            'uuid' => Str::uuid(),
            'user_id' => $data['user_id'] ?? auth()->id(),
            'tenant_id' => $data['tenant_id'] ?? tenant('id'),
            'module' => $data['module'] ?? null,
            'action' => $data['action'],
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'description' => $data['description'],
            'metadata' => $data['metadata'] ?? [],
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
        ]);

        Log::info('Activity logged', ['activity_id' => $activity->id, 'action' => $activity->action]);
        
        return $activity;
    }

    /**
     * Log a user-specific action.
     */
    public function logUserAction(string $action, string $description, array $metadata = []): Activity
    {
        return $this->log([
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a system event (no user).
     */
    public function logSystemEvent(string $action, string $description, array $metadata = []): Activity
    {
        return $this->log([
            'user_id' => null,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Get activities with filters.
     */
    public function getActivities(array $filters = [])
    {
        $query = Activity::query()->with(['user', 'tenant']);

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['tenant_id'])) {
            $query->forTenant($filters['tenant_id']);
        }

        if (isset($filters['module'])) {
            $query->byModule($filters['module']);
        }

        if (isset($filters['action'])) {
            $query->byAction($filters['action']);
        }

        if (isset($filters['start_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date'] ?? null);
        }

        return $query->latest('created_at')->paginate(50);
    }

    /**
     * Get a single activity by ID.
     */
    public function getActivityById($id)
    {
        return Activity::with(['user', 'tenant'])->findOrFail($id);
    }

    /**
     * Get activity statistics.
     */
    public function getActivityStats(): array
    {
        $totalActivities = Activity::count();
        $todayActivities = Activity::whereDate('created_at', today())->count();
        $weekActivities = Activity::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        
        $activitiesByModule = Activity::select('module')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('module')
            ->get()
            ->pluck('count', 'module')
            ->toArray();

        $activitiesByAction = Activity::select('action')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('action')
            ->get()
            ->pluck('count', 'action')
            ->toArray();

        return [
            'total_activities' => $totalActivities,
            'today_activities' => $todayActivities,
            'week_activities' => $weekActivities,
            'activities_by_module' => $activitiesByModule,
            'activities_by_action' => $activitiesByAction,
        ];
    }

    /**
     * Cleanup old activities based on retention policy.
     */
    public function cleanupOldActivities(int $days = 90): int
    {
        $deletedCount = Activity::where('created_at', '<', now()->subDays($days))->delete();
        
        Log::info('Old activities cleaned up', ['deleted_count' => $deletedCount, 'days_threshold' => $days]);
        
        return $deletedCount;
    }
}
