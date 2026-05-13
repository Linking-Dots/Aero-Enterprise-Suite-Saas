<?php

namespace Aero\Quality\Http\Controllers;

use Illuminate\Routing\Controller;
use Inertia\Inertia;

class QualityController extends Controller
{
    public function dashboard()
    {
        // Dynamic widgets removed — widget registry is being deleted
        $dynamicWidgets = [];

        return Inertia::render('Quality/Dashboard', [
            'title' => 'Quality Dashboard',
            'dynamicWidgets' => $dynamicWidgets,
        ]);
    }

    public function index()
    {
        // Dynamic widgets removed — widget registry is being deleted
        $dynamicWidgets = [];

        return Inertia::render('Quality/Dashboard', [
            'title' => 'Quality Management',
            'dynamicWidgets' => $dynamicWidgets,
        ]);
    }
}
