<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class BillingDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/Dashboard', [
            'stats' => $this->buildStats(),
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->buildStats());
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStats(): array
    {
        $activeSubs = Subscription::where('status', Subscription::STATUS_ACTIVE)->count();
        $trialSubs = Subscription::where('status', Subscription::STATUS_TRIALING)->count();
        $cancelledSubs = Subscription::where('status', Subscription::STATUS_CANCELLED)->count();
        $mrr = Subscription::where('status', Subscription::STATUS_ACTIVE)->sum('amount');
        $revenue30 = Invoice::where('status', Invoice::STATUS_PAID)
            ->where('paid_at', '>=', now()->subDays(30))
            ->sum('total');

        return [
            'active_subscriptions' => $activeSubs,
            'trialing_subscriptions' => $trialSubs,
            'cancelled_subscriptions' => $cancelledSubs,
            'mrr' => number_format((float) $mrr, 2),
            'arr' => number_format((float) $mrr * 12, 2),
            'revenue_last_30_days' => number_format((float) $revenue30, 2),
            'plans_count' => Plan::where('status', 'active')->count(),
        ];
    }
}
