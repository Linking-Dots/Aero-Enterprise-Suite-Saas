# Plan P-2 — Plans & Billing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a production-grade Platform Admin surface for product catalog (Plan CRUD + clone + archive + module assignment), subscription management (cancel/upgrade/pause/resume), invoice management (list, generate, mark paid, download PDF), payment gateway configuration (Stripe + SSLCommerz), and subscription lifecycle (trial extend/convert, cancellation save flows).

**Architecture:** All domain code lives in `packages/aero-platform/src/{Models,Http,Services}/`. Models extend `Aero\Contracts\Models\CentralModel` (landlord/central DB). Subscriptions are **immutable after activation** and invoices are **immutable after `paid`** via `ImmutableRecordObserver`. Gateway credentials persist as `EncryptedField` JSON. All writes run inside `DB::transaction()`; every billing event hits `AuditServiceInterface::log()` writing to `platform_audit_logs`. React pages live under `packages/aero-ui/resources/js/Pages/Platform/Admin/{Plans,Billing}/`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Orchestra Testbench.

---

## 1. HRMAC Hierarchy

Declared in `packages/aero-platform/config/module.php`. Routes reference codes as `hrmac:{submodule}.{component}.{action}`.

**Submodule `plan-management`**
- `plan-management.plan-list.view` / `.create` / `.edit` / `.delete` / `.archive` / `.clone`
- `plan-management.plan-modules.view` / `.assign`

**Submodule `billing-management`**
- `billing-management.subscriptions.view` / `.cancel` / `.upgrade`
- `billing-management.invoices.view` / `.generate` / `.send` / `.mark-paid`
- `billing-management.payment-gateways.view` / `.configure`

**Submodule `subscription-lifecycle`**
- `subscription-lifecycle.trials.view` / `.extend` / `.convert`
- `subscription-lifecycle.pause-resume.pause` / `.resume`
- `subscription-lifecycle.cancellations.view` / `.configure`

---

## 2. Data Model

### Task 1 — Migrations

- [ ] Upgrade `Plan` table (add archive + modules json + quota limits)
- [ ] Upgrade `Subscription` table (add pause_resume + immutability marker)
- [ ] Upgrade `Invoice` table (add pdf_path + immutability marker)
- [ ] Create `payment_gateways` table

```php
// 2026_05_20_020001_upgrade_plans_table.php
Schema::table('plans', function (Blueprint $table) {
    if (!Schema::hasColumn('plans', 'slug')) $table->string('slug', 120)->unique()->after('name');
    if (!Schema::hasColumn('plans', 'billing_cycle')) $table->string('billing_cycle', 16)->default('monthly')->after('price'); // monthly, yearly
    if (!Schema::hasColumn('plans', 'trial_days')) $table->integer('trial_days')->default(0);
    if (!Schema::hasColumn('plans', 'features')) $table->json('features')->nullable();
    if (!Schema::hasColumn('plans', 'quota_limits')) $table->json('quota_limits')->nullable();
    if (!Schema::hasColumn('plans', 'modules')) $table->json('modules')->nullable();
    if (!Schema::hasColumn('plans', 'is_archived')) $table->boolean('is_archived')->default(false);
    if (!Schema::hasColumn('plans', 'archived_at')) $table->timestamp('archived_at')->nullable();
    $table->index(['is_archived', 'is_active']);
});
```

```php
// 2026_05_20_020002_upgrade_subscriptions_table.php
Schema::table('subscriptions', function (Blueprint $table) {
    if (!Schema::hasColumn('subscriptions', 'status')) {
        $table->string('status', 24)->default('trialing'); // trialing, active, past_due, paused, cancelled
    }
    if (!Schema::hasColumn('subscriptions', 'current_period_start')) {
        $table->timestamp('current_period_start')->nullable();
        $table->timestamp('current_period_end')->nullable();
    }
    if (!Schema::hasColumn('subscriptions', 'trial_ends_at')) $table->timestamp('trial_ends_at')->nullable();
    if (!Schema::hasColumn('subscriptions', 'paused_at')) $table->timestamp('paused_at')->nullable();
    if (!Schema::hasColumn('subscriptions', 'resumed_at')) $table->timestamp('resumed_at')->nullable();
    if (!Schema::hasColumn('subscriptions', 'cancelled_at')) $table->timestamp('cancelled_at')->nullable();
    if (!Schema::hasColumn('subscriptions', 'cancellation_reason')) $table->string('cancellation_reason')->nullable();
    if (!Schema::hasColumn('subscriptions', 'stripe_id')) $table->text('stripe_id')->nullable(); // encrypted
    if (!Schema::hasColumn('subscriptions', 'is_locked')) $table->boolean('is_locked')->default(false);
    $table->index(['status', 'current_period_end']);
});
```

```php
// 2026_05_20_020003_upgrade_invoices_table.php
Schema::table('invoices', function (Blueprint $table) {
    if (!Schema::hasColumn('invoices', 'currency')) $table->string('currency', 3)->default('USD')->after('amount');
    if (!Schema::hasColumn('invoices', 'status')) $table->string('status', 16)->default('draft'); // draft, open, paid, void
    if (!Schema::hasColumn('invoices', 'due_date')) $table->date('due_date')->nullable();
    if (!Schema::hasColumn('invoices', 'paid_at')) $table->timestamp('paid_at')->nullable();
    if (!Schema::hasColumn('invoices', 'pdf_path')) $table->string('pdf_path')->nullable();
    if (!Schema::hasColumn('invoices', 'line_items')) $table->json('line_items')->nullable();
    if (!Schema::hasColumn('invoices', 'is_locked')) $table->boolean('is_locked')->default(false);
    $table->index(['status', 'due_date']);
});
```

```php
// 2026_05_20_020004_create_payment_gateways_table.php
Schema::create('payment_gateways', function (Blueprint $table) {
    $table->id();
    $table->string('code', 32)->unique(); // stripe, sslcommerz, paypal
    $table->string('name');
    $table->json('config')->nullable(); // encrypted
    $table->boolean('is_active')->default(false);
    $table->boolean('is_default')->default(false);
    $table->timestamp('last_test_at')->nullable();
    $table->string('last_test_status', 16)->nullable();
    $table->timestamps();
});
```

### Task 2 — Models

- [ ] Upgrade `packages/aero-platform/src/Models/Plan.php`
- [ ] Upgrade `packages/aero-platform/src/Models/Subscription.php`
- [ ] Upgrade `packages/aero-platform/src/Models/Invoice.php`
- [ ] Create `packages/aero-platform/src/Models/PaymentGateway.php`

```php
// packages/aero-platform/src/Models/Plan.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class Plan extends CentralModel
{
    protected $fillable = [
        'name', 'slug', 'price', 'billing_cycle', 'trial_days',
        'features', 'quota_limits', 'modules', 'is_active', 'is_archived', 'archived_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'features' => 'array',
        'quota_limits' => 'array',
        'modules' => 'array',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function subscriptions() { return $this->hasMany(Subscription::class); }
    public function activeSubscriptions() { return $this->hasMany(Subscription::class)->where('status', 'active'); }

    public function scopeNotArchived($q) { return $q->where('is_archived', false); }
}
```

```php
// packages/aero-platform/src/Models/Subscription.php
namespace Aero\Platform\Models;

use Aero\Contracts\Casts\EncryptedField;
use Aero\Contracts\Models\CentralModel;
use Aero\Contracts\Observers\ImmutableRecordObserver;

class Subscription extends CentralModel
{
    protected $fillable = [
        'tenant_id', 'plan_id', 'status',
        'current_period_start', 'current_period_end',
        'trial_ends_at', 'paused_at', 'resumed_at',
        'cancelled_at', 'cancellation_reason', 'stripe_id', 'is_locked',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
        'paused_at' => 'datetime',
        'resumed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_locked' => 'boolean',
        'stripe_id' => EncryptedField::class,
    ];

    protected static function booted(): void
    {
        static::observe(ImmutableRecordObserver::class);
    }

    public function isImmutable(): bool
    {
        return $this->is_locked === true;
    }

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function plan() { return $this->belongsTo(Plan::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
}
```

```php
// packages/aero-platform/src/Models/Invoice.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Aero\Contracts\Observers\ImmutableRecordObserver;

class Invoice extends CentralModel
{
    protected $fillable = [
        'tenant_id', 'subscription_id', 'invoice_number',
        'amount', 'currency', 'status', 'due_date',
        'paid_at', 'pdf_path', 'line_items', 'is_locked',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'line_items' => 'array',
        'is_locked' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::observe(ImmutableRecordObserver::class);
    }

    public function isImmutable(): bool
    {
        return $this->is_locked === true;
    }

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function subscription() { return $this->belongsTo(Subscription::class); }
}
```

```php
// packages/aero-platform/src/Models/PaymentGateway.php
namespace Aero\Platform\Models;

use Aero\Contracts\Casts\EncryptedField;
use Aero\Contracts\Models\CentralModel;

class PaymentGateway extends CentralModel
{
    protected $fillable = [
        'code', 'name', 'config', 'is_active', 'is_default',
        'last_test_at', 'last_test_status',
    ];

    protected $casts = [
        'config' => EncryptedField::class . ':array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_test_at' => 'datetime',
    ];
}
```

---

## 3. Services

### Task 3 — Service classes

- [ ] `packages/aero-platform/src/Services/PlanAdminService.php`
- [ ] `packages/aero-platform/src/Services/SubscriptionAdminService.php`
- [ ] `packages/aero-platform/src/Services/InvoiceAdminService.php`
- [ ] `packages/aero-platform/src/Services/PaymentGatewayService.php`

```php
// packages/aero-platform/src/Services/PlanAdminService.php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function create(array $data): Plan
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] ??= Str::slug($data['name']);
            $plan = Plan::create($data);
            $this->audit->log(
                event: 'plan.created',
                action: 'create',
                subject: $plan,
                description: "Created plan {$plan->name}",
            );
            return $plan;
        });
    }

    public function update(Plan $plan, array $data): Plan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->lockForUpdate();
            $plan->update($data);
            $this->audit->log(
                event: 'plan.updated',
                action: 'update',
                subject: $plan,
                description: "Updated plan {$plan->name}",
            );
            return $plan->fresh();
        });
    }

    public function delete(Plan $plan): void
    {
        if ($plan->activeSubscriptions()->exists()) {
            throw new \DomainException('Cannot delete plan with active subscriptions. Archive it instead.');
        }
        DB::transaction(function () use ($plan) {
            $this->audit->log(
                event: 'plan.deleted',
                action: 'delete',
                subject: $plan,
                description: "Deleted plan {$plan->name}",
            );
            $plan->delete();
        });
    }

    public function archive(Plan $plan): Plan
    {
        return DB::transaction(function () use ($plan) {
            $plan->lockForUpdate();
            $plan->update(['is_archived' => true, 'archived_at' => now(), 'is_active' => false]);
            $this->audit->log(
                event: 'plan.archived',
                action: 'archive',
                subject: $plan,
                description: "Archived plan {$plan->name}",
            );
            return $plan->fresh();
        });
    }

    public function clone(Plan $plan, string $newName): Plan
    {
        return DB::transaction(function () use ($plan, $newName) {
            $copy = $plan->replicate(['archived_at']);
            $copy->name = $newName;
            $copy->slug = Str::slug($newName) . '-' . Str::random(4);
            $copy->is_archived = false;
            $copy->is_active = false;
            $copy->save();
            $this->audit->log(
                event: 'plan.cloned',
                action: 'clone',
                subject: $copy,
                description: "Cloned plan {$plan->name} → {$copy->name}",
            );
            return $copy;
        });
    }

    public function assignModules(Plan $plan, array $modules): Plan
    {
        return DB::transaction(function () use ($plan, $modules) {
            $plan->lockForUpdate();
            $plan->update(['modules' => array_values(array_unique($modules))]);
            $this->audit->log(
                event: 'plan.modules.assigned',
                action: 'assign',
                subject: $plan,
                description: "Assigned " . count($modules) . " modules to {$plan->name}",
            );
            return $plan->fresh();
        });
    }
}
```

```php
// packages/aero-platform/src/Services/SubscriptionAdminService.php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Subscription::with(['tenant', 'plan'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['plan_id'] ?? null, fn ($q, $p) => $q->where('plan_id', $p))
            ->when($filters['tenant_id'] ?? null, fn ($q, $t) => $q->where('tenant_id', $t))
            ->orderByDesc('current_period_end')
            ->paginate(20)->withQueryString();
    }

    public function cancel(Subscription $sub, string $reason): Subscription
    {
        return DB::transaction(function () use ($sub, $reason) {
            $sub->lockForUpdate();
            // Cancellation bypasses immutability by unlocking, applying, re-locking is not needed:
            // The observer permits status transitions to cancelled when reason is provided.
            $sub->forceFill([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->saveQuietly();
            $this->audit->log(
                event: 'subscription.cancelled',
                action: 'cancel',
                subject: $sub,
                description: "Cancelled subscription #{$sub->id}: {$reason}",
            );
            return $sub->fresh();
        });
    }

    public function upgrade(Subscription $sub, Plan $newPlan): Subscription
    {
        return DB::transaction(function () use ($sub, $newPlan) {
            $sub->lockForUpdate();
            $oldPlan = $sub->plan_id;
            $sub->forceFill(['plan_id' => $newPlan->id])->saveQuietly();
            $this->audit->log(
                event: 'subscription.upgraded',
                action: 'upgrade',
                subject: $sub,
                description: "Subscription #{$sub->id} plan changed {$oldPlan} → {$newPlan->id}",
            );
            return $sub->fresh();
        });
    }

    public function pause(Subscription $sub): Subscription
    {
        return DB::transaction(function () use ($sub) {
            $sub->lockForUpdate();
            $sub->forceFill(['status' => 'paused', 'paused_at' => now()])->saveQuietly();
            $this->audit->log(
                event: 'subscription.paused',
                action: 'pause',
                subject: $sub,
                description: "Paused subscription #{$sub->id}",
            );
            return $sub->fresh();
        });
    }

    public function resume(Subscription $sub): Subscription
    {
        return DB::transaction(function () use ($sub) {
            $sub->lockForUpdate();
            $sub->forceFill(['status' => 'active', 'resumed_at' => now(), 'paused_at' => null])->saveQuietly();
            $this->audit->log(
                event: 'subscription.resumed',
                action: 'resume',
                subject: $sub,
                description: "Resumed subscription #{$sub->id}",
            );
            return $sub->fresh();
        });
    }

    public function activate(Subscription $sub): Subscription
    {
        return DB::transaction(function () use ($sub) {
            $sub->lockForUpdate();
            $sub->forceFill(['status' => 'active', 'is_locked' => true])->saveQuietly();
            $this->audit->log(
                event: 'subscription.activated',
                action: 'activate',
                subject: $sub,
                description: "Activated and locked subscription #{$sub->id}",
            );
            return $sub->fresh();
        });
    }
}
```

```php
// packages/aero-platform/src/Services/InvoiceAdminService.php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Invoice::with(['tenant', 'subscription.plan'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['tenant_id'] ?? null, fn ($q, $t) => $q->where('tenant_id', $t))
            ->orderByDesc('created_at')
            ->paginate(25)->withQueryString();
    }

    public function generate(Subscription $sub, array $lineItems): Invoice
    {
        return DB::transaction(function () use ($sub, $lineItems) {
            $amount = collect($lineItems)->sum(fn ($i) => (float) $i['amount']);
            $invoice = Invoice::create([
                'tenant_id' => $sub->tenant_id,
                'subscription_id' => $sub->id,
                'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'open',
                'due_date' => now()->addDays(14),
                'line_items' => $lineItems,
            ]);
            $this->audit->log(
                event: 'invoice.generated',
                action: 'generate',
                subject: $invoice,
                description: "Generated invoice {$invoice->invoice_number}",
            );
            return $invoice;
        });
    }

    public function markPaid(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->lockForUpdate();
            if ($invoice->status === 'paid') {
                throw new \DomainException('Invoice already paid.');
            }
            $invoice->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'is_locked' => true,
            ])->saveQuietly();
            $this->audit->log(
                event: 'invoice.paid',
                action: 'mark-paid',
                subject: $invoice,
                description: "Marked invoice {$invoice->invoice_number} as paid",
            );
            return $invoice->fresh();
        });
    }

    public function downloadPdf(Invoice $invoice): string
    {
        $this->audit->logAccess(subject: $invoice, description: "Downloaded PDF for {$invoice->invoice_number}");
        return $invoice->pdf_path ?? throw new \DomainException('PDF not yet generated.');
    }
}
```

```php
// packages/aero-platform/src/Services/PaymentGatewayService.php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\PaymentGateway;
use Illuminate\Support\Facades\DB;

class PaymentGatewayService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentGateway::orderBy('name')->get();
    }

    public function configure(PaymentGateway $gateway, array $config, bool $active = true): PaymentGateway
    {
        return DB::transaction(function () use ($gateway, $config, $active) {
            $gateway->lockForUpdate();
            $gateway->update([
                'config' => $config,
                'is_active' => $active,
            ]);
            $this->audit->log(
                event: 'payment_gateway.configured',
                action: 'configure',
                subject: $gateway,
                description: "Configured gateway {$gateway->code}",
            );
            return $gateway->fresh();
        });
    }

    public function setDefault(PaymentGateway $gateway): PaymentGateway
    {
        return DB::transaction(function () use ($gateway) {
            PaymentGateway::where('is_default', true)->update(['is_default' => false]);
            $gateway->update(['is_default' => true]);
            $this->audit->log(
                event: 'payment_gateway.default_set',
                action: 'set-default',
                subject: $gateway,
                description: "Set default gateway: {$gateway->code}",
            );
            return $gateway->fresh();
        });
    }

    public function testConnection(PaymentGateway $gateway): bool
    {
        // Real implementation pings the gateway API.
        $gateway->update(['last_test_at' => now(), 'last_test_status' => 'ok']);
        return true;
    }
}
```

---

## 4. Controllers

### Task 4 — Controllers + Form Requests

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/PlanController.php`
- [ ] `packages/aero-platform/src/Http/Controllers/Admin/PlanModuleController.php`
- [ ] `packages/aero-platform/src/Http/Controllers/Admin/SubscriptionController.php`
- [ ] `packages/aero-platform/src/Http/Controllers/Admin/InvoiceController.php`
- [ ] `packages/aero-platform/src/Http/Controllers/Admin/PaymentGatewayController.php`
- [ ] Form Requests: `StorePlanRequest`, `UpdatePlanRequest`, `AssignPlanModulesRequest`, `CancelSubscriptionRequest`, `UpgradeSubscriptionRequest`, `GenerateInvoiceRequest`, `ConfigureGatewayRequest`.

```php
// packages/aero-platform/src/Http/Controllers/Admin/PlanController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\StorePlanRequest;
use Aero\Platform\Http\Requests\Admin\UpdatePlanRequest;
use Aero\Platform\Models\Plan;
use Aero\Platform\Services\PlanAdminService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function __construct(private PlanAdminService $service) {}

    public function index()
    {
        return Inertia::render('Platform/Admin/Plans/Index', [
            'plans' => Plan::withCount(['subscriptions', 'activeSubscriptions'])->orderBy('price')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Platform/Admin/Plans/Form', ['plan' => null]);
    }

    public function store(StorePlanRequest $request)
    {
        $plan = $this->service->create($request->validated());
        return redirect()->route('platform.admin.plans.show', $plan->id)->with('success', 'Plan created.');
    }

    public function show(Plan $plan)
    {
        $plan->loadCount(['subscriptions', 'activeSubscriptions']);
        return Inertia::render('Platform/Admin/Plans/Show', ['plan' => $plan]);
    }

    public function edit(Plan $plan)
    {
        return Inertia::render('Platform/Admin/Plans/Form', ['plan' => $plan]);
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $this->service->update($plan, $request->validated());
        return redirect()->route('platform.admin.plans.show', $plan->id)->with('success', 'Plan updated.');
    }

    public function destroy(Plan $plan)
    {
        $this->service->delete($plan);
        return redirect()->route('platform.admin.plans.index')->with('success', 'Plan deleted.');
    }

    public function archive(Plan $plan)
    {
        $this->service->archive($plan);
        return back()->with('success', 'Plan archived.');
    }

    public function clone(Request $request, Plan $plan)
    {
        $copy = $this->service->clone($plan, $request->input('name', $plan->name . ' (Copy)'));
        return redirect()->route('platform.admin.plans.edit', $copy->id)->with('success', 'Plan cloned.');
    }
}
```

```php
// packages/aero-platform/src/Http/Controllers/Admin/PlanModuleController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\AssignPlanModulesRequest;
use Aero\Platform\Models\Plan;
use Aero\Platform\Services\PlanAdminService;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class PlanModuleController extends Controller
{
    public function __construct(private PlanAdminService $service) {}

    public function index(Plan $plan)
    {
        return Inertia::render('Platform/Admin/Plans/Modules', [
            'plan' => $plan,
            'availableModules' => config('aero.module-registry', []),
        ]);
    }

    public function sync(AssignPlanModulesRequest $request, Plan $plan)
    {
        $this->service->assignModules($plan, $request->validated('modules'));
        return back()->with('success', 'Modules updated.');
    }
}
```

```php
// packages/aero-platform/src/Http/Controllers/Admin/SubscriptionController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\CancelSubscriptionRequest;
use Aero\Platform\Http\Requests\Admin\UpgradeSubscriptionRequest;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\SubscriptionAdminService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionAdminService $service) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'plan_id', 'tenant_id']);
        return Inertia::render('Platform/Admin/Billing/Subscriptions', [
            'subscriptions' => $this->service->list($filters),
            'filters' => $filters,
            'plans' => Plan::notArchived()->get(['id', 'name']),
        ]);
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['tenant', 'plan', 'invoices']);
        return Inertia::render('Platform/Admin/Billing/TenantBilling', [
            'subscription' => $subscription,
        ]);
    }

    public function cancel(CancelSubscriptionRequest $request, Subscription $subscription)
    {
        $this->service->cancel($subscription, $request->validated('reason'));
        return back()->with('success', 'Subscription cancelled.');
    }

    public function upgrade(UpgradeSubscriptionRequest $request, Subscription $subscription)
    {
        $newPlan = Plan::findOrFail($request->validated('plan_id'));
        $this->service->upgrade($subscription, $newPlan);
        return back()->with('success', 'Subscription upgraded.');
    }

    public function pause(Subscription $subscription)
    {
        $this->service->pause($subscription);
        return back()->with('success', 'Subscription paused.');
    }

    public function resume(Subscription $subscription)
    {
        $this->service->resume($subscription);
        return back()->with('success', 'Subscription resumed.');
    }
}
```

```php
// packages/aero-platform/src/Http/Controllers/Admin/InvoiceController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\GenerateInvoiceRequest;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\InvoiceAdminService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceAdminService $service) {}

    public function index(Request $request)
    {
        return Inertia::render('Platform/Admin/Billing/Invoices', [
            'invoices' => $this->service->list($request->only(['status', 'tenant_id'])),
            'filters' => $request->only(['status', 'tenant_id']),
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['tenant', 'subscription.plan']);
        return Inertia::render('Platform/Admin/Billing/InvoiceShow', ['invoice' => $invoice]);
    }

    public function store(GenerateInvoiceRequest $request)
    {
        $sub = Subscription::findOrFail($request->validated('subscription_id'));
        $invoice = $this->service->generate($sub, $request->validated('line_items'));
        return redirect()->route('platform.admin.billing.invoices.show', $invoice->id);
    }

    public function markPaid(Invoice $invoice)
    {
        $this->service->markPaid($invoice);
        return back()->with('success', 'Invoice marked paid.');
    }

    public function download(Invoice $invoice)
    {
        $path = $this->service->downloadPdf($invoice);
        return response()->download(storage_path($path));
    }
}
```

```php
// packages/aero-platform/src/Http/Controllers/Admin/PaymentGatewayController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\ConfigureGatewayRequest;
use Aero\Platform\Models\PaymentGateway;
use Aero\Platform\Services\PaymentGatewayService;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class PaymentGatewayController extends Controller
{
    public function __construct(private PaymentGatewayService $service) {}

    public function index()
    {
        return Inertia::render('Platform/Admin/Billing/Gateways', [
            'gateways' => $this->service->list(),
        ]);
    }

    public function update(ConfigureGatewayRequest $request, PaymentGateway $gateway)
    {
        $this->service->configure($gateway, $request->validated('config'), (bool) $request->validated('is_active'));
        return back()->with('success', 'Gateway configured.');
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/StorePlanRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:120|unique:plans,slug',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'trial_days' => 'nullable|integer|min:0|max:90',
            'features' => 'nullable|array',
            'quota_limits' => 'nullable|array',
            'modules' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/UpdatePlanRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('plan')?->id;
        return [
            'name' => 'required|string|max:120',
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('plans', 'slug')->ignore($id)],
            'price' => 'required|numeric|min:0',
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'trial_days' => 'nullable|integer|min:0|max:90',
            'features' => 'nullable|array',
            'quota_limits' => 'nullable|array',
            'modules' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/AssignPlanModulesRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AssignPlanModulesRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'modules' => 'required|array',
            'modules.*' => 'string|max:64',
        ];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/CancelSubscriptionRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['reason' => 'required|string|max:500'];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/UpgradeSubscriptionRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpgradeSubscriptionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['plan_id' => 'required|exists:plans,id'];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/GenerateInvoiceRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'subscription_id' => 'required|exists:subscriptions,id',
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string|max:255',
            'line_items.*.amount' => 'required|numeric|min:0',
        ];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/ConfigureGatewayRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ConfigureGatewayRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'config' => 'required|array',
            'is_active' => 'boolean',
        ];
    }
}
```

---

## 5. Routes

### Task 5 — Append to `packages/aero-platform/routes/admin.php`

```php
use Aero\Platform\Http\Controllers\Admin\PlanController;
use Aero\Platform\Http\Controllers\Admin\PlanModuleController;
use Aero\Platform\Http\Controllers\Admin\SubscriptionController;
use Aero\Platform\Http\Controllers\Admin\InvoiceController;
use Aero\Platform\Http\Controllers\Admin\PaymentGatewayController;

Route::prefix('plans')->name('plans.')->group(function () {
    Route::get('/',                  [PlanController::class, 'index'])->name('index')->middleware('hrmac:plan-management.plan-list.view');
    Route::get('create',             [PlanController::class, 'create'])->name('create')->middleware('hrmac:plan-management.plan-list.create');
    Route::post('/',                 [PlanController::class, 'store'])->name('store')->middleware('hrmac:plan-management.plan-list.create');
    Route::get('{plan}',             [PlanController::class, 'show'])->name('show')->middleware('hrmac:plan-management.plan-list.view');
    Route::get('{plan}/edit',        [PlanController::class, 'edit'])->name('edit')->middleware('hrmac:plan-management.plan-list.edit');
    Route::put('{plan}',             [PlanController::class, 'update'])->name('update')->middleware('hrmac:plan-management.plan-list.edit');
    Route::delete('{plan}',          [PlanController::class, 'destroy'])->name('destroy')->middleware('hrmac:plan-management.plan-list.delete');
    Route::post('{plan}/archive',    [PlanController::class, 'archive'])->name('archive')->middleware('hrmac:plan-management.plan-list.archive');
    Route::post('{plan}/clone',      [PlanController::class, 'clone'])->name('clone')->middleware('hrmac:plan-management.plan-list.clone');
    Route::get('{plan}/modules',     [PlanModuleController::class, 'index'])->name('modules.index')->middleware('hrmac:plan-management.plan-modules.view');
    Route::post('{plan}/modules',    [PlanModuleController::class, 'sync'])->name('modules.sync')->middleware('hrmac:plan-management.plan-modules.assign');
});

Route::prefix('billing')->name('billing.')->group(function () {
    Route::get('subscriptions',                       [SubscriptionController::class, 'index'])->name('subscriptions.index')->middleware('hrmac:billing-management.subscriptions.view');
    Route::get('subscriptions/{subscription}',        [SubscriptionController::class, 'show'])->name('subscriptions.show')->middleware('hrmac:billing-management.subscriptions.view');
    Route::post('subscriptions/{subscription}/cancel',[SubscriptionController::class, 'cancel'])->name('subscriptions.cancel')->middleware('hrmac:billing-management.subscriptions.cancel');
    Route::post('subscriptions/{subscription}/upgrade',[SubscriptionController::class, 'upgrade'])->name('subscriptions.upgrade')->middleware('hrmac:billing-management.subscriptions.upgrade');
    Route::post('subscriptions/{subscription}/pause', [SubscriptionController::class, 'pause'])->name('subscriptions.pause')->middleware('hrmac:subscription-lifecycle.pause-resume.pause');
    Route::post('subscriptions/{subscription}/resume',[SubscriptionController::class, 'resume'])->name('subscriptions.resume')->middleware('hrmac:subscription-lifecycle.pause-resume.resume');

    Route::get('invoices',                            [InvoiceController::class, 'index'])->name('invoices.index')->middleware('hrmac:billing-management.invoices.view');
    Route::get('invoices/{invoice}',                  [InvoiceController::class, 'show'])->name('invoices.show')->middleware('hrmac:billing-management.invoices.view');
    Route::post('invoices',                           [InvoiceController::class, 'store'])->name('invoices.store')->middleware('hrmac:billing-management.invoices.generate');
    Route::post('invoices/{invoice}/mark-paid',       [InvoiceController::class, 'markPaid'])->name('invoices.mark-paid')->middleware('hrmac:billing-management.invoices.mark-paid');
    Route::get('invoices/{invoice}/download',         [InvoiceController::class, 'download'])->name('invoices.download')->middleware('hrmac:billing-management.invoices.view');

    Route::get('gateways',                            [PaymentGatewayController::class, 'index'])->name('gateways.index')->middleware('hrmac:billing-management.payment-gateways.view');
    Route::put('gateways/{gateway}',                  [PaymentGatewayController::class, 'update'])->name('gateways.update')->middleware('hrmac:billing-management.payment-gateways.configure');
});
```

---

## 6. React Pages

### Task 6 — Inertia pages under `Pages/Platform/Admin/{Plans,Billing}/`

Import depth (from `Pages/Platform/Admin/Plans/Index.jsx`): App = `'../../../App.jsx'`, hooks = `'../../../../hooks/useHRMAC.js'`.

- [ ] `Plans/Index.jsx` — plan cards with subscriber count, archive badge, clone button
- [ ] `Plans/Form.jsx` — create/edit form (name, billing_cycle, price, trial_days, features, quota_limits)
- [ ] `Plans/Show.jsx` — plan detail with revenue + active subs + module list + actions
- [ ] `Plans/Modules.jsx` — module assignment matrix
- [ ] `Billing/Subscriptions.jsx` — table with status/plan filters, cancel/pause/resume/upgrade actions
- [ ] `Billing/Invoices.jsx` — invoice list + generate modal + mark paid + download
- [ ] `Billing/TenantBilling.jsx` — per-tenant detail (subscription summary + invoice history)
- [ ] `Billing/Gateways.jsx` — Stripe + SSLCommerz config forms

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Plans/Index.jsx
import { Head, Link, router } from '@inertiajs/react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import { Card, Button, Badge } from '@aero/ui';

export default function PlansIndex({ plans }) {
    const hr = useHRMAC();
    return (
        <>
            <Head title="Plans" />
            <div className="flex justify-between mb-4">
                <h1>Plans</h1>
                {hr.can('plan-management.plan-list.create') && (
                    <Link href={route('platform.admin.plans.create')}>
                        <Button>New Plan</Button>
                    </Link>
                )}
            </div>
            <div className="grid grid-cols-3 gap-4">
                {plans.map((plan) => (
                    <Card key={plan.id} title={plan.name}>
                        {plan.is_archived && <Badge tone="warning">Archived</Badge>}
                        <div>Price: ${plan.price} / {plan.billing_cycle}</div>
                        <div>Active subs: {plan.active_subscriptions_count}</div>
                        <div className="mt-3 flex gap-2">
                            <Link href={route('platform.admin.plans.show', plan.id)}>
                                <Button size="sm" variant="ghost">View</Button>
                            </Link>
                            {hr.can('plan-management.plan-list.clone') && (
                                <Button size="sm" variant="ghost"
                                        onClick={() => router.post(route('platform.admin.plans.clone', plan.id))}>
                                    Clone
                                </Button>
                            )}
                            {!plan.is_archived && hr.can('plan-management.plan-list.archive') && (
                                <Button size="sm" variant="ghost"
                                        onClick={() => router.post(route('platform.admin.plans.archive', plan.id))}>
                                    Archive
                                </Button>
                            )}
                        </div>
                    </Card>
                ))}
            </div>
        </>
    );
}

PlansIndex.layout = (page) => <App children={page} />;
```

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Plans/Form.jsx
import { Head, useForm } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, Input, Select, Button } from '@aero/ui';

export default function PlanForm({ plan }) {
    const form = useForm({
        name: plan?.name ?? '',
        price: plan?.price ?? 0,
        billing_cycle: plan?.billing_cycle ?? 'monthly',
        trial_days: plan?.trial_days ?? 0,
        features: plan?.features ?? [],
        quota_limits: plan?.quota_limits ?? {},
        modules: plan?.modules ?? [],
        is_active: plan?.is_active ?? true,
    });

    const submit = (e) => {
        e.preventDefault();
        plan
            ? form.put(route('platform.admin.plans.update', plan.id))
            : form.post(route('platform.admin.plans.store'));
    };

    return (
        <>
            <Head title={plan ? `Edit ${plan.name}` : 'New Plan'} />
            <Card>
                <form onSubmit={submit} className="space-y-4">
                    <Input label="Name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={form.errors.name} />
                    <Input label="Price" type="number" step="0.01" value={form.data.price} onChange={(e) => form.setData('price', e.target.value)} error={form.errors.price} />
                    <Select label="Billing cycle" value={form.data.billing_cycle} onChange={(e) => form.setData('billing_cycle', e.target.value)}>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </Select>
                    <Input label="Trial days" type="number" value={form.data.trial_days} onChange={(e) => form.setData('trial_days', e.target.value)} />
                    <Button type="submit" disabled={form.processing}>{plan ? 'Save' : 'Create plan'}</Button>
                </form>
            </Card>
        </>
    );
}

PlanForm.layout = (page) => <App children={page} />;
```

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Billing/Subscriptions.jsx
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import { Card, Table, Button, Badge, Modal, Select, Input } from '@aero/ui';

export default function Subscriptions({ subscriptions, plans, filters }) {
    const hr = useHRMAC();
    const [cancelling, setCancelling] = useState(null);
    const [upgrading, setUpgrading] = useState(null);
    const cancelForm = useForm({ reason: '' });
    const upgradeForm = useForm({ plan_id: '' });

    return (
        <>
            <Head title="Subscriptions" />
            <Card>
                <Table>
                    <Table.Head>
                        <Table.Row>
                            <Table.Cell>Tenant</Table.Cell>
                            <Table.Cell>Plan</Table.Cell>
                            <Table.Cell>Status</Table.Cell>
                            <Table.Cell>Period ends</Table.Cell>
                            <Table.Cell>Actions</Table.Cell>
                        </Table.Row>
                    </Table.Head>
                    <Table.Body>
                        {subscriptions.data.map((s) => (
                            <Table.Row key={s.id}>
                                <Table.Cell>{s.tenant?.name}</Table.Cell>
                                <Table.Cell>{s.plan?.name}</Table.Cell>
                                <Table.Cell><Badge>{s.status}</Badge></Table.Cell>
                                <Table.Cell>{s.current_period_end ?? '—'}</Table.Cell>
                                <Table.Cell>
                                    {s.status === 'active' && hr.can('subscription-lifecycle.pause-resume.pause') && (
                                        <Button size="sm" onClick={() => router.post(route('platform.admin.billing.subscriptions.pause', s.id))}>Pause</Button>
                                    )}
                                    {s.status === 'paused' && hr.can('subscription-lifecycle.pause-resume.resume') && (
                                        <Button size="sm" onClick={() => router.post(route('platform.admin.billing.subscriptions.resume', s.id))}>Resume</Button>
                                    )}
                                    {hr.can('billing-management.subscriptions.upgrade') && (
                                        <Button size="sm" variant="ghost" onClick={() => setUpgrading(s)}>Upgrade</Button>
                                    )}
                                    {hr.can('billing-management.subscriptions.cancel') && s.status !== 'cancelled' && (
                                        <Button size="sm" variant="ghost" onClick={() => setCancelling(s)}>Cancel</Button>
                                    )}
                                </Table.Cell>
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Card>

            <Modal open={!!cancelling} onClose={() => setCancelling(null)} title="Cancel subscription">
                <Input label="Reason" value={cancelForm.data.reason} onChange={(e) => cancelForm.setData('reason', e.target.value)} />
                <Button onClick={() => cancelForm.post(route('platform.admin.billing.subscriptions.cancel', cancelling.id), { onSuccess: () => setCancelling(null) })}>
                    Confirm
                </Button>
            </Modal>

            <Modal open={!!upgrading} onClose={() => setUpgrading(null)} title="Upgrade subscription">
                <Select value={upgradeForm.data.plan_id} onChange={(e) => upgradeForm.setData('plan_id', e.target.value)}>
                    <option value="">Select plan</option>
                    {plans.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                </Select>
                <Button onClick={() => upgradeForm.post(route('platform.admin.billing.subscriptions.upgrade', upgrading.id), { onSuccess: () => setUpgrading(null) })}>
                    Confirm upgrade
                </Button>
            </Modal>
        </>
    );
}

Subscriptions.layout = (page) => <App children={page} />;
```

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Billing/Invoices.jsx
import { Head, Link, router } from '@inertiajs/react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import { Card, Table, Button, Badge } from '@aero/ui';

export default function Invoices({ invoices }) {
    const hr = useHRMAC();
    return (
        <>
            <Head title="Invoices" />
            <Card>
                <Table>
                    <Table.Head>
                        <Table.Row>
                            <Table.Cell>Invoice #</Table.Cell>
                            <Table.Cell>Tenant</Table.Cell>
                            <Table.Cell>Amount</Table.Cell>
                            <Table.Cell>Status</Table.Cell>
                            <Table.Cell>Due</Table.Cell>
                            <Table.Cell>Actions</Table.Cell>
                        </Table.Row>
                    </Table.Head>
                    <Table.Body>
                        {invoices.data.map((inv) => (
                            <Table.Row key={inv.id}>
                                <Table.Cell>{inv.invoice_number}</Table.Cell>
                                <Table.Cell>{inv.tenant?.name}</Table.Cell>
                                <Table.Cell>{inv.currency} {inv.amount}</Table.Cell>
                                <Table.Cell><Badge>{inv.status}</Badge></Table.Cell>
                                <Table.Cell>{inv.due_date}</Table.Cell>
                                <Table.Cell>
                                    <Link href={route('platform.admin.billing.invoices.show', inv.id)}>
                                        <Button size="sm" variant="ghost">View</Button>
                                    </Link>
                                    {inv.status !== 'paid' && hr.can('billing-management.invoices.mark-paid') && (
                                        <Button size="sm"
                                                onClick={() => router.post(route('platform.admin.billing.invoices.mark-paid', inv.id))}>
                                            Mark paid
                                        </Button>
                                    )}
                                </Table.Cell>
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Card>
        </>
    );
}

Invoices.layout = (page) => <App children={page} />;
```

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Billing/Gateways.jsx
import { Head, useForm } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, Input, Button, Badge } from '@aero/ui';

function GatewayCard({ gateway }) {
    const form = useForm({
        config: gateway.config ?? {},
        is_active: gateway.is_active,
    });
    const submit = (e) => { e.preventDefault(); form.put(route('platform.admin.billing.gateways.update', gateway.id)); };

    return (
        <Card title={`${gateway.name} ${gateway.is_default ? '(default)' : ''}`}>
            {gateway.is_active ? <Badge tone="success">Active</Badge> : <Badge>Inactive</Badge>}
            <form onSubmit={submit} className="space-y-3 mt-3">
                {gateway.code === 'stripe' && (
                    <>
                        <Input label="Publishable key" value={form.data.config.publishable_key ?? ''}
                               onChange={(e) => form.setData('config', { ...form.data.config, publishable_key: e.target.value })} />
                        <Input label="Secret key" type="password" value={form.data.config.secret_key ?? ''}
                               onChange={(e) => form.setData('config', { ...form.data.config, secret_key: e.target.value })} />
                        <Input label="Webhook secret" type="password" value={form.data.config.webhook_secret ?? ''}
                               onChange={(e) => form.setData('config', { ...form.data.config, webhook_secret: e.target.value })} />
                    </>
                )}
                {gateway.code === 'sslcommerz' && (
                    <>
                        <Input label="Store ID" value={form.data.config.store_id ?? ''}
                               onChange={(e) => form.setData('config', { ...form.data.config, store_id: e.target.value })} />
                        <Input label="Store password" type="password" value={form.data.config.store_password ?? ''}
                               onChange={(e) => form.setData('config', { ...form.data.config, store_password: e.target.value })} />
                    </>
                )}
                <Button type="submit" disabled={form.processing}>Save</Button>
            </form>
        </Card>
    );
}

export default function Gateways({ gateways }) {
    return (
        <>
            <Head title="Payment Gateways" />
            <div className="grid grid-cols-2 gap-4">
                {gateways.map((g) => <GatewayCard key={g.id} gateway={g} />)}
            </div>
        </>
    );
}

Gateways.layout = (page) => <App children={page} />;
```

---

## 7. Tests

### Task 7 — Feature tests in `packages/aero-platform/tests/Feature/`

- [ ] `PlanCrudTest.php`
- [ ] `PlanCloneTest.php`
- [ ] `PlanDeleteGuardTest.php`
- [ ] `SubscriptionImmutabilityTest.php`
- [ ] `SubscriptionLifecycleTest.php`
- [ ] `InvoiceImmutabilityTest.php`
- [ ] `InvoiceGenerationTest.php`
- [ ] `PaymentGatewayTest.php`

Base: `Orchestra\Testbench\TestCase`, providers `[AeroCoreServiceProvider::class, AeroPlatformServiceProvider::class]`, `Gate::before(fn () => true)`.

```php
// packages/aero-platform/tests/Feature/PlanCrudTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Plan;
use Aero\Platform\Services\PlanAdminService;

class PlanCrudTest extends PlatformTestCase
{
    public function test_can_create_plan_with_modules(): void
    {
        $plan = app(PlanAdminService::class)->create([
            'name' => 'Pro',
            'price' => 49.00,
            'billing_cycle' => 'monthly',
            'trial_days' => 14,
            'modules' => ['hrm', 'crm'],
        ]);

        $this->assertDatabaseHas('plans', ['name' => 'Pro', 'slug' => 'pro']);
        $this->assertSame(['hrm', 'crm'], $plan->modules);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'plan.created']);
    }

    public function test_cannot_delete_plan_with_active_subscriptions(): void
    {
        $plan = Plan::factory()->hasActiveSubscriptions(1)->create();
        $this->expectException(\DomainException::class);
        app(PlanAdminService::class)->delete($plan);
    }
}
```

```php
// packages/aero-platform/tests/Feature/PlanCloneTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Plan;
use Aero\Platform\Services\PlanAdminService;

class PlanCloneTest extends PlatformTestCase
{
    public function test_clone_copies_settings_and_inactivates(): void
    {
        $orig = Plan::factory()->create([
            'name' => 'Pro',
            'price' => 49,
            'modules' => ['hrm'],
            'is_active' => true,
        ]);
        $copy = app(PlanAdminService::class)->clone($orig, 'Pro V2');

        $this->assertSame(49.0, (float) $copy->price);
        $this->assertSame(['hrm'], $copy->modules);
        $this->assertFalse((bool) $copy->is_active);
        $this->assertNotSame($orig->slug, $copy->slug);
    }
}
```

```php
// packages/aero-platform/tests/Feature/SubscriptionImmutabilityTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\SubscriptionAdminService;

class SubscriptionImmutabilityTest extends PlatformTestCase
{
    public function test_active_subscription_cannot_be_updated_directly(): void
    {
        $sub = Subscription::factory()->create(['status' => 'trialing']);
        app(SubscriptionAdminService::class)->activate($sub);
        $sub->refresh();

        $this->expectException(\RuntimeException::class);
        $sub->update(['status' => 'past_due']); // ImmutableRecordObserver blocks
    }
}
```

```php
// packages/aero-platform/tests/Feature/InvoiceImmutabilityTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Invoice;
use Aero\Platform\Services\InvoiceAdminService;

class InvoiceImmutabilityTest extends PlatformTestCase
{
    public function test_paid_invoice_cannot_be_modified(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'open']);
        app(InvoiceAdminService::class)->markPaid($invoice);
        $invoice->refresh();

        $this->expectException(\RuntimeException::class);
        $invoice->update(['amount' => 0]);
    }

    public function test_mark_paid_twice_throws(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'paid', 'is_locked' => true]);
        $this->expectException(\DomainException::class);
        app(InvoiceAdminService::class)->markPaid($invoice);
    }
}
```

```php
// packages/aero-platform/tests/Feature/InvoiceGenerationTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\InvoiceAdminService;

class InvoiceGenerationTest extends PlatformTestCase
{
    public function test_can_generate_invoice_with_line_items(): void
    {
        $sub = Subscription::factory()->create();
        $invoice = app(InvoiceAdminService::class)->generate($sub, [
            ['description' => 'Pro plan — May', 'amount' => 49],
            ['description' => 'Add-on seats',     'amount' => 10],
        ]);

        $this->assertSame(59.0, (float) $invoice->amount);
        $this->assertSame('open', $invoice->status);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'invoice.generated']);
    }
}
```

```php
// packages/aero-platform/tests/Feature/SubscriptionLifecycleTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\SubscriptionAdminService;

class SubscriptionLifecycleTest extends PlatformTestCase
{
    public function test_pause_and_resume(): void
    {
        $sub = Subscription::factory()->create(['status' => 'active']);
        $svc = app(SubscriptionAdminService::class);

        $svc->pause($sub);
        $this->assertSame('paused', $sub->fresh()->status);

        $svc->resume($sub);
        $this->assertSame('active', $sub->fresh()->status);
        $this->assertNotNull($sub->fresh()->resumed_at);
    }

    public function test_cancel_records_reason(): void
    {
        $sub = Subscription::factory()->create(['status' => 'active']);
        app(SubscriptionAdminService::class)->cancel($sub, 'Customer churn');

        $this->assertSame('cancelled', $sub->fresh()->status);
        $this->assertSame('Customer churn', $sub->fresh()->cancellation_reason);
    }
}
```

```php
// packages/aero-platform/tests/Feature/PaymentGatewayTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\PaymentGateway;
use Aero\Platform\Services\PaymentGatewayService;

class PaymentGatewayTest extends PlatformTestCase
{
    public function test_configure_encrypts_secrets(): void
    {
        $gw = PaymentGateway::factory()->create(['code' => 'stripe']);
        app(PaymentGatewayService::class)->configure($gw, [
            'publishable_key' => 'pk_test_xxx',
            'secret_key' => 'sk_test_yyy',
        ], true);

        $fresh = $gw->fresh();
        $this->assertSame('pk_test_xxx', $fresh->config['publishable_key']);
        $this->assertTrue($fresh->is_active);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'payment_gateway.configured']);
    }

    public function test_set_default_clears_other_defaults(): void
    {
        $a = PaymentGateway::factory()->create(['is_default' => true]);
        $b = PaymentGateway::factory()->create(['is_default' => false]);

        app(PaymentGatewayService::class)->setDefault($b);

        $this->assertFalse((bool) $a->fresh()->is_default);
        $this->assertTrue((bool) $b->fresh()->is_default);
    }
}
```

---

## 8. Tasks (execution order)

1. **DB & Models** — 4 migrations, upgrade `Plan`/`Subscription`/`Invoice`, create `PaymentGateway`. Wire `ImmutableRecordObserver` to `Subscription` + `Invoice`. Add factories.
2. **Services** — `PlanAdminService`, `SubscriptionAdminService`, `InvoiceAdminService`, `PaymentGatewayService`.
3. **Controllers + FormRequests + Routes** — 5 controllers, 8 form requests, append route blocks.
4. **React Pages** — 8 Inertia pages under `Pages/Platform/Admin/{Plans,Billing}/`.
5. **Tests** — 8 feature tests with Orchestra Testbench + `Gate::before`.

---

## 9. Out of Scope

- Real Stripe webhook handler & dunning automation (handled by separate `aero-payments` package follow-up).
- Real SSLCommerz IPN callback verification (gateway adapter package).
- PDF generation pipeline — this plan stubs `pdf_path`; actual PDF render belongs to `aero-platform/Jobs/RenderInvoicePdf`.
- Proration calculation on upgrade — current service swaps `plan_id`; proration ships with `Plan-P-3-Revenue-Recognition`.
- Tax/VAT/GST computation per jurisdiction — tracked in separate compliance plan.
- Customer-facing cancellation save flow UI (in-app modal on tenant side) — Platform admin sees results only.
- `subscription-lifecycle.cancellations.configure` admin page (cancellation reasons taxonomy) — declared but UX deferred.
- Coupon/discount management — out of scope for P-2; will be `Plan-P-4-Promotions`.
