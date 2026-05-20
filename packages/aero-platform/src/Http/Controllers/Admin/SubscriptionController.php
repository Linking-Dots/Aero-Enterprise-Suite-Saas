<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\SubscriptionAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionAdminService $svc
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/Subscriptions', [
            'subscriptions' => $this->svc->list($request->only(['status', 'plan_id', 'search'])),
            'filters' => $request->only(['status', 'plan_id', 'search']),
        ]);
    }

    public function show(Subscription $subscription): Response
    {
        return Inertia::render('Platform/Admin/Billing/P2/SubscriptionShow', [
            'subscription' => $subscription->load(['plan', 'owner']),
        ]);
    }

    public function cancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->svc->cancel($subscription, $request->string('reason')->toString());

        return back()->with('success', 'Subscription cancelled.');
    }

    public function changePlan(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate([
            'plan_id' => ['required', 'string', 'exists:plans,id'],
        ]);

        $this->svc->changePlan($subscription, $request->string('plan_id')->toString());

        return back()->with('success', 'Subscription plan updated.');
    }

    public function reactivate(Subscription $subscription): RedirectResponse
    {
        $this->svc->reactivate($subscription);

        return back()->with('success', 'Subscription reactivated.');
    }
}
