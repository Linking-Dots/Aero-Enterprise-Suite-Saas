<?php

declare(strict_types=1);

namespace Aero\Notifications\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationApiController
{
    public function index(Request $request): array
    {
        $notifications = Auth::user()->notifications()->paginate(20)->toArray();
        return ['notifications' => $notifications['data'], 'meta' => $notifications];
    }

    public function markRead(Request $request, string $id): array { Auth::user()->notifications()->where('id', $id)->first()?->markAsRead(); return ['success' => true]; }
    public function markAllRead(Request $request): array { Auth::user()->unreadNotifications->markAsRead(); return ['success' => true]; }
    public function unreadCount(Request $request): array { return ['count' => Auth::user()->unreadNotifications->count()]; }
}
