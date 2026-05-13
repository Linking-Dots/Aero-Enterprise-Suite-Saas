<?php

declare(strict_types=1);

namespace Aero\Core\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Recent Activity Widget for Core Dashboard
 *
 * Displays a timeline of recent system activities:
 * - User logins/logouts
 * - Settings changes
 * - Role updates
 * - User management actions
 *
 * This is a FEED widget - activity stream.
 * Uses optimized single query with UNION and 3-minute caching.
 */
class RecentActivityWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 3;

    protected int|string $span = 2;

    protected CoreWidgetCategory $category = CoreWidgetCategory::FEED;

    protected array $requiredPermissions = ['dashboard.view_activity'];

    public function getKey(): string
    {
        return 'core.recent_activity';
    }

    public function getComponent(): string
    {
        return 'Widgets/Core/RecentActivityWidget';
    }

    public function getTitle(): string
    {
        return 'Recent Activity';
    }

    public function getDescription(): string
    {
        return 'Latest system activities and events';
    }

    public function getModuleCode(): string
    {
        return 'core';
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
     *
     * Uses optimized UNION query and 3-minute caching for activity feed.
     */
    public function getData(): array
    {
        // Cache activity feed for 3 minutes
        return Cache::remember('dashboard.recent_activity.'.auth()->id(), 180, function () {
            return $this->fetchRecentActivities();
        });
    }

    /**
     * Fetch recent activities using optimized UNION query.
     */
    protected function fetchRecentActivities(): array
    {
        $activities = [];

        try {
            // OPTIMIZED: Use UNION to combine auth and audit events in single query
            if (Schema::hasTable('authentication_events') && Schema::hasTable('audit_logs')) {
                $activities = $this->fetchCombinedActivities();
            } elseif (Schema::hasTable('authentication_events')) {
                $activities = $this->fetchAuthenticationEvents();
            } elseif (Schema::hasTable('audit_logs')) {
                $activities = $this->fetchAuditEvents();
            }
        } catch (\Throwable $e) {
            Log::warning('RecentActivityWidget: Failed to fetch activities', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }

        // If no activities, show placeholder
        if (empty($activities)) {
            $activities = [[
                'id' => 'placeholder_1',
                'type' => 'info',
                'icon' => 'InformationCircleIcon',
                'color' => 'default',
                'message' => 'No recent activity to display',
                'user' => 'System',
                'timestamp' => now()->toDateTimeString(),
                'timeAgo' => 'just now',
            ]];
        }

        return [
            'activities' => array_slice($activities, 0, 8),
            'totalToday' => count($activities),
            'viewAllRoute' => 'audit.logs',
        ];
    }

    /**
     * Fetch combined activities using UNION for optimal performance.
     */
    protected function fetchCombinedActivities(): array
    {
        $results = DB::select("
            (SELECT 
                CONCAT('auth_', ae.id) as id,
                ae.event_type as type,
                ae.created_at,
                ae.ip_address,
                u.name as user_name,
                'auth' as source
            FROM authentication_events ae
            INNER JOIN users u ON ae.user_id = u.id
            ORDER BY ae.created_at DESC
            LIMIT 10)
            
            UNION ALL
            
            (SELECT 
                CONCAT('audit_', al.id) as id,
                al.action as type,
                al.created_at,
                NULL as ip_address,
                COALESCE(u.name, 'System') as user_name,
                'audit' as source
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 10)
            
            ORDER BY created_at DESC
            LIMIT 8
        ");

        return array_map(function ($event) {
            $isAuth = str_starts_with($event->id, 'auth_');

            return [
                'id' => $event->id,
                'type' => $this->mapEventType($event->type, $isAuth),
                'icon' => $isAuth ? $this->getEventIcon($event->type) : $this->getAuditIcon($event->type),
                'color' => $isAuth ? $this->getEventColor($event->type) : $this->getAuditColor($event->type),
                'message' => $isAuth
                    ? $this->formatAuthMessage($event)
                    : $this->formatSimpleAuditMessage($event),
                'user' => $event->user_name,
                'timestamp' => $event->created_at,
                'timeAgo' => Carbon::parse($event->created_at)->diffForHumans(),
            ];
        }, $results);
    }

    /**
     * Fetch only authentication events (fallback).
     */
    protected function fetchAuthenticationEvents(): array
    {
        $authEvents = DB::table('authentication_events')
            ->join('users', 'authentication_events.user_id', '=', 'users.id')
            ->select([
                'authentication_events.id',
                'authentication_events.event_type',
                'authentication_events.created_at',
                'authentication_events.ip_address',
                'users.name as user_name',
            ])
            ->orderByDesc('authentication_events.created_at')
            ->limit(8)
            ->get();

        return $authEvents->map(function ($event) {
            return [
                'id' => 'auth_'.$event->id,
                'type' => $this->mapEventType($event->event_type, true),
                'icon' => $this->getEventIcon($event->event_type),
                'color' => $this->getEventColor($event->event_type),
                'message' => $this->formatEventMessage($event),
                'user' => $event->user_name,
                'timestamp' => $event->created_at,
                'timeAgo' => Carbon::parse($event->created_at)->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Fetch only audit events (fallback).
     */
    protected function fetchAuditEvents(): array
    {
        $auditEvents = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->select([
                'audit_logs.id',
                'audit_logs.action',
                'audit_logs.auditable_type',
                'audit_logs.created_at',
                'users.name as user_name',
            ])
            ->orderByDesc('audit_logs.created_at')
            ->limit(8)
            ->get();

        return $auditEvents->map(function ($event) {
            return [
                'id' => 'audit_'.$event->id,
                'type' => $event->action,
                'icon' => $this->getAuditIcon($event->action),
                'color' => $this->getAuditColor($event->action),
                'message' => $this->formatAuditMessage($event),
                'user' => $event->user_name ?? 'System',
                'timestamp' => $event->created_at,
                'timeAgo' => Carbon::parse($event->created_at)->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Map event type to readable label.
     */
    protected function mapEventType(string $eventType, bool $isAuth = false): string
    {
        if (! $isAuth) {
            return $eventType;
        }

        return match ($eventType) {
            'login' => 'login',
            'logout' => 'logout',
            'login_failed' => 'failed_login',
            'password_reset' => 'password_reset',
            default => 'activity',
        };
    }

    /**
     * Format simple auth message for combined query.
     */
    protected function formatAuthMessage(object $event): string
    {
        $action = match ($event->type) {
            'login' => 'logged in',
            'logout' => 'logged out',
            'login_failed' => 'failed login attempt',
            'password_reset' => 'reset password',
            default => $event->type,
        };

        $ip = $event->ip_address ? " from {$event->ip_address}" : '';

        return "{$event->user_name} {$action}{$ip}";
    }

    /**
     * Format simple audit message for combined query.
     */
    protected function formatSimpleAuditMessage(object $event): string
    {
        $action = ucfirst(strtolower($event->type));

        return "{$event->user_name} {$action}";
    }

    /**
     * Get icon for auth event type.
     */
    protected function getEventIcon(string $eventType): string
    {
        return match ($eventType) {
            'login' => 'ArrowRightOnRectangleIcon',
            'logout' => 'ArrowLeftOnRectangleIcon',
            'login_failed' => 'ExclamationTriangleIcon',
            'password_reset' => 'KeyIcon',
            default => 'InformationCircleIcon',
        };
    }

    /**
     * Get color for auth event type.
     */
    protected function getEventColor(string $eventType): string
    {
        return match ($eventType) {
            'login' => 'success',
            'logout' => 'default',
            'login_failed' => 'danger',
            'password_reset' => 'warning',
            default => 'primary',
        };
    }

    /**
     * Format auth event message.
     */
    protected function formatEventMessage(object $event): string
    {
        $action = match ($event->event_type) {
            'login' => 'logged in',
            'logout' => 'logged out',
            'login_failed' => 'failed login attempt',
            'password_reset' => 'reset password',
            default => $event->event_type,
        };

        $ip = $event->ip_address ? " from {$event->ip_address}" : '';

        return "{$event->user_name} {$action}{$ip}";
    }

    /**
     * Get icon for audit action.
     */
    protected function getAuditIcon(string $action): string
    {
        return match (strtolower($action)) {
            'create', 'created' => 'PlusCircleIcon',
            'update', 'updated' => 'PencilSquareIcon',
            'delete', 'deleted' => 'TrashIcon',
            default => 'DocumentTextIcon',
        };
    }

    /**
     * Get color for audit action.
     */
    protected function getAuditColor(string $action): string
    {
        return match (strtolower($action)) {
            'create', 'created' => 'success',
            'update', 'updated' => 'primary',
            'delete', 'deleted' => 'danger',
            default => 'default',
        };
    }

    /**
     * Format audit event message.
     */
    protected function formatAuditMessage(object $event): string
    {
        $model = class_basename($event->auditable_type ?? 'Record');
        $action = ucfirst(strtolower($event->action));
        $user = $event->user_name ?? 'System';

        return "{$user} {$action} {$model}";
    }
}
