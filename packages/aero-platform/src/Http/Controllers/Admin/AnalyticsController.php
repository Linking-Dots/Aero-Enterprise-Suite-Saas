<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\PlatformAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(private PlatformAnalyticsService $svc) {}

    /**
     * Platform Analytics command centre — the /analytics landing.
     */
    public function overview(Request $request): Response
    {
        $range = $request->input('range', '6m');

        return Inertia::render('Platform/Admin/Analytics/P2/Analytics', [
            'overview' => fn () => $this->svc->overview($range),
        ]);
    }

    // Revenue / Tenant / Usage analytics are now subsumed by the command centre
    // (overview). These legacy sub-routes redirect to it so old links keep working.
    public function dashboard(): RedirectResponse
    {
        return redirect()->route('platform.admin.analytics.index');
    }

    public function revenue(): RedirectResponse
    {
        return redirect()->route('platform.admin.analytics.index');
    }

    public function tenants(): RedirectResponse
    {
        return redirect()->route('platform.admin.analytics.index');
    }

    public function usage(): RedirectResponse
    {
        return redirect()->route('platform.admin.analytics.index');
    }

    private function range(Request $request): array
    {
        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        return [$from, $to];
    }
}
