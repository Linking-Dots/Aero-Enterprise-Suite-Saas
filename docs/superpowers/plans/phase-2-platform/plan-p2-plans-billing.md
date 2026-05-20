# Plan P-2 — Plans & Billing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a production-grade Platform Admin surface for product catalog (Plan CRUD + clone + archive + module assignment), subscription management (cancel/upgrade, immutable after activation), invoice management (list, generate, send, mark-paid, download PDF, immutable after payment), payment gateway configuration (Stripe + SSLCommerz), and a billing dashboard with MRR/ARR + outstanding metrics.

**Architecture:** All domain code lives in `packages/aero-platform/src/{Models,Http,Services}/`. Models extend `Aero\Contracts\Models\CentralModel` (landlord/central DB). **Subscriptions are immutable after `status='active'`** and **Invoices are immutable after `status='paid'`** via `ImmutableRecordObserver`. Payment gateway credentials persist with `EncryptedField` JSON casts. All writes run inside `DB::transaction()`; every billing event hits `AuditServiceInterface::log()` writing to `platform_audit_logs`. React pages live under `packages/aero-ui/resources/js/Pages/Platform/Admin/{Plans,Billing}/`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Orchestra Testbench, DomPDF (PDF), Stripe SDK, SSLCommerz adapter.

---

## 1. HRMAC Hierarchy

Declared in `packages/aero-platform/config/module.php`. Routes reference codes as `hrmac:{submodule}.{component}.{action}`.

**Submodule `plan-management`**
- `plan-management.plan-list.view` / `.create` / `.edit` / `.delete` / `.archive` / `.clone`
- `plan-management.plan-details.view` / `.view-subscribers` / `.view-revenue` / `.export`
- `plan-management.plan-modules.view` / `.assign`

**Submodule `billing-management`**
- `billing-management.billing-dashboard.view`
- `billing-management.subscriptions.view` / `.cancel` / `.upgrade`
- `billing-management.invoices.view` / `.generate` / `.send` / `.mark-paid`
- `billing-management.payment-gateways.view` / `.configure`

### Task 0 — Update `packages/aero-platform/config/module.php`

- [ ] Add/confirm all submodules + components + actions above
- [ ] Run `php artisan hrmac:sync --module=platform`

```php
// excerpt — packages/aero-platform/config/module.php
'submodules' => [
    'plan-management' => [
        'label' => 'Plan Management',
        'components' => [
            'plan-list'    => ['actions' => ['view','create','edit','delete','archive','clone']],
            'plan-details' => ['actions' => ['view','view-subscribers','view-revenue','export']],
            'plan-modules' => ['actions' => ['view','assign']],
        ],
    ],
    'billing-management' => [
        'label' => 'Billing Management',
        'components' => [
            'billing-dashboard' => ['actions' => ['view']],
            'subscriptions'     => ['actions' => ['view','cancel','upgrade']],
            'invoices'          => ['actions' => ['view','generate','send','mark-paid']],
            'payment-gateways'  => ['actions' => ['view','configure']],
        ],
    ],
],
```

---

## 2. Data Model

### Task 1 — Migrations

- [ ] `packages/aero-platform/database/migrations/2026_05_20_020001_upgrade_plans_table.php`
- [ ] `packages/aero-platform/database/migrations/2026_05_20_020002_create_plan_modules_table.php`
- [ ] `packages/aero-platform/database/migrations/2026_05_20_020003_upgrade_subscriptions_table.php`
- [ ] `packages/aero-platform/database/migrations/2026_05_20_020004_upgrade_invoices_table.php`
- [ ] `packages/aero-platform/database/migrations/2026_05_20_020005_create_payment_gateways_table.php`

```php
// 2026_05_20_020001_upgrade_plans_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'slug'))           $table->string('slug', 120)->unique()->after('name');
            if (!Schema::hasColumn('plans', 'description'))    $table->text('description')->nullable();
            if (!Schema::hasColumn('plans', 'price_monthly'))  $table->decimal('price_monthly', 12, 2)->default(0);
            if (!Schema::hasColumn('plans', 'price_annual'))   $table->decimal('price_annual',  12, 2)->default(0);
            if (!Schema::hasColumn('plans', 'currency'))       $table->string('currency', 3)->default('USD');
            if (!Schema::hasColumn('plans', 'trial_days'))     $table->integer('trial_days')->default(0);
            if (!Schema::hasColumn('plans', 'status'))         $table->string('status', 16)->default('active'); // active|archived
            if (!Schema::hasColumn('plans', 'is_public'))      $table->boolean('is_public')->default(true);
            if (!Schema::hasColumn('plans', 'features'))       $table->json('features')->nullable();
            if (!Schema::hasColumn('plans', 'limits'))         $table->json('limits')->nullable();
            if (!Schema::hasColumn('plans', 'stripe_price_id_monthly')) $table->string('stripe_price_id_monthly')->nullable();
            if (!Schema::hasColumn('plans', 'stripe_price_id_annual'))  $table->string('stripe_price_id_annual')->nullable();
            $table->index(['status']);
            $table->index(['is_public']);
        });
    }
    public function down(): void {}
};
```

```php
// 2026_05_20_020002_create_plan_modules_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('plan_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->string('module_code', 64);
            $table->boolean('is_enabled')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
            $table->unique(['plan_id','module_code']);
            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('plan_modules'); }
};
```

```php
// 2026_05_20_020003_upgrade_subscriptions_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'status'))               $table->string('status', 24)->default('trialing'); // trialing|active|cancelled|past_due|unpaid
            if (!Schema::hasColumn('subscriptions', 'billing_cycle'))        $table->string('billing_cycle', 16)->default('monthly');
            if (!Schema::hasColumn('subscriptions', 'current_period_start')) $table->timestamp('current_period_start')->nullable();
            if (!Schema::hasColumn('subscriptions', 'current_period_end'))   $table->timestamp('current_period_end')->nullable();
            if (!Schema::hasColumn('subscriptions', 'trial_ends_at'))        $table->timestamp('trial_ends_at')->nullable();
            if (!Schema::hasColumn('subscriptions', 'cancelled_at'))         $table->timestamp('cancelled_at')->nullable();
            if (!Schema::hasColumn('subscriptions', 'cancel_reason'))        $table->string('cancel_reason')->nullable();
            if (!Schema::hasColumn('subscriptions', 'stripe_subscription_id')) $table->string('stripe_subscription_id')->nullable();
            $table->index(['status']);
            $table->index(['tenant_id','status']);
        });
    }
    public function down(): void {}
};
```

```php
// 2026_05_20_020004_upgrade_invoices_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'reference'))     $table->string('reference', 32)->unique();
            if (!Schema::hasColumn('invoices', 'amount'))        $table->decimal('amount', 12, 2)->default(0);
            if (!Schema::hasColumn('invoices', 'currency'))      $table->string('currency', 3)->default('USD');
            if (!Schema::hasColumn('invoices', 'tax_amount'))    $table->decimal('tax_amount', 12, 2)->default(0);
            if (!Schema::hasColumn('invoices', 'status'))        $table->string('status', 16)->default('draft'); // draft|sent|paid|voided
            if (!Schema::hasColumn('invoices', 'due_date'))      $table->date('due_date')->nullable();
            if (!Schema::hasColumn('invoices', 'paid_at'))       $table->timestamp('paid_at')->nullable();
            if (!Schema::hasColumn('invoices', 'stripe_invoice_id')) $table->string('stripe_invoice_id')->nullable();
            if (!Schema::hasColumn('invoices', 'pdf_path'))      $table->string('pdf_path')->nullable();
            $table->index(['status']);
            $table->index(['tenant_id','status']);
        });
    }
    public function down(): void {}
};
```

```php
// 2026_05_20_020005_create_payment_gateways_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique(); // stripe|sslcommerz
            $table->string('label');
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_default')->default(false);
            $table->text('config')->nullable(); // EncryptedField (json string)
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payment_gateways'); }
};
```

### Task 2 — Models

- [ ] `packages/aero-platform/src/Models/Plan.php`
- [ ] `packages/aero-platform/src/Models/PlanModule.php`
- [ ] `packages/aero-platform/src/Models/Subscription.php` (+ ImmutableRecordObserver binding)
- [ ] `packages/aero-platform/src/Models/Invoice.php` (+ ImmutableRecordObserver binding)
- [ ] `packages/aero-platform/src/Models/PaymentGateway.php`
- [ ] `packages/aero-platform/src/Exceptions/SubscriptionFinalizedException.php`
- [ ] `packages/aero-platform/src/Exceptions/InvoiceFinalizedException.php`

```php
// packages/aero-platform/src/Models/Plan.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class Plan extends CentralModel
{
    protected $table = 'plans';

    protected $fillable = [
        'name','slug','description','price_monthly','price_annual','currency',
        'trial_days','status','is_public','features','limits',
        'stripe_price_id_monthly','stripe_price_id_annual',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_annual'  => 'decimal:2',
        'is_public'     => 'boolean',
        'features'      => 'array',
        'limits'        => 'array',
    ];

    public function modules() { return $this->hasMany(PlanModule::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class); }
}
```

```php
// packages/aero-platform/src/Models/PlanModule.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class PlanModule extends CentralModel
{
    protected $table = 'plan_modules';
    protected $fillable = ['plan_id','module_code','is_enabled','config'];
    protected $casts = ['is_enabled' => 'boolean', 'config' => 'array'];
}
```

```php
// packages/aero-platform/src/Models/Subscription.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Aero\Platform\Observers\SubscriptionImmutableObserver;

class Subscription extends CentralModel
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'tenant_id','plan_id','status','billing_cycle',
        'current_period_start','current_period_end','trial_ends_at',
        'cancelled_at','cancel_reason','stripe_subscription_id',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end'   => 'datetime',
        'trial_ends_at'        => 'datetime',
        'cancelled_at'         => 'datetime',
    ];

    protected static function booted(): void
    {
        static::observe(SubscriptionImmutableObserver::class);
    }

    public function plan() { return $this->belongsTo(Plan::class); }
    public function invoices() { return $this->hasMany(Invoice::class); }
}
```

```php
// packages/aero-platform/src/Models/Invoice.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Aero\Platform\Observers\InvoiceImmutableObserver;

class Invoice extends CentralModel
{
    protected $table = 'invoices';

    protected $fillable = [
        'tenant_id','subscription_id','reference','amount','currency',
        'tax_amount','status','due_date','paid_at','stripe_invoice_id','pdf_path',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'due_date'   => 'date',
        'paid_at'    => 'datetime',
    ];

    protected static function booted(): void
    {
        static::observe(InvoiceImmutableObserver::class);
    }

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
    protected $table = 'payment_gateways';
    protected $fillable = ['code','label','is_enabled','is_default','config'];
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'config'     => EncryptedField::class,
    ];
}
```

```php
// packages/aero-platform/src/Exceptions/SubscriptionFinalizedException.php
namespace Aero\Platform\Exceptions;

class SubscriptionFinalizedException extends \DomainException
{
    public function __construct(string $message = 'Active subscription is immutable except for status transitions')
    {
        parent::__construct($message, 422);
    }
}
```

```php
// packages/aero-platform/src/Exceptions/InvoiceFinalizedException.php
namespace Aero\Platform\Exceptions;

class InvoiceFinalizedException extends \DomainException
{
    public function __construct(string $message = 'Paid invoices are immutable')
    {
        parent::__construct($message, 422);
    }
}
```

### Task 3 — Immutability Observers

- [ ] `packages/aero-platform/src/Observers/SubscriptionImmutableObserver.php`
- [ ] `packages/aero-platform/src/Observers/InvoiceImmutableObserver.php`

```php
// packages/aero-platform/src/Observers/SubscriptionImmutableObserver.php
namespace Aero\Platform\Observers;

use Aero\Platform\Exceptions\SubscriptionFinalizedException;
use Aero\Platform\Models\Subscription;

class SubscriptionImmutableObserver
{
    public function updating(Subscription $sub): void
    {
        // Use the ORIGINAL status to decide locking — once active, lock plan_id / billing_cycle.
        $originalStatus = $sub->getOriginal('status');
        if ($originalStatus !== 'active') return;

        $locked = ['plan_id','billing_cycle','current_period_start','current_period_end'];
        foreach ($locked as $field) {
            if ($sub->isDirty($field)) {
                throw new SubscriptionFinalizedException(
                    "Cannot modify '{$field}' on an active subscription"
                );
            }
        }
        // status transitions remain allowed
    }

    public function deleting(Subscription $sub): void
    {
        if ($sub->status === 'active') {
            throw new SubscriptionFinalizedException('Cannot delete an active subscription');
        }
    }
}
```

```php
// packages/aero-platform/src/Observers/InvoiceImmutableObserver.php
namespace Aero\Platform\Observers;

use Aero\Platform\Exceptions\InvoiceFinalizedException;
use Aero\Platform\Models\Invoice;

class InvoiceImmutableObserver
{
    public function updating(Invoice $inv): void
    {
        if ($inv->getOriginal('status') !== 'paid') return;

        // Once paid, NOTHING may change.
        if (count($inv->getDirty()) > 0) {
            throw new InvoiceFinalizedException();
        }
    }

    public function deleting(Invoice $inv): void
    {
        if ($inv->status === 'paid') {
            throw new InvoiceFinalizedException('Cannot delete a paid invoice');
        }
    }
}
```

---

## 3. Services

### Task 4 — `PlanService`

- [ ] `packages/aero-platform/src/Services/PlanService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\PlanModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters)
    {
        $q = Plan::query()->withCount('subscriptions');
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        return $q->orderBy('price_monthly')->paginate(25)->withQueryString();
    }

    public function create(array $data): Plan
    {
        return DB::transaction(function () use ($data) {
            $plan = Plan::create([
                'name'          => $data['name'],
                'slug'          => $data['slug'] ?? Str::slug($data['name']),
                'description'   => $data['description'] ?? null,
                'price_monthly' => $data['price_monthly'] ?? 0,
                'price_annual'  => $data['price_annual'] ?? 0,
                'currency'      => $data['currency'] ?? 'USD',
                'trial_days'    => $data['trial_days'] ?? 0,
                'status'        => 'active',
                'is_public'     => $data['is_public'] ?? true,
                'features'      => $data['features'] ?? [],
                'limits'        => $data['limits'] ?? [],
                'stripe_price_id_monthly' => $data['stripe_price_id_monthly'] ?? null,
                'stripe_price_id_annual'  => $data['stripe_price_id_annual'] ?? null,
            ]);

            if (!empty($data['modules'])) {
                $this->assignModules($plan, $data['modules']);
            }

            $this->audit->log(
                event: 'PLAN_CREATED', action: 'create', subject: $plan,
                description: "Plan {$plan->name} created"
            );

            return $plan;
        });
    }

    public function update(Plan $plan, array $data): Plan
    {
        return DB::transaction(function () use ($plan, $data) {
            $plan->fill(array_intersect_key($data, array_flip([
                'name','description','price_monthly','price_annual','currency',
                'trial_days','is_public','features','limits',
                'stripe_price_id_monthly','stripe_price_id_annual',
            ])))->save();

            if (array_key_exists('modules', $data)) {
                $this->assignModules($plan, $data['modules']);
            }

            $this->audit->log(
                event: 'PLAN_UPDATED', action: 'update', subject: $plan,
                description: "Plan {$plan->name} updated"
            );

            return $plan->fresh();
        });
    }

    public function delete(Plan $plan): void
    {
        $active = $plan->subscriptions()->where('status', 'active')->count();
        if ($active > 0) {
            abort(422, "Cannot delete plan with $active active subscribers — archive instead.");
        }

        DB::transaction(function () use ($plan) {
            $this->audit->log(
                event: 'PLAN_DELETED', action: 'delete', subject: $plan,
                description: "Plan {$plan->name} deleted"
            );
            $plan->delete();
        });
    }

    public function archive(Plan $plan): Plan
    {
        return DB::transaction(function () use ($plan) {
            $plan->update(['status' => 'archived', 'is_public' => false]);
            $this->audit->log(
                event: 'PLAN_ARCHIVED', action: 'archive', subject: $plan,
                description: "Plan {$plan->name} archived"
            );
            return $plan->fresh();
        });
    }

    public function clone(Plan $plan): Plan
    {
        return DB::transaction(function () use ($plan) {
            $copy = $plan->replicate(['stripe_price_id_monthly','stripe_price_id_annual']);
            $copy->name = $plan->name . ' (Copy)';
            $copy->slug = $plan->slug . '-copy-' . Str::random(6);
            $copy->status = 'active';
            $copy->is_public = false;
            $copy->save();

            foreach ($plan->modules as $m) {
                $copy->modules()->create([
                    'module_code' => $m->module_code,
                    'is_enabled'  => $m->is_enabled,
                    'config'      => $m->config,
                ]);
            }

            $this->audit->log(
                event: 'PLAN_CLONED', action: 'clone', subject: $copy,
                description: "Plan {$plan->name} cloned to {$copy->name}"
            );

            return $copy;
        });
    }

    public function assignModules(Plan $plan, array $modules): void
    {
        DB::transaction(function () use ($plan, $modules) {
            $plan->modules()->delete();
            foreach ($modules as $code => $cfg) {
                if (is_int($code)) { $code = $cfg; $cfg = []; }
                $plan->modules()->create([
                    'module_code' => $code,
                    'is_enabled'  => $cfg['is_enabled'] ?? true,
                    'config'      => $cfg['config'] ?? null,
                ]);
            }
        });
    }
}
```

### Task 5 — `SubscriptionAdminService`

- [ ] `packages/aero-platform/src/Services/SubscriptionAdminService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters)
    {
        $q = Subscription::query()->with(['plan']);
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['tenant_id'])) $q->where('tenant_id', $filters['tenant_id']);
        return $q->orderByDesc('created_at')->paginate(25)->withQueryString();
    }

    public function show(int $id): Subscription
    {
        return Subscription::with(['plan','invoices' => fn ($q) => $q->latest()->limit(10)])
            ->findOrFail($id);
    }

    public function cancel(Subscription $sub, string $reason, int $actorId): Subscription
    {
        if ($sub->status === 'cancelled') {
            abort(422, 'Subscription is already cancelled');
        }

        return DB::transaction(function () use ($sub, $reason, $actorId) {
            $sub->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancel_reason' => $reason,
            ]);

            $this->audit->log(
                event: 'SUBSCRIPTION_CANCELLED', action: 'cancel', subject: $sub,
                description: "Subscription {$sub->id} cancelled by actor $actorId: $reason"
            );

            return $sub->fresh();
        });
    }

    public function upgrade(Subscription $sub, int $newPlanId, int $actorId): Subscription
    {
        // Immutability: cannot mutate plan_id directly on active subscription.
        // Pattern: cancel old, create new active subscription on the new plan.
        return DB::transaction(function () use ($sub, $newPlanId, $actorId) {
            $this->cancel($sub, "Upgraded to plan #$newPlanId", $actorId);

            $new = Subscription::create([
                'tenant_id'            => $sub->tenant_id,
                'plan_id'              => $newPlanId,
                'status'               => 'active',
                'billing_cycle'        => $sub->billing_cycle,
                'current_period_start' => now(),
                'current_period_end'   => now()->addMonth(),
            ]);

            $this->audit->log(
                event: 'SUBSCRIPTION_UPGRADED', action: 'upgrade', subject: $new,
                description: "Tenant {$sub->tenant_id} upgraded from plan {$sub->plan_id} to plan $newPlanId"
            );

            return $new;
        });
    }
}
```

### Task 6 — `InvoiceAdminService`

- [ ] `packages/aero-platform/src/Services/InvoiceAdminService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Subscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters)
    {
        $q = Invoice::query()->with(['subscription.plan']);
        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['tenant_id'])) $q->where('tenant_id', $filters['tenant_id']);
        return $q->orderByDesc('created_at')->paginate(25)->withQueryString();
    }

    public function generate(Subscription $sub): Invoice
    {
        return DB::transaction(function () use ($sub) {
            $year = now()->year;
            $seq  = Invoice::whereYear('created_at', $year)->count() + 1;
            $ref  = sprintf('INV-%d-%06d', $year, $seq);

            $amount = $sub->billing_cycle === 'annual'
                ? $sub->plan->price_annual
                : $sub->plan->price_monthly;

            $invoice = Invoice::create([
                'tenant_id'       => $sub->tenant_id,
                'subscription_id' => $sub->id,
                'reference'       => $ref,
                'amount'          => $amount,
                'currency'        => $sub->plan->currency,
                'tax_amount'      => 0,
                'status'          => 'draft',
                'due_date'        => now()->addDays(14)->toDateString(),
            ]);

            // Generate PDF
            $pdf = Pdf::loadView('platform::invoices.pdf', ['invoice' => $invoice->fresh('subscription.plan')]);
            $path = "invoices/{$ref}.pdf";
            Storage::disk('local')->put($path, $pdf->output());

            // Must not violate immutability — invoice is still draft at this point.
            $invoice->update(['pdf_path' => $path]);

            $this->audit->log(
                event: 'INVOICE_GENERATED', action: 'generate', subject: $invoice,
                description: "Invoice {$ref} generated for tenant {$sub->tenant_id}"
            );

            return $invoice->fresh();
        });
    }

    public function send(Invoice $invoice): Invoice
    {
        if ($invoice->status === 'paid') {
            abort(422, 'Cannot send a paid invoice');
        }

        return DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => 'sent']);
            $this->audit->log(
                event: 'INVOICE_SENT', action: 'send', subject: $invoice,
                description: "Invoice {$invoice->reference} sent"
            );
            return $invoice->fresh();
        });
    }

    public function markPaid(Invoice $invoice, int $actorId): Invoice
    {
        if ($invoice->status === 'paid') {
            abort(422, 'Invoice already paid');
        }

        return DB::transaction(function () use ($invoice, $actorId) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            $this->audit->log(
                event: 'INVOICE_MARKED_PAID', action: 'mark-paid', subject: $invoice,
                description: "Invoice {$invoice->reference} marked paid by actor $actorId"
            );
            return $invoice->fresh();
        });
    }

    public function downloadPdf(Invoice $invoice): string
    {
        abort_unless($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path), 404);
        return Storage::disk('local')->path($invoice->pdf_path);
    }
}
```

### Task 7 — `PaymentGatewayService`

- [ ] `packages/aero-platform/src/Services/PaymentGatewayService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\PaymentGateway;
use Illuminate\Support\Facades\DB;

class PaymentGatewayService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function getConfig(string $code): PaymentGateway
    {
        return PaymentGateway::firstOrCreate(
            ['code' => $code],
            ['label' => ucfirst($code), 'is_enabled' => false, 'config' => []]
        );
    }

    public function updateConfig(string $code, array $data): PaymentGateway
    {
        return DB::transaction(function () use ($code, $data) {
            $gw = $this->getConfig($code);

            $gw->update([
                'is_enabled' => $data['is_enabled'] ?? $gw->is_enabled,
                'is_default' => $data['is_default'] ?? $gw->is_default,
                'config'     => $data['config'] ?? [],
            ]);

            if (($data['is_default'] ?? false) === true) {
                PaymentGateway::where('id', '!=', $gw->id)->update(['is_default' => false]);
            }

            $this->audit->log(
                event: 'PAYMENT_GATEWAY_UPDATED', action: 'configure', subject: $gw,
                description: "Payment gateway $code updated"
            );

            return $gw->fresh();
        });
    }

    public function testConnection(string $code): array
    {
        $gw = $this->getConfig($code);
        $cfg = $gw->config ?? [];

        return match ($code) {
            'stripe'     => $this->testStripe($cfg),
            'sslcommerz' => $this->testSslCommerz($cfg),
            default      => ['ok' => false, 'message' => 'Unknown gateway'],
        };
    }

    private function testStripe(array $cfg): array
    {
        if (empty($cfg['secret_key'])) return ['ok' => false, 'message' => 'Missing secret_key'];

        try {
            $stripe = new \Stripe\StripeClient($cfg['secret_key']);
            $stripe->balance->retrieve();
            return ['ok' => true, 'message' => 'Stripe connection OK'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function testSslCommerz(array $cfg): array
    {
        foreach (['store_id','store_password'] as $k) {
            if (empty($cfg[$k])) return ['ok' => false, 'message' => "Missing $k"];
        }
        return ['ok' => true, 'message' => 'SSLCommerz credentials present'];
    }
}
```

---

## 4. Controllers

All in `packages/aero-platform/src/Http/Controllers/Admin/`.

### Task 8 — `PlanController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/PlanController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\PlanStoreRequest;
use Aero\Platform\Http\Requests\PlanUpdateRequest;
use Aero\Platform\Models\Plan;
use Aero\Platform\Services\PlanService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanController extends Controller
{
    public function __construct(private PlanService $svc) {}

    public function index(Request $request)
    {
        return Inertia::render('Platform/Admin/Plans/Index', [
            'plans'   => $this->svc->list($request->only(['status'])),
            'filters' => $request->only(['status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Platform/Admin/Plans/Form', [
            'plan' => null,
            'availableModules' => $this->availableModuleCodes(),
        ]);
    }

    public function store(PlanStoreRequest $request)
    {
        $plan = $this->svc->create($request->validated());
        return redirect()->route('platform.admin.plans.show', $plan)->with('success', 'Plan created');
    }

    public function show(Plan $plan)
    {
        $plan->load(['modules','subscriptions.invoices']);

        return Inertia::render('Platform/Admin/Plans/Show', [
            'plan' => $plan,
            'metrics' => [
                'active_subscribers' => $plan->subscriptions()->where('status','active')->count(),
                'mrr' => (float) $plan->subscriptions()
                    ->where('status','active')
                    ->where('billing_cycle','monthly')
                    ->count() * (float) $plan->price_monthly,
            ],
        ]);
    }

    public function edit(Plan $plan)
    {
        return Inertia::render('Platform/Admin/Plans/Form', [
            'plan' => $plan->load('modules'),
            'availableModules' => $this->availableModuleCodes(),
        ]);
    }

    public function update(PlanUpdateRequest $request, Plan $plan)
    {
        $this->svc->update($plan, $request->validated());
        return redirect()->route('platform.admin.plans.show', $plan)->with('success', 'Plan updated');
    }

    public function destroy(Plan $plan)
    {
        $this->svc->delete($plan);
        return redirect()->route('platform.admin.plans.index')->with('success', 'Plan deleted');
    }

    public function archive(Plan $plan)
    {
        $this->svc->archive($plan);
        return back()->with('success', 'Plan archived');
    }

    public function clone(Plan $plan)
    {
        $copy = $this->svc->clone($plan);
        return redirect()->route('platform.admin.plans.show', $copy)->with('success', 'Plan cloned');
    }

    public function assignModules(Request $request, Plan $plan)
    {
        $request->validate(['modules' => 'required|array']);
        $this->svc->assignModules($plan, $request->input('modules'));
        return back()->with('success', 'Modules assigned');
    }

    private function availableModuleCodes(): array
    {
        return ['hrm','finance','crm','inventory','project','marketing','support','ai-hub'];
    }
}
```

### Task 9 — `BillingDashboardController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/BillingDashboardController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BillingDashboardController extends Controller
{
    public function index()
    {
        $mrr = (float) DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->where('subscriptions.billing_cycle', 'monthly')
            ->sum('plans.price_monthly');

        $arr = (float) DB::table('subscriptions')
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->where('subscriptions.status', 'active')
            ->where('subscriptions.billing_cycle', 'annual')
            ->sum('plans.price_annual');

        return Inertia::render('Platform/Admin/Billing/Dashboard', [
            'stats' => [
                'mrr'              => $mrr,
                'arr'              => $arr,
                'active_subs'      => Subscription::where('status','active')->count(),
                'pending_invoices' => Invoice::whereIn('status', ['draft','sent'])->count(),
                'overdue'          => Invoice::where('status','sent')->whereDate('due_date','<', now())->count(),
            ],
            'recent_subscriptions' => Subscription::with('plan')->orderByDesc('created_at')->limit(10)->get(),
            'recent_invoices'      => Invoice::orderByDesc('created_at')->limit(10)->get(),
        ]);
    }
}
```

### Task 10 — `SubscriptionController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/SubscriptionController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\SubscriptionAdminService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionAdminService $svc) {}

    public function index(Request $request)
    {
        return Inertia::render('Platform/Admin/Billing/Subscriptions', [
            'subscriptions' => $this->svc->list($request->only(['status','tenant_id'])),
            'filters'       => $request->only(['status','tenant_id']),
            'plans'         => Plan::orderBy('name')->get(['id','name','price_monthly','price_annual']),
        ]);
    }

    public function show(Subscription $subscription)
    {
        return Inertia::render('Platform/Admin/Billing/SubscriptionShow', [
            'subscription' => $this->svc->show($subscription->id),
        ]);
    }

    public function cancel(Request $request, Subscription $subscription)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $this->svc->cancel($subscription, $request->string('reason'), $request->user()->id);
        return back()->with('success', 'Subscription cancelled');
    }

    public function upgrade(Request $request, Subscription $subscription)
    {
        $request->validate(['plan_id' => 'required|integer|exists:plans,id']);
        $new = $this->svc->upgrade($subscription, $request->integer('plan_id'), $request->user()->id);
        return redirect()->route('platform.admin.billing.subscriptions.show', $new)
            ->with('success', 'Subscription upgraded');
    }
}
```

### Task 11 — `InvoiceController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/InvoiceController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Invoice;
use Aero\Platform\Models\Subscription;
use Aero\Platform\Services\InvoiceAdminService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceAdminService $svc) {}

    public function index(Request $request)
    {
        return Inertia::render('Platform/Admin/Billing/Invoices', [
            'invoices' => $this->svc->list($request->only(['status','tenant_id'])),
            'filters'  => $request->only(['status','tenant_id']),
        ]);
    }

    public function show(Invoice $invoice)
    {
        return response()->json($invoice->load('subscription.plan'));
    }

    public function generate(Request $request)
    {
        $request->validate(['subscription_id' => 'required|integer|exists:subscriptions,id']);
        $sub = Subscription::findOrFail($request->integer('subscription_id'));
        $inv = $this->svc->generate($sub);
        return back()->with('success', "Invoice {$inv->reference} generated");
    }

    public function send(Invoice $invoice)
    {
        $this->svc->send($invoice);
        return back()->with('success', 'Invoice sent');
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        $this->svc->markPaid($invoice, $request->user()->id);
        return back()->with('success', 'Invoice marked paid');
    }

    public function download(Invoice $invoice)
    {
        return response()->download($this->svc->downloadPdf($invoice), "{$invoice->reference}.pdf");
    }
}
```

### Task 12 — `PaymentGatewayController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/PaymentGatewayController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\PaymentGateway;
use Aero\Platform\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentGatewayController extends Controller
{
    public function __construct(private PaymentGatewayService $svc) {}

    public function index()
    {
        return Inertia::render('Platform/Admin/Billing/Gateways', [
            'gateways' => [
                'stripe'     => $this->svc->getConfig('stripe'),
                'sslcommerz' => $this->svc->getConfig('sslcommerz'),
            ],
        ]);
    }

    public function update(Request $request, string $code)
    {
        $data = $request->validate([
            'is_enabled' => 'boolean',
            'is_default' => 'boolean',
            'config'     => 'nullable|array',
        ]);

        $this->svc->updateConfig($code, $data);
        return back()->with('success', ucfirst($code).' gateway updated');
    }

    public function test(string $code)
    {
        return response()->json($this->svc->testConnection($code));
    }
}
```

### Task 13 — Form Requests

- [ ] `packages/aero-platform/src/Http/Requests/PlanStoreRequest.php`
- [ ] `packages/aero-platform/src/Http/Requests/PlanUpdateRequest.php`

```php
// PlanStoreRequest.php
namespace Aero\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'slug'          => 'nullable|string|max:120|unique:plans,slug',
            'description'   => 'nullable|string|max:2000',
            'price_monthly' => 'required|numeric|min:0',
            'price_annual'  => 'required|numeric|min:0',
            'currency'      => 'required|string|size:3',
            'trial_days'    => 'integer|min:0|max:365',
            'is_public'     => 'boolean',
            'features'      => 'nullable|array',
            'limits'        => 'nullable|array',
            'modules'       => 'nullable|array',
            'stripe_price_id_monthly' => 'nullable|string|max:120',
            'stripe_price_id_annual'  => 'nullable|string|max:120',
        ];
    }
}
```

```php
// PlanUpdateRequest.php
namespace Aero\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name'          => 'sometimes|string|max:255',
            'description'   => 'nullable|string|max:2000',
            'price_monthly' => 'sometimes|numeric|min:0',
            'price_annual'  => 'sometimes|numeric|min:0',
            'currency'      => 'sometimes|string|size:3',
            'trial_days'    => 'sometimes|integer|min:0|max:365',
            'is_public'     => 'sometimes|boolean',
            'features'      => 'nullable|array',
            'limits'        => 'nullable|array',
            'modules'       => 'nullable|array',
            'stripe_price_id_monthly' => 'nullable|string|max:120',
            'stripe_price_id_annual'  => 'nullable|string|max:120',
        ];
    }
}
```

---

## 5. Routes

### Task 14 — Register routes

- [ ] Append to `packages/aero-platform/routes/admin.php`

```php
use Aero\Platform\Http\Controllers\Admin\BillingDashboardController;
use Aero\Platform\Http\Controllers\Admin\InvoiceController;
use Aero\Platform\Http\Controllers\Admin\PaymentGatewayController;
use Aero\Platform\Http\Controllers\Admin\PlanController;
use Aero\Platform\Http\Controllers\Admin\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:landlord'])->prefix('platform/admin')->name('platform.admin.')->group(function () {

    // Plans
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/',              [PlanController::class, 'index'])->name('index')->middleware('hrmac:plan-management.plan-list.view');
        Route::get('/create',        [PlanController::class, 'create'])->name('create')->middleware('hrmac:plan-management.plan-list.create');
        Route::post('/',             [PlanController::class, 'store'])->name('store')->middleware('hrmac:plan-management.plan-list.create');
        Route::get('/{plan}',        [PlanController::class, 'show'])->name('show')->middleware('hrmac:plan-management.plan-details.view');
        Route::get('/{plan}/edit',   [PlanController::class, 'edit'])->name('edit')->middleware('hrmac:plan-management.plan-list.edit');
        Route::put('/{plan}',        [PlanController::class, 'update'])->name('update')->middleware('hrmac:plan-management.plan-list.edit');
        Route::delete('/{plan}',     [PlanController::class, 'destroy'])->name('destroy')->middleware('hrmac:plan-management.plan-list.delete');
        Route::post('/{plan}/archive', [PlanController::class, 'archive'])->name('archive')->middleware('hrmac:plan-management.plan-list.archive');
        Route::post('/{plan}/clone',   [PlanController::class, 'clone'])->name('clone')->middleware('hrmac:plan-management.plan-list.clone');
        Route::post('/{plan}/modules', [PlanController::class, 'assignModules'])->name('modules.assign')->middleware('hrmac:plan-management.plan-modules.assign');
    });

    // Billing
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/dashboard', [BillingDashboardController::class, 'index'])->name('dashboard')->middleware('hrmac:billing-management.billing-dashboard.view');

        // Subscriptions
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/',                 [SubscriptionController::class, 'index'])->name('index')->middleware('hrmac:billing-management.subscriptions.view');
            Route::get('/{subscription}',   [SubscriptionController::class, 'show'])->name('show')->middleware('hrmac:billing-management.subscriptions.view');
            Route::post('/{subscription}/cancel',  [SubscriptionController::class, 'cancel'])->name('cancel')->middleware('hrmac:billing-management.subscriptions.cancel');
            Route::post('/{subscription}/upgrade', [SubscriptionController::class, 'upgrade'])->name('upgrade')->middleware('hrmac:billing-management.subscriptions.upgrade');
        });

        // Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/',           [InvoiceController::class, 'index'])->name('index')->middleware('hrmac:billing-management.invoices.view');
            Route::get('/{invoice}',  [InvoiceController::class, 'show'])->name('show')->middleware('hrmac:billing-management.invoices.view');
            Route::post('/generate',  [InvoiceController::class, 'generate'])->name('generate')->middleware('hrmac:billing-management.invoices.generate');
            Route::post('/{invoice}/send',      [InvoiceController::class, 'send'])->name('send')->middleware('hrmac:billing-management.invoices.send');
            Route::post('/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])->name('mark-paid')->middleware('hrmac:billing-management.invoices.mark-paid');
            Route::get('/{invoice}/download',   [InvoiceController::class, 'download'])->name('download')->middleware('hrmac:billing-management.invoices.view');
        });

        // Payment Gateways
        Route::prefix('gateways')->name('gateways.')->group(function () {
            Route::get('/',            [PaymentGatewayController::class, 'index'])->name('index')->middleware('hrmac:billing-management.payment-gateways.view');
            Route::put('/{code}',      [PaymentGatewayController::class, 'update'])->name('update')->middleware('hrmac:billing-management.payment-gateways.configure');
            Route::post('/{code}/test', [PaymentGatewayController::class, 'test'])->name('test')->middleware('hrmac:billing-management.payment-gateways.view');
        });
    });
});
```

---

## 6. React Pages

All pages live under `packages/aero-ui/resources/js/Pages/Platform/Admin/`. Depth-4 imports: `App` = `'../../../App.jsx'`, `useHRMAC` = `'../../../../hooks/useHRMAC.js'`. All UI from `@aero/ui`, no inline styles, no `window.confirm`.

### Task 15 — `Plans/Index.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Plans/Index.jsx`

```jsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, CardHeader, Chip, Select, SelectItem,
  Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure,
} from '@aero/ui';
import { useState } from 'react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function PlansIndex() {
  const { plans, filters } = usePage().props;
  const { hasAccess, canCreate } = useHRMAC('plan-management.plan-list');
  const archiveModal = useDisclosure();
  const [target, setTarget] = useState(null);

  const filter = (status) => router.get(route('platform.admin.plans.index'),
    { status }, { preserveState: true, replace: true });

  return (
    <>
      <Head title="Plans" />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-semibold">Plans</h1>
          <div className="flex gap-2">
            <Select label="Status" selectedKeys={filters.status ? [filters.status] : []}
              onSelectionChange={(k) => filter([...k][0] ?? null)} className="w-48">
              <SelectItem key="active">Active</SelectItem>
              <SelectItem key="archived">Archived</SelectItem>
            </Select>
            {canCreate && (
              <Button as={Link} href={route('platform.admin.plans.create')} color="primary">New Plan</Button>
            )}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {plans.data.map((p) => (
            <Card key={p.id}>
              <CardHeader className="justify-between">
                <Link href={route('platform.admin.plans.show', p.id)} className="font-semibold text-primary">{p.name}</Link>
                <Chip size="sm" color={p.status === 'active' ? 'success' : 'default'}>{p.status}</Chip>
              </CardHeader>
              <CardBody>
                <div className="text-sm text-default-500">{p.currency} {p.price_monthly}/mo · {p.price_annual}/yr</div>
                <div className="text-sm">{p.subscriptions_count} subscribers</div>
                <div className="mt-3 flex gap-2">
                  {hasAccess('clone') && (
                    <Button size="sm" variant="flat"
                      onPress={() => router.post(route('platform.admin.plans.clone', p.id))}>Clone</Button>
                  )}
                  {hasAccess('archive') && p.status === 'active' && (
                    <Button size="sm" color="warning" variant="flat"
                      onPress={() => { setTarget(p); archiveModal.onOpen(); }}>Archive</Button>
                  )}
                </div>
              </CardBody>
            </Card>
          ))}
        </div>
      </div>

      <Modal isOpen={archiveModal.isOpen} onClose={archiveModal.onClose}>
        <ModalContent>
          <ModalHeader>Archive {target?.name}?</ModalHeader>
          <ModalBody>
            Archived plans are hidden from public signup. Existing subscribers keep their plan.
          </ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={archiveModal.onClose}>Cancel</Button>
            <Button color="warning"
              onPress={() => router.post(route('platform.admin.plans.archive', target.id),
                {}, { onSuccess: () => archiveModal.onClose() })}>Archive</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}

PlansIndex.layout = (page) => <App>{page}</App>;
```

### Task 16 — `Plans/Form.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Plans/Form.jsx`

```jsx
import { Head, useForm, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, CardHeader, Checkbox, Input, Switch, Textarea,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function PlanForm() {
  const { plan, availableModules } = usePage().props;
  const editing = !!plan;

  const form = useForm({
    name:          plan?.name ?? '',
    slug:          plan?.slug ?? '',
    description:   plan?.description ?? '',
    price_monthly: plan?.price_monthly ?? 0,
    price_annual:  plan?.price_annual ?? 0,
    currency:      plan?.currency ?? 'USD',
    trial_days:    plan?.trial_days ?? 0,
    is_public:     plan?.is_public ?? true,
    features:      plan?.features ?? [],
    limits:        plan?.limits ?? { users: 0, storage_gb: 0, api_calls: 0 },
    modules:       (plan?.modules ?? []).reduce((acc, m) => { acc[m.module_code] = { is_enabled: true }; return acc; }, {}),
    stripe_price_id_monthly: plan?.stripe_price_id_monthly ?? '',
    stripe_price_id_annual:  plan?.stripe_price_id_annual ?? '',
  });

  const submit = (e) => {
    e.preventDefault();
    editing
      ? form.put(route('platform.admin.plans.update', plan.id))
      : form.post(route('platform.admin.plans.store'));
  };

  const toggleModule = (code, checked) => {
    const next = { ...form.data.modules };
    if (checked) next[code] = { is_enabled: true };
    else delete next[code];
    form.setData('modules', next);
  };

  return (
    <>
      <Head title={editing ? `Edit ${plan.name}` : 'New Plan'} />
      <form onSubmit={submit} className="space-y-4">
        <Card>
          <CardHeader>Basic Info</CardHeader>
          <CardBody className="grid grid-cols-2 gap-3">
            <Input label="Name" value={form.data.name} onValueChange={(v) => form.setData('name', v)}
              isInvalid={!!form.errors.name} errorMessage={form.errors.name} />
            <Input label="Slug" value={form.data.slug} onValueChange={(v) => form.setData('slug', v)} />
            <Textarea className="col-span-2" label="Description" value={form.data.description}
              onValueChange={(v) => form.setData('description', v)} />
          </CardBody>
        </Card>

        <Card>
          <CardHeader>Pricing</CardHeader>
          <CardBody className="grid grid-cols-3 gap-3">
            <Input label="Price / Month" type="number" step="0.01" value={String(form.data.price_monthly)}
              onValueChange={(v) => form.setData('price_monthly', Number(v))} />
            <Input label="Price / Year" type="number" step="0.01" value={String(form.data.price_annual)}
              onValueChange={(v) => form.setData('price_annual', Number(v))} />
            <Input label="Currency" value={form.data.currency} onValueChange={(v) => form.setData('currency', v.toUpperCase())} />
            <Input label="Trial Days" type="number" value={String(form.data.trial_days)}
              onValueChange={(v) => form.setData('trial_days', Number(v))} />
            <Input label="Stripe Price ID (monthly)" value={form.data.stripe_price_id_monthly}
              onValueChange={(v) => form.setData('stripe_price_id_monthly', v)} />
            <Input label="Stripe Price ID (annual)" value={form.data.stripe_price_id_annual}
              onValueChange={(v) => form.setData('stripe_price_id_annual', v)} />
            <Switch isSelected={form.data.is_public} onValueChange={(v) => form.setData('is_public', v)}>
              Public (visible on signup)
            </Switch>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>Quota Limits</CardHeader>
          <CardBody className="grid grid-cols-3 gap-3">
            <Input label="Max Users" type="number" value={String(form.data.limits.users ?? 0)}
              onValueChange={(v) => form.setData('limits', { ...form.data.limits, users: Number(v) })} />
            <Input label="Storage (GB)" type="number" value={String(form.data.limits.storage_gb ?? 0)}
              onValueChange={(v) => form.setData('limits', { ...form.data.limits, storage_gb: Number(v) })} />
            <Input label="API Calls / Month" type="number" value={String(form.data.limits.api_calls ?? 0)}
              onValueChange={(v) => form.setData('limits', { ...form.data.limits, api_calls: Number(v) })} />
          </CardBody>
        </Card>

        <Card>
          <CardHeader>Module Assignment</CardHeader>
          <CardBody className="grid grid-cols-3 gap-2">
            {availableModules.map((code) => (
              <Checkbox key={code} isSelected={!!form.data.modules[code]}
                onValueChange={(v) => toggleModule(code, v)}>
                {code}
              </Checkbox>
            ))}
          </CardBody>
        </Card>

        <div className="flex justify-end">
          <Button type="submit" color="primary" isLoading={form.processing}>
            {editing ? 'Save Changes' : 'Create Plan'}
          </Button>
        </div>
      </form>
    </>
  );
}

PlanForm.layout = (page) => <App>{page}</App>;
```

### Task 17 — `Plans/Show.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Plans/Show.jsx`

```jsx
import { Head, Link, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, CardHeader, Chip,
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
} from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function PlanShow() {
  const { plan, metrics } = usePage().props;
  const { hasAccess } = useHRMAC('plan-management.plan-list');

  return (
    <>
      <Head title={plan.name} />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-semibold">{plan.name}</h1>
            <Chip size="sm">{plan.status}</Chip>
          </div>
          {hasAccess('edit') && (
            <Button as={Link} href={route('platform.admin.plans.edit', plan.id)} color="primary">Edit</Button>
          )}
        </div>

        <div className="grid grid-cols-3 gap-4">
          <Card><CardBody>
            <div className="text-sm text-default-500">Active Subscribers</div>
            <div className="text-3xl font-semibold">{metrics.active_subscribers}</div>
          </CardBody></Card>
          <Card><CardBody>
            <div className="text-sm text-default-500">MRR Contribution</div>
            <div className="text-3xl font-semibold">{plan.currency} {metrics.mrr.toFixed(2)}</div>
          </CardBody></Card>
          <Card><CardBody>
            <div className="text-sm text-default-500">Price</div>
            <div>{plan.currency} {plan.price_monthly}/mo</div>
            <div>{plan.currency} {plan.price_annual}/yr</div>
          </CardBody></Card>
        </div>

        <Card>
          <CardHeader>Modules</CardHeader>
          <CardBody className="flex flex-wrap gap-2">
            {plan.modules?.map((m) => (
              <Chip key={m.id} color={m.is_enabled ? 'primary' : 'default'}>{m.module_code}</Chip>
            )) ?? <span className="text-default-400">No modules assigned</span>}
          </CardBody>
        </Card>

        <Card>
          <CardHeader>Subscribers</CardHeader>
          <CardBody>
            <Table aria-label="Subscribers" removeWrapper>
              <TableHeader>
                <TableColumn>Tenant</TableColumn><TableColumn>Status</TableColumn>
                <TableColumn>Cycle</TableColumn><TableColumn>Period End</TableColumn>
              </TableHeader>
              <TableBody emptyContent="No subscribers" items={plan.subscriptions ?? []}>
                {(s) => (
                  <TableRow key={s.id}>
                    <TableCell>{s.tenant_id}</TableCell>
                    <TableCell><Chip size="sm">{s.status}</Chip></TableCell>
                    <TableCell>{s.billing_cycle}</TableCell>
                    <TableCell>{s.current_period_end ? new Date(s.current_period_end).toLocaleDateString() : '—'}</TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </CardBody>
        </Card>
      </div>
    </>
  );
}

PlanShow.layout = (page) => <App>{page}</App>;
```

### Task 18 — `Billing/Dashboard.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Billing/Dashboard.jsx`

```jsx
import { Head, usePage } from '@inertiajs/react';
import {
  Card, CardBody, CardHeader,
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Chip,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function BillingDashboard() {
  const { stats, recent_subscriptions, recent_invoices } = usePage().props;

  const cards = [
    { label: 'MRR',              value: `$${Number(stats.mrr).toFixed(2)}` },
    { label: 'ARR',              value: `$${Number(stats.arr).toFixed(2)}` },
    { label: 'Active Subs',      value: stats.active_subs },
    { label: 'Pending Invoices', value: stats.pending_invoices },
    { label: 'Overdue',          value: stats.overdue },
  ];

  return (
    <>
      <Head title="Billing Dashboard" />
      <div className="space-y-4">
        <h1 className="text-2xl font-semibold">Billing Dashboard</h1>

        <div className="grid grid-cols-5 gap-4">
          {cards.map((c) => (
            <Card key={c.label}>
              <CardBody>
                <div className="text-sm text-default-500">{c.label}</div>
                <div className="text-2xl font-semibold mt-1">{c.value}</div>
              </CardBody>
            </Card>
          ))}
        </div>

        <div className="grid grid-cols-2 gap-4">
          <Card>
            <CardHeader>Recent Subscriptions</CardHeader>
            <CardBody>
              <Table aria-label="Recent subs" removeWrapper>
                <TableHeader>
                  <TableColumn>Tenant</TableColumn><TableColumn>Plan</TableColumn><TableColumn>Status</TableColumn>
                </TableHeader>
                <TableBody emptyContent="None" items={recent_subscriptions}>
                  {(s) => (
                    <TableRow key={s.id}>
                      <TableCell>{s.tenant_id}</TableCell>
                      <TableCell>{s.plan?.name ?? '—'}</TableCell>
                      <TableCell><Chip size="sm">{s.status}</Chip></TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </CardBody>
          </Card>

          <Card>
            <CardHeader>Recent Invoices</CardHeader>
            <CardBody>
              <Table aria-label="Recent invoices" removeWrapper>
                <TableHeader>
                  <TableColumn>Reference</TableColumn><TableColumn>Amount</TableColumn><TableColumn>Status</TableColumn>
                </TableHeader>
                <TableBody emptyContent="None" items={recent_invoices}>
                  {(i) => (
                    <TableRow key={i.id}>
                      <TableCell>{i.reference}</TableCell>
                      <TableCell>{i.currency} {i.amount}</TableCell>
                      <TableCell><Chip size="sm">{i.status}</Chip></TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
            </CardBody>
          </Card>
        </div>
      </div>
    </>
  );
}

BillingDashboard.layout = (page) => <App>{page}</App>;
```

### Task 19 — `Billing/Subscriptions.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Billing/Subscriptions.jsx`

```jsx
import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, Chip, Input, Modal, ModalContent, ModalHeader,
  ModalBody, ModalFooter, Pagination, Select, SelectItem,
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, useDisclosure,
} from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function Subscriptions() {
  const { subscriptions, filters, plans } = usePage().props;
  const { hasAccess } = useHRMAC('billing-management.subscriptions');

  const cancelModal = useDisclosure();
  const upgradeModal = useDisclosure();
  const [target, setTarget] = useState(null);
  const [reason, setReason] = useState('');
  const [planId, setPlanId] = useState(null);

  const filter = (patch) => router.get(route('platform.admin.billing.subscriptions.index'),
    { ...filters, ...patch }, { preserveState: true, replace: true });

  const doCancel = () => router.post(route('platform.admin.billing.subscriptions.cancel', target.id),
    { reason }, { onSuccess: () => cancelModal.onClose() });

  const doUpgrade = () => router.post(route('platform.admin.billing.subscriptions.upgrade', target.id),
    { plan_id: planId }, { onSuccess: () => upgradeModal.onClose() });

  return (
    <>
      <Head title="Subscriptions" />
      <Card>
        <CardBody>
          <div className="flex gap-3 mb-4">
            <Select label="Status" selectedKeys={filters.status ? [filters.status] : []}
              onSelectionChange={(k) => filter({ status: [...k][0] ?? null })} className="max-w-xs">
              {['trialing','active','cancelled','past_due','unpaid'].map((s) =>
                <SelectItem key={s}>{s}</SelectItem>)}
            </Select>
          </div>

          <Table aria-label="Subscriptions" removeWrapper>
            <TableHeader>
              <TableColumn>Tenant</TableColumn><TableColumn>Plan</TableColumn>
              <TableColumn>Cycle</TableColumn><TableColumn>Status</TableColumn>
              <TableColumn>Period End</TableColumn><TableColumn>Actions</TableColumn>
            </TableHeader>
            <TableBody emptyContent="No subscriptions" items={subscriptions.data}>
              {(s) => (
                <TableRow key={s.id}>
                  <TableCell>{s.tenant_id}</TableCell>
                  <TableCell>{s.plan?.name ?? '—'}</TableCell>
                  <TableCell>{s.billing_cycle}</TableCell>
                  <TableCell><Chip size="sm" color={s.status === 'active' ? 'success' : 'default'}>{s.status}</Chip></TableCell>
                  <TableCell>{s.current_period_end ? new Date(s.current_period_end).toLocaleDateString() : '—'}</TableCell>
                  <TableCell className="flex gap-2">
                    {s.status === 'active' && hasAccess('upgrade') && (
                      <Button size="sm" onPress={() => { setTarget(s); upgradeModal.onOpen(); }}>Upgrade</Button>
                    )}
                    {s.status !== 'cancelled' && hasAccess('cancel') && (
                      <Button size="sm" color="danger" onPress={() => { setTarget(s); setReason(''); cancelModal.onOpen(); }}>Cancel</Button>
                    )}
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>

          {subscriptions.last_page > 1 && (
            <div className="mt-4 flex justify-center">
              <Pagination total={subscriptions.last_page} page={subscriptions.current_page}
                onChange={(p) => filter({ page: p })} />
            </div>
          )}
        </CardBody>
      </Card>

      <Modal isOpen={cancelModal.isOpen} onClose={cancelModal.onClose}>
        <ModalContent>
          <ModalHeader>Cancel Subscription</ModalHeader>
          <ModalBody>
            <Input label="Reason" value={reason} onValueChange={setReason} />
          </ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={cancelModal.onClose}>Back</Button>
            <Button color="danger" isDisabled={!reason} onPress={doCancel}>Cancel Subscription</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      <Modal isOpen={upgradeModal.isOpen} onClose={upgradeModal.onClose}>
        <ModalContent>
          <ModalHeader>Upgrade Subscription</ModalHeader>
          <ModalBody>
            <Select label="Target Plan" selectedKeys={planId ? [String(planId)] : []}
              onSelectionChange={(k) => setPlanId(Number([...k][0]))}>
              {plans.map((p) => <SelectItem key={p.id}>{p.name}</SelectItem>)}
            </Select>
          </ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={upgradeModal.onClose}>Cancel</Button>
            <Button color="primary" isDisabled={!planId} onPress={doUpgrade}>Upgrade</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}

Subscriptions.layout = (page) => <App>{page}</App>;
```

### Task 20 — `Billing/Invoices.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Billing/Invoices.jsx`

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, Chip, Pagination, Select, SelectItem,
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell,
} from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

const COLOR = { draft: 'default', sent: 'primary', paid: 'success', voided: 'danger' };

export default function Invoices() {
  const { invoices, filters } = usePage().props;
  const { hasAccess } = useHRMAC('billing-management.invoices');

  const filter = (patch) => router.get(route('platform.admin.billing.invoices.index'),
    { ...filters, ...patch }, { preserveState: true, replace: true });

  return (
    <>
      <Head title="Invoices" />
      <Card>
        <CardBody>
          <div className="flex gap-3 mb-4">
            <Select label="Status" selectedKeys={filters.status ? [filters.status] : []}
              onSelectionChange={(k) => filter({ status: [...k][0] ?? null })} className="max-w-xs">
              {['draft','sent','paid','voided'].map((s) => <SelectItem key={s}>{s}</SelectItem>)}
            </Select>
          </div>

          <Table aria-label="Invoices" removeWrapper>
            <TableHeader>
              <TableColumn>Reference</TableColumn><TableColumn>Tenant</TableColumn>
              <TableColumn>Amount</TableColumn><TableColumn>Status</TableColumn>
              <TableColumn>Due</TableColumn><TableColumn>Actions</TableColumn>
            </TableHeader>
            <TableBody emptyContent="No invoices" items={invoices.data}>
              {(i) => (
                <TableRow key={i.id}>
                  <TableCell>{i.reference}</TableCell>
                  <TableCell>{i.tenant_id}</TableCell>
                  <TableCell>{i.currency} {i.amount}</TableCell>
                  <TableCell><Chip size="sm" color={COLOR[i.status]}>{i.status}</Chip></TableCell>
                  <TableCell>{i.due_date ?? '—'}</TableCell>
                  <TableCell className="flex gap-2">
                    {i.status === 'draft' && hasAccess('send') && (
                      <Button size="sm" onPress={() => router.post(route('platform.admin.billing.invoices.send', i.id))}>Send</Button>
                    )}
                    {i.status !== 'paid' && hasAccess('mark-paid') && (
                      <Button size="sm" color="success"
                        onPress={() => router.post(route('platform.admin.billing.invoices.mark-paid', i.id))}>
                        Mark Paid
                      </Button>
                    )}
                    <Button size="sm" variant="flat" as="a"
                      href={route('platform.admin.billing.invoices.download', i.id)}>PDF</Button>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>

          {invoices.last_page > 1 && (
            <div className="mt-4 flex justify-center">
              <Pagination total={invoices.last_page} page={invoices.current_page}
                onChange={(p) => filter({ page: p })} />
            </div>
          )}
        </CardBody>
      </Card>
    </>
  );
}

Invoices.layout = (page) => <App>{page}</App>;
```

### Task 21 — `Billing/Gateways.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Billing/Gateways.jsx`

```jsx
import { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Button, Card, CardBody, CardHeader, Input, Switch } from '@aero/ui';
import App from '../../../App.jsx';

function GatewayForm({ code, gateway, fields }) {
  const form = useForm({
    is_enabled: gateway.is_enabled,
    is_default: gateway.is_default,
    config: fields.reduce((acc, f) => { acc[f] = ''; return acc; }, {}),
  });
  const [testResult, setTestResult] = useState(null);

  const submit = (e) => {
    e.preventDefault();
    form.put(route('platform.admin.billing.gateways.update', code));
  };

  const testConn = async () => {
    setTestResult({ status: 'testing' });
    const res = await fetch(route('platform.admin.billing.gateways.test', code), { method: 'POST' });
    setTestResult(await res.json());
  };

  return (
    <Card>
      <CardHeader className="justify-between">
        <span className="font-semibold">{gateway.label}</span>
        <Switch isSelected={form.data.is_enabled}
          onValueChange={(v) => form.setData('is_enabled', v)}>Enabled</Switch>
      </CardHeader>
      <CardBody>
        <form onSubmit={submit} className="space-y-3">
          {fields.map((f) => (
            <Input key={f} label={f} type={f.toLowerCase().includes('secret') || f.toLowerCase().includes('password') ? 'password' : 'text'}
              value={form.data.config[f] ?? ''}
              onValueChange={(v) => form.setData('config', { ...form.data.config, [f]: v })} />
          ))}
          <Switch isSelected={form.data.is_default}
            onValueChange={(v) => form.setData('is_default', v)}>Default Gateway</Switch>
          <div className="flex gap-2">
            <Button type="submit" color="primary" isLoading={form.processing}>Save</Button>
            <Button variant="flat" onPress={testConn}>Test Connection</Button>
          </div>
          {testResult && (
            <div className={testResult.ok ? 'text-success text-sm' : 'text-danger text-sm'}>
              {testResult.message ?? testResult.status}
            </div>
          )}
        </form>
      </CardBody>
    </Card>
  );
}

export default function Gateways() {
  const { gateways } = usePage().props;

  return (
    <>
      <Head title="Payment Gateways" />
      <div className="grid grid-cols-2 gap-4">
        <GatewayForm code="stripe" gateway={gateways.stripe}
          fields={['publishable_key', 'secret_key', 'webhook_secret']} />
        <GatewayForm code="sslcommerz" gateway={gateways.sslcommerz}
          fields={['store_id', 'store_password', 'is_sandbox']} />
      </div>
    </>
  );
}

Gateways.layout = (page) => <App>{page}</App>;
```

---

## 7. PDF Template

### Task 22 — Invoice PDF Blade

- [ ] `packages/aero-platform/resources/views/invoices/pdf.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->reference }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .total { text-align: right; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Invoice {{ $invoice->reference }}</h1>
    <p>Tenant: {{ $invoice->tenant_id }}<br>
       Due: {{ $invoice->due_date }}<br>
       Status: {{ strtoupper($invoice->status) }}</p>

    <table>
        <thead>
            <tr><th>Description</th><th>Amount</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->subscription->plan->name ?? 'Subscription' }} ({{ $invoice->subscription->billing_cycle ?? '—' }})</td>
                <td>{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr>
                <td>Tax</td>
                <td>{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</td>
            </tr>
            <tr class="total">
                <td>Total</td>
                <td>{{ $invoice->currency }} {{ number_format($invoice->amount + $invoice->tax_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
```

---

## 8. Tests

All tests live under `packages/aero-platform/tests/Feature/Admin/`. Use `Gate::before(fn () => true)` and boot `AeroCoreServiceProvider` + `AeroPlatformServiceProvider`.

### Task 23 — `PlanControllerTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/PlanControllerTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Platform\AeroPlatformServiceProvider;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class PlanControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AeroCoreServiceProvider::class, AeroPlatformServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
        $this->actingAs($this->makeLandlordUser(), 'landlord');
    }

    private function makeLandlordUser() { /* factory stub */ }

    public function test_creates_plan_with_module_assignment(): void
    {
        $r = $this->post(route('platform.admin.plans.store'), [
            'name'          => 'Pro',
            'price_monthly' => 49,
            'price_annual'  => 490,
            'currency'      => 'USD',
            'modules'       => ['hrm' => ['is_enabled' => true], 'finance' => ['is_enabled' => true]],
        ]);
        $r->assertRedirect();

        $plan = Plan::where('name','Pro')->first();
        $this->assertNotNull($plan);
        $this->assertSame(2, $plan->modules()->count());
    }

    public function test_cannot_delete_plan_with_active_subscribers(): void
    {
        $plan = Plan::factory()->create();
        Subscription::factory()->create(['plan_id' => $plan->id, 'status' => 'active']);

        $this->delete(route('platform.admin.plans.destroy', $plan))->assertStatus(422);
        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_archives_plan(): void
    {
        $plan = Plan::factory()->create(['status' => 'active']);
        $this->post(route('platform.admin.plans.archive', $plan))->assertRedirect();
        $this->assertSame('archived', $plan->fresh()->status);
    }

    public function test_cloned_plan_has_same_modules_but_different_slug(): void
    {
        $plan = Plan::factory()->create(['slug' => 'starter']);
        $plan->modules()->create(['module_code' => 'hrm', 'is_enabled' => true]);

        $this->post(route('platform.admin.plans.clone', $plan))->assertRedirect();

        $copy = Plan::where('id', '!=', $plan->id)->latest()->first();
        $this->assertNotSame($plan->slug, $copy->slug);
        $this->assertSame(1, $copy->modules()->count());
        $this->assertSame('hrm', $copy->modules()->first()->module_code);
    }
}
```

### Task 24 — `SubscriptionImmutabilityTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/SubscriptionImmutabilityTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Platform\AeroPlatformServiceProvider;
use Aero\Platform\Exceptions\SubscriptionFinalizedException;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class SubscriptionImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AeroCoreServiceProvider::class, AeroPlatformServiceProvider::class];
    }

    public function test_cannot_change_plan_id_on_active_subscription(): void
    {
        Gate::before(fn () => true);
        $planA = Plan::factory()->create();
        $planB = Plan::factory()->create();
        $sub = Subscription::factory()->create(['plan_id' => $planA->id, 'status' => 'active']);

        $this->expectException(SubscriptionFinalizedException::class);
        $sub->plan_id = $planB->id;
        $sub->save();
    }

    public function test_can_transition_active_to_cancelled(): void
    {
        Gate::before(fn () => true);
        $sub = Subscription::factory()->create(['status' => 'active']);

        $sub->status = 'cancelled';
        $sub->cancelled_at = now();
        $sub->save();

        $this->assertSame('cancelled', $sub->fresh()->status);
    }

    public function test_cannot_cancel_already_cancelled_subscription(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->makeLandlordUser(), 'landlord');

        $sub = Subscription::factory()->create(['status' => 'cancelled']);
        $this->post(route('platform.admin.billing.subscriptions.cancel', $sub),
            ['reason' => 'x'])->assertStatus(422);
    }

    private function makeLandlordUser() { /* stub */ }
}
```

### Task 25 — `InvoiceImmutabilityTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/InvoiceImmutabilityTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Platform\AeroPlatformServiceProvider;
use Aero\Platform\Exceptions\InvoiceFinalizedException;
use Aero\Platform\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class InvoiceImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AeroCoreServiceProvider::class, AeroPlatformServiceProvider::class];
    }

    public function test_cannot_change_amount_on_paid_invoice(): void
    {
        Gate::before(fn () => true);
        $inv = Invoice::factory()->create(['status' => 'paid', 'amount' => 49.00]);

        $this->expectException(InvoiceFinalizedException::class);
        $inv->amount = 99.00;
        $inv->save();
    }

    public function test_pdf_is_generated_on_invoice_creation(): void
    {
        Storage::fake('local');
        Gate::before(fn () => true);

        $sub = \Aero\Platform\Models\Subscription::factory()->create();
        $svc = app(\Aero\Platform\Services\InvoiceAdminService::class);
        $invoice = $svc->generate($sub);

        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);
    }
}
```

---

## 9. Task Checklist Summary

- [ ] Task 0  — `config/module.php` HRMAC hierarchy
- [ ] Task 1  — Migrations (plans, plan_modules, subscriptions, invoices, payment_gateways)
- [ ] Task 2  — Models (Plan, PlanModule, Subscription, Invoice, PaymentGateway) + Exceptions
- [ ] Task 3  — Immutability Observers (Subscription, Invoice)
- [ ] Task 4  — `PlanService`
- [ ] Task 5  — `SubscriptionAdminService`
- [ ] Task 6  — `InvoiceAdminService`
- [ ] Task 7  — `PaymentGatewayService`
- [ ] Task 8  — `PlanController`
- [ ] Task 9  — `BillingDashboardController`
- [ ] Task 10 — `SubscriptionController`
- [ ] Task 11 — `InvoiceController`
- [ ] Task 12 — `PaymentGatewayController`
- [ ] Task 13 — Form Requests
- [ ] Task 14 — `routes/admin.php`
- [ ] Task 15 — `Plans/Index.jsx`
- [ ] Task 16 — `Plans/Form.jsx`
- [ ] Task 17 — `Plans/Show.jsx`
- [ ] Task 18 — `Billing/Dashboard.jsx`
- [ ] Task 19 — `Billing/Subscriptions.jsx`
- [ ] Task 20 — `Billing/Invoices.jsx`
- [ ] Task 21 — `Billing/Gateways.jsx`
- [ ] Task 22 — Invoice PDF Blade template
- [ ] Task 23 — `PlanControllerTest`
- [ ] Task 24 — `SubscriptionImmutabilityTest`
- [ ] Task 25 — `InvoiceImmutabilityTest`

---

## 10. Acceptance Criteria

- All HRMAC codes from section 1 are declared in `config/module.php` and enforced on routes.
- Subscriptions cannot have `plan_id`, `billing_cycle`, or period dates mutated once `status='active'` (verified by observer + test).
- Invoices cannot have any field mutated once `status='paid'` (verified by observer + test).
- Upgrade flow cancels the old subscription and creates a new one on the new plan (no in-place mutation).
- Plans with active subscribers cannot be deleted (422); they must be archived.
- Cloning a plan duplicates its module assignments with a unique slug.
- Payment gateway `config` (Stripe keys, SSLCommerz credentials) is encrypted at rest via `EncryptedField`.
- Every billing event writes a record to `platform_audit_logs`.
- Invoice PDF is generated on `generate()` and stored at `storage/app/invoices/{reference}.pdf`.
- All writes are inside `DB::transaction()`.
- React UI: all imports from `@aero/ui`, no inline styles, all destructive actions go through Modal.
- Tests in section 8 pass under `php artisan test --filter=Platform`.
