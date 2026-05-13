<?php

declare(strict_types=1);

namespace Aero\Core\Widgets;

use Aero\Core\Contracts\AbstractDashboardWidget;
use Aero\Contracts\CoreWidgetCategory;

/**
 * Notifications Widget for Core Dashboard
 *
 * Shows recent unread notifications for the user.
 * This is an ALERT widget - requires user attention.
 *
 * Appears on: Core Dashboard (/dashboard)
 */
class NotificationsWidget extends AbstractDashboardWidget
{
    protected string $position = 'main_left';

    protected int $order = 5;

    protected int|string $span = 1;

    protected CoreWidgetCategory $category = CoreWidgetCategory::ALERT;

    protected array $requiredPermissions = []; // No permissions needed - user's own notifications

    public function getKey(): string
    {
        return 'core.notifications';
    }

    public function getComponent(): string
    {
        return 'Widgets/Core/NotificationsWidget';
    }

    public function getTitle(): string
    {
        return 'Notifications';
    }

    public function getDescription(): string
    {
        return 'Recent notifications';
    }

    public function getModuleCode(): string
    {
        return 'core';
    }

    /**
     * Notifications widget is always enabled.
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
        $user = auth()->user();

        if (! $user) {
            return [
                'notifications' => [],
                'unreadCount' => 0,
                'hasMore' => false,
            ];
        }

        // Get recent unread notifications
        $notifications = $user->unreadNotifications()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $this->getNotificationType($notification->type),
                    'title' => $notification->data['title'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'icon' => $notification->data['icon'] ?? 'BellIcon',
                    'color' => $notification->data['color'] ?? 'default',
                    'route' => $notification->data['route'] ?? null,
                    'createdAt' => $notification->created_at->diffForHumans(),
                ];
            })
            ->toArray();

        return [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
            'hasMore' => $user->unreadNotifications()->count() > 5,
        ];
    }

    /**
     * Get friendly notification type from class name.
     */
    protected function getNotificationType(string $className): string
    {
        $parts = explode('\\', $className);
        $name = end($parts);

        // Convert camelCase to words
        return preg_replace('/([a-z])([A-Z])/', '$1 $2', str_replace('Notification', '', $name));
    }
}
