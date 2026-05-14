<?php

declare(strict_types=1);

namespace Aero\Core\Http\Controllers\Profile;

use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferenceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Core/Profile/NotificationPreferences', [
            'preferences' => [],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        return back()->with('success', 'Preferences updated.');
    }

    public function updateGlobal(Request $request): RedirectResponse
    {
        return back()->with('success', 'Global preferences updated.');
    }

    public function reset(Request $request): RedirectResponse
    {
        return back()->with('success', 'Preferences reset.');
    }
}
