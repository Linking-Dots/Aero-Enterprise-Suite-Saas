<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\PlatformDashboardService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private PlatformDashboardService $svc) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Dashboard/Index', [
            'stats' => $this->svc->stats(),
            'recentTenants' => $this->svc->recentTenants(),
            'systemHealth' => $this->svc->systemHealth(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->svc->stats());
    }

    public function systemHealth(): JsonResponse
    {
        return response()->json($this->svc->systemHealth());
    }
}
