<?php

declare(strict_types=1);

namespace Aero\Notifications\Http\Controllers\Profile;

use Aero\Notifications\Models\UserNotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationPreferenceController
{
    public function index(Request $request): array
    {
        return ['preferences' => UserNotificationPreference::getForUser(Auth::id())];
    }

    public function update(Request $request): array
    {
        $data = $request->validate(['event_type' => 'required|string','channel' => 'required|string','enabled' => 'required|boolean']);
        UserNotificationPreference::setForUser(Auth::id(), $data['event_type'], $data['channel'], $data['enabled']);
        return ['success' => true];
    }
}
