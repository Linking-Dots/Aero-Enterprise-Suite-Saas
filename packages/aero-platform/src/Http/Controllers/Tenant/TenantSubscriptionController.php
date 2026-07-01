<?php

namespace Aero\Platform\Http\Controllers\Tenant;

use Aero\Auth\Models\User;
use Aero\Core\Services\ModuleAccessService;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantStat;
use Aero\Platform\Models\UsageRecord;
use Aero\Platform\Services\Billing\TenantSubscriptionPresenter;
use Aero\Platform\Services\ProductSubscriptionService;
use Aero\Platform\Services\SubscriptionLifecycleService;
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
        protected ProductSubscriptionService $productService,
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
        if ($tab === 'products') {
            abort_unless($access->canAccessComponent($request->user(), 'core', 'subscription', 'products')['allowed'] ?? false, 403);
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
            // Product subscriptions (separate from the plan) — shown on Overview + Products:
            'products' => in_array($tab, ['overview', 'products'], true) ? $this->resolveProducts($tenant->id) : null,
            // Products tab: the add-on catalog to subscribe to:
            'catalog' => $tab === 'products' ? $this->resolveCatalog($tenant->id) : null,
            // Plans tab:
            'plans' => $tab === 'plans' ? $this->resolvePlans($subscription) : null,
            'currentPlanId' => $tab === 'plans' ? $subscription?->plan_id : null,
            // Invoices tab:
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

    public function products(Request $request): Response
    {
        return $this->index($request->merge(['tab' => 'products']));
    }

    /**
     * Change the tenant's subscription plan.
     */
    public function changePlan(Request $request): RedirectResponse
    {
        // plans is a central table; validate against the central connection
        // (the default connection here is the tenant DB, which has no plans table).
        $request->validate([
            'plan_id' => ['required', 'exists:'.(new Plan)->getConnectionName().'.plans,id'],
        ]);

        $tenant = tenant();
        $subscription = Subscription::where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->firstOrFail();

        $newPlan = Plan::findOrFail($request->plan_id);
        $direction = $this->presenter->direction($subscription->plan, $newPlan);

        // Per-direction HRMAC authorization (route gates plans.view baseline).
        $access = app(ModuleAccessService::class);
        $action = $direction === 'upgrade' ? 'upgrade' : 'downgrade';
        abort_unless(
            $access->canPerformAction($request->user(), 'core', 'subscription', 'plans', $action)['allowed'] ?? false,
            403
        );

        if ($direction === 'upgrade') {
            $this->lifecycleService->upgrade($subscription, $newPlan);
            $message = 'Plan upgraded successfully.';
        } else {
            $this->lifecycleService->downgrade($subscription, $newPlan);
            $message = 'Plan change scheduled.';
        }

        return back()->with('success', $message);
    }

    /**
     * Stream the invoice PDF — 403 if invoice is not owned by the current tenant,
     * 404 if the PDF file is missing from storage.
     */
    public function downloadInvoice(Invoice $invoice): StreamedResponse
    {
        $tenant = tenant();

        abort_unless($this->presenter->invoiceBelongsToTenant($invoice, $tenant->id), 403);
        abort_if(empty($invoice->pdf_path) || ! Storage::exists($invoice->pdf_path), 404);

        return Storage::download($invoice->pdf_path, ($invoice->invoice_number ?? 'invoice').'.pdf');
    }

    /**
     * Cancel the tenant's subscription.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $tenant = tenant();
        $subscription = Subscription::where('billable_type', Tenant::class)
            ->where('billable_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->firstOrFail();

        $this->lifecycleService->cancel($subscription);

        return back()->with('success', 'Subscription cancellation scheduled.');
    }

    /**
     * Subscribe the tenant to an add-on product — a subscription separate from the plan.
     */
    public function subscribeProduct(Request $request): RedirectResponse
    {
        // products is a central table; validate against the central connection.
        $request->validate([
            'product_id' => ['required', 'exists:'.(new Product)->getConnectionName().'.products,id'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
        ]);

        $tenant = tenant();
        // Only products actually offered in the marketplace may be self-subscribed,
        // not any active product (some active products are intentionally hidden).
        $product = Product::marketplaceVisible()->findOrFail($request->product_id);
        $cycle = $request->input('billing_cycle', 'monthly');

        $alreadySubscribed = ProductSubscription::where('tenant_id', $tenant->id)
            ->where('product_id', $product->id)
            ->whereIn('status', ['active', 'trialing'])
            ->exists();

        if ($alreadySubscribed) {
            return back()->with('error', "You are already subscribed to {$product->name}.");
        }

        $this->productService->subscribe($tenant->id, $product->code, $cycle);

        return back()->with('success', "Subscribed to {$product->name}.");
    }

    /**
     * Cancel an add-on product subscription — 403 if it is not this tenant's.
     */
    public function cancelProduct(ProductSubscription $productSubscription): RedirectResponse
    {
        $tenant = tenant();

        abort_unless($this->presenter->productSubscriptionBelongsToTenant($productSubscription, $tenant->id), 403);

        $this->productService->cancel($productSubscription->id, 'Tenant self-service cancellation.');

        return back()->with('success', 'Add-on cancellation scheduled.');
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

        // Users live in the tenant DB (already tenant-scoped) — there is no
        // tenant_id column here, unlike the central users table. Count directly.
        $usersUsed = User::whereNull('deleted_at')->count();
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
        // hasAccess() = active OR trialing — matches the catalog's "subscribed"
        // flag so "Your add-ons" and the catalog never disagree.
        return ProductSubscription::where('tenant_id', $tenantId)
            ->hasAccess()
            ->with('product')
            ->get()
            ->map(fn (ProductSubscription $sub) => $this->presenter->product($sub))
            ->all();
    }

    /**
     * The add-on catalog (marketplace-visible products), each flagged with whether
     * the tenant already holds an active/trialing subscription to it.
     *
     * @return array<int,array<string,mixed>>
     */
    protected function resolveCatalog(string $tenantId): array
    {
        // Same definition as "Your add-ons" (resolveProducts) so the catalog's
        // "subscribed" flag can never disagree with the list.
        $subscribedProductIds = ProductSubscription::where('tenant_id', $tenantId)
            ->hasAccess()
            ->pluck('product_id')
            ->all();

        return Product::marketplaceVisible()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Product $p) => $this->presenter->catalogProduct($p, in_array($p->id, $subscribedProductIds, true)))
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    protected function resolveInvoices(string $tenantId, Request $request): array
    {
        $paginator = Invoice::where('billable_type', Tenant::class)
            ->where('billable_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $paginator->getCollection()->transform(fn (Invoice $invoice) => $this->presenter->invoice($invoice));

        return $paginator->toArray();
    }
}
