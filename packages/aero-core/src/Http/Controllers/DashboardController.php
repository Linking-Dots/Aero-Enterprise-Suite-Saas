<?php

namespace Aero\Core\Http\Controllers;

use Aero\Core\Models\Announcement;
use Aero\Core\Services\Dashboard\AdminDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private AdminDashboardService $dashboardService) {}

    public function index(): Response
    {
        $stats = $this->dashboardService->getTenantStats();
        $announcements = Announcement::active()
            ->with('author')
            ->orderBy('is_pinned', 'desc')
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Core/Dashboard/Index', [
            'stats' => $stats,
            'announcements' => $announcements,
        ]);
    }
}
