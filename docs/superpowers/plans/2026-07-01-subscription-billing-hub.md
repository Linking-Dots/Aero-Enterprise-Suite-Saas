# Subscription / Billing Hub Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the broken 4-page Subscription cluster into one working, best-practice tabbed billing hub (Overview / Plans / Usage / Invoices) backed by correct data, real invoices, fixed routes, and correct HRMAC gates.

**Architecture:** A single Inertia hub page (`Core/Subscription/Index`) renders persistent KPIs + an `@aero/ui` `Tabs` strip + the active panel; tab switching is a partial `router.get` that re-fetches the active tab's data (the just-shipped Audit/Activity canon). Backend reshaping lives in `aero-platform` (`TenantSubscriptionController` + a pure `TenantSubscriptionPresenter`); routes live in the existing SaaS-only conditional block in `aero-core`. Pure shaping/guard logic is unit-tested; integrated tenant flows are verified live with Playwright.

**Tech Stack:** Laravel 12, Inertia v2 (`router.*`, `Inertia::render`), React 18, `@aero/ui`, HRMAC, PHPUnit, Playwright.

## Global Constraints

- Package-first: ALL backend code in `packages/aero-platform`; routes in `packages/aero-core/routes/web.php`; frontend in `packages/aero-ui`. Host apps are dumb wrappers.
- Dual-mode: standalone has **no** subscription routes — all routing stays inside the existing `class_exists('Aero\Platform\Http\Controllers\Tenant\TenantSubscriptionController')` block. Never break standalone boot.
- Frontend: `@aero/ui` components only; **no inline `style={}`**; Inertia v2 (`router.*`, `useForm`) only; registered icons only (`EllipsisHorizontalIcon` for row menus); money via `Intl.NumberFormat` using the record's currency.
- HRMAC: route middleware `hrmac:core.subscription.<component>.<action>`; React gating via `useHRMAC('core.subscription.<component>.<action>')`; in-controller defense-in-depth via `app(\Aero\Core\Services\ModuleAccessService::class)->canAccessComponent($user,'core','subscription',$component)['allowed']` and `->canPerformAction($user,'core','subscription',$component,$action)['allowed']`.
- Transactions: all writes in `DB::transaction()` (the lifecycle service already is).
- Theme: theme-drawer settings must reach Plans cards + Invoices table container, not only `.aeos-card-auto`.
- Verify version-sensitive APIs against Context7 / `docs/standards/tech-versions.md` — Laravel 12, React 18, Inertia v2, HeroUI, Tailwind v4. Never write framework code from memory.
- Models: `Plan`, `Subscription`, `Invoice`, `ProductSubscription`, `UsageRecord`, `TenantStat` are central (`CentralModel`/Cashier). Subscription↔Tenant is the polymorphic `billable_type=Aero\Platform\Models\Tenant, billable_id=tenant->id`.

**Grounded field facts (verified in source, do not re-guess):**
- `Plan`: `name`, `monthly_price` (decimal:2), `yearly_price`, `currency`, `features` (array cast), `max_users` (int, 0=unlimited), `max_storage_gb` (int, 0=unlimited), `duration_in_months`, `is_active`, `monthly_price`/`yearly_price` accessors `getEffectivePrice()`.
- `Subscription`: `plan_id`, `billing_cycle`, `amount`, `currency`, `status`, `current_period_start`, `trial_ends_at`, `billable_type`/`billable_id`; `->plan` (BelongsTo), `->billingInvoices()` (HasMany Invoice), `->isTrialing()`, scopes `active()`.
- `Invoice`: polymorphic `billable`, `invoice_number`, `status` (draft/issued/paid/void/overdue/refunded), `currency`, `total` (decimal:2), `billing_period_start`/`billing_period_end` (date), `paid_at`, `created_at`, `pdf_path`. `Invoice::STATUS_PAID` etc.
- `ProductSubscription`: `product_id`, `status`, `amount`, `currency`; `->product` (BelongsTo Product, `Product` has `name`); scope `active()`.
- `TenantStat`: `tenant_id`, `date`, `storage_used_mb`. Usage source (mirrors `PlanEntitlementService`): users used = `Aero\Auth\Models\User::where('tenant_id',$id)->whereNull('deleted_at')->count()`; storage used GB = latest `TenantStat` `storage_used_mb / 1024`, fallback `tenant->metadata['storage_usage_gb'] ?? 0`.
- `SubscriptionLifecycleService`: `upgrade($sub,$plan)`, `downgrade($sub,$plan)`, `cancel($sub)` — all transactional, return fresh `Subscription`.
- Nav collapse lever: `'collapse_nav' => true` on a submodule in `config/module.php` makes it a single leaf nav link (components stay registered for HRMAC). Implemented in `AbstractModuleProvider::registerNavigation()`.
- Inertia partial reload idiom (canon, `Core/AuditLogs/Index.jsx`): `router.get(route('core.subscription.index'), {tab,...}, {preserveState:true, preserveScroll:true, only:[...]})`; server computes the active tab's data from `$request->get('tab')`.
- `IndexPageLayout` props in use: `title`, `breadcrumb`, `description`, `tabs`, `actions`, `kpis` (array of `<Stat>`), `filters`, `table`, `pagination`.

---

### Task 1: `TenantSubscriptionPresenter` — pure shapers (unit-tested)

Extract all model→array shaping into a pure, dependency-free class so the controller stays thin and the shaping is unit-testable without tenant/DB context.

**Files:**
- Create: `packages/aero-platform/src/Services/Billing/TenantSubscriptionPresenter.php`
- Test: `packages/aero-platform/tests/Unit/Billing/TenantSubscriptionPresenterTest.php`

**Interfaces:**
- Produces (consumed by Tasks 3, 6, 7):
  - `plan(?Plan $plan, ?string $billingCycle): ?array` → `['id','name','price','interval','currency','features']`
  - `usage(int $usersUsed, int $usersLimit, float $storageUsedGb, int $storageLimitGb, array $metrics): array` → `['users'=>['used','limit'],'storage'=>['used_gb','limit_gb'],'metrics'=>[...]]`
  - `invoice(Invoice $invoice): array` → `['id','number','date','period_start','period_end','amount','currency','status','has_pdf']`
  - `product(ProductSubscription $sub): array` → `['id','name','status','price','currency']`
  - `summary(?Subscription $sub, ?Plan $plan, array $usage, ?int $daysLeft): array` → `['plan_name','price','interval','currency','status','days_left','users','storage']`
  - `direction(Plan $current, Plan $new): string` → `'upgrade'` | `'downgrade'` (by effective price; equal → `'upgrade'`)

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Billing;

use Aero\Platform\Models\Plan;
use Aero\Platform\Services\Billing\TenantSubscriptionPresenter;
use PHPUnit\Framework\TestCase;

class TenantSubscriptionPresenterTest extends TestCase
{
    private TenantSubscriptionPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->presenter = new TenantSubscriptionPresenter();
    }

    public function test_plan_shapes_monthly_price_and_interval(): void
    {
        $plan = new Plan(['name' => 'Pro', 'monthly_price' => '49.00', 'yearly_price' => '490.00', 'currency' => 'USD', 'features' => ['A', 'B']]);

        $shaped = $this->presenter->plan($plan, 'monthly');

        $this->assertSame('Pro', $shaped['name']);
        $this->assertEquals(49.00, $shaped['price']);
        $this->assertSame('month', $shaped['interval']);
        $this->assertSame('USD', $shaped['currency']);
        $this->assertSame(['A', 'B'], $shaped['features']);
    }

    public function test_plan_uses_yearly_price_for_yearly_cycle(): void
    {
        $plan = new Plan(['name' => 'Pro', 'monthly_price' => '49.00', 'yearly_price' => '490.00']);

        $shaped = $this->presenter->plan($plan, 'yearly');

        $this->assertEquals(490.00, $shaped['price']);
        $this->assertSame('year', $shaped['interval']);
    }

    public function test_plan_returns_null_when_no_plan(): void
    {
        $this->assertNull($this->presenter->plan(null, 'monthly'));
    }

    public function test_usage_nests_users_and_storage(): void
    {
        $usage = $this->presenter->usage(7, 10, 3.5, 50, ['api_calls' => 1200]);

        $this->assertSame(['used' => 7, 'limit' => 10], $usage['users']);
        $this->assertSame(['used_gb' => 3.5, 'limit_gb' => 50], $usage['storage']);
        $this->assertSame(['api_calls' => 1200], $usage['metrics']);
    }

    public function test_direction_is_upgrade_when_new_costs_more(): void
    {
        $current = new Plan(['monthly_price' => '20.00', 'duration_in_months' => 1]);
        $new = new Plan(['monthly_price' => '50.00', 'duration_in_months' => 1]);

        $this->assertSame('upgrade', $this->presenter->direction($current, $new));
    }

    public function test_direction_is_downgrade_when_new_costs_less(): void
    {
        $current = new Plan(['monthly_price' => '50.00', 'duration_in_months' => 1]);
        $new = new Plan(['monthly_price' => '20.00', 'duration_in_months' => 1]);

        $this->assertSame('downgrade', $this->presenter->direction($current, $new));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd packages/aero-platform && vendor/bin/phpunit tests/Unit/Billing/TenantSubscriptionPresenterTest.php`
Expected: FAIL — class `TenantSubscriptionPresenter` not found.

> If `packages/aero-platform` has no local `vendor/bin/phpunit`, run from the host app instead: `cd c:/laragon/www/aeos365 && vendor/bin/phpunit packages/aero-platform/tests/Unit/Billing/TenantSubscriptionPresenterTest.php`. Use whichever runner the repo already uses for aero-platform unit tests (check `phpunit.xml`).

- [ ] **Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Billing;

use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Models\Subscription;

/**
 * Pure shaping for the tenant self-service Subscription hub.
 *
 * Every method is side-effect free (no DB, no tenant context) so it can be
 * unit-tested directly and reused by the controller.
 */
class TenantSubscriptionPresenter
{
    /**
     * @return array{id:?string,name:?string,price:float,interval:string,currency:string,features:array}|null
     */
    public function plan(?Plan $plan, ?string $billingCycle): ?array
    {
        if (! $plan) {
            return null;
        }

        $isYearly = $billingCycle === 'yearly';
        $price = $isYearly ? (float) $plan->yearly_price : (float) $plan->monthly_price;
        $features = is_array($plan->features) ? array_values($plan->features) : [];

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'price' => $price,
            'interval' => $isYearly ? 'year' : 'month',
            'currency' => $plan->currency ?? 'USD',
            'features' => $features,
        ];
    }

    /**
     * @param  array<string,mixed>  $metrics
     * @return array{users:array{used:int,limit:int},storage:array{used_gb:float,limit_gb:int},metrics:array<string,mixed>}
     */
    public function usage(int $usersUsed, int $usersLimit, float $storageUsedGb, int $storageLimitGb, array $metrics): array
    {
        return [
            'users' => ['used' => $usersUsed, 'limit' => $usersLimit],
            'storage' => ['used_gb' => round($storageUsedGb, 2), 'limit_gb' => $storageLimitGb],
            'metrics' => $metrics,
        ];
    }

    /**
     * @return array{id:string,number:?string,date:?string,period_start:?string,period_end:?string,amount:float,currency:string,status:?string,has_pdf:bool}
     */
    public function invoice(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->invoice_number,
            'date' => optional($invoice->created_at)->toDateString(),
            'period_start' => optional($invoice->billing_period_start)->toDateString(),
            'period_end' => optional($invoice->billing_period_end)->toDateString(),
            'amount' => (float) $invoice->total,
            'currency' => $invoice->currency ?? 'USD',
            'status' => $invoice->status,
            'has_pdf' => ! empty($invoice->pdf_path),
        ];
    }

    /**
     * @return array{id:string,name:?string,status:?string,price:float,currency:string}
     */
    public function product(ProductSubscription $sub): array
    {
        return [
            'id' => $sub->id,
            'name' => $sub->product?->name,
            'status' => $sub->status,
            'price' => (float) $sub->amount,
            'currency' => $sub->currency ?? 'USD',
        ];
    }

    /**
     * @param  array{users:array{used:int,limit:int},storage:array{used_gb:float,limit_gb:int}}  $usage
     * @return array<string,mixed>
     */
    public function summary(?Subscription $sub, ?Plan $plan, array $usage, ?int $daysLeft): array
    {
        $shapedPlan = $this->plan($plan, $sub?->billing_cycle);

        return [
            'plan_name' => $shapedPlan['name'] ?? null,
            'price' => $shapedPlan['price'] ?? null,
            'interval' => $shapedPlan['interval'] ?? null,
            'currency' => $shapedPlan['currency'] ?? 'USD',
            'status' => $sub?->status,
            'days_left' => $daysLeft,
            'users' => $usage['users'],
            'storage' => $usage['storage'],
        ];
    }

    /**
     * Determine plan-change direction by effective price. Equal price → upgrade.
     */
    public function direction(Plan $current, Plan $new): string
    {
        return (float) $new->getEffectivePrice() >= (float) $current->getEffectivePrice()
            ? 'upgrade'
            : 'downgrade';
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Billing/TenantSubscriptionPresenterTest.php`
Expected: PASS (6 tests).

- [ ] **Step 5: Commit**

```bash
git add packages/aero-platform/src/Services/Billing/TenantSubscriptionPresenter.php packages/aero-platform/tests/Unit/Billing/TenantSubscriptionPresenterTest.php
git commit -m "feat(subscription): pure TenantSubscriptionPresenter shapers + unit tests"
```

---

### Task 2: Routes, HRMAC gate corrections, and nav collapse

Fix the wrong gates, add the two missing routes, and collapse the 3 sub-nav links into one hub link. Controller methods for the new routes land in Tasks 5–6; here we wire routing to method names that will exist.

**Files:**
- Modify: `packages/aero-core/routes/web.php:236-245` (the `class_exists(TenantSubscriptionController)` block)
- Modify: `packages/aero-core/config/module.php` (subscription submodule, ~line 136-179)

**Interfaces:**
- Produces (consumed by Tasks 3-8): routes `core.subscription.{index,plans,usage,invoices,change-plan,cancel,invoices.download}` with corrected gates; controller methods referenced: `index, plans, usage, invoices, changePlan, cancel, downloadInvoice`.

- [ ] **Step 1: Replace the route block**

In `packages/aero-core/routes/web.php`, replace the existing group body (lines 238-244) with:

```php
    Route::middleware(['auth:web', 'resolve.tenant.context'])->prefix('subscription')->name('core.subscription.')->group(function () use ($subscriptionController) {
        Route::get('/', [$subscriptionController, 'index'])->name('index')->middleware('hrmac:core.subscription.plans.view');
        Route::get('/plans', [$subscriptionController, 'plans'])->name('plans')->middleware('hrmac:core.subscription.plans.view');
        Route::get('/usage', [$subscriptionController, 'usage'])->name('usage')->middleware('hrmac:core.subscription.usage.view');
        Route::get('/invoices', [$subscriptionController, 'invoices'])->name('invoices')->middleware('hrmac:core.subscription.invoices.view');
        Route::get('/invoices/{invoice}/download', [$subscriptionController, 'downloadInvoice'])->name('invoices.download')->middleware('hrmac:core.subscription.invoices.download');
        Route::post('/change-plan', [$subscriptionController, 'changePlan'])->name('change-plan')->middleware('hrmac:core.subscription.plans.view');
        Route::post('/cancel', [$subscriptionController, 'cancel'])->name('cancel')->middleware('hrmac:core.subscription.plans.cancel');
    });
```

(Note: `change-plan` is gated at `plans.view` baseline; the controller authorizes `plans.upgrade` vs `plans.downgrade` per direction in Task 4.)

- [ ] **Step 2: Add `collapse_nav` to the subscription submodule**

In `packages/aero-core/config/module.php`, in the `subscription` submodule (the block with `'code' => 'subscription'`), add the `collapse_nav` key next to `show_in_nav`:

```php
            'route' => '/subscription',
            'priority' => 2,
            'show_in_nav' => true, // Real tenant self-service page; no other nav home
            'collapse_nav' => true, // Single hub link; Plans/Usage/Invoices are in-page tabs
            'plan' => 'saas',
```

Leave the three `components` (plans/usage/invoices) untouched — their HRMAC actions stay registered.

- [ ] **Step 3: Verify routing + gates resolve (no tenant context needed)**

Run from the SaaS host app:

```bash
cd c:/laragon/www/aeos365 && php artisan route:list --name=core.subscription
```

Expected: 7 rows — `index, plans, usage, invoices, invoices.download, change-plan, cancel` — each showing `auth:web`, `resolve.tenant.context`, and the corrected `hrmac:...` middleware (`usage.view`, `invoices.view`, `invoices.download`, `plans.cancel`).

- [ ] **Step 4: Verify standalone is unaffected**

```bash
cd c:/laragon/www/aeos365-standalone && php artisan route:list --name=core.subscription
```

Expected: empty / no such routes (the `class_exists` guard keeps them SaaS-only). Standalone boots clean.

- [ ] **Step 5: Commit**

```bash
git add packages/aero-core/routes/web.php packages/aero-core/config/module.php
git commit -m "fix(subscription): correct HRMAC gates, add cancel + invoice-download routes, collapse nav to one hub link"
```

---

### Task 3: Controller `index()` reshape (Overview / Plans / Usage tabs)

Rewrite the controller to render the hub with correctly shaped, per-tab data and in-controller per-tab gating. Invoices data is added in Task 7; here `invoices` is computed only as an empty placeholder for the invoices tab (Task 7 fills it).

**Files:**
- Modify: `packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php`

**Interfaces:**
- Consumes: `TenantSubscriptionPresenter` (Task 1), `SubscriptionLifecycleService`, `PlanEntitlementService` patterns.
- Produces (consumed by Task 8 frontend): `Core/Subscription/Index` props — `tab`, `summary`, `plan`, `usage`, `products`, `plans`, `currentPlanId`, `invoices`.

- [ ] **Step 1: Replace `index()` and add helpers + per-tab gate**

Replace the class body's `index()` and `resolveUsage()` and add the `plans()`/`usage()`/`invoices()` thin wrappers + shaping helpers. Constructor injects the presenter:

```php
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
        $usersLimit = (int) ($plan->max_users ?? 0);

        $storageLimit = (int) ($plan->max_storage_gb ?? 0);
        $latestStat = TenantStat::where('tenant_id', $tenantId)->orderByDesc('date')->first();
        if ($latestStat && $latestStat->storage_used_mb > 0) {
            $storageUsedGb = (float) $latestStat->storage_used_mb / 1024;
        } else {
            $tenant = Tenant::find($tenantId);
            $storageUsedGb = (float) (($tenant->metadata['storage_usage_gb'] ?? 0));
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

    // resolveInvoices() + changePlan() + cancel() + downloadInvoice() added in Tasks 4-7.
}
```

> Add a temporary stub so the class is valid until Task 7:
> ```php
> protected function resolveInvoices(string $tenantId, Request $request): array
> {
>     return ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1];
> }
> ```

- [ ] **Step 2: Boot-check the controller compiles**

Run: `cd c:/laragon/www/aeos365 && php artisan route:list --name=core.subscription`
Expected: still lists the 7 routes with no class/parse errors.

- [ ] **Step 3: Commit**

```bash
git add packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php
git commit -m "feat(subscription): reshape index() into per-tab hub data (overview/plans/usage) with in-controller per-tab gates"
```

---

### Task 4: `changePlan` → Inertia redirect + per-direction authorize

Make `changePlan` return a valid Inertia redirect (fixes the JSON-vs-Inertia crash) and authorize upgrade vs downgrade by direction.

**Files:**
- Modify: `packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php`

**Interfaces:**
- Consumes: `TenantSubscriptionPresenter::direction()` (Task 1), `SubscriptionLifecycleService::upgrade/downgrade`.
- Produces: `core.subscription.change-plan` returns `RedirectResponse` with a `success` flash.

- [ ] **Step 1: Add `changePlan()`**

```php
    public function changePlan(Request $request): RedirectResponse
    {
        $request->validate(['plan_id' => ['required', 'exists:plans,id']]);

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
```

- [ ] **Step 2: Verify the JSON return is gone**

Run: `grep -n "response()->json" packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php`
Expected: no matches (the only `changePlan` path returns `back()`).

- [ ] **Step 3: Boot-check**

Run: `cd c:/laragon/www/aeos365 && php artisan route:list --name=core.subscription.change-plan`
Expected: one row, no parse errors.

- [ ] **Step 4: Commit**

```bash
git add packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php
git commit -m "fix(subscription): changePlan returns Inertia redirect + per-direction HRMAC authorize"
```

---

### Task 5: `cancel` endpoint

Wire the existing `SubscriptionLifecycleService::cancel()` to a real tenant endpoint (the page's dead `cancel` route).

**Files:**
- Modify: `packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php`

**Interfaces:**
- Produces: `core.subscription.cancel` returns `RedirectResponse` with `success` flash.

- [ ] **Step 1: Add `cancel()`**

```php
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
```

- [ ] **Step 2: Boot-check**

Run: `cd c:/laragon/www/aeos365 && php artisan route:list --name=core.subscription.cancel`
Expected: one row gated `hrmac:core.subscription.plans.cancel`.

- [ ] **Step 3: Commit**

```bash
git add packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php
git commit -m "feat(subscription): add tenant cancel endpoint wired to SubscriptionLifecycleService::cancel"
```

---

### Task 6: `downloadInvoice` endpoint + tenant-ownership guard (unit-tested)

Stream the invoice PDF, but only if the invoice belongs to the current tenant (cross-tenant leak guard). Extract the ownership check into a pure, unit-testable presenter guard.

**Files:**
- Modify: `packages/aero-platform/src/Services/Billing/TenantSubscriptionPresenter.php`
- Modify: `packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php`
- Modify: `packages/aero-platform/tests/Unit/Billing/TenantSubscriptionPresenterTest.php`

**Interfaces:**
- Produces: `TenantSubscriptionPresenter::invoiceBelongsToTenant(Invoice $invoice, string $tenantId): bool`; route `core.subscription.invoices.download` streams or aborts 403/404.

- [ ] **Step 1: Write the failing guard test**

Add to `TenantSubscriptionPresenterTest`:

```php
    public function test_invoice_ownership_guard_matches_tenant(): void
    {
        $invoice = new \Aero\Platform\Models\Invoice([
            'billable_type' => \Aero\Platform\Models\Tenant::class,
            'billable_id' => 'tenant-123',
        ]);

        $this->assertTrue($this->presenter->invoiceBelongsToTenant($invoice, 'tenant-123'));
        $this->assertFalse($this->presenter->invoiceBelongsToTenant($invoice, 'tenant-999'));
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `vendor/bin/phpunit tests/Unit/Billing/TenantSubscriptionPresenterTest.php --filter test_invoice_ownership_guard`
Expected: FAIL — method not defined.

- [ ] **Step 3: Add the guard to the presenter**

```php
    public function invoiceBelongsToTenant(Invoice $invoice, string $tenantId): bool
    {
        return $invoice->billable_type === Tenant::class
            && (string) $invoice->billable_id === (string) $tenantId;
    }
```

(Add `use Aero\Platform\Models\Tenant;` to the presenter imports.)

- [ ] **Step 4: Run to verify it passes**

Run: `vendor/bin/phpunit tests/Unit/Billing/TenantSubscriptionPresenterTest.php`
Expected: PASS (all tests).

- [ ] **Step 5: Add `downloadInvoice()` to the controller**

```php
    public function downloadInvoice(Invoice $invoice): StreamedResponse
    {
        $tenant = tenant();

        abort_unless($this->presenter->invoiceBelongsToTenant($invoice, $tenant->id), 403);
        abort_if(empty($invoice->pdf_path) || ! Storage::exists($invoice->pdf_path), 404);

        return Storage::download($invoice->pdf_path, ($invoice->invoice_number ?? 'invoice').'.pdf');
    }
```

> Route-model binding: `{invoice}` resolves `Invoice` by UUID. Confirm `Invoice` uses the default key (`id`) — it does (`HasUuids`, `$keyType='string'`).

- [ ] **Step 6: Boot-check**

Run: `cd c:/laragon/www/aeos365 && php artisan route:list --name=core.subscription.invoices.download`
Expected: one row, `{invoice}` param, gated `hrmac:core.subscription.invoices.download`.

- [ ] **Step 7: Commit**

```bash
git add packages/aero-platform/src/Services/Billing/TenantSubscriptionPresenter.php packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php packages/aero-platform/tests/Unit/Billing/TenantSubscriptionPresenterTest.php
git commit -m "feat(subscription): tenant-authorized invoice PDF download + ownership guard unit test"
```

---

### Task 7: Real paginated invoices (`resolveInvoices`)

Replace the Task 3 stub with a real, paginated query over the `Invoice` model, shaped for the panel.

**Files:**
- Modify: `packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php`

**Interfaces:**
- Consumes: `TenantSubscriptionPresenter::invoice()` (Task 1).
- Produces: `invoices` prop = Laravel paginator array (`data, total, current_page, last_page, ...`) with each row shaped by `presenter->invoice()`.

- [ ] **Step 1: Replace the `resolveInvoices()` stub**

```php
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
```

- [ ] **Step 2: Boot-check**

Run: `cd c:/laragon/www/aeos365 && php artisan route:list --name=core.subscription.invoices`
Expected: routes still resolve; no parse errors.

- [ ] **Step 3: Commit**

```bash
git add packages/aero-platform/src/Http/Controllers/Tenant/TenantSubscriptionController.php
git commit -m "feat(subscription): real paginated Invoice data for the invoices tab"
```

---

### Task 8: Frontend hub — `Index.jsx` + 4 panels (delete old standalone pages)

Build the tabbed hub mirroring the `Core/AuditLogs/Index.jsx` canon. Extract each tab body into a focused panel component. Delete the now-folded standalone pages.

**Files:**
- Rewrite: `packages/aero-ui/resources/js/Pages/Core/Subscription/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Subscription/panels/OverviewPanel.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Subscription/panels/PlansPanel.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Subscription/panels/UsagePanel.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Subscription/panels/InvoicesPanel.jsx`
- Delete: `packages/aero-ui/resources/js/Pages/Core/Subscription/Plans.jsx`, `Usage.jsx`, `Invoices.jsx`

**Interfaces:**
- Consumes controller props (Task 3, 7): `tab, summary, plan, usage, products, plans, currentPlanId, invoices`.

- [ ] **Step 1: Create `panels/UsagePanel.jsx`**

```jsx
import { Card, CardBody, VStack, HStack, Box, Text, Eyebrow, Mono, Badge, Progress } from '@aero/ui';

function UsageBar({ label, used, limit, unit = '' }) {
  const pct = limit > 0 ? Math.min(100, Math.round((used / limit) * 100)) : 0;
  const intent = limit === 0 ? 'neutral' : pct >= 90 ? 'danger' : pct >= 70 ? 'warning' : 'success';
  return (
    <VStack gap={1}>
      <HStack gap={2} align="center">
        <Box grow><Text size="sm">{label}</Text></Box>
        <Mono size="sm" tone="secondary">{used}{unit} / {limit === 0 ? '∞' : `${limit}${unit}`}</Mono>
        <Badge intent={intent} size="sm">{limit === 0 ? '—' : `${pct}%`}</Badge>
      </HStack>
      <Progress value={pct} intent={intent} />
    </VStack>
  );
}

export default function UsagePanel({ usage }) {
  const u = usage ?? {};
  const users = u.users ?? { used: 0, limit: 0 };
  const storage = u.storage ?? { used_gb: 0, limit_gb: 0 };
  const metrics = Object.entries(u.metrics ?? {});

  return (
    <VStack gap={4}>
      <Card>
        <CardBody>
          <VStack gap={4}>
            <Eyebrow>Resource Usage</Eyebrow>
            <UsageBar label="Users" used={users.used} limit={users.limit} />
            <UsageBar label="Storage" used={storage.used_gb} limit={storage.limit_gb} unit=" GB" />
          </VStack>
        </CardBody>
      </Card>
      {metrics.length > 0 && (
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Metered Usage</Eyebrow>
              {metrics.map(([name, qty]) => (
                <HStack key={name} gap={2} align="center">
                  <Box grow><Text size="sm">{name}</Text></Box>
                  <Mono size="sm" tone="secondary">{qty}</Mono>
                </HStack>
              ))}
            </VStack>
          </CardBody>
        </Card>
      )}
    </VStack>
  );
}
```

- [ ] **Step 2: Create `panels/PlansPanel.jsx`**

```jsx
import { Card, CardBody, VStack, HStack, Box, Text, Eyebrow, Heading, Badge, Button } from '@aero/ui';

function money(amount, currency = 'USD') {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(amount) || 0);
}

function PlanCard({ plan, isCurrent, onChange, busy }) {
  return (
    <Card>
      <CardBody>
        <VStack gap={4}>
          <HStack gap={2} align="center">
            <Box grow>
              <VStack gap={1}>
                <Eyebrow>{plan.name}</Eyebrow>
                <HStack gap={1} align="baseline">
                  <Heading size="lg">{money(plan.price, plan.currency)}</Heading>
                  <Text tone="secondary" size="sm">/ {plan.interval ?? 'month'}</Text>
                </HStack>
              </VStack>
            </Box>
            {isCurrent && <Badge intent="success">Current Plan</Badge>}
          </HStack>
          {Array.isArray(plan.features) && plan.features.length > 0 && (
            <VStack gap={2}>
              {plan.features.map((feat, i) => (
                <HStack key={i} gap={2} align="center">
                  <Badge intent="success" size="sm">✓</Badge>
                  <Text size="sm">{feat}</Text>
                </HStack>
              ))}
            </VStack>
          )}
          {isCurrent ? (
            <Button intent="ghost" disabled fullWidth type="button">Active Plan</Button>
          ) : (
            <Button intent="primary" fullWidth type="button" loading={busy} onClick={() => onChange(plan.id)}>
              Switch to {plan.name}
            </Button>
          )}
        </VStack>
      </CardBody>
    </Card>
  );
}

export default function PlansPanel({ plans, currentPlanId, onChangePlan, onCancel, changingId, canCancel }) {
  const list = plans ?? [];
  return (
    <VStack gap={4}>
      {list.length === 0 ? (
        <Text tone="secondary">No plans available.</Text>
      ) : (
        <HStack gap={4} align="start" wrap>
          {list.map(plan => (
            <Box key={plan.id} grow>
              <PlanCard plan={plan} isCurrent={plan.id === currentPlanId}
                onChange={onChangePlan} busy={changingId === plan.id} />
            </Box>
          ))}
        </HStack>
      )}
      {canCancel && (
        <HStack gap={2} justify="end">
          <Button intent="danger" size="sm" type="button" onClick={onCancel}>Cancel Subscription</Button>
        </HStack>
      )}
    </VStack>
  );
}
```

- [ ] **Step 3: Create `panels/InvoicesPanel.jsx`**

```jsx
import { router } from '@inertiajs/react';
import { DataTable, Button, Badge, Pagination, VStack, Text, Mono, EmptyState } from '@aero/ui';

const STATUS_INTENT = { paid: 'success', issued: 'neutral', overdue: 'danger', void: 'neutral', refunded: 'warning', draft: 'neutral' };

function money(amount, currency = 'USD') {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(amount) || 0);
}
function fmtDate(d) { return d ? new Date(d).toLocaleDateString() : '—'; }

export default function InvoicesPanel({ invoices, loading, onPage }) {
  const inv = invoices ?? { data: [], total: 0, current_page: 1, last_page: 1 };

  const columns = [
    { key: 'number', label: 'Invoice #', width: '18%', render: r => <Mono size="sm">{r.number ?? '—'}</Mono> },
    { key: 'date', label: 'Date', width: '14%', render: r => fmtDate(r.date) },
    { key: 'period', label: 'Period', width: '24%',
      render: r => (r.period_start && r.period_end
        ? <Text size="sm">{fmtDate(r.period_start)} – {fmtDate(r.period_end)}</Text>
        : <Text tone="tertiary" size="sm">—</Text>) },
    { key: 'amount', label: 'Amount', width: '14%', render: r => <Text size="sm">{money(r.amount, r.currency)}</Text> },
    { key: 'status', label: 'Status', width: '12%',
      render: r => <Badge intent={STATUS_INTENT[r.status] ?? 'neutral'}>{r.status ?? '—'}</Badge> },
    { key: 'actions', label: '', width: '14%', align: 'right',
      render: r => r.has_pdf
        ? <Button intent="ghost" size="sm" type="button" leftIcon="arrowDownTray"
            onClick={() => window.open(route('core.subscription.invoices.download', r.id), '_blank')}>PDF</Button>
        : <Text tone="tertiary" size="sm">—</Text> },
  ];

  if (!loading && (inv.data ?? []).length === 0) {
    return <EmptyState icon="documentText" title="No invoices yet"
      description="Invoices will appear here once your billing history begins." />;
  }

  return (
    <VStack gap={3}>
      <DataTable columns={columns} rows={inv.data ?? []} loading={loading} />
      {inv.last_page > 1 && (
        <Pagination page={inv.current_page} total={inv.last_page} onChange={onPage} />
      )}
    </VStack>
  );
}
```

- [ ] **Step 4: Create `panels/OverviewPanel.jsx`**

```jsx
import { Card, CardBody, VStack, HStack, Box, Text, Eyebrow, Badge } from '@aero/ui';
import UsagePanel from './UsagePanel.jsx';

function money(amount, currency = 'USD') {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(amount) || 0);
}

export default function OverviewPanel({ summary, plan, usage, products }) {
  const s = summary ?? {};
  const prods = products ?? [];
  const features = plan?.features ?? [];

  return (
    <VStack gap={4}>
      <Card>
        <CardBody>
          <VStack gap={3}>
            <Eyebrow>Current Plan</Eyebrow>
            <HStack gap={2} align="baseline">
              <Text size="lg">{s.plan_name ?? '—'}</Text>
              {s.price != null && <Text tone="secondary" size="sm">{money(s.price, s.currency)} / {s.interval}</Text>}
              {s.status && <Badge intent={s.status === 'active' ? 'success' : 'warning'}>{s.status}</Badge>}
            </HStack>
            {s.days_left != null && <Text tone="secondary" size="sm">Trial: {s.days_left} days left</Text>}
          </VStack>
        </CardBody>
      </Card>

      <UsagePanel usage={usage} />

      <Card>
        <CardBody>
          <VStack gap={3}>
            <Eyebrow>Active Products</Eyebrow>
            {prods.length > 0 ? (
              <VStack gap={2}>
                {prods.map(p => (
                  <HStack key={p.id} gap={2} align="center">
                    <Box grow><Text size="sm">{p.name ?? '—'}</Text></Box>
                    <Text tone="secondary" size="sm">{money(p.price, p.currency)}</Text>
                    <Badge intent={p.status === 'active' ? 'success' : 'neutral'} size="sm">{p.status}</Badge>
                  </HStack>
                ))}
              </VStack>
            ) : (
              <Text tone="secondary" size="sm">No add-on products.</Text>
            )}
          </VStack>
        </CardBody>
      </Card>

      {features.length > 0 && (
        <Card>
          <CardBody>
            <VStack gap={3}>
              <Eyebrow>Plan Features</Eyebrow>
              <VStack gap={2}>
                {features.map((f, i) => (
                  <HStack key={i} gap={2} align="center">
                    <Badge intent="success" size="sm">✓</Badge>
                    <Text size="sm">{f}</Text>
                  </HStack>
                ))}
              </VStack>
            </VStack>
          </CardBody>
        </Card>
      )}
    </VStack>
  );
}
```

- [ ] **Step 5: Rewrite `Index.jsx` as the hub**

```jsx
import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { IndexPageLayout, Tabs, Stat, useToast, useHRMAC } from '@aero/ui';
import App from '@/Pages/App.jsx';
import OverviewPanel from './panels/OverviewPanel.jsx';
import PlansPanel from './panels/PlansPanel.jsx';
import UsagePanel from './panels/UsagePanel.jsx';
import InvoicesPanel from './panels/InvoicesPanel.jsx';

function money(amount, currency = 'USD') {
  if (amount == null) return '—';
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(Number(amount) || 0);
}

const ONLY = ['tab', 'summary', 'plan', 'usage', 'products', 'plans', 'currentPlanId', 'invoices'];

export default function SubscriptionIndex({ tab: initialTab, summary, plan, usage, products, plans, currentPlanId, invoices }) {
  const toast = useToast();
  const canUsage    = useHRMAC('core.subscription.usage.view');
  const canInvoices = useHRMAC('core.subscription.invoices.view');
  const canUpgrade  = useHRMAC('core.subscription.plans.upgrade');
  const canCancel   = useHRMAC('core.subscription.plans.cancel');

  const [tab, setTab] = useState(initialTab || 'overview');
  const [changingId, setChangingId] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const offStart  = router.on('start',  () => setLoading(true));
    const offFinish = router.on('finish', () => setLoading(false));
    return () => { offStart(); offFinish(); };
  }, []);

  const switchTab = next => {
    setTab(next);
    router.get(route('core.subscription.index'), { tab: next }, {
      preserveState: true, preserveScroll: true, only: ONLY,
    });
  };

  const changePlan = planId => {
    if (!confirm('Switch to this plan? This affects your billing.')) return;
    setChangingId(planId);
    router.post(route('core.subscription.change-plan'), { plan_id: planId }, {
      preserveScroll: true,
      onSuccess: () => toast.success('Plan updated.'),
      onError:   () => toast.error('Failed to change plan.'),
      onFinish:  () => setChangingId(null),
    });
  };

  const cancel = () => {
    if (!confirm('Cancel your subscription? It stays active until the end of the billing period.')) return;
    router.post(route('core.subscription.cancel'), {}, {
      preserveScroll: true,
      onSuccess: () => toast.success('Subscription cancellation scheduled.'),
      onError:   () => toast.error('Failed to cancel subscription.'),
    });
  };

  const invoicesPage = page => {
    router.get(route('core.subscription.index'), { tab: 'invoices', page }, {
      preserveState: true, preserveScroll: true, only: ['invoices', 'tab'],
    });
  };

  const tabs = [
    { value: 'overview', label: 'Overview' },
    { value: 'plans',    label: 'Plans' },
    canUsage    && { value: 'usage',    label: 'Usage' },
    canInvoices && { value: 'invoices', label: 'Invoices' },
  ].filter(Boolean);

  const s = summary ?? {};
  const usersStat = s.users ?? { used: 0, limit: 0 };
  const storageStat = s.storage ?? { used_gb: 0, limit_gb: 0 };

  return (
    <IndexPageLayout
      title="Subscription & Billing"
      breadcrumb={[
        { label: 'Dashboard', href: route('core.dashboard') },
        { label: 'Subscription & Billing' },
      ]}
      description="Manage your plan, usage, and billing history."
      tabs={<Tabs value={tab} tabs={tabs} onChange={switchTab} />}
      kpis={[
        <Stat key="plan" title="Current Plan" value={s.plan_name ?? '—'} icon="sparkles" iconTone="indigo" />,
        <Stat key="price" title="Billing" value={s.price != null ? money(s.price, s.currency) : '—'}
          description={s.interval ? `per ${s.interval}` : undefined} icon="currencyDollar" iconTone="success" />,
        <Stat key="users" title="Users"
          value={`${usersStat.used} / ${usersStat.limit === 0 ? '∞' : usersStat.limit}`} icon="users" iconTone="amber" />,
        <Stat key="storage" title="Storage"
          value={`${storageStat.used_gb} / ${storageStat.limit_gb === 0 ? '∞' : `${storageStat.limit_gb} GB`}`}
          icon="server" iconTone="amber" />,
      ]}
      table={
        tab === 'plans' ? (
          <PlansPanel plans={plans} currentPlanId={currentPlanId}
            onChangePlan={canUpgrade ? changePlan : () => toast.error('You lack permission to change plans.')}
            onCancel={cancel} changingId={changingId} canCancel={canCancel} />
        ) : tab === 'usage' ? (
          <UsagePanel usage={usage} />
        ) : tab === 'invoices' ? (
          <InvoicesPanel invoices={invoices} loading={loading} onPage={invoicesPage} />
        ) : (
          <OverviewPanel summary={summary} plan={plan} usage={usage} products={products} />
        )
      }
    />
  );
}

SubscriptionIndex.layout = page => <App title="Subscription & Billing">{page}</App>;
```

- [ ] **Step 6: Delete the folded standalone pages**

```bash
git rm packages/aero-ui/resources/js/Pages/Core/Subscription/Plans.jsx packages/aero-ui/resources/js/Pages/Core/Subscription/Usage.jsx packages/aero-ui/resources/js/Pages/Core/Subscription/Invoices.jsx
```

- [ ] **Step 7: Build the frontend**

Run: `cd c:/laragon/www/aeos365 && npm run build` (or confirm the running `vite` dev server recompiles with no errors).
Expected: build succeeds; no missing-import or unresolved-`route()` errors for `Core/Subscription/*`.

- [ ] **Step 8: Commit**

```bash
git add packages/aero-ui/resources/js/Pages/Core/Subscription/
git commit -m "feat(subscription): tabbed billing hub (Overview/Plans/Usage/Invoices) + focused panels; drop folded standalone pages"
```

---

### Task 9: Live Playwright UAT (the verification gate)

Verify the integrated tenant flows in the real app (the AEOS canon for tenant SaaS pages). Vite is already up.

**Login:** `http://democorp.aeos365.test` — `admin@democorp.com` / `Aeos365!Admin`. (democorp must be subscribed for the nav link to show; if absent, subscribe via platform admin first.)

- [ ] **Step 1: Confirm nav collapsed to one link**
  Navigate to the dashboard. Confirm the sidebar shows a single "Subscription & Billing" link (no separate Plans/Usage/Invoices children). Verify via the authenticated `#app` `data-page` prop (read `navigation`/`props`), not by guessing routes.

- [ ] **Step 2: Overview tab**
  Open `/subscription`. Expect 0 console errors. KPIs show real plan/billing/users/storage. Overview shows plan, usage bars, products (or "No add-on products."), features.

- [ ] **Step 3: Tab switching in place**
  Click Plans, Usage, Invoices. Each switches **without a full page reload** (watch the network: `XHR` partial `X-Inertia` requests with `only=`, not document loads). 0 console errors on each.

- [ ] **Step 4: Plans + change-plan single request**
  On Plans, click "Switch to <other plan>", confirm the dialog. Verify in the network tab **exactly one** `POST /subscription/change-plan` fires, returns an Inertia redirect (not JSON), and a success toast appears. No "valid Inertia response" error.

- [ ] **Step 5: Cancel**
  Click "Cancel Subscription", confirm. One `POST /subscription/cancel`, success toast, no error.

- [ ] **Step 6: Invoices + download authorization**
  Open Invoices. If empty, confirm the honest empty state. If invoices exist with a PDF, click PDF → downloads. Then attempt a foreign invoice id: `window.open(route('core.subscription.invoices.download', '<some-other-tenant-invoice-uuid>'))` → expect **403** (cross-tenant guard).

- [ ] **Step 7: Theme reaches Plans cards + Invoices table**
  Open the theme drawer, change card-style / radius / borders. Confirm the change visibly applies to the Plans plan cards and the Invoices table container (not just `.aeos-card-auto`).

- [ ] **Step 8: Run the backend unit suite once**
  Run: `cd c:/laragon/www/aeos365 && vendor/bin/phpunit packages/aero-platform/tests/Unit/Billing/TenantSubscriptionPresenterTest.php`
  Expected: PASS (all tests).

- [ ] **Step 9: Commit any fixes found during UAT**

```bash
git add -A
git commit -m "fix(subscription): UAT corrections for the billing hub"
```

---

## Self-Review

**Spec coverage:**
- §3 Frontend hub + panels → Task 8. ✓
- §4 Inertia load model → Task 3 (per-tab `$tab` data) + Task 8 (`only` partial reload, canon). ✓ (Note: chose the Audit canon's per-`$tab` server compute over `Inertia::lazy()` — same UX, matches just-shipped precedent, no post-mount fetch.)
- §5.1 Plan shaping → Task 1 `plan()`. ✓
- §5.2 Usage shaping → Task 1 `usage()` + Task 3 `resolveUsage()`. ✓
- §5.3 Invoices real → Task 7. ✓
- §5.4 Products read-only → Task 1 `product()` + Task 3 `resolveProducts()` + Task 8 OverviewPanel. ✓
- §5.5 changePlan Inertia + direction authorize → Task 4. ✓
- §5.6 cancel → Task 5. ✓
- §5.7 download + ownership guard → Task 6. ✓
- §6 routes + gates → Task 2. ✓
- §7 nav collapse → Task 2 (`collapse_nav`). ✓
- §8 dual-mode safety → Task 2 Step 4. ✓
- §9 live verification → Task 9. ✓

**Placeholder scan:** The Task 3 `resolveInvoices()` stub is explicitly temporary and replaced in Task 7 (not a placeholder left in final code). No TBD/TODO remain.

**Type consistency:** Presenter method names (`plan`, `usage`, `invoice`, `product`, `summary`, `direction`, `invoiceBelongsToTenant`) are identical across Tasks 1/3/4/6/7. Prop names (`tab, summary, plan, usage, products, plans, currentPlanId, invoices`) match between controller (Task 3/7) and frontend (Task 8). `ONLY` array in Task 8 lists exactly the controller's prop names.

**Open verification at execution (do not guess):** confirm the aero-platform PHPUnit runner path; confirm `Tenant::find($id)->metadata` is array-cast (fallback branch); confirm `Storage::download` uses the disk that `pdf_path` is stored against (adjust disk if `pdf_path` is absolute).
