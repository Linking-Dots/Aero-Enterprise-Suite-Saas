<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Services\UserPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserPreferenceController
{
    protected UserPreferenceService $preferenceService;

    public function __construct(UserPreferenceService $preferenceService)
    {
        $this->preferenceService = $preferenceService;
    }

    /**
     * Display user preferences page.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $activeTab = $request->get('tab', 'notifications');

        $preferences = [
            'notifications' => $this->preferenceService->getNotificationPreferences($userId),
            'theme' => $this->preferenceService->getThemePreferences($userId),
            'locale' => $this->preferenceService->getLocalePreferences($userId),
            'accessibility' => $this->preferenceService->getAccessibilityPreferences($userId),
        ];

        return Inertia::render('Core/UserPreferences/Index', [
            'preferences' => $preferences,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Update user preferences.
     */
    public function update(Request $request)
    {
        $userId = Auth::id();
        $category = $request->get('category');
        $preferences = $request->get('preferences', []);

        $result = match ($category) {
            'notifications' => $this->preferenceService->setNotificationPreferences($userId, $preferences),
            'theme' => $this->preferenceService->setThemePreferences($userId, $preferences),
            'locale' => $this->preferenceService->setLocalePreferences($userId, $preferences),
            'accessibility' => $this->preferenceService->setAccessibilityPreferences($userId, $preferences),
            default => [],
        };

        return back()->with('success', 'Preferences updated successfully.');
    }

    /**
     * Get notification preferences (API).
     */
    public function getNotificationPreferences()
    {
        $userId = Auth::id();
        return response()->json([
            'preferences' => $this->preferenceService->getNotificationPreferences($userId),
        ]);
    }

    /**
     * Update notification preferences (API).
     */
    public function updateNotificationPreferences(Request $request)
    {
        $userId = Auth::id();
        $preferences = $request->validate([
            'email_enabled' => 'sometimes|boolean',
            'in_app_enabled' => 'sometimes|boolean',
            'push_enabled' => 'sometimes|boolean',
            'digest_frequency' => 'sometimes|in:immediate,daily,weekly',
            'dnd_enabled' => 'sometimes|boolean',
            'dnd_start_time' => 'sometimes|nullable|date_format:H:i',
            'dnd_end_time' => 'sometimes|nullable|date_format:H:i',
        ]);

        $result = $this->preferenceService->setNotificationPreferences($userId, $preferences);

        return response()->json([
            'success' => true,
            'preferences' => $result,
        ]);
    }

    /**
     * Get theme preferences (API).
     */
    public function getThemePreferences()
    {
        $userId = Auth::id();
        return response()->json([
            'preferences' => $this->preferenceService->getThemePreferences($userId),
        ]);
    }

    /**
     * Update theme preferences (API).
     */
    public function updateThemePreferences(Request $request)
    {
        $userId = Auth::id();
        $preferences = $request->validate([
            'theme' => 'sometimes|in:light,dark,system',
            'accent_color' => 'sometimes|string',
            'density' => 'sometimes|in:comfortable,compact',
            'border_radius' => 'sometimes|in:none,small,medium,large',
        ]);

        $result = $this->preferenceService->setThemePreferences($userId, $preferences);

        return response()->json([
            'success' => true,
            'preferences' => $result,
        ]);
    }

    /**
     * Get locale preferences (API).
     */
    public function getLocalePreferences()
    {
        $userId = Auth::id();
        return response()->json([
            'preferences' => $this->preferenceService->getLocalePreferences($userId),
        ]);
    }

    /**
     * Update locale preferences (API).
     */
    public function updateLocalePreferences(Request $request)
    {
        $userId = Auth::id();
        $preferences = $request->validate([
            'language' => 'sometimes|string',
            'timezone' => 'sometimes|string|timezone',
            'date_format' => 'sometimes|string',
            'time_format' => 'sometimes|string',
            'currency' => 'sometimes|string',
            'number_format' => 'sometimes|string',
        ]);

        $result = $this->preferenceService->setLocalePreferences($userId, $preferences);

        return response()->json([
            'success' => true,
            'preferences' => $result,
        ]);
    }

    /**
     * Get accessibility preferences (API).
     */
    public function getAccessibilityPreferences()
    {
        $userId = Auth::id();
        return response()->json([
            'preferences' => $this->preferenceService->getAccessibilityPreferences($userId),
        ]);
    }

    /**
     * Update accessibility preferences (API).
     */
    public function updateAccessibilityPreferences(Request $request)
    {
        $userId = Auth::id();
        $preferences = $request->validate([
            'font_size' => 'sometimes|in:small,medium,large,extra-large',
            'high_contrast' => 'sometimes|boolean',
            'reduced_motion' => 'sometimes|boolean',
            'screen_reader' => 'sometimes|boolean',
        ]);

        $result = $this->preferenceService->setAccessibilityPreferences($userId, $preferences);

        return response()->json([
            'success' => true,
            'preferences' => $result,
        ]);
    }
}
