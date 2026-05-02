<?php

namespace Aero\Platform\Http\Controllers\Tenant;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\UsageRecord;
use Aero\Platform\Services\SubscriptionLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionLifecycleService $lifecycleService
    ) {}

    /**
     * Show the subscription overview page.
     */
    public function index(): Response
    {
        $tenant = tenant();
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->first();

        $plan = $subscription?->plan;

        $daysLeft = null;
        if ($subscription && $subscription->isTrialing() && $subscription->trial_ends_at) {
            $daysLeft = (int) now()->diffInDays($subscription->trial_ends_at, false);
            $daysLeft = max(0, $daysLeft);
        }

        $usage = $this->resolveUsage($tenant->id, $subscription);

        return Inertia::render('Subscription/Index', [
            'subscription' => $subscription,
            'plan' => $plan,
            'usage' => $usage,
            'daysLeft' => $daysLeft,
        ]);
    }

    /**
     * Show available plans for upgrade/downgrade.
     */
    public function plans(): Response
    {
        $tenant = tenant();
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->first();

        $plans = Plan::where('is_active', true)
            ->orderBy('monthly_price')
            ->get();

        return Inertia::render('Subscription/Plans', [
            'plans' => $plans,
            'currentPlan' => $subscription?->plan,
            'subscription' => $subscription,
        ]);
    }

    /**
     * Change the tenant's subscription plan.
     */
    public function changePlan(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        $tenant = tenant();
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->firstOrFail();

        $newPlan = Plan::findOrFail($request->plan_id);
        $currentPlan = $subscription->plan;

        $isUpgrade = $newPlan->monthly_price > $currentPlan->monthly_price;

        if ($isUpgrade) {
            $updated = $this->lifecycleService->upgrade($subscription, $newPlan);
        } else {
            $updated = $this->lifecycleService->downgrade($subscription, $newPlan);
        }

        return response()->json([
            'message' => $isUpgrade ? 'Plan upgraded successfully.' : 'Plan downgrade scheduled.',
            'subscription' => $updated,
        ]);
    }

    /**
     * Show the usage dashboard.
     */
    public function usage(): Response
    {
        $tenant = tenant();
        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->first();

        $usage = $this->resolveUsage($tenant->id, $subscription);

        return Inertia::render('Subscription/Usage', [
            'subscription' => $subscription,
            'plan' => $subscription?->plan,
            'usage' => $usage,
        ]);
    }

    /**
     * Show the invoices list.
     */
    public function invoices(): Response
    {
        $tenant = tenant();

        $invoices = Subscription::where('tenant_id', $tenant->id)
            ->with('plan')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Subscription $sub) => [
                'id' => $sub->id,
                'plan_name' => $sub->plan?->name,
                'amount' => $sub->amount,
                'currency' => $sub->currency ?? 'USD',
                'billing_cycle' => $sub->billing_cycle,
                'status' => $sub->status,
                'starts_at' => $sub->starts_at?->toDateString(),
                'ends_at' => $sub->ends_at?->toDateString(),
                'stripe_id' => $sub->stripe_id,
                'created_at' => $sub->created_at?->toDateString(),
            ]);

        return Inertia::render('Subscription/Invoices', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * Build the usage array for a tenant's current billing period.
     *
     * @return array<string, mixed>
     */
    protected function resolveUsage(string $tenantId, ?Subscription $subscription): array
    {
        if (! $subscription) {
            return [];
        }

        $periodStart = $subscription->current_period_start
            ?? $subscription->starts_at
            ?? now()->startOfMonth();

        $records = UsageRecord::where('tenant_id', $tenantId)
            ->where('billing_period_start', '>=', $periodStart)
            ->get()
            ->groupBy('metric_name')
            ->map(fn ($group) => $group->sum('quantity'));

        return $records->toArray();
    }
}
