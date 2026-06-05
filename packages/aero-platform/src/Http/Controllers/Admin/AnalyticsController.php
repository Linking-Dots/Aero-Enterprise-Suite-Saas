<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\PlatformAnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(private PlatformAnalyticsService $svc) {}

    public function dashboard(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        return Inertia::render('Platform/Admin/Analytics/Revenue', [
            'revenue' => $this->svc->revenue($from, $to),
            'tenants' => $this->svc->tenantAnalytics($from, $to),
            'range' => compact('from', 'to'),
        ]);
    }

    public function revenue(Request $request): Response
    {
        [$from, $to] = $this->range($request);
        $bucket = $request->input('bucket', 'day');

        return Inertia::render('Platform/Admin/Analytics/Revenue', [
            'data' => $this->svc->revenue($from, $to, $bucket),
            'range' => compact('from', 'to'),
            'bucket' => $bucket,
        ]);
    }

    public function tenants(Request $request): Response
    {
        [$from, $to] = $this->range($request);

        return Inertia::render('Platform/Admin/Analytics/Tenants', [
            'data' => $this->svc->tenantAnalytics($from, $to),
            'range' => compact('from', 'to'),
        ]);
    }

    public function usage(): Response
    {
        return Inertia::render('Platform/Admin/Analytics/Usage', [
            'data' => $this->svc->usageAnalytics(),
        ]);
    }

    private function range(Request $request): array
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return [$from, $to];
    }
}
