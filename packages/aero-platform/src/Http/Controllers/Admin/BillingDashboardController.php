<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\BillingDashboardService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class BillingDashboardController extends Controller
{
    public function __construct(
        private readonly BillingDashboardService $svc
    ) {}

    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/Dashboard', [
            // Single source of truth; the page's ~45s poll refreshes it via a
            // partial reload (only: ['overview']).
            'overview' => fn () => $this->svc->overview(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->svc->live());
    }
}
