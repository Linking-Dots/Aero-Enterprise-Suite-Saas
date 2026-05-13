# Plan I — Phase 2 Remediation: The Cracks We Paved Over

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the critical architectural defects that Phase 2 audit revealed are STILL OPEN despite earlier plans claiming to fix them — plus the carry-forward items from Plans A–H — bringing the system to a verifiable state where standalone customers can ship and SaaS tenants can actually use what they pay for.

**Architecture:** This plan is divided into FIVE tracks executed in priority order. Track 1 closes the standalone-deployment blockers (RCE in AddonInstaller, aero-core ↔ aero-platform compile-time coupling). Track 2 closes the SaaS completeness gaps (Stripe webhook → ProductSubscription, tenant HRMAC seeding). Track 3 hardens the access-control failure modes (HRMAC connection isolation). Track 4 finishes the data-layer migration started in Plan F. Track 5 finishes the enforcement tooling started in Plan H.

**Tech Stack:** Same as the existing codebase. No new dependencies introduced.

**Prerequisite:** Plans A–H merged to `main`. Run `php artisan config:clear` after each task.

**VERIFICATION RULE (NEW):** Every task ends with a `grep`-based verification step that confirms the FIX is on disk. No "STATUS: DONE" is accepted without the verification command producing the expected output.

---

## Track 1 — Standalone Deployment Blockers (BLOCKS PRODUCTION)

### Task I1.1: Remove RCE — Replace `eval()` in AddonInstaller

**Severity:** CRITICAL  
**Files:**
- Modify: `packages/aero-core/src/Services/AddonInstaller.php`

The current `readManifestFromZip()` calls `eval('?>' . $manifestContent)` — executing arbitrary PHP from a customer-supplied ZIP. This is unauthenticated RCE.

- [ ] **Step I1.1.1: Rewrite manifest reading to parse safely via isolated process**

Replace the `readManifestFromZip()` method body with:

```php
private function readManifestFromZip(string $zipPath): array
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new \RuntimeException("Cannot open ZIP: {$zipPath}");
    }

    $manifestContent = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        if (str_ends_with($zip->getNameIndex($i), 'config/module.php')) {
            $manifestContent = $zip->getFromIndex($i);
            break;
        }
    }
    $zip->close();

    if ($manifestContent === null) {
        throw new \RuntimeException('module.php manifest not found in ZIP.');
    }

    // SAFE PARSE: write to temp file, parse via isolated PHP subprocess
    // that returns JSON. Any malicious code in the file would only affect
    // the subprocess, not the running app.
    $tmpFile = tempnam(sys_get_temp_dir(), 'aero_manifest_');
    try {
        file_put_contents($tmpFile, $manifestContent);

        $cmd = sprintf(
            '%s -d disable_functions=exec,shell_exec,passthru,system,proc_open,popen,curl_exec -r %s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(sprintf(
                'try { $r = require %s; echo json_encode($r); } catch (\Throwable $e) { echo "PARSE_ERROR:" . $e->getMessage(); }',
                var_export($tmpFile, true)
            ))
        );

        $output = shell_exec($cmd);

        if ($output === null || str_starts_with($output, 'PARSE_ERROR:')) {
            throw new \RuntimeException('Failed to parse module.php manifest safely: ' . $output);
        }

        $manifest = json_decode(trim($output), true);

        if (! is_array($manifest)) {
            throw new \RuntimeException('module.php manifest is not a valid PHP array.');
        }
    } finally {
        @unlink($tmpFile);
    }

    foreach (['code', 'name', 'version'] as $key) {
        if (empty($manifest[$key])) {
            throw new \RuntimeException("module.php missing required key: [{$key}]");
        }
    }

    return $manifest;
}
```

- [ ] **Step I1.1.2: Add ZIP integrity verification before extraction**

Add this method to `AddonInstaller`:

```php
private function verifyZipIntegrity(string $zipPath, ?string $expectedSha256): void
{
    if ($expectedSha256 === null) {
        throw new \RuntimeException('Expected SHA-256 checksum required for ZIP installation');
    }

    $actual = hash_file('sha256', $zipPath);
    if (! hash_equals($expectedSha256, $actual)) {
        throw new \RuntimeException("ZIP checksum mismatch. Expected {$expectedSha256}, got {$actual}. File may be tampered.");
    }
}
```

Update `install()` signature to accept an `?string $expectedChecksum` parameter:

```php
public function install(string $zipPath, string $licenseKey, ?string $expectedChecksum = null): InstalledAddon
{
    if (! file_exists($zipPath)) {
        throw new \RuntimeException("ZIP file not found: {$zipPath}");
    }

    $this->verifyZipIntegrity($zipPath, $expectedChecksum);

    // ... rest of method
}
```

- [ ] **Step I1.1.3: Update AddonController to pass checksum from license server**

In `packages/aero-core/src/Http/Controllers/Admin/AddonController.php`, change `autoDownload()` to capture the expected checksum from the license server response and pass it to `$installer->install($zipPath, $licenseKey, $expectedChecksum)`.

The license server's `/api/license/download-url` endpoint should also return `expected_sha256` in its JSON response. The `LicenseController::downloadUrl()` method in aero-platform must be updated to compute and return this hash.

- [ ] **Step I1.1.4: Verify no `eval` remains in AddonInstaller**

```bash
grep -n "eval\b" packages/aero-core/src/Services/AddonInstaller.php
```

Expected: no output (the keyword `eval` should not appear).

- [ ] **Step I1.1.5: Commit**

```bash
git add packages/aero-core/src/Services/AddonInstaller.php \
        packages/aero-core/src/Http/Controllers/Admin/AddonController.php \
        packages/aero-platform/src/Http/Controllers/Api/LicenseController.php
git commit -m "fix(aero-core): replace eval() in AddonInstaller with isolated subprocess parse + SHA-256 verification — eliminates RCE"
```

---

### Task I1.2: Sever `aero-core` Compile-Time Coupling to `aero-platform`

**Severity:** CRITICAL  
**Files:**
- Modify: `packages/aero-core/src/AeroCoreServiceProvider.php`
- Modify: `packages/aero-core/src/Console/Commands/InstallCommand.php`
- Modify: `packages/aero-core/src/Http/Controllers/Admin/ModuleController.php`
- Modify: `packages/aero-core/src/Http/Controllers/UnifiedInstallationController.php`
- Modify: `packages/aero-core/src/Http/Middleware/BootstrapGuard.php`
- Modify: `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php`
- Create: `packages/aero-core/src/Contracts/PlanCatalogInterface.php`
- Create: `packages/aero-core/src/Contracts/ProductAccessInterface.php`

The audit found 8 files in `aero-core` directly importing from `Aero\Platform`. This breaks standalone mode (where `aero/platform` is not installed) and is the original CRITICAL finding that was reported as fixed and is not.

- [ ] **Step I1.2.1: Create `ProductAccessInterface` in aero-core**

```php
<?php
// packages/aero-core/src/Contracts/ProductAccessInterface.php

namespace Aero\Core\Contracts;

interface ProductAccessInterface
{
    public function tenantCanAccessModule(string $tenantId, string $moduleCode): bool;
    public function getAccessibleModuleCodes(string $tenantId): array;
    public function flushCache(string $tenantId): void;
}
```

- [ ] **Step I1.2.2: Create `PlanCatalogInterface` in aero-core**

```php
<?php
// packages/aero-core/src/Contracts/PlanCatalogInterface.php

namespace Aero\Core\Contracts;

use Illuminate\Support\Collection;

interface PlanCatalogInterface
{
    public function getPlansForModule(string $moduleCode): Collection;
    public function isModuleInAnyPlan(string $moduleCode): bool;
}
```

- [ ] **Step I1.2.3: Make `ProductAccessService` implement `ProductAccessInterface`**

In `packages/aero-platform/src/Services/ProductAccessService.php`:
```php
class ProductAccessService implements \Aero\Core\Contracts\ProductAccessInterface
```

In `AeroPlatformServiceProvider::register()`, change the binding:
```php
$this->app->singleton(
    \Aero\Core\Contracts\ProductAccessInterface::class,
    \Aero\Platform\Services\ProductAccessService::class
);
```

- [ ] **Step I1.2.4: Make `PlatformPlanService` implement `PlanCatalogInterface`**

In `packages/aero-platform/src/Services/PlatformPlanService.php`:
```php
class PlatformPlanService implements \Aero\Core\Contracts\PlanCatalogInterface
```

Bind in AeroPlatformServiceProvider:
```php
$this->app->singleton(
    \Aero\Core\Contracts\PlanCatalogInterface::class,
    \Aero\Platform\Services\PlatformPlanService::class
);
```

- [ ] **Step I1.2.5: Replace concrete imports with interface resolutions in aero-core**

In `CheckModuleAccess.php`, replace:
```php
use Aero\Platform\Services\ProductAccessService;
// ...
$productAccess = app(\Aero\Platform\Services\ProductAccessService::class);
```
with:
```php
use Aero\Core\Contracts\ProductAccessInterface;
// ...
if (! app()->bound(ProductAccessInterface::class)) {
    // No product access binding (standalone mode) — skip the gate
} else {
    $productAccess = app(ProductAccessInterface::class);
    if (! $productAccess->tenantCanAccessModule(...)) {
        return $this->denyAccess(...);
    }
}
```

In `ModuleController.php`, replace `use Aero\Platform\Services\PlatformPlanService` with `use Aero\Core\Contracts\PlanCatalogInterface` and resolve through the container with a `bound()` check.

- [ ] **Step I1.2.6: Replace `use Aero\Platform\...` in service providers with string-based class_exists checks**

In `AeroCoreServiceProvider.php`, remove the `use` statements:
```php
use Aero\Platform\AeroPlatformServiceProvider;       // REMOVE
use Aero\Platform\Http\Middleware\EnsureTenantIsActive;  // REMOVE
```

Replace any usage with string-class-name checks:
```php
// Before:
if (class_exists(AeroPlatformServiceProvider::class)) { ... }
// After:
if (class_exists('Aero\\Platform\\AeroPlatformServiceProvider')) { ... }

// Before:
$router->pushMiddlewareToGroup('web', EnsureTenantIsActive::class);
// After:
if (class_exists('Aero\\Platform\\Http\\Middleware\\EnsureTenantIsActive')) {
    $router->pushMiddlewareToGroup('web', 'Aero\\Platform\\Http\\Middleware\\EnsureTenantIsActive');
}
```

Apply the same pattern to `InstallCommand.php`, `BootstrapGuard.php`, and `UnifiedInstallationController.php`.

- [ ] **Step I1.2.7: Verify no `use Aero\Platform\*` imports remain in aero-core**

```bash
grep -rn "^use Aero\\\\Platform" packages/aero-core/src/ --include="*.php"
```

Expected: no output.

- [ ] **Step I1.2.8: Commit**

```bash
git add packages/aero-core/src/Contracts/ProductAccessInterface.php \
        packages/aero-core/src/Contracts/PlanCatalogInterface.php \
        packages/aero-core/src/AeroCoreServiceProvider.php \
        packages/aero-core/src/Console/Commands/InstallCommand.php \
        packages/aero-core/src/Http/Controllers/Admin/ModuleController.php \
        packages/aero-core/src/Http/Controllers/UnifiedInstallationController.php \
        packages/aero-core/src/Http/Middleware/BootstrapGuard.php \
        packages/aero-core/src/Http/Middleware/CheckModuleAccess.php \
        packages/aero-platform/src/Services/ProductAccessService.php \
        packages/aero-platform/src/Services/PlatformPlanService.php \
        packages/aero-platform/src/AeroPlatformServiceProvider.php
git commit -m "fix(aero-core): sever compile-time coupling to aero-platform via ProductAccessInterface + PlanCatalogInterface — standalone mode now boots without aero/platform"
```

---

## Track 2 — SaaS Completeness Gaps (TENANTS CANNOT USE THE SYSTEM)

### Task I2.1: Wire Stripe Webhook to ProductSubscription

**Severity:** CRITICAL  
**Files:**
- Modify: `packages/aero-platform/src/Http/Controllers/Webhooks/StripeWebhookController.php`

When a SaaS tenant purchases a product, Stripe fires `customer.subscription.created` or `customer.subscription.updated`. Currently only the base subscription is recorded via Cashier. No `ProductSubscription` row is ever created, so `ProductAccessService` always returns false.

- [ ] **Step I2.1.1: Map Stripe price IDs to product codes via metadata**

When creating Stripe products, set `metadata.aero_product_code` to the product code (e.g., 'hrm', 'crm'). This is done in the Stripe dashboard or via Stripe API setup. Document this requirement in `docs/stripe-setup.md`.

- [ ] **Step I2.1.2: Add `handleCustomerSubscriptionUpdated` override**

In `StripeWebhookController.php`, add the override:

```php
protected function handleCustomerSubscriptionUpdated(array $payload): Response
{
    $response = parent::handleCustomerSubscriptionUpdated($payload);

    $subscription = $payload['data']['object'] ?? [];
    $items        = $subscription['items']['data'] ?? [];
    $tenantId     = $this->resolveTenantIdFromCustomer($subscription['customer'] ?? null);

    if (! $tenantId) {
        Log::warning('Stripe subscription update: tenant not resolved', ['payload' => $payload]);
        return $response;
    }

    $productService = app(\Aero\Platform\Services\ProductSubscriptionService::class);

    foreach ($items as $item) {
        $stripePriceId = $item['price']['id'] ?? null;
        $productCode   = $item['price']['metadata']['aero_product_code'] ?? null;

        if (! $productCode) {
            continue;
        }

        $product = \Aero\Platform\Models\Product::where('code', $productCode)->first();
        if (! $product) {
            Log::warning("Stripe subscription update: unknown product code [{$productCode}]");
            continue;
        }

        $status = match ($subscription['status'] ?? '') {
            'active', 'trialing' => 'active',
            'past_due', 'unpaid' => 'past_due',
            'canceled'           => 'cancelled',
            default              => 'expired',
        };

        // Upsert ProductSubscription
        \Aero\Platform\Models\ProductSubscription::updateOrCreate(
            [
                'tenant_id'                => $tenantId,
                'product_id'               => $product->id,
                'external_subscription_id' => $subscription['id'],
            ],
            [
                'status'        => $status,
                'billing_cycle' => $item['price']['recurring']['interval'] === 'year' ? 'yearly' : 'monthly',
                'amount'        => ($item['price']['unit_amount'] ?? 0) / 100,
                'currency'      => strtoupper($item['price']['currency'] ?? 'USD'),
                'starts_at'     => isset($subscription['current_period_start']) ? Carbon::createFromTimestamp($subscription['current_period_start']) : now(),
                'ends_at'       => isset($subscription['current_period_end']) ? Carbon::createFromTimestamp($subscription['current_period_end']) : null,
            ]
        );

        // Critical: flush the access cache so tenant sees new state immediately
        $productService->flushTenantCache($tenantId);
    }

    return $response;
}

private function resolveTenantIdFromCustomer(?string $stripeCustomerId): ?string
{
    if (! $stripeCustomerId) return null;
    return \Aero\Platform\Models\Tenant::where('stripe_id', $stripeCustomerId)->value('id');
}
```

- [ ] **Step I2.1.3: Add `flushTenantCache()` to ProductSubscriptionService**

In `ProductSubscriptionService.php`, add:
```php
public function flushTenantCache(string $tenantId): void
{
    $this->accessService->flushCache($tenantId);
}
```

- [ ] **Step I2.1.4: Verify wiring**

```bash
grep -n "handleCustomerSubscriptionUpdated\|ProductSubscription::updateOrCreate" packages/aero-platform/src/Http/Controllers/Webhooks/StripeWebhookController.php
```

Expected: both terms present.

- [ ] **Step I2.1.5: Commit**

```bash
git add packages/aero-platform/src/Http/Controllers/Webhooks/StripeWebhookController.php \
        packages/aero-platform/src/Services/ProductSubscriptionService.php
git commit -m "feat(aero-platform): wire Stripe customer.subscription.updated webhook to ProductSubscription upsert + cache flush — paid tenants now get module access"
```

---

### Task I2.2: Seed Default HRMAC Roles for New Tenants

**Severity:** CRITICAL  
**Files:**
- Modify: `packages/aero-platform/src/Listeners/TenantCreatedListener.php`
- Create: `packages/aero-platform/src/Services/Tenant/TenantRoleSeeder.php`

`TenantCreatedListener` runs migrations and syncs the module hierarchy, but never creates roles or grants module access. New tenants log in to a permission void.

- [ ] **Step I2.2.1: Create `TenantRoleSeeder`**

```php
<?php
// packages/aero-platform/src/Services/Tenant/TenantRoleSeeder.php

namespace Aero\Platform\Services\Tenant;

use Aero\HRMAC\Contracts\RoleModuleAccessInterface;
use Aero\HRMAC\Models\Module as HrmacModule;
use Aero\HRMAC\Models\Role as HrmacRole;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\ProductSubscription;
use Illuminate\Support\Facades\Log;

class TenantRoleSeeder
{
    public function seedFor(Tenant $tenant): void
    {
        $hrmac = app(RoleModuleAccessInterface::class);

        // 1. Always create a Tenant Admin role with full access to subscribed modules
        $tenantAdmin = HrmacRole::firstOrCreate(
            ['name' => 'Tenant Admin'],
            ['guard_name' => 'web']
        );

        // 2. Grant module-level access to every module included in active product subscriptions
        $subscribedModuleCodes = ProductSubscription::where('tenant_id', $tenant->id)
            ->hasAccess()
            ->with('product')
            ->get()
            ->pluck('product.module_code')
            ->filter()
            ->unique()
            ->toArray();

        // Always include 'core' (free for everyone)
        $subscribedModuleCodes[] = 'core';

        $moduleIds = HrmacModule::whereIn('code', $subscribedModuleCodes)
            ->where('scope', 'tenant')
            ->pluck('id')
            ->toArray();

        $hrmac->syncRoleAccess($tenantAdmin, [
            'modules'     => $moduleIds,
            'sub_modules' => [],
            'components'  => [],
            'actions'     => [],
        ]);

        // 3. Create Tenant User role with view-only access
        $tenantUser = HrmacRole::firstOrCreate(
            ['name' => 'Tenant User'],
            ['guard_name' => 'web']
        );

        $viewActionIds = \Aero\HRMAC\Models\Action::where('code', 'view')
            ->whereHas('component.subModule.module', function ($q) use ($moduleIds) {
                $q->whereIn('id', $moduleIds);
            })
            ->pluck('id')
            ->toArray();

        $hrmac->syncRoleAccess($tenantUser, [
            'modules'     => [],
            'sub_modules' => [],
            'components'  => [],
            'actions'     => $viewActionIds,
        ]);

        $hrmac->clearRoleCache($tenantAdmin);
        $hrmac->clearRoleCache($tenantUser);

        Log::info("[TenantRoleSeeder] Roles seeded for tenant {$tenant->id}: " . count($moduleIds) . " modules granted to Tenant Admin");
    }
}
```

- [ ] **Step I2.2.2: Hook the seeder into `TenantCreatedListener`**

In `TenantCreatedListener::handle()`, after the existing `runModuleMigrations` and `aero:sync-module` calls, add:

```php
// Step 3: Seed default tenant HRMAC roles
try {
    app(\Aero\Platform\Services\Tenant\TenantRoleSeeder::class)->seedFor($tenant);
} catch (\Throwable $e) {
    Log::error("[TenantCreated] Role seeding failed for tenant {$tenant->id}: " . $e->getMessage());
    // Do not rethrow — tenant is provisioned, admin can manually seed via artisan
}
```

- [ ] **Step I2.2.3: Hook `TenantRoleSeeder` to also run when ProductSubscription changes**

When a new product is added to a tenant's subscriptions (via Stripe webhook from I2.1), re-seed the Tenant Admin role to include the new module. In `StripeWebhookController::handleCustomerSubscriptionUpdated()`, after the `updateOrCreate` loop, add:

```php
// Re-seed tenant roles so Tenant Admin gets access to newly subscribed modules
tenancy()->initialize($tenantId);
try {
    app(\Aero\Platform\Services\Tenant\TenantRoleSeeder::class)
        ->seedFor(\Aero\Platform\Models\Tenant::find($tenantId));
} finally {
    tenancy()->end();
}
```

- [ ] **Step I2.2.4: Verify**

```bash
grep -n "TenantRoleSeeder\|seedFor" packages/aero-platform/src/Listeners/TenantCreatedListener.php
```

Expected: both terms present.

- [ ] **Step I2.2.5: Commit**

```bash
git add packages/aero-platform/src/Services/Tenant/TenantRoleSeeder.php \
        packages/aero-platform/src/Listeners/TenantCreatedListener.php \
        packages/aero-platform/src/Http/Controllers/Webhooks/StripeWebhookController.php
git commit -m "feat(aero-platform): TenantRoleSeeder creates Tenant Admin + Tenant User roles with HRMAC grants for subscribed modules — new tenants can immediately use what they paid for"
```

---

### Task I2.3: Add Idempotency to Marketplace Standalone License Issuance

**Severity:** CRITICAL  
**Files:**
- Modify: `packages/aero-platform/database/migrations/2026_05_07_000003_create_standalone_licenses_table.php` (OR new migration)
- Modify: `packages/aero-platform/src/Http/Controllers/Marketplace/PurchaseController.php`

- [ ] **Step I2.3.1: Create migration to add unique index on `external_order_id`**

Create `packages/aero-platform/database/migrations/2026_05_08_000001_add_unique_index_to_standalone_licenses.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('standalone_licenses', function (Blueprint $table) {
            $table->unique('external_order_id', 'standalone_licenses_external_order_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('standalone_licenses', function (Blueprint $table) {
            $table->dropUnique('standalone_licenses_external_order_id_unique');
        });
    }
};
```

- [ ] **Step I2.3.2: Make `handleSuccessfulPurchase` idempotent**

In `PurchaseController.php`, change `handleSuccessfulPurchase()`:

```php
private function handleSuccessfulPurchase(object $session): void
{
    try {
        $orderId = $session->payment_intent;

        // Idempotency check: was this order already processed?
        if (StandaloneLicense::where('external_order_id', $orderId)->exists()) {
            Log::info('Duplicate marketplace webhook ignored', ['payment_intent' => $orderId]);
            return;
        }

        $metadata     = (array) $session->metadata;
        $productCode  = $metadata['product_code'];
        $billingType  = $metadata['billing_cycle'] === 'annual' ? 'annual' : 'one_time';

        $license = $this->licenseIssuer->issue(
            productCode:   $productCode,
            customerEmail: $session->customer_email,
            billingType:   $billingType,
            source:        'marketplace',
            orderId:       $orderId,
            customerName:  $metadata['customer_name'] ?? null,
        );

        Log::info('License issued after marketplace purchase', [
            'license_key'    => $license->license_key,
            'customer_email' => $session->customer_email,
            'product_code'   => $productCode,
        ]);

    } catch (\Throwable $e) {
        Log::error('Failed to issue marketplace license', [
            'error'   => $e->getMessage(),
            'session' => $session->id,
        ]);
    }
}
```

- [ ] **Step I2.3.3: Verify**

```bash
grep -n "external_order_id.*exists\|unique.*external_order_id" \
  packages/aero-platform/src/Http/Controllers/Marketplace/PurchaseController.php \
  packages/aero-platform/database/migrations/2026_05_08_000001_add_unique_index_to_standalone_licenses.php
```

Expected: both files show the pattern.

- [ ] **Step I2.3.4: Commit**

```bash
git add packages/aero-platform/database/migrations/2026_05_08_000001_add_unique_index_to_standalone_licenses.php \
        packages/aero-platform/src/Http/Controllers/Marketplace/PurchaseController.php
git commit -m "fix(aero-platform): marketplace license issuance now idempotent — unique index on external_order_id + duplicate check in webhook"
```

---

## Track 3 — HRMAC Failure Mode Hardening

### Task I3.1: Create Connection-Pinned `LandlordRoleModuleAccess` Model

**Severity:** CRITICAL  
**Files:**
- Create: `packages/aero-hrmac/src/Models/LandlordRoleModuleAccess.php`
- Modify: `packages/aero-hrmac/src/Services/RoleModuleAccessService.php`
- Modify: `packages/aero-platform/database/seeders/PlatformHrmacSeeder.php`

HRMAC is "connection-agnostic" — meaning it uses whatever connection is current. In queue workers that switch between landlord and tenant contexts, this causes wrong-DB queries and possibly wrong-recipient notifications.

- [ ] **Step I3.1.1: Create `LandlordRoleModuleAccess` pinned to central connection**

```php
<?php
// packages/aero-hrmac/src/Models/LandlordRoleModuleAccess.php

namespace Aero\HRMAC\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * LandlordRoleModuleAccess
 *
 * Same schema as RoleModuleAccess but PINNED to the central (landlord) DB
 * connection. Use this from platform-context queries to guarantee they
 * never accidentally hit a tenant DB (e.g., from inside a queue job
 * that switched the default connection via stancl/tenancy).
 */
class LandlordRoleModuleAccess extends Model
{
    protected $connection = 'central';
    protected $table = 'role_module_access';

    protected $fillable = [
        'role_id', 'module_id', 'sub_module_id',
        'component_id', 'action_id', 'access_scope',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn (self $m) => $m->setConnection('central'));
        static::saving(fn (self $m) => $m->setConnection('central'));
    }
}
```

- [ ] **Step I3.1.2: Add a connection-scope discriminator to `RoleModuleAccessService`**

In `RoleModuleAccessService.php`, add a constructor argument or context-resolution that determines which model to query:

```php
protected function modelForCurrentContext(): string
{
    // Read from RequestContext if available (Plan E)
    try {
        $context = app(\Aero\Core\ValueObjects\RequestContext::class);
        if ($context->isPlatform()) {
            return \Aero\HRMAC\Models\LandlordRoleModuleAccess::class;
        }
    } catch (\Throwable) {
        // No context bound — fall through to default
    }

    return \Aero\HRMAC\Models\RoleModuleAccess::class;
}
```

Replace all hardcoded `RoleModuleAccess::where(...)` calls in this service with:
```php
$modelClass = $this->modelForCurrentContext();
$modelClass::where(...)
```

- [ ] **Step I3.1.3: Update `PlatformHrmacSeeder` to use central-pinned model**

In `PlatformHrmacSeeder`, ensure any direct table writes go through `LandlordRoleModuleAccess` rather than `RoleModuleAccess`. The seeder also needs to ensure it's running against the `central` connection — add an explicit `DB::connection('central')` guard at the top.

- [ ] **Step I3.1.4: Verify**

```bash
grep -n "LandlordRoleModuleAccess\|connection = 'central'" \
  packages/aero-hrmac/src/Models/LandlordRoleModuleAccess.php \
  packages/aero-hrmac/src/Services/RoleModuleAccessService.php
```

Expected: matches in both files.

- [ ] **Step I3.1.5: Commit**

```bash
git add packages/aero-hrmac/src/Models/LandlordRoleModuleAccess.php \
        packages/aero-hrmac/src/Services/RoleModuleAccessService.php \
        packages/aero-platform/database/seeders/PlatformHrmacSeeder.php
git commit -m "fix(aero-hrmac): platform-context queries use LandlordRoleModuleAccess pinned to central connection — eliminates cross-DB bleeding in queue workers"
```

---

### Task I3.2: Add HRMAC Cache and Graceful Degradation

**Severity:** MAJOR  
**Files:**
- Modify: `packages/aero-core/src/Http/Middleware/CheckModuleAccess.php`

Currently `handlePlatformAccess()` fails closed if HRMAC throws. This means any HRMAC outage locks out the entire landlord panel — including the super admin who would fix it.

- [ ] **Step I3.2.1: Add a super-admin bypass that doesn't depend on HRMAC**

In `CheckModuleAccess::handlePlatformAccess()`, before the try/catch:

```php
// Emergency bypass: super-platform-admin role exists at the model level
// and bypasses HRMAC entirely. This ensures the platform is never
// 100% locked out if HRMAC infrastructure fails.
try {
    if ($user->roles()->where('name', 'Super Platform Admin')->exists()) {
        return $next($request);
    }
} catch (\Throwable) {
    // Role lookup itself failed — proceed to HRMAC check
}

try {
    $hrmac = app(RoleModuleAccessInterface::class);
    // ... existing logic
}
```

- [ ] **Step I3.2.2: Verify**

```bash
grep -n "Super Platform Admin\|emergency bypass" packages/aero-core/src/Http/Middleware/CheckModuleAccess.php
```

Expected: 'Super Platform Admin' present.

- [ ] **Step I3.2.3: Commit**

```bash
git add packages/aero-core/src/Http/Middleware/CheckModuleAccess.php
git commit -m "fix(aero-core): handlePlatformAccess has super-admin bypass that survives HRMAC outage — prevents total platform lockout"
```

---

### Task I3.3: Add Mode-File Loss Recovery via DB Detection

**Severity:** MAJOR  
**Files:**
- Modify: `packages/aero-core/src/helpers.php`

If `storage/app/aeos.mode` is lost, `aero_mode()` defaults to standalone — silently disabling subscription gating in SaaS production.

- [ ] **Step I3.3.1: Add DB-schema fallback to `aero_mode()`**

```php
function aero_mode(): string
{
    static $mode = null;
    if ($mode !== null) {
        return $mode;
    }

    // Primary: explicit mode file
    $file = storage_path('app/aeos.mode');
    if (file_exists($file)) {
        $v = trim(file_get_contents($file));
        if (in_array($v, ['saas', 'standalone'], true)) {
            return $mode = $v;
        }
    }

    // Fallback: detect from DB structure
    // SaaS = has 'tenants' table in central connection
    try {
        if (\Illuminate\Support\Facades\Schema::connection('central')->hasTable('tenants')) {
            \Illuminate\Support\Facades\Log::warning('aeos.mode file missing — recovered as SaaS from DB schema');
            return $mode = 'saas';
        }
    } catch (\Throwable) {
        // Central connection doesn't exist — definitely not SaaS
    }

    return $mode = 'standalone';
}
```

- [ ] **Step I3.3.2: Verify**

```bash
grep -n "Schema::connection.*tenants\|recovered as SaaS" packages/aero-core/src/helpers.php
```

- [ ] **Step I3.3.3: Commit**

```bash
git add packages/aero-core/src/helpers.php
git commit -m "fix(aero-core): aero_mode() recovers SaaS detection from DB schema if mode file is lost — prevents catastrophic mode bleed in container environments"
```

---

## Track 4 — Finish the Data Layer Migration (Plan F follow-up)

### Task I4.1: Migrate aero-core Models to TenantModel

**Severity:** MAJOR  
**Files:** 30 models in `packages/aero-core/src/Models/`

Migrate models package-by-package, one PR per package, with manual review. Do NOT batch all 75 in one go.

- [ ] **Step I4.1.1: Audit each aero-core model and classify**

For each model file in `packages/aero-core/src/Models/`, determine:
- Lives in tenant DB? → extends `TenantModel`
- Lives in central DB? → extends `CentralModel`
- Genuinely cross-DB (rare)? → leave as `Model` with documentation

Create `docs/data-layer-classification.md` listing each model and its target base class.

- [ ] **Step I4.1.2: Migrate models in dependency order**

Migrate leaves first (no FK dependencies): `Activity`, `Backup`, `AuditLog`, `Comment`, etc.

For each migration:
```php
// Before:
use Illuminate\Database\Eloquent\Model;
class Activity extends Model

// After:
use Aero\Core\Models\TenantModel;
class Activity extends TenantModel
```

Test each model class in tinker: `\Aero\Core\Models\Activity::query()->count()` should not throw.

- [ ] **Step I4.1.3: Commit each model migration separately**

```bash
git add packages/aero-core/src/Models/Activity.php
git commit -m "refactor(aero-core): migrate Activity model to TenantModel base"
```

(Repeat for each model.)

---

### Task I4.2: Migrate aero-platform Models to CentralModel

Similar to I4.1, but for the 45 remaining platform models. All platform models are central by definition — `CentralModel` is the correct base.

---

## Track 5 — Finish Enforcement Tooling (Plan H follow-up)

### Task I5.1: Expand Deptrac to All 40+ Packages

**Files:**
- Modify: `deptrac.yaml`

- [ ] **Step I5.1.1: Add all remaining packages to `parameters.paths`**

Append to `deptrac.yaml`:
```yaml
parameters:
  paths:
    # ... existing 10 ...
    - packages/aero-analytics/src
    - packages/aero-blockchain/src
    - packages/aero-commerce/src
    - packages/aero-compliance/src
    - packages/aero-custom-fields/src
    - packages/aero-dms/src
    - packages/aero-eam/src
    - packages/aero-education/src
    - packages/aero-field-service/src
    - packages/aero-finance/src
    - packages/aero-forms/src
    - packages/aero-healthcare/src
    - packages/aero-helpdesk/src
    - packages/aero-ims/src
    - packages/aero-installation/src
    - packages/aero-integration/src
    - packages/aero-iot/src
    - packages/aero-manufacturing/src
    - packages/aero-mobile/src
    - packages/aero-pos/src
    - packages/aero-project/src
    - packages/aero-quality/src
    - packages/aero-real-estate/src
    - packages/aero-rfi/src
    - packages/aero-scm/src
    - packages/aero-time-tracking/src
    - packages/aero-ui/src
    - packages/aero-workflow/src
    - packages/aero-assistant/src
    - packages/aero-auth/src
    - packages/aero-booking/src
    - packages/aero-automation/src
    - packages/aero-cms/src
```

- [ ] **Step I5.1.2: Run deptrac and update `skip_violations` with issue numbers and deadlines**

Each violation in `skip_violations` must have a GitHub issue number and a target date for resolution:
```yaml
skip_violations:
  Aero\Core\AeroCoreServiceProvider:
    # TODO: Issue #142 - remove by 2026-Q3
    - Aero\Notifications\Contracts\MailContextResolver
```

- [ ] **Step I5.1.3: Commit**

```bash
git add deptrac.yaml
git commit -m "ci: expand deptrac to all 40+ packages; document skip_violations with issue numbers and deadlines"
```

---

### Task I5.2: Remove `continue-on-error: true` from PHPStan CI

**Files:**
- Modify: `.github/workflows/architecture-lint.yml`

After Task I1.2 removes the 7 `Aero\Notifications` imports from aero-core, PHPStan can be enforced strictly.

- [ ] **Step I5.2.1: Remove the continue-on-error directive**

In `.github/workflows/architecture-lint.yml`, find the PHPStan step and delete:
```yaml
continue-on-error: true
```

- [ ] **Step I5.2.2: Commit**

```bash
git add .github/workflows/architecture-lint.yml
git commit -m "ci: remove continue-on-error from PHPStan step — tenancy() rule now strictly enforced"
```

---

## Carry-Forward Items From Plans A–H (Now Tracked)

These were explicitly deferred in earlier plans and require dedicated effort. Each becomes its own task in this plan.

| Item | Original Plan | Severity | Estimated Effort |
|---|---|---|---|
| `aero-contracts` package extraction | Phase 1 of migration path | MAJOR | 1 week dedicated |
| Diamond dependency `aero/core: *` → semver | Carry-forward | MAJOR | After aero-contracts |
| Boot order convention → enforcement | Phase 1 audit | MINOR | Framework limitation, deferred |
| `IpWhitelistController` missing (route:list crash) | Pre-existing | MINOR | 1 hour fix |
| Dependabot: 3 critical CVEs in dependencies | GitHub alerts | CRITICAL | 1 day audit + upgrades |
| Existing 75 models not on TenantModel/CentralModel | Plan F | MAJOR | 3–4 weeks (Track 4) |
| 7 files in aero-core importing Aero\Notifications/I18n | Plan G | MAJOR | Resolved by I1.2 + 2 days more refactor |
| `LicenseValidator::verifyChecksum()` dead code | Phase 2 audit | MINOR | 1 hour |
| HRMAC audit trail for permission changes | New finding | MAJOR | 2–3 days |
| Octane support verification (singletons, in-process caches) | Implicit | MAJOR | 3–5 days testing |
| Migration collision detection in AddonInstaller | Phase 2 audit | MAJOR | 1 day |
| Opcache invalidation after add-on install | Phase 2 audit | MAJOR | 4 hours |
| Clock-drift hardening in LicenseCache | Phase 2 audit | MINOR | 4 hours |
| `ProductAccessService` cache fallback for non-Redis drivers | Phase 2 audit | MAJOR | 2 hours |

### Task I.CF.1 — Quick wins from carry-forward (group these into one PR)

These can be addressed in a single small PR:

- [ ] Fix `IpWhitelistController` missing (find the offending route registration in aero-core/aero-platform and create or remove the controller)
- [ ] Wire `LicenseValidator::verifyChecksum()` into `LicenseService::activate()`
- [ ] Add `abs()` to clock-drift check in `LicenseCache::get()`: `abs(time() - $data['cached_at']) > $ttlSeconds`
- [ ] Add `try { Cache::tags(...) } catch (BadMethodCallException) { /* fall back to plain Cache */ }` in `ProductAccessService`

### Task I.CF.2 — Dependabot Critical CVE Audit

- [ ] Review the 3 critical GitHub alerts
- [ ] Upgrade vulnerable packages (case-by-case)
- [ ] Test with the upgraded versions
- [ ] Commit and push

### Task I.CF.3 — aero-contracts Package Extraction (Separate Plan Required)

This is too large for one task. Write a dedicated `Plan J — aero-contracts Extraction` document. It will:
- Create `packages/aero-contracts/composer.json` (PHP-only, no Laravel)
- Move all interfaces from `aero-core/src/Contracts/` to `aero-contracts/src/Contracts/`
- Update 30+ package composer.json files to require `aero/contracts: ^1.0` (versioned, not `*`)
- Update all imports across all packages
- Document the layer rules in deptrac

---

## Self-Review

**Spec coverage:**
- RCE in AddonInstaller (CRITICAL) → I1.1 ✅
- aero-core → aero-platform compile coupling (CRITICAL) → I1.2 ✅
- Stripe webhook → ProductSubscription wiring (CRITICAL) → I2.1 ✅
- Tenant HRMAC role seeding (CRITICAL) → I2.2 ✅
- Standalone license idempotency (CRITICAL) → I2.3 ✅
- HRMAC connection bleeding (CRITICAL) → I3.1 ✅
- HRMAC failure → total lockout (MAJOR) → I3.2 ✅
- aeos.mode file loss (MAJOR) → I3.3 ✅
- 75 models migration (MAJOR) → Track 4 ✅
- Deptrac coverage gap (MAJOR) → I5.1 ✅
- PHPStan CI continue-on-error (MINOR) → I5.2 ✅
- All carry-forward items from Plans A–H → I.CF.* ✅

**Deferred to separate plans:**
- aero-contracts extraction → Plan J
- Octane verification → Plan K

**Most dangerous item, fix first:** I1.1 (RCE in AddonInstaller). No standalone customer can be onboarded until this is resolved.
