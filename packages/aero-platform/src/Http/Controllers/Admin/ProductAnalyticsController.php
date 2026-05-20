<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\FunnelDefinition;
use Aero\Platform\Services\ProductAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProductAnalyticsController extends Controller
{
    public function __construct(
        private ProductAnalyticsService $svc,
        private AuditServiceInterface $audit,
    ) {}

    public function featureUsage(Request $request): Response
    {
        $days = (int) $request->input('days', 30);

        return Inertia::render('Platform/Admin/ProductAnalytics/Features', [
            'rows' => $this->svc->featureUsage($days),
            'days' => $days,
        ]);
    }

    public function cohorts(Request $request): Response
    {
        $months = (int) $request->input('months', 6);

        return Inertia::render('Platform/Admin/ProductAnalytics/Cohorts', [
            'matrix' => $this->svc->cohortRetention($months),
            'months' => $months,
        ]);
    }

    public function funnels(Request $request): Response
    {
        $funnels = FunnelDefinition::orderByDesc('id')->get();
        $selected = $request->input('funnel_id')
            ? $funnels->firstWhere('id', (int) $request->input('funnel_id'))
            : $funnels->first();

        return Inertia::render('Platform/Admin/ProductAnalytics/Funnels', [
            'funnels' => $funnels,
            'selected' => $selected,
            'analysis' => $selected ? $this->svc->funnelAnalysis($selected) : null,
        ]);
    }

    public function storeFunnel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'steps' => ['required', 'array', 'min:2'],
            'steps.*.event' => ['required', 'string', 'max:128'],
            'steps.*.label' => ['nullable', 'string', 'max:160'],
        ]);

        return DB::transaction(function () use ($data) {
            $funnel = FunnelDefinition::create([
                'name' => $data['name'],
                'steps' => $data['steps'],
                'created_by' => Auth::guard('landlord')->id(),
            ]);

            $this->audit->log(
                event: 'platform.funnel.created',
                action: 'manage',
                subject: $funnel,
                description: "Funnel created: {$funnel->name}",
            );

            return back()->with('success', 'Funnel saved.');
        });
    }

    public function adoption(): Response
    {
        return Inertia::render('Platform/Admin/ProductAnalytics/Adoption', [
            'data' => $this->svc->adoptionMetrics(),
        ]);
    }
}
