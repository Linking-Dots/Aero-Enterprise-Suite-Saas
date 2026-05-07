# Plan C — SaaS Marketplace, License Issuance & License Validation API

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `domain.com/marketplace` public page where standalone products are sold, implement license key generation on purchase, build the license validation API that standalone installations call home to, and serve signed ZIP download URLs.

**Architecture:** All marketplace and license endpoints live on the **central domain** (no tenant routing). The marketplace page is a public Inertia page. Purchase flow: browse → checkout (Stripe) → webhook → generate license key → store `standalone_licenses` record → send email with key + download link. License validation: standalone app POSTs to `domain.com/api/license/validate` with key + domain hash; server returns `{status: valid|expired|invalid}`. Download: license server generates a time-limited signed URL to S3/local storage.

**Tech Stack:** Laravel 11, `aero-platform`, Stripe Cashier (already in platform), Inertia.js, existing `plans` and `products` models from Plan B.

**Prerequisites:** Plan A (bug fixes) and Plan B (products/subscriptions) must be complete.

---

## File Map

### New Files
- `packages/aero-platform/database/migrations/XXXX_create_standalone_licenses_table.php`
- `packages/aero-platform/src/Models/StandaloneLicense.php`
- `packages/aero-platform/src/Services/LicenseIssuer.php`
- `packages/aero-platform/src/Services/DownloadService.php`
- `packages/aero-platform/src/Http/Controllers/Marketplace/CatalogController.php`
- `packages/aero-platform/src/Http/Controllers/Marketplace/PurchaseController.php`
- `packages/aero-platform/src/Http/Controllers/Api/LicenseController.php`
- `packages/aero-platform/routes/marketplace.php`
- `packages/aero-platform/routes/license-api.php`
- `packages/aero-platform/tests/Feature/LicenseIssuerTest.php`
- `packages/aero-platform/tests/Feature/LicenseValidationApiTest.php`

### Modified Files
- `packages/aero-platform/src/AeroPlatformServiceProvider.php` — register new services and routes

---

## Task C1: Standalone Licenses Table & Model

**Files:**
- Create: `packages/aero-platform/database/migrations/2026_05_07_000003_create_standalone_licenses_table.php`
- Create: `packages/aero-platform/src/Models/StandaloneLicense.php`

- [ ] **Step C1.1: Write the migration**

```php
<?php
// packages/aero-platform/database/migrations/2026_05_07_000003_create_standalone_licenses_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standalone_licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained()->onDelete('restrict');
            $table->string('license_key', 64)->unique(); // XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX
            $table->string('customer_email');
            $table->string('customer_name')->nullable();
            $table->string('status')->default('active'); // active, expired, revoked, suspended
            $table->string('bound_domain_hash', 64)->nullable(); // SHA-256 of bound domain, set on first activation
            $table->integer('activation_count')->default(0);
            $table->integer('max_activations')->default(1); // how many domains can activate this key
            $table->string('purchase_source')->nullable(); // 'marketplace', 'codecanyon', 'direct'
            $table->string('external_order_id')->nullable(); // Stripe payment intent or marketplace order
            $table->string('billing_type')->default('one_time'); // 'one_time' | 'annual'
            $table->timestamp('expires_at')->nullable(); // null = perpetual (one-time purchase)
            $table->timestamp('last_validated_at')->nullable();
            $table->string('current_version')->nullable(); // last version they downloaded
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_email');
            $table->index('status');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standalone_licenses');
    }
};
```

- [ ] **Step C1.2: Write the StandaloneLicense model**

```php
<?php
// packages/aero-platform/src/Models/StandaloneLicense.php

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StandaloneLicense extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'product_id', 'license_key', 'customer_email', 'customer_name',
        'status', 'bound_domain_hash', 'activation_count', 'max_activations',
        'purchase_source', 'external_order_id', 'billing_type',
        'expires_at', 'last_validated_at', 'current_version', 'metadata',
    ];

    protected $casts = [
        'expires_at'         => 'datetime',
        'last_validated_at'  => 'datetime',
        'activation_count'   => 'integer',
        'max_activations'    => 'integer',
        'metadata'           => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isDomainBound(): bool
    {
        return $this->bound_domain_hash !== null;
    }

    public function domainMatches(string $domainHash): bool
    {
        if (! $this->isDomainBound()) {
            return true; // Not yet bound — any domain can activate
        }
        return hash_equals($this->bound_domain_hash, $domainHash);
    }

    public function canActivateOnNewDomain(): bool
    {
        return $this->activation_count < $this->max_activations;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
```

- [ ] **Step C1.3: Run migration**

```bash
php artisan migrate --path=packages/aero-platform/database/migrations/2026_05_07_000003_create_standalone_licenses_table.php
```

- [ ] **Step C1.4: Commit**

```bash
git add packages/aero-platform/database/migrations/2026_05_07_000003_create_standalone_licenses_table.php \
        packages/aero-platform/src/Models/StandaloneLicense.php
git commit -m "feat(aero-platform): add standalone_licenses table and model"
```

---

## Task C2: LicenseIssuer Service

**Files:**
- Create: `packages/aero-platform/src/Services/LicenseIssuer.php`

Generates cryptographically sound license keys and creates `StandaloneLicense` records.

- [ ] **Step C2.1: Write failing tests**

Create `packages/aero-platform/tests/Feature/LicenseIssuerTest.php`:

```php
<?php
// packages/aero-platform/tests/Feature/LicenseIssuerTest.php

namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\StandaloneLicense;
use Aero\Platform\Services\LicenseIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseIssuerTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_valid_format_license_key(): void
    {
        $issuer = app(LicenseIssuer::class);
        $key    = $issuer->generateKey('hrm');

        $this->assertMatchesRegularExpression(
            '/^[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}-[A-Z0-9]{8}$/',
            $key
        );
    }

    public function test_issue_creates_standalone_license_record(): void
    {
        $product = Product::factory()->create(['code' => 'hrm', 'module_code' => 'hrm']);

        $issuer  = app(LicenseIssuer::class);
        $license = $issuer->issue(
            productCode:   'hrm',
            customerEmail: 'buyer@example.com',
            billingType:   'one_time',
            source:        'marketplace',
            orderId:       'pi_test_123',
        );

        $this->assertInstanceOf(StandaloneLicense::class, $license);
        $this->assertEquals('active', $license->status);
        $this->assertEquals('buyer@example.com', $license->customer_email);
        $this->assertNull($license->expires_at);
        $this->assertNull($license->bound_domain_hash);
    }

    public function test_annual_license_expires_in_one_year(): void
    {
        Product::factory()->create(['code' => 'crm', 'module_code' => 'crm']);
        $issuer  = app(LicenseIssuer::class);
        $license = $issuer->issue('crm', 'buyer@example.com', 'annual', 'marketplace');

        $this->assertNotNull($license->expires_at);
        $this->assertTrue($license->expires_at->isNextYear());
    }
}
```

- [ ] **Step C2.2: Run tests — expect failure**

```bash
php artisan test packages/aero-platform/tests/Feature/LicenseIssuerTest.php
```

Expected: `LicenseIssuer class not found`

- [ ] **Step C2.3: Write LicenseIssuer**

```php
<?php
// packages/aero-platform/src/Services/LicenseIssuer.php

namespace Aero\Platform\Services;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\StandaloneLicense;
use Illuminate\Support\Str;

class LicenseIssuer
{
    /**
     * Generate a unique license key for the given product code.
     * Format: XXXXXXXX-XXXXXXXX-XXXXXXXX-XXXXXXXX
     *
     * The first two characters of the last segment are a checksum of the
     * first three segments + a salt, for offline format verification.
     */
    public function generateKey(string $productCode): string
    {
        do {
            $seg1 = strtoupper(Str::random(8));
            $seg2 = strtoupper(Str::random(8));
            $seg3 = strtoupper(Str::random(8));

            // Build checksum: first 2 chars of md5(seg1+seg2+seg3+salt)
            $salt     = config('license.checksum_salt', 'aero-license-salt');
            $checksum = strtoupper(substr(md5($seg1 . $seg2 . $seg3 . $salt), 0, 2));

            // Last segment: checksum prefix + 6 random chars
            $seg4 = $checksum . strtoupper(Str::random(6));

            $key = "{$seg1}-{$seg2}-{$seg3}-{$seg4}";

        } while (StandaloneLicense::where('license_key', $key)->exists());

        return $key;
    }

    /**
     * Issue a new license to a customer after purchase.
     */
    public function issue(
        string  $productCode,
        string  $customerEmail,
        string  $billingType,   // 'one_time' | 'annual'
        string  $source,        // 'marketplace' | 'codecanyon' | 'direct'
        ?string $orderId        = null,
        ?string $customerName   = null,
        int     $maxActivations = 1,
    ): StandaloneLicense {
        $product = Product::active()->where('code', $productCode)->firstOrFail();

        $key      = $this->generateKey($productCode);
        $expiresAt = $billingType === 'annual' ? now()->addYear() : null;

        return StandaloneLicense::create([
            'id'               => Str::uuid(),
            'product_id'       => $product->id,
            'license_key'      => $key,
            'customer_email'   => $customerEmail,
            'customer_name'    => $customerName,
            'status'           => 'active',
            'billing_type'     => $billingType,
            'purchase_source'  => $source,
            'external_order_id'=> $orderId,
            'max_activations'  => $maxActivations,
            'activation_count' => 0,
            'expires_at'       => $expiresAt,
        ]);
    }
}
```

- [ ] **Step C2.4: Run tests — all should pass**

```bash
php artisan test packages/aero-platform/tests/Feature/LicenseIssuerTest.php
```

Expected: 3 tests PASS.

- [ ] **Step C2.5: Commit**

```bash
git add packages/aero-platform/src/Services/LicenseIssuer.php \
        packages/aero-platform/tests/Feature/LicenseIssuerTest.php
git commit -m "feat(aero-platform): LicenseIssuer — generate checksum-embedded keys, issue standalone licenses"
```

---

## Task C3: License Validation API

**Files:**
- Create: `packages/aero-platform/src/Http/Controllers/Api/LicenseController.php`
- Create: `packages/aero-platform/routes/license-api.php`

This API is called by standalone installations on boot (daily) and during add-on activation. It lives on the central domain, no tenant context.

- [ ] **Step C3.1: Write failing tests**

Create `packages/aero-platform/tests/Feature/LicenseValidationApiTest.php`:

```php
<?php
// packages/aero-platform/tests/Feature/LicenseValidationApiTest.php

namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Product;
use Aero\Platform\Models\StandaloneLicense;
use Aero\Platform\Services\LicenseIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LicenseValidationApiTest extends TestCase
{
    use RefreshDatabase;

    private StandaloneLicense $license;

    protected function setUp(): void
    {
        parent::setUp();
        $product = Product::factory()->create(['code' => 'hrm', 'module_code' => 'hrm']);
        $issuer  = app(LicenseIssuer::class);
        $this->license = $issuer->issue('hrm', 'buyer@example.com', 'one_time', 'marketplace');
    }

    public function test_activation_binds_domain_on_first_use(): void
    {
        $response = $this->postJson('/api/license/activate', [
            'license_key' => $this->license->license_key,
            'product_id'  => 'hrm',
            'domain'      => 'client.com',
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'valid']);
        $this->assertNotNull($this->license->fresh()->bound_domain_hash);
    }

    public function test_validation_accepts_correct_domain(): void
    {
        $domainHash = hash('sha256', 'client.com');
        $this->license->update(['bound_domain_hash' => $domainHash, 'activation_count' => 1]);

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $this->license->license_key,
            'product_id'  => 'hrm',
            'domain_hash' => $domainHash,
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'valid']);
    }

    public function test_validation_rejects_wrong_domain(): void
    {
        $this->license->update([
            'bound_domain_hash' => hash('sha256', 'client.com'),
            'activation_count'  => 1,
        ]);

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $this->license->license_key,
            'product_id'  => 'hrm',
            'domain_hash' => hash('sha256', 'pirate.com'),
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'invalid']);
    }

    public function test_validation_returns_expired_for_expired_license(): void
    {
        $this->license->update([
            'expires_at'        => now()->subDay(),
            'bound_domain_hash' => hash('sha256', 'client.com'),
            'activation_count'  => 1,
        ]);

        $response = $this->postJson('/api/license/validate', [
            'license_key' => $this->license->license_key,
            'product_id'  => 'hrm',
            'domain_hash' => hash('sha256', 'client.com'),
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'expired']);
    }

    public function test_unknown_license_key_returns_invalid(): void
    {
        $response = $this->postJson('/api/license/validate', [
            'license_key' => 'AAAAAAAA-BBBBBBBB-CCCCCCCC-DDDDDDDD',
            'product_id'  => 'hrm',
            'domain_hash' => hash('sha256', 'any.com'),
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'invalid']);
    }
}
```

- [ ] **Step C3.2: Run tests — expect failure**

```bash
php artisan test packages/aero-platform/tests/Feature/LicenseValidationApiTest.php
```

Expected: route 404 (controller doesn't exist yet).

- [ ] **Step C3.3: Write the LicenseController**

```php
<?php
// packages/aero-platform/src/Http/Controllers/Api/LicenseController.php

namespace Aero\Platform\Http\Controllers\Api;

use Aero\Platform\Models\StandaloneLicense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LicenseController extends Controller
{
    /**
     * POST /api/license/activate
     *
     * Called by standalone installer during installation.
     * Binds the domain on first activation.
     */
    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => ['required', 'string'],
            'product_id'  => ['required', 'string'],
            'domain'      => ['required', 'string'],
        ]);

        $license = StandaloneLicense::with('product')
            ->where('license_key', $request->license_key)
            ->first();

        if (! $license || ! $license->isActive()) {
            return response()->json(['status' => 'invalid', 'message' => 'License not found or inactive.']);
        }

        if ($license->product->code !== $request->product_id) {
            return response()->json(['status' => 'invalid', 'message' => 'License does not belong to this product.']);
        }

        $domainHash = hash('sha256', strtolower($request->domain));

        // Already bound to a different domain
        if ($license->isDomainBound() && ! $license->domainMatches($domainHash)) {
            if (! $license->canActivateOnNewDomain()) {
                return response()->json([
                    'status'  => 'invalid',
                    'message' => 'License is already activated on another domain. Contact support to transfer.',
                ]);
            }
        }

        // Bind domain on first activation
        if (! $license->isDomainBound()) {
            $license->update([
                'bound_domain_hash' => $domainHash,
                'activation_count'  => 1,
                'last_validated_at' => now(),
            ]);
        } else {
            $license->update(['last_validated_at' => now()]);
        }

        return response()->json([
            'status'      => 'valid',
            'product_id'  => $license->product->code,
            'expires_at'  => $license->expires_at?->toIso8601String(),
        ]);
    }

    /**
     * POST /api/license/validate
     *
     * Called by standalone app daily (cached, no user interaction).
     * Verifies the license is still active and the domain matches.
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => ['required', 'string'],
            'product_id'  => ['required', 'string'],
            'domain_hash' => ['required', 'string'],
        ]);

        $license = StandaloneLicense::with('product')
            ->where('license_key', $request->license_key)
            ->first();

        if (! $license) {
            return response()->json(['status' => 'invalid']);
        }

        if ($license->product->code !== $request->product_id) {
            return response()->json(['status' => 'invalid']);
        }

        // Domain mismatch
        if (! $license->domainMatches($request->domain_hash)) {
            return response()->json(['status' => 'invalid', 'message' => 'Domain mismatch.']);
        }

        // Expired
        if ($license->expires_at !== null && $license->expires_at->isPast()) {
            return response()->json([
                'status'     => 'expired',
                'expired_at' => $license->expires_at->toIso8601String(),
                'message'    => 'License expired. Renew at aerosuite.com/renew',
            ]);
        }

        // Revoked or suspended
        if (! in_array($license->status, ['active'], true)) {
            return response()->json(['status' => 'invalid', 'message' => "License status: {$license->status}"]);
        }

        $license->update(['last_validated_at' => now()]);

        return response()->json([
            'status'     => 'valid',
            'product_id' => $license->product->code,
            'expires_at' => $license->expires_at?->toIso8601String(),
        ]);
    }

    /**
     * GET /api/marketplace/catalog
     *
     * Returns the public product catalog for standalone admin panel add-on pages.
     * Cached — safe to call on every page load.
     */
    public function catalog(): JsonResponse
    {
        $products = \Aero\Platform\Models\Product::marketplaceVisible()
            ->orderBy('sort_order')
            ->get(['id', 'code', 'module_code', 'name', 'description', 'icon',
                   'monthly_price', 'yearly_price', 'currency', 'version', 'metadata']);

        return response()->json([
            'products'       => $products,
            'marketplace_url' => config('app.url') . '/marketplace',
            'cached_at'      => now()->toIso8601String(),
        ]);
    }
}
```

- [ ] **Step C3.4: Register routes in license-api.php**

Create `packages/aero-platform/routes/license-api.php`:

```php
<?php
// packages/aero-platform/routes/license-api.php
// These routes are on the CENTRAL domain — no tenant routing, no auth required.

use Aero\Platform\Http\Controllers\Api\LicenseController;
use Illuminate\Support\Facades\Route;

// Rate-limited license API — called by standalone installations
Route::middleware(['throttle:60,1'])
    ->prefix('api/license')
    ->group(function () {
        Route::post('activate', [LicenseController::class, 'activate'])->name('api.license.activate');
        Route::post('validate', [LicenseController::class, 'validate'])->name('api.license.validate');
    });

// Marketplace catalog API — called by standalone admin panels
Route::middleware(['throttle:30,1'])
    ->get('api/marketplace/catalog', [LicenseController::class, 'catalog'])
    ->name('api.marketplace.catalog');
```

- [ ] **Step C3.5: Load these routes in AeroPlatformServiceProvider**

In `packages/aero-platform/src/AeroPlatformServiceProvider.php`, in `boot()`, add alongside existing route loading:

```php
// License API routes — central domain, no auth, no CSRF
Route::middleware(['api'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->group(__DIR__ . '/../routes/license-api.php');
```

- [ ] **Step C3.6: Run tests — all should pass**

```bash
php artisan test packages/aero-platform/tests/Feature/LicenseValidationApiTest.php
```

Expected: 5 tests PASS.

- [ ] **Step C3.7: Commit**

```bash
git add packages/aero-platform/src/Http/Controllers/Api/LicenseController.php \
        packages/aero-platform/routes/license-api.php \
        packages/aero-platform/src/AeroPlatformServiceProvider.php \
        packages/aero-platform/tests/Feature/LicenseValidationApiTest.php
git commit -m "feat(aero-platform): license validation API — activate, validate, domain binding, catalog endpoint"
```

---

## Task C4: Public Marketplace Page & Purchase Flow

**Files:**
- Create: `packages/aero-platform/src/Http/Controllers/Marketplace/CatalogController.php`
- Create: `packages/aero-platform/src/Http/Controllers/Marketplace/PurchaseController.php`
- Create: `packages/aero-platform/routes/marketplace.php`

The marketplace page at `domain.com/marketplace` is a public Inertia page. It lists products, has a purchase flow (Stripe Checkout), and on successful webhook → issues a license → emails customer.

- [ ] **Step C4.1: Write the CatalogController**

```php
<?php
// packages/aero-platform/src/Http/Controllers/Marketplace/CatalogController.php

namespace Aero\Platform\Http\Controllers\Marketplace;

use Aero\Platform\Models\Product;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(): Response
    {
        $products = Product::marketplaceVisible()
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Marketplace/Catalog', [
            'products' => $products->map(fn ($p) => [
                'id'             => $p->id,
                'code'           => $p->code,
                'name'           => $p->name,
                'description'    => $p->description,
                'icon'           => $p->icon,
                'monthly_price'  => $p->monthly_price,
                'yearly_price'   => $p->yearly_price,
                'currency'       => $p->currency,
                'metadata'       => $p->metadata,
            ]),
        ]);
    }

    public function show(string $code): Response
    {
        $product = Product::marketplaceVisible()
            ->where('code', $code)
            ->firstOrFail();

        return Inertia::render('Marketplace/ProductDetail', [
            'product' => $product,
        ]);
    }
}
```

- [ ] **Step C4.2: Write the PurchaseController**

```php
<?php
// packages/aero-platform/src/Http/Controllers/Marketplace/PurchaseController.php

namespace Aero\Platform\Http\Controllers\Marketplace;

use Aero\Platform\Models\Product;
use Aero\Platform\Services\LicenseIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(private readonly LicenseIssuer $licenseIssuer) {}

    /**
     * Show the checkout page for a specific product.
     */
    public function checkout(Request $request, string $productCode): Response
    {
        $product      = Product::active()->where('code', $productCode)->firstOrFail();
        $billingCycle = $request->query('cycle', 'one_time'); // 'one_time' | 'monthly' | 'annual'

        $price = match ($billingCycle) {
            'annual'     => $product->yearly_price,
            'monthly'    => $product->monthly_price,
            default      => $product->monthly_price, // one_time uses monthly price as base
        };

        return Inertia::render('Marketplace/Checkout', [
            'product'      => $product,
            'billingCycle' => $billingCycle,
            'price'        => $price,
            'currency'     => $product->currency,
        ]);
    }

    /**
     * Create a Stripe Checkout session and redirect to Stripe.
     */
    public function createCheckoutSession(Request $request): RedirectResponse
    {
        $request->validate([
            'product_code'  => ['required', 'string'],
            'billing_cycle' => ['required', 'in:one_time,annual'],
            'email'         => ['required', 'email'],
            'name'          => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::active()->where('code', $request->product_code)->firstOrFail();
        $cycle   = $request->billing_cycle;
        $amount  = $cycle === 'annual' ? $product->yearly_price : $product->monthly_price;

        // Stripe Checkout Session (using Stripe PHP SDK directly — no Cashier needed here)
        $stripe  = new \Stripe\StripeClient(config('cashier.secret'));

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => strtolower($product->currency),
                    'product_data' => ['name' => $product->name],
                    'unit_amount'  => (int) ($amount * 100), // cents
                ],
                'quantity'   => 1,
            ]],
            'mode'                 => 'payment',
            'customer_email'       => $request->email,
            'metadata'             => [
                'product_code'  => $product->code,
                'billing_cycle' => $cycle,
                'customer_name' => $request->name ?? '',
            ],
            'success_url' => route('marketplace.purchase.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('marketplace.product', $product->code),
        ]);

        return redirect($session->url);
    }

    /**
     * Stripe webhook — called after successful payment.
     * Issues license key and emails customer.
     */
    public function webhook(Request $request): \Illuminate\Http\Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('cashier.webhook.secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            if ($session->payment_status === 'paid') {
                $this->handleSuccessfulPurchase($session);
            }
        }

        return response('ok', 200);
    }

    /**
     * Success landing page — shown after Stripe redirects back.
     */
    public function success(Request $request): Response
    {
        return Inertia::render('Marketplace/PurchaseSuccess', [
            'message' => 'Your purchase was successful. Check your email for your license key and download link.',
        ]);
    }

    private function handleSuccessfulPurchase(object $session): void
    {
        try {
            $metadata     = (array) $session->metadata;
            $productCode  = $metadata['product_code'];
            $billingType  = $metadata['billing_cycle'] === 'annual' ? 'annual' : 'one_time';
            $customerEmail = $session->customer_email;
            $customerName  = $metadata['customer_name'] ?? null;

            $license = $this->licenseIssuer->issue(
                productCode:   $productCode,
                customerEmail: $customerEmail,
                billingType:   $billingType,
                source:        'marketplace',
                orderId:       $session->payment_intent,
                customerName:  $customerName,
            );

            // Send email with license key and download link
            // TODO: create LicensePurchasedMail mailable
            Log::info('License issued after purchase', [
                'license_key'   => $license->license_key,
                'customer_email'=> $customerEmail,
                'product_code'  => $productCode,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to issue license after purchase', [
                'error'   => $e->getMessage(),
                'session' => $session->id,
            ]);
        }
    }
}
```

- [ ] **Step C4.3: Write marketplace routes**

Create `packages/aero-platform/routes/marketplace.php`:

```php
<?php
// packages/aero-platform/routes/marketplace.php
// Public routes on the central domain — no auth required.

use Aero\Platform\Http\Controllers\Marketplace\CatalogController;
use Aero\Platform\Http\Controllers\Marketplace\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->prefix('marketplace')
    ->name('marketplace.')
    ->group(function () {
        // Public catalog
        Route::get('/',                       [CatalogController::class, 'index'])->name('index');
        Route::get('/products/{code}',        [CatalogController::class, 'show'])->name('product');

        // Purchase flow
        Route::get('/checkout/{productCode}', [PurchaseController::class, 'checkout'])->name('checkout');
        Route::post('/checkout/session',      [PurchaseController::class, 'createCheckoutSession'])->name('checkout.session');
        Route::get('/purchase/success',       [PurchaseController::class, 'success'])->name('purchase.success');

        // Stripe webhook — CSRF exempt
        Route::post('/webhook/stripe',        [PurchaseController::class, 'webhook'])
            ->name('webhook.stripe')
            ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    });
```

- [ ] **Step C4.4: Load marketplace routes from AeroPlatformServiceProvider**

In `AeroPlatformServiceProvider::boot()`, alongside other central route loading:

```php
// Marketplace routes — central domain only, no tenant context
if (! $this->app->routesAreCached()) {
    Route::group([], __DIR__ . '/../routes/marketplace.php');
}
```

- [ ] **Step C4.5: Verify routes are registered**

```bash
php artisan route:list | grep marketplace
```

Expected: `GET|HEAD  marketplace/`, `GET|HEAD  marketplace/products/{code}`, `POST marketplace/checkout/session`, `POST marketplace/webhook/stripe`

- [ ] **Step C4.6: Commit**

```bash
git add packages/aero-platform/src/Http/Controllers/Marketplace/ \
        packages/aero-platform/routes/marketplace.php \
        packages/aero-platform/src/AeroPlatformServiceProvider.php
git commit -m "feat(aero-platform): marketplace catalog, Stripe checkout flow, license issuance on purchase"
```

---

## Task C5: DownloadService — Signed ZIP Download URLs

**Files:**
- Create: `packages/aero-platform/src/Services/DownloadService.php`

After purchase, customers get a time-limited signed URL to download their product ZIP.

- [ ] **Step C5.1: Write the DownloadService**

```php
<?php
// packages/aero-platform/src/Services/DownloadService.php

namespace Aero\Platform\Services;

use Aero\Platform\Models\StandaloneLicense;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DownloadService
{
    /**
     * Generate a time-limited signed download URL for a license.
     * Expires after 48 hours — customer must re-request if they need it again.
     */
    public function generateSignedUrl(StandaloneLicense $license): string
    {
        $productCode = $license->product->code;
        $version     = $license->product->version;

        return URL::temporarySignedRoute(
            'marketplace.download',
            now()->addHours(48),
            [
                'license' => $license->id,
                'product' => $productCode,
            ]
        );
    }

    /**
     * Stream the product ZIP file to the browser.
     * Called from the signed download route.
     */
    public function stream(StandaloneLicense $license): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $productCode = $license->product->code;
        $version     = $license->product->version;

        // ZIPs stored in storage/app/releases/{product}-v{version}-standalone.zip
        // In production, use S3: Storage::disk('s3')->...
        $path     = "releases/{$productCode}-v{$version}-standalone.zip";
        $filename = "{$productCode}-v{$version}-standalone.zip";

        if (! Storage::exists($path)) {
            abort(404, "Release file not found for {$productCode} v{$version}");
        }

        return Storage::download($path, $filename);
    }
}
```

- [ ] **Step C5.2: Register the download route in marketplace.php**

In `packages/aero-platform/routes/marketplace.php`, add inside the Route group:

```php
// Signed download route — requires valid signature, no auth
Route::get('/download/{license}/{product}',
    [\Aero\Platform\Http\Controllers\Marketplace\DownloadController::class, 'download'])
    ->name('download')
    ->middleware('signed');
```

- [ ] **Step C5.3: Create DownloadController**

```php
<?php
// packages/aero-platform/src/Http/Controllers/Marketplace/DownloadController.php

namespace Aero\Platform\Http\Controllers\Marketplace;

use Aero\Platform\Models\StandaloneLicense;
use Aero\Platform\Services\DownloadService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DownloadController extends Controller
{
    public function __construct(private readonly DownloadService $downloads) {}

    public function download(Request $request, string $licenseId): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $license = StandaloneLicense::with('product')->findOrFail($licenseId);

        // Verify the license is active before allowing download
        if (! $license->isActive()) {
            abort(403, 'License is not active.');
        }

        return $this->downloads->stream($license);
    }
}
```

- [ ] **Step C5.4: Commit**

```bash
git add packages/aero-platform/src/Services/DownloadService.php \
        packages/aero-platform/src/Http/Controllers/Marketplace/DownloadController.php \
        packages/aero-platform/routes/marketplace.php
git commit -m "feat(aero-platform): DownloadService — signed time-limited ZIP download URLs"
```
