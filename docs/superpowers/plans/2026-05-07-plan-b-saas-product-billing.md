# Plan B — SaaS Product Billing & Access Control

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `products` + `product_subscriptions` layer to `aero-platform` so SaaS tenants can subscribe to individual business modules (HRM, CRM, etc.) independently of their plan; update `CheckModuleAccess` to gate module access on active product subscriptions.

**Architecture:** `products` table lives in the central DB and maps one product to one `module_code`. `product_subscriptions` is a per-tenant subscription (like the existing `subscriptions` table for plans). `CheckModuleAccess` gains a second check: for feature modules, tenant must have an active `product_subscription`. Plan subscriptions govern resource limits only — they never gate module access. Standalone mode skips this check entirely (no product subscription concept).

**Tech Stack:** Laravel 11, Eloquent, existing `aero-platform` package, existing `plans`/`subscriptions` migrations as reference.

**Prerequisite:** Plan A (bug fixes) must be complete before this plan is executed.

---

## File Map

### New Files
- `packages/aero-platform/database/migrations/XXXX_create_products_table.php`
- `packages/aero-platform/database/migrations/XXXX_create_product_subscriptions_table.php`
- `packages/aero-platform/src/Models/Product.php`
- `packages/aero-platform/src/Models/ProductSubscription.php`
- `packages/aero-platform/src/Services/ProductAccessService.php`
- `packages/aero-platform/src/Services/ProductSubscriptionService.php`
- `packages/aero-platform/src/Http/Controllers/Admin/ProductController.php`
- `packages/aero-platform/src/Http/Controllers/Admin/ProductSubscriptionController.php`
- `packages/aero-platform/database/seeders/ProductSeeder.php`
- `packages/aero-platform/tests/Feature/ProductAccessTest.php`

### Modified Files
- `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php` — add product subscription check
- `packages/aero-platform/src/AeroPlatformServiceProvider.php` — register ProductAccessService
- `packages/aero-platform/routes/admin.php` — add product management routes

---

## Task B1: Products Migration & Model

**Files:**
- Create: `packages/aero-platform/database/migrations/2026_05_07_000001_create_products_table.php`
- Create: `packages/aero-platform/src/Models/Product.php`

- [ ] **Step B1.1: Write the migration**

```php
<?php
// packages/aero-platform/database/migrations/2026_05_07_000001_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();           // 'hrm', 'crm', 'finance'
            $table->string('module_code')->unique();    // matches module.php 'code' field
            $table->string('name');                     // 'HRM Suite', 'CRM Suite'
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_marketplace_visible')->default(true); // show on public marketplace
            $table->integer('sort_order')->default(0);
            $table->string('version')->default('1.0.0');
            $table->json('metadata')->nullable();       // screenshots, features list, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('module_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

- [ ] **Step B1.2: Write the Product model**

```php
<?php
// packages/aero-platform/src/Models/Product.php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'code', 'module_code', 'name', 'description', 'icon',
        'monthly_price', 'yearly_price', 'currency',
        'is_active', 'is_marketplace_visible', 'sort_order', 'version', 'metadata',
    ];

    protected $casts = [
        'monthly_price'          => 'decimal:2',
        'yearly_price'           => 'decimal:2',
        'is_active'              => 'boolean',
        'is_marketplace_visible' => 'boolean',
        'metadata'               => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ProductSubscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(ProductSubscription::class)
            ->where('status', 'active');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMarketplaceVisible($query)
    {
        return $query->where('is_marketplace_visible', true)->where('is_active', true);
    }
}
```

- [ ] **Step B1.3: Run the migration**

```bash
php artisan migrate --path=packages/aero-platform/database/migrations/2026_05_07_000001_create_products_table.php
```

Expected: `Migrated: ...2026_05_07_000001_create_products_table`

- [ ] **Step B1.4: Commit**

```bash
git add packages/aero-platform/database/migrations/2026_05_07_000001_create_products_table.php \
        packages/aero-platform/src/Models/Product.php
git commit -m "feat(aero-platform): add products table and Product model"
```

---

## Task B2: Product Subscriptions Migration & Model

**Files:**
- Create: `packages/aero-platform/database/migrations/2026_05_07_000002_create_product_subscriptions_table.php`
- Create: `packages/aero-platform/src/Models/ProductSubscription.php`

- [ ] **Step B2.1: Write the migration**

```php
<?php
// packages/aero-platform/database/migrations/2026_05_07_000002_create_product_subscriptions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->foreignUuid('product_id')->constrained()->onDelete('restrict');
            $table->string('billing_cycle');            // 'monthly', 'yearly'
            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('active'); // active, cancelled, past_due, trialing, paused, expired
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('external_subscription_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
            $table->index('status');
            $table->index('ends_at');
            $table->index(['tenant_id', 'status']);
            // Prevent duplicate active subscriptions to same product per tenant
            $table->unique(['tenant_id', 'product_id', 'status'], 'unique_active_product_sub');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_subscriptions');
    }
};
```

- [ ] **Step B2.2: Write the ProductSubscription model**

```php
<?php
// packages/aero-platform/src/Models/ProductSubscription.php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSubscription extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'product_id', 'billing_cycle', 'amount', 'discount_amount',
        'currency', 'status', 'trial_starts_at', 'trial_ends_at',
        'starts_at', 'ends_at', 'cancelled_at', 'cancellation_reason',
        'payment_method', 'external_subscription_id', 'metadata',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'trial_starts_at' => 'datetime',
        'trial_ends_at'   => 'datetime',
        'starts_at'       => 'datetime',
        'ends_at'         => 'datetime',
        'cancelled_at'    => 'datetime',
        'metadata'        => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    public function hasAccess(): bool
    {
        return $this->isActive() || $this->isTrialing();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    public function scopeHasAccess($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'active')
              ->where(fn ($sub) => $sub->whereNull('ends_at')->orWhere('ends_at', '>', now()));
        })->orWhere(function ($q) {
            $q->where('status', 'trialing')
              ->where('trial_ends_at', '>', now());
        });
    }
}
```

- [ ] **Step B2.3: Run migration**

```bash
php artisan migrate --path=packages/aero-platform/database/migrations/2026_05_07_000002_create_product_subscriptions_table.php
```

- [ ] **Step B2.4: Commit**

```bash
git add packages/aero-platform/database/migrations/2026_05_07_000002_create_product_subscriptions_table.php \
        packages/aero-platform/src/Models/ProductSubscription.php
git commit -m "feat(aero-platform): add product_subscriptions table and ProductSubscription model"
```

---

## Task B3: ProductAccessService

**Files:**
- Create: `packages/aero-platform/src/Services/ProductAccessService.php`

This service is the single source of truth for "does this tenant have access to this module?" in SaaS mode. It is called by `CheckModuleAccess` middleware.

- [ ] **Step B3.1: Write the service**

```php
<?php
// packages/aero-platform/src/Services/ProductAccessService.php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Illuminate\Support\Facades\Cache;

class ProductAccessService
{
    /**
     * Check if the current tenant has an active product subscription
     * granting access to the given module code.
     *
     * Returns true for 'core' module — always available to all tenants.
     * Returns true if module_code maps to no product (uncatalogued modules are free).
     */
    public function tenantCanAccessModule(string $tenantId, string $moduleCode): bool
    {
        // Core module is always accessible — it's the base platform
        if ($moduleCode === 'core') {
            return true;
        }

        $cacheKey = "product_access:{$tenantId}:{$moduleCode}";

        return Cache::tags(["tenant:{$tenantId}", 'product-access'])
            ->remember($cacheKey, 300, function () use ($tenantId, $moduleCode) {
                $product = Product::active()
                    ->where('module_code', $moduleCode)
                    ->first();

                // If no product is registered for this module, allow access
                // This prevents lockout during development / migration
                if ($product === null) {
                    return true;
                }

                return ProductSubscription::where('tenant_id', $tenantId)
                    ->where('product_id', $product->id)
                    ->hasAccess()
                    ->exists();
            });
    }

    /**
     * Get all module codes the tenant currently has access to.
     */
    public function getAccessibleModuleCodes(string $tenantId): array
    {
        $cacheKey = "product_access:all:{$tenantId}";

        return Cache::tags(["tenant:{$tenantId}", 'product-access'])
            ->remember($cacheKey, 300, function () use ($tenantId) {
                // Always include 'core'
                $accessible = ['core'];

                $subscribedProductIds = ProductSubscription::where('tenant_id', $tenantId)
                    ->hasAccess()
                    ->pluck('product_id');

                $moduleCodes = Product::whereIn('id', $subscribedProductIds)
                    ->pluck('module_code')
                    ->toArray();

                return array_merge($accessible, $moduleCodes);
            });
    }

    /**
     * Flush the access cache for a tenant.
     * Call whenever a product subscription is created, updated, or cancelled.
     */
    public function flushCache(string $tenantId): void
    {
        Cache::tags(["tenant:{$tenantId}", 'product-access'])->flush();
    }
}
```

- [ ] **Step B3.2: Register in AeroPlatformServiceProvider**

Open `packages/aero-platform/src/AeroPlatformServiceProvider.php`. In the `register()` method, add:

```php
$this->app->singleton(\Aero\Platform\Services\ProductAccessService::class);
```

- [ ] **Step B3.3: Commit**

```bash
git add packages/aero-platform/src/Services/ProductAccessService.php \
        packages/aero-platform/src/AeroPlatformServiceProvider.php
git commit -m "feat(aero-platform): add ProductAccessService — tenant module access via product subscriptions"
```

---

## Task B4: Update CheckModuleAccess to Enforce Product Subscriptions

**Files:**
- Modify: `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php`

`CheckModuleAccess` currently only checks RBAC (roles/permissions). In SaaS mode it must also verify the tenant has an active product subscription for the module.

- [ ] **Step B4.1: Write the failing test first**

Create `packages/aero-platform/tests/Feature/ProductAccessTest.php`:

```php
<?php
// packages/aero-platform/tests/Feature/ProductAccessTest.php

namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Aero\Platform\Services\ProductAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_with_active_product_subscription_can_access_module(): void
    {
        $product = Product::factory()->create(['module_code' => 'hrm']);

        ProductSubscription::factory()->create([
            'tenant_id'  => 'test-tenant',
            'product_id' => $product->id,
            'status'     => 'active',
            'starts_at'  => now()->subDay(),
            'ends_at'    => null,
        ]);

        $service = app(ProductAccessService::class);

        $this->assertTrue($service->tenantCanAccessModule('test-tenant', 'hrm'));
    }

    public function test_tenant_without_product_subscription_cannot_access_module(): void
    {
        Product::factory()->create(['module_code' => 'hrm']);

        $service = app(ProductAccessService::class);

        $this->assertFalse($service->tenantCanAccessModule('test-tenant', 'hrm'));
    }

    public function test_core_module_always_accessible(): void
    {
        $service = app(ProductAccessService::class);
        $this->assertTrue($service->tenantCanAccessModule('any-tenant', 'core'));
    }

    public function test_expired_subscription_denies_access(): void
    {
        $product = Product::factory()->create(['module_code' => 'crm']);

        ProductSubscription::factory()->create([
            'tenant_id'  => 'test-tenant',
            'product_id' => $product->id,
            'status'     => 'active',
            'starts_at'  => now()->subMonth(),
            'ends_at'    => now()->subDay(), // expired yesterday
        ]);

        $service = app(ProductAccessService::class);
        $this->assertFalse($service->tenantCanAccessModule('test-tenant', 'crm'));
    }

    public function test_module_with_no_product_registered_is_accessible(): void
    {
        // Module exists in module.php but no Product record → allow (graceful fallback)
        $service = app(ProductAccessService::class);
        $this->assertTrue($service->tenantCanAccessModule('test-tenant', 'some-unregistered-module'));
    }
}
```

- [ ] **Step B4.2: Run test — expect failure**

```bash
php artisan test packages/aero-platform/tests/Feature/ProductAccessTest.php
```

Expected: `ProductAccessTest::test_tenant_with_active_product_subscription_can_access_module` → PASS (service exists). Others may fail if factories missing — add `Product::factory()` and `ProductSubscription::factory()` before continuing if needed.

- [ ] **Step B4.3: Update CheckModuleAccess to call ProductAccessService in SaaS mode**

In `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php`, find the `handle()` method. After the tenant context check and before the existing RBAC check, add a product subscription check. Replace the section from `// Empty string check` to the end of the method with:

```php
// Normalise empty strings → null
$subModuleCode = ($subModuleCode === '' || $subModuleCode === null) ? null : $subModuleCode;
$componentCode = ($componentCode === '' || $componentCode === null) ? null : $componentCode;
$actionCode    = ($actionCode    === '' || $actionCode    === null) ? null : $actionCode;

// ── SaaS product subscription gate ──────────────────────────────────────
// In SaaS mode, check that the tenant has an active product subscription
// for the top-level module. This is orthogonal to plan — plan governs limits only.
if (! $isStandalone && class_exists(\Aero\Platform\Services\ProductAccessService::class)) {
    /** @var \Aero\Platform\Services\ProductAccessService $productAccess */
    $productAccess = app(\Aero\Platform\Services\ProductAccessService::class);

    if (! $productAccess->tenantCanAccessModule(tenant()->getTenantKey(), $moduleCode)) {
        return $this->denyAccess(
            $request,
            'no_product_subscription',
            "Your account does not have an active subscription for this module. Subscribe from Settings > Products.",
            402,
            ['module' => $moduleCode]
        );
    }
}
// ────────────────────────────────────────────────────────────────────────

// Determine the level of RBAC access to check
if ($actionCode !== null) {
    $accessCheck = $this->moduleAccessService->canPerformAction($user, $moduleCode, $subModuleCode, $componentCode, $actionCode);
} elseif ($componentCode !== null) {
    $accessCheck = $this->moduleAccessService->canAccessComponent($user, $moduleCode, $subModuleCode, $componentCode);
} elseif ($subModuleCode !== null) {
    $accessCheck = $this->moduleAccessService->canAccessSubModule($user, $moduleCode, $subModuleCode);
} else {
    $accessCheck = $this->moduleAccessService->canAccessModule($user, $moduleCode);
}

if (! $accessCheck['allowed']) {
    $statusCode = match ($accessCheck['reason']) {
        'plan_restriction'      => 402,
        'not_found'             => 404,
        'insufficient_permissions' => 403,
        default                 => 403,
    };

    return $this->denyAccess($request, $accessCheck['reason'], $accessCheck['message'] ?? $accessCheck['reason'], $statusCode, [
        'module'    => $moduleCode,
        'submodule' => $subModuleCode,
        'component' => $componentCode,
        'action'    => $actionCode,
    ]);
}

return $next($request);
```

- [ ] **Step B4.4: Run tests — all should pass**

```bash
php artisan test packages/aero-platform/tests/Feature/ProductAccessTest.php
```

Expected: all 5 tests PASS.

- [ ] **Step B4.5: Commit**

```bash
git add packages/aero-core/src/Http/Middleware/CheckModuleAccess.php \
        packages/aero-platform/tests/Feature/ProductAccessTest.php
git commit -m "feat(aero-core): CheckModuleAccess enforces product subscriptions in SaaS mode (402 when no subscription)"
```

---

## Task B5: Product Seeder (Core Products)

**Files:**
- Create: `packages/aero-platform/database/seeders/ProductSeeder.php`

Seeds the products that correspond to the packages in the monorepo. Run during SaaS platform installation.

- [ ] **Step B5.1: Write the seeder**

```php
<?php
// packages/aero-platform/database/seeders/ProductSeeder.php

namespace Aero\Platform\Database\Seeders;

use Aero\Platform\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    private array $products = [
        [
            'code'        => 'hrm',
            'module_code' => 'hrm',
            'name'        => 'HRM Suite',
            'description' => 'Complete human resource management — employees, attendance, leave, payroll, performance.',
            'icon'        => 'UserGroupIcon',
            'monthly_price' => 29.00,
            'yearly_price'  => 290.00,
            'sort_order'  => 1,
        ],
        [
            'code'        => 'crm',
            'module_code' => 'crm',
            'name'        => 'CRM Suite',
            'description' => 'Customer relationship management — leads, contacts, pipeline, deals.',
            'icon'        => 'BuildingOfficeIcon',
            'monthly_price' => 39.00,
            'yearly_price'  => 390.00,
            'sort_order'  => 2,
        ],
        [
            'code'        => 'project',
            'module_code' => 'project',
            'name'        => 'Project Management',
            'description' => 'Projects, tasks, milestones, Gantt charts, time tracking.',
            'icon'        => 'RectangleStackIcon',
            'monthly_price' => 24.00,
            'yearly_price'  => 240.00,
            'sort_order'  => 3,
        ],
        // Add remaining products here following the same pattern
    ];

    public function run(): void
    {
        foreach ($this->products as $data) {
            Product::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['id' => Str::uuid()])
            );
        }

        $this->command->info('Products seeded: ' . count($this->products));
    }
}
```

- [ ] **Step B5.2: Run seeder**

```bash
php artisan db:seed --class="Aero\Platform\Database\Seeders\ProductSeeder"
```

Expected: `Products seeded: 3` (or however many you define).

- [ ] **Step B5.3: Commit**

```bash
git add packages/aero-platform/database/seeders/ProductSeeder.php
git commit -m "feat(aero-platform): add ProductSeeder with core product catalog"
```

---

## Task B6: ProductSubscriptionService (subscribe, cancel, renew)

**Files:**
- Create: `packages/aero-platform/src/Services/ProductSubscriptionService.php`

Handles the lifecycle of a product subscription: create, cancel, flush access cache.

- [ ] **Step B6.1: Write the service**

```php
<?php
// packages/aero-platform/src/Services/ProductSubscriptionService.php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\ProductSubscription;
use Illuminate\Support\Str;

class ProductSubscriptionService
{
    public function __construct(
        private readonly ProductAccessService $accessService
    ) {}

    /**
     * Create a new product subscription for a tenant.
     * Called after a successful payment.
     */
    public function subscribe(
        string $tenantId,
        string $productCode,
        string $billingCycle,
        ?string $externalSubscriptionId = null,
        ?int $trialDays = null
    ): ProductSubscription {
        $product = Product::active()->where('code', $productCode)->firstOrFail();

        $amount = $billingCycle === 'yearly' ? $product->yearly_price : $product->monthly_price;

        $startsAt = now();
        $endsAt   = null;
        $trialEndsAt = $trialDays ? now()->addDays($trialDays) : null;
        $status   = $trialDays ? 'trialing' : 'active';

        $subscription = ProductSubscription::create([
            'id'                       => Str::uuid(),
            'tenant_id'                => $tenantId,
            'product_id'               => $product->id,
            'billing_cycle'            => $billingCycle,
            'amount'                   => $amount,
            'currency'                 => $product->currency,
            'status'                   => $status,
            'starts_at'                => $startsAt,
            'ends_at'                  => $endsAt,
            'trial_starts_at'          => $trialDays ? $startsAt : null,
            'trial_ends_at'            => $trialEndsAt,
            'external_subscription_id' => $externalSubscriptionId,
        ]);

        $this->accessService->flushCache($tenantId);

        return $subscription;
    }

    /**
     * Cancel a product subscription at period end.
     */
    public function cancel(string $subscriptionId, string $reason = ''): ProductSubscription
    {
        $subscription = ProductSubscription::findOrFail($subscriptionId);
        $subscription->update([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => $reason,
            // Access continues until ends_at — do not set ends_at here
            // Billing system sets ends_at when the period actually ends
        ]);

        $this->accessService->flushCache($subscription->tenant_id);

        return $subscription->fresh();
    }

    /**
     * Mark a subscription as expired (called by renewal cron when payment fails).
     */
    public function expire(string $subscriptionId): ProductSubscription
    {
        $subscription = ProductSubscription::findOrFail($subscriptionId);
        $subscription->update([
            'status'  => 'expired',
            'ends_at' => now(),
        ]);

        $this->accessService->flushCache($subscription->tenant_id);

        return $subscription->fresh();
    }
}
```

- [ ] **Step B6.2: Register in AeroPlatformServiceProvider**

```php
$this->app->singleton(\Aero\Platform\Services\ProductSubscriptionService::class);
```

- [ ] **Step B6.3: Commit**

```bash
git add packages/aero-platform/src/Services/ProductSubscriptionService.php \
        packages/aero-platform/src/AeroPlatformServiceProvider.php
git commit -m "feat(aero-platform): add ProductSubscriptionService — subscribe, cancel, expire lifecycle"
```
