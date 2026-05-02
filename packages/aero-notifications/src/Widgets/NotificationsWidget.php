<?php

declare(strict_types=1);

namespace Aero\Notifications\Widgets;

use Illuminate\Support\Facades\Auth;

class NotificationsWidget
{
    public function getUnreadCount(): int
    {
        return Auth::check() ? Auth::user()->unreadNotifications->count() : 0;
    }

    public function getRecentNotifications(int $limit = 5): array
    {
        if (! Auth::check()) return [];
        return Auth::user()->notifications()->take($limit)->get()->toArray();
    }

    public function getData(): array
    {
        return ['unread_count' => $this->getUnreadCount(), 'recent' => $this->getRecentNotifications()];
    }
}
