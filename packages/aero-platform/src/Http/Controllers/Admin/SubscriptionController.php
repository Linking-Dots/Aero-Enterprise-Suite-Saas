<?php

declare(strict_types=1);

namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\ProductSubscription;
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
            'subscriptions' => $this->svc->list($request->only(['status', 'tenant_id'])),
            'product_subscriptions' => ProductSubscription::with('product')
                ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
                ->when($request->input('tenant_id'), fn ($q, $t) => $q->where('tenant_id', $t))
                ->orderByDesc('created_at')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $request->only(['status', 'tenant_id']),
            'plans' => Plan::orderBy('name')->get(['id', 'name', 'price_monthly', 'price_annual']),
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

    public function upgrade(Request $request, Subscription $subscription): RedirectResponse
    {
        $request->validate(['plan_id' => 'required|integer|exists:plans,id']);
        $new = $this->svc->upgrade($subscription, $request->integer('plan_id'), $request->user()->id);

        return redirect()->route('platform.admin.billing.subscriptions.show', $new)
            ->with('success', 'Subscription upgraded');
    }

    public function reactivate(Subscription $subscription): RedirectResponse
    {
        $this->svc->reactivate($subscription);

        return back()->with('success', 'Subscription reactivated.');
    }
}
