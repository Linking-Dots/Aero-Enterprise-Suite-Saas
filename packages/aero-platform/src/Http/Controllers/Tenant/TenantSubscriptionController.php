<?php

namespace Aero\Platform\Http\Controllers\Tenant;

use Aero\Auth\Models\User;
use Aero\Core\Services\ModuleAccessService;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantStat;
use Aero\Platform\Models\UsageRecord;
use Aero\Platform\Services\Billing\TenantSubscriptionPresenter;
use Aero\Platform\Services\SubscriptionLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantSubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionLifecycleService $lifecycleService,
        protected TenantSubscriptionPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $tab = (string) $request->get('tab', 'overview');
        $tenant = tenant();

        // Defense in depth: route gates plans.view; stricter tabs re-check their
        // own HRMAC component so a crafted ?tab= cannot read data the user lacks.
        $access = app(ModuleAccessService::class);
        if ($tab === 'usage') {
            abort_unless($access->canAccessComponent($request->user(), 'core', 'subscription', 'usage')['allowed'] ?? false, 403);
        }
        if ($tab === 'invoices') {
            abort_unless($access->canAccessComponent($request->user(), 'core', 'subscription', 'invoices')['allowed'] ?? false, 403);
        }

        $subscription = $this->currentSubscription($tenant->id);
        $plan = $subscription?->plan;
        $usage = $this->resolveUsage($tenant->id, $subscription);
        $daysLeft = $this->resolveDaysLeft($subscription);

        return Inertia::render('Core/Subscription/Index', [
            'tab' => $tab,
            'summary' => $this->presenter->summary($subscription, $plan, $usage, $daysLeft),
            'plan' => $this->presenter->plan($plan, $subscription?->billing_cycle),
            // Overview + Usage tabs need detailed usage:
            'usage' => in_array($tab, ['overview', 'usage'], true) ? $usage : null,
            // Overview tab: read-only products:
            'products' => $tab === 'overview' ? $this->resolveProducts($tenant->id) : null,
            // Plans tab:
            'plans' => $tab === 'plans' ? $this->resolvePlans($subscription) : null,
            'currentPlanId' => $tab === 'plans' ? $subscription?->plan_id : null,
            // Invoices tab (filled in Task 7):
            'invoices' => $tab === 'invoices' ? $this->resolveInvoices($tenant->id, $request) : null,
        ]);
    }

    public function plans(Request $request): Response
    {
        return $this->index($request->merge(['tab' => 'plans']));
    }

    public function usage(Request $request): Response
    {
        return $this->index($request->merge(['tab' => 'usage']));
    }

    public function invoices(Request $request): Response
    {
        return $this->index($request->merge(['tab' => 'invoices']));
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

    protected function currentSubscription(string $tenantId): ?Subscription
    {
        return Subscription::where('billable_type', Tenant::class)
            ->where('billable_id', $tenantId)
            ->with('plan')
            ->latest()
            ->first();
    }

    protected function resolveDaysLeft(?Subscription $subscription): ?int
    {
        if ($subscription && $subscription->isTrialing() && $subscription->trial_ends_at) {
            return max(0, (int) now()->diffInDays($subscription->trial_ends_at, false));
        }

        return null;
    }

    /**
     * @return array{users:array{used:int,limit:int},storage:array{used_gb:float,limit_gb:int},metrics:array<string,mixed>}
     */
    protected function resolveUsage(string $tenantId, ?Subscription $subscription): array
    {
        $plan = $subscription?->plan;

        $usersUsed = User::where('tenant_id', $tenantId)->whereNull('deleted_at')->count();
        $usersLimit = (int) ($plan?->max_users ?? 0);

        $storageLimit = (int) ($plan?->max_storage_gb ?? 0);
        $latestStat = TenantStat::where('tenant_id', $tenantId)->orderByDesc('date')->first();
        if ($latestStat && $latestStat->storage_used_mb > 0) {
            $storageUsedGb = (float) $latestStat->storage_used_mb / 1024;
        } else {
            $tenant = Tenant::find($tenantId);
            $storageUsedGb = (float) data_get($tenant, 'metadata.storage_usage_gb', 0);
        }

        $metrics = [];
        if ($subscription) {
            $periodStart = $subscription->current_period_start ?? $subscription->starts_at ?? now()->startOfMonth();
            $metrics = UsageRecord::where('tenant_id', $tenantId)
                ->where('billing_period_start', '>=', $periodStart)
                ->get()
                ->groupBy('metric_name')
                ->map(fn ($group) => $group->sum('quantity'))
                ->toArray();
        }

        return $this->presenter->usage($usersUsed, $usersLimit, $storageUsedGb, $storageLimit, $metrics);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    protected function resolvePlans(?Subscription $subscription): array
    {
        return Plan::where('is_active', true)
            ->orderBy('monthly_price')
            ->get()
            ->map(fn (Plan $plan) => $this->presenter->plan($plan, $subscription?->billing_cycle))
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    protected function resolveProducts(string $tenantId): array
    {
        return ProductSubscription::where('tenant_id', $tenantId)
            ->active()
            ->with('product')
            ->get()
            ->map(fn (ProductSubscription $sub) => $this->presenter->product($sub))
            ->all();
    }

    protected function resolveInvoices(string $tenantId, Request $request): array
    {
        return ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1];
    }
}
