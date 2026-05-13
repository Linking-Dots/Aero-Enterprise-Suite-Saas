<?php

namespace Aero\Rfi\Http\Controllers;

use Aero\Rfi\Services\RfiService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * RfiDashboardController
 *
 * Handles the RFI module dashboard.
 */
class RfiDashboardController extends Controller
{
    public function __construct(
        protected RfiService $rfiService,
    ) {}

    /**
     * Display the RFI dashboard.
     */
    public function index(Request $request): Response
    {
        $stats = $this->rfiService->getDashboardStats();
        $completionRate = $this->rfiService->getCompletionRate();
        $resolutionRate = $this->rfiService->getObjectionResolutionRate();
        $pendingLocations = $this->rfiService->getLocationsPendingReview();

        // Dynamic widgets removed — widget registry is being deleted
        $dynamicWidgets = [];

        return Inertia::render('Rfi/Dashboard/Index', [
            'title' => 'RFI Dashboard',
            'stats' => $stats,
            'completionRate' => $completionRate,
            'resolutionRate' => $resolutionRate,
            'pendingLocations' => $pendingLocations,
            'dynamicWidgets' => $dynamicWidgets,
        ]);
    }
}
