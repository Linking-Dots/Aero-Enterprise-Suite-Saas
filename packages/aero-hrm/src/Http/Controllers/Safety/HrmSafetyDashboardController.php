<?php

namespace Aero\HRM\Http\Controllers\Safety;

use Aero\HRM\Services\Safety\SafetyKpiService;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

final class HrmSafetyDashboardController extends Controller
{
    public function __construct(private SafetyKpiService $kpi) {}

    public function index(): Response
    {
        return Inertia::render('HRM/Safety/Dashboard', $this->kpi->dashboard());
    }
}
