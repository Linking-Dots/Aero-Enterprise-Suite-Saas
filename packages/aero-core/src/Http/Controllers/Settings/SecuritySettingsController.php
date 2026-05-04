<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecuritySettingsController extends Controller
{
    /**
     * Display the security settings landing page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Core/Settings/Security', [
            'title' => 'Security Settings',
        ]);
    }
}
