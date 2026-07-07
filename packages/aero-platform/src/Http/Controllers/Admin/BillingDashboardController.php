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
        return Inertia::render('Platform/Admin/Billing/Dashboard', [
            // overview loads on full render; live is refreshed by the page's ~30s
            // poll via a partial reload (only: ['live']).
            'overview' => fn () => $this->svc->overview(),
            'live'     => fn () => $this->svc->live(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->svc->live());
    }
}
