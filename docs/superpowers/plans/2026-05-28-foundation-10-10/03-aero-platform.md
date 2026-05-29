# aero-platform — Plan to 10/10

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Current score:** 5.5/10 (per audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 12–18 engineer-days

**Goal:** Close all production-breaking bugs in the platform package (broken analytics query, broken renewal/retry jobs, stubbed payment retry, hardcoded connection name, subdomain hijack vector, BYOC credential leak, DROP DATABASE risk, module sync race) and bring HRMAC + policy + audit consistency to 10/10.

**Architecture:** No structural rewrite. Targeted fixes within existing patterns. Stancl/tenancy stays. Cashier stays. Three drift areas get consolidated (provisioner, plan controller, sync command).

**Tech Stack:** Laravel 12, stancl/tenancy v3, Laravel Cashier (Stripe), polymorphic Subscription, EncryptedField for BYOC.

**Prerequisite:** Phase 0 wiring plan in flight ([00-wiring-blockers.md](00-wiring-blockers.md)) — Horizon, observability, IdentifyTenant deletion all depend on Phase 0 work.

---

## Reference evidence (from audit)

- 62 sub-modules in `config/module.php` (3321 lines) — only 5 focus areas audited
- 825 PHP files, 116 migrations, 65 tests, 51 admin controllers, 615 routes in `routes/admin.php`
- Critical bugs: B-1, B-2, A-1, X-1, C-1, C-2, L-1, M-1, X-4, X-5

## File Structure

| File | Responsibility |
|---|---|
| `packages/aero-platform/src/Http/Middleware/IdentifyTenant.php` | DELETE (also Phase 0 Task 3) |
| `packages/aero-platform/src/Services/PlatformAnalyticsService.php` | Fix broken `plan_id` query (A-1, A-3) |
| `packages/aero-platform/src/Jobs/ProcessSubscriptionRenewalsJob.php` | Polymorphic query, use Eloquent (B-1) |
| `packages/aero-platform/src/Jobs/RetryFailedPaymentsJob.php` | Implement actual Stripe retry (B-2) |
| `packages/aero-platform/src/Jobs/ProvisionTenant.php` | Replace `DB::connection('mysql')`, fix BYOC config leak, harden rollback (X-1, X-4, X-5) |
| `packages/aero-platform/src/Http/Middleware/PlatformSuperAdmin.php` | Replace role-name string match with HRMAC capability check (L-1) |
| `packages/aero-platform/src/Models/Subscription.php` | Remove legacy `tenant_id` field (B-3) |
| `packages/aero-platform/src/Models/Plan.php` | Remove duplicate price/status columns (B-4) |
| `packages/aero-platform/src/Models/Tenant.php` | Fix `scopeOnTrial()` polymorphic relation (X-7) |
| `packages/aero-platform/src/Console/Commands/SyncModuleHierarchy.php` | Add advisory lock; consolidate 3 implementations (M-1) |
| `packages/aero-platform/config/tenancy.php` | Add `reserved_subdomains` block (C-2) |
| `packages/aero-platform/src/Http/Controllers/Admin/RegistrationController.php` | Use reserved list in validator (C-2) |
| `packages/aero-platform/src/Policies/` | Add `InvoicePolicy`, `SubscriptionPolicy`, `RefundPolicy`, `TenantDomainPolicy`, `TenantDatabasePolicy`, `LandlordUserPolicy` |
| `packages/aero-platform/routes/admin.php` | Normalize HRMAC paths (4-level), remove inline closures (L-2, X-8) |
| `packages/aero-platform/database/migrations/2026_05_28_*` (new) | Backfill subscriptions billable_*, drop legacy columns, add covering indexes |
| `packages/aero-platform/tests/Feature/Tenancy/AnalyticsTest.php` | Add regression for A-1 |
| `packages/aero-platform/tests/Feature/Billing/RenewalJobTest.php` | New |
| `packages/aero-platform/tests/Feature/Billing/RetryPaymentJobTest.php` | New |
| `packages/aero-platform/tests/Feature/Security/SubdomainReservationTest.php` | New |
| `packages/aero-platform/tests/Feature/Provisioning/DropDatabaseGuardTest.php` | New |

---

## Task 1: Fix broken `PlatformAnalyticsService::tenantAnalytics()` (A-1)

**Severity:** Critical. Throws "Unknown column 'plan_id'" in production OR silently returns nulls.

**Files:**
- Modify: `packages/aero-platform/src/Services/PlatformAnalyticsService.php:60-62`
- Modify: `packages/aero-platform/tests/Feature/AnalyticsTest.php`

- [ ] **Step 1: Write failing regression test**

```php
public function test_tenant_analytics_by_plan_uses_polymorphic_subscription(): void
{
    $planA = Plan::factory()->create(['name' => 'Pro']);
    $planB = Plan::factory()->create(['name' => 'Basic']);
    $t1 = Tenant::factory()->withSubscription($planA)->create();
    $t2 = Tenant::factory()->withSubscription($planB)->create();
    $t3 = Tenant::factory()->withSubscription($planA)->create();

    $result = app(PlatformAnalyticsService::class)->tenantAnalytics();

    $this->assertSame(2, $result['by_plan']['Pro']);
    $this->assertSame(1, $result['by_plan']['Basic']);
}
```

- [ ] **Step 2: Run test (FAIL — Unknown column 'plan_id')**

- [ ] **Step 3: Fix query**

```php
// Before
$byPlan = Tenant::query()
    ->select('plan_id', DB::raw('COUNT(*) as count'))
    ->groupBy('plan_id')
    ->get();

// After — polymorphic via Cashier subscriptions
$byPlan = DB::table('tenants')
    ->join('subscriptions', function ($j) {
        $j->on('subscriptions.billable_id', '=', 'tenants.id')
          ->where('subscriptions.billable_type', '=', Tenant::class)
          ->where('subscriptions.stripe_status', '=', 'active');
    })
    ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
    ->select('plans.name as plan_name', DB::raw('COUNT(DISTINCT tenants.id) as count'))
    ->groupBy('plans.name')
    ->pluck('count', 'plan_name')
    ->toArray();
```

- [ ] **Step 4: Run test (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "fix(platform): PlatformAnalyticsService uses polymorphic Subscription join (closes broken plan_id query)"
```

---

## Task 2: Fix `ProcessSubscriptionRenewalsJob` polymorphic mismatch (B-1)

**Severity:** Critical. Tenants never receive renewal reminders.

**Files:**
- Modify: `packages/aero-platform/src/Jobs/ProcessSubscriptionRenewalsJob.php:45-49`
- Create: `packages/aero-platform/tests/Feature/Billing/RenewalJobTest.php`

- [ ] **Step 1: Write failing test**

```php
public function test_job_dispatches_reminder_for_tenant_subscription_morphed_via_billable(): void
{
    $tenant = Tenant::factory()->create();
    $tenant->subscriptions()->create([
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'next_billing_date' => now()->addDays(3),
    ]);

    Notification::fake();
    (new ProcessSubscriptionRenewalsJob)->handle();
    Notification::assertSentTo($tenant->admins, RenewalReminderNotification::class);
}
```

- [ ] **Step 2: Run (FAIL)**

- [ ] **Step 3: Replace raw DB query**

```php
// Before
$subscriptions = DB::table('subscriptions')
    ->where('next_billing_date', whereLike(...))
    ->get();

// After
$subscriptions = Subscription::query()
    ->where('stripe_status', 'active')
    ->whereDate('next_billing_date', now()->addDays(3))
    ->with('billable')
    ->get();

foreach ($subscriptions as $sub) {
    if ($sub->billable instanceof Tenant) {
        $sub->billable->notifyAdmins(new RenewalReminderNotification($sub));
    }
}
```

- [ ] **Step 4: Run (PASS) + commit**

```bash
git commit -am "fix(platform): ProcessSubscriptionRenewalsJob uses polymorphic billable (closes B-1)"
```

---

## Task 3: Implement actual payment retry in `RetryFailedPaymentsJob` (B-2)

**Severity:** Critical. Currently a `// TODO` stub — past_due tenants never auto-recover.

**Files:**
- Modify: `packages/aero-platform/src/Jobs/RetryFailedPaymentsJob.php:67-76`
- Create: `packages/aero-platform/tests/Feature/Billing/RetryPaymentJobTest.php`

- [ ] **Step 1: Write failing test using Stripe mock**

```php
public function test_retry_calls_stripe_payment_intent_confirm(): void
{
    Cashier::fake(); // or use stripe-mock
    $tenant = Tenant::factory()->withPastDueSubscription()->create();
    $sub = $tenant->currentSubscription();

    (new RetryFailedPaymentsJob)->handle();

    Cashier::stripe()->paymentIntents->assertConfirmed($sub->latest_invoice_pi);
}
```

- [ ] **Step 2: Run (FAIL)**

- [ ] **Step 3: Replace TODO with real Stripe call**

```php
foreach ($subscriptions as $subscription) {
    try {
        $latestInvoice = Cashier::stripe()->invoices->retrieve($subscription->latest_invoice_id, [
            'expand' => ['payment_intent'],
        ]);
        Cashier::stripe()->paymentIntents->confirm($latestInvoice->payment_intent->id);

        $this->audit->log('billing.payment.retry.success', $subscription, [...]);
    } catch (StripeException $e) {
        $subscription->update(['next_retry_at' => now()->addHours(24)]);
        $this->audit->log('billing.payment.retry.failed', $subscription, ['error' => $e->getMessage()]);
    }
}
```

- [ ] **Step 4: Run (PASS) + commit**

```bash
git commit -am "fix(platform): RetryFailedPaymentsJob actually calls Stripe (closes B-2 stub)"
```

---

## Task 4: Delete custom `IdentifyTenant` middleware (C-1)

**This is shared with Phase 0 Task 3.** Verify completion there.

- [ ] **Step 1: Confirm Phase 0 Task 3 closed**

- [ ] **Step 2: Re-grep**

```bash
grep -rn "IdentifyTenant\|identify\.tenant" packages/aero-platform/
```

Expected: empty.

---

## Task 5: Replace `DB::connection('mysql')` hardcoding (X-1)

**Severity:** Critical for standalone deployment.

**Files:**
- Modify: `packages/aero-platform/src/Jobs/ProvisionTenant.php:1583`

- [ ] **Step 1: Write failing test on standalone fixture**

```php
public function test_audit_event_writes_to_default_connection_in_standalone(): void
{
    config(['database.default' => 'pgsql_test', 'aero.mode' => 'standalone']);
    // ... assert audit_logs row created on pgsql_test
}
```

- [ ] **Step 2: Replace hardcoded connection**

```php
// Before
DB::connection('mysql')->table('audit_logs')->insert([...]);

// After — use AuditService contract
app(\Aero\Contracts\AuditServiceInterface::class)->log($action, null, $payload);
```

- [ ] **Step 3: PASS + commit**

```bash
git commit -am "fix(platform): ProvisionTenant routes audit via AuditService contract (closes X-1)"
```

---

## Task 6: Reserved subdomain list (C-2)

**Severity:** Critical. Without this, a tenant can register `mail.aeos365.com` and hijack platform mail DNS.

**Files:**
- Modify: `packages/aero-platform/config/tenancy.php`
- Modify: `packages/aero-platform/src/Http/Controllers/Admin/RegistrationController.php`
- Modify: `packages/aero-platform/src/Http/Requests/RegisterTenantRequest.php` (or wherever validation lives)
- Create: `packages/aero-platform/tests/Feature/Security/SubdomainReservationTest.php`

- [ ] **Step 1: Write failing test**

```php
public function test_reserved_subdomains_are_rejected(): void
{
    foreach (['mail', 'static', 'cdn', 'app', 'smtp', 'imap', 'support', 'status', 'admin', 'api', 'www', 'central', 'ws'] as $sub) {
        $response = $this->postJson('/register', ['subdomain' => $sub, ...]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['subdomain']);
    }
}
```

- [ ] **Step 2: Run (FAIL — all currently allowed)**

- [ ] **Step 3: Add config**

```php
// config/tenancy.php
'reserved_subdomains' => [
    'admin', 'www', 'api', 'app', 'mail', 'smtp', 'imap', 'pop',
    'cdn', 'static', 'media', 'assets',
    'central', 'platform', 'landlord',
    'support', 'status', 'help', 'docs',
    'ws', 'websocket', 'broadcast',
    'stats', 'metrics', 'analytics', 'dashboard',
    'ftp', 'sftp', 'ssh', 'ns1', 'ns2', 'ns3',
    'root', 'system', 'sys',
    'horizon', 'telescope', 'pulse',
    'auth', 'sso', 'oauth', 'login',
],
```

- [ ] **Step 4: Add validation rule**

```php
public function rules(): array
{
    return [
        'subdomain' => [
            'required', 'string', 'min:3', 'max:63',
            'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
            Rule::notIn(config('tenancy.reserved_subdomains')),
        ],
    ];
}
```

- [ ] **Step 5: PASS + commit**

```bash
git commit -am "feat(platform): reserved subdomain list (closes C-2 hijack vector)"
```

---

## Task 7: Replace `PlatformSuperAdmin` role-name string match (L-1)

**Files:**
- Modify: `packages/aero-platform/src/Http/Middleware/PlatformSuperAdmin.php:43`

- [ ] **Step 1: Write failing test**

```php
public function test_super_admin_check_uses_landlord_guard_capability_not_role_name(): void
{
    // tenant user with a role literally named 'Super Administrator' must NOT pass
    $tenantUser = User::factory()->withRole('Super Administrator')->create();
    $this->actingAs($tenantUser, 'web');
    $response = $this->get('/admin/platform-dashboard');
    $response->assertForbidden();
}
```

- [ ] **Step 2: Run (FAIL if string-match passes)**

- [ ] **Step 3: Replace**

```php
// Before
if ($user->hasRole('Super Administrator')) return $next($request);

// After
if (auth('landlord')->check() &&
    $user instanceof LandlordUser &&
    $user->hasPermissionTo('platform.*', 'landlord')) {
    return $next($request);
}
abort(403);
```

- [ ] **Step 4: PASS + commit**

```bash
git commit -am "fix(platform): PlatformSuperAdmin uses landlord-guard capability (closes L-1)"
```

---

## Task 8: Remove legacy `tenant_id` from `Subscription` + duplicate columns from `Plan` (B-3, B-4)

**Files:**
- Create: `packages/aero-platform/database/migrations/2026_05_28_001000_backfill_subscriptions_billable.php`
- Create: `packages/aero-platform/database/migrations/2026_05_28_001001_drop_legacy_subscription_tenant_id.php`
- Create: `packages/aero-platform/database/migrations/2026_05_28_001002_drop_legacy_plan_columns.php`
- Modify: `packages/aero-platform/src/Models/Subscription.php` — remove `tenant_id` from `$fillable`
- Modify: `packages/aero-platform/src/Models/Plan.php` — remove `monthly_price`, `is_active`, `stripe_monthly_price_id` from `$fillable`

- [ ] **Step 1: Write backfill migration (idempotent)**

```php
public function up(): void
{
    DB::table('subscriptions')
        ->whereNotNull('tenant_id')
        ->whereNull('billable_id')
        ->orderBy('id')
        ->chunkById(500, function ($rows) {
            foreach ($rows as $r) {
                DB::table('subscriptions')->where('id', $r->id)->update([
                    'billable_id' => $r->tenant_id,
                    'billable_type' => Tenant::class,
                ]);
            }
        });
}
```

- [ ] **Step 2: Write drop migration (run AFTER backfill confirmed)**

```php
public function up(): void
{
    Schema::table('subscriptions', function (Blueprint $t) {
        $t->dropColumn('tenant_id');
    });
}
```

- [ ] **Step 3: Update model `$fillable`, remove constants referencing old columns**

- [ ] **Step 4: Run tests, confirm Subscription factory still works**

- [ ] **Step 5: Commit**

```bash
git commit -am "refactor(platform): drop legacy Subscription.tenant_id (B-3) + duplicate Plan columns (B-4)"
```

---

## Task 9: Fix `Tenant::scopeOnTrial` polymorphic relation (X-7)

**Files:**
- Modify: `packages/aero-platform/src/Models/Tenant.php:251-333`

- [ ] **Step 1: Define explicit polymorphic relation**

```php
public function platformSubscriptions(): MorphMany
{
    return $this->morphMany(Subscription::class, 'billable');
}
```

- [ ] **Step 2: Fix scope**

```php
public function scopeOnTrial(Builder $query): Builder
{
    return $query->whereHas('platformSubscriptions', fn ($q) =>
        $q->where('stripe_status', 'trialing')
          ->where('trial_ends_at', '>', now())
    );
}
```

- [ ] **Step 3: Add tests**

- [ ] **Step 4: Commit**

```bash
git commit -am "fix(platform): Tenant::scopeOnTrial uses explicit polymorphic relation (X-7)"
```

---

## Task 10: Consolidate `SyncModuleHierarchy` + add advisory lock (M-1)

**Files:**
- Modify: `packages/aero-platform/src/Console/Commands/SyncModuleHierarchy.php`
- Delete: `packages/aero-hrmac/src/Console/Commands/SyncModuleHierarchy.php` (or platform deletes its own; pick canonical)
- Modify: `packages/aero-platform/src/Jobs/ProvisionTenant.php:760-802` — call canonical command, don't inline

**Decision:** Canonical lives in **aero-hrmac** (it's the access-control engine; platform consumes it). Platform's command delegates.

- [ ] **Step 1: Write concurrent execution test (race detection)**

```php
public function test_concurrent_sync_does_not_create_duplicates(): void
{
    Process::start('php artisan modules:sync')->wait();
    Process::start('php artisan modules:sync')->wait(); // race attempt
    $this->assertSame(1, DB::table('modules')->where('code', 'core')->count());
}
```

- [ ] **Step 2: Add advisory lock to hrmac command**

```php
public function handle(): int
{
    $lock = DB::statement('SELECT GET_LOCK(?, 30) as ok', ['aero:sync-modules']);
    if (! $lock) {
        $this->error('Another sync is in progress; aborting.');
        return self::FAILURE;
    }
    try {
        // ... sync logic
    } finally {
        DB::statement('SELECT RELEASE_LOCK(?)', ['aero:sync-modules']);
    }
    return self::SUCCESS;
}
```

- [ ] **Step 3: Delete platform's duplicate command + replace `ProvisionTenant::syncModuleToDatabase` with `Artisan::call('modules:sync', ['--tenant' => $tenant->id])`**

- [ ] **Step 4: Run tests + commit**

```bash
git commit -am "refactor(platform): consolidate SyncModuleHierarchy via aero-hrmac canonical + add advisory lock (M-1)"
```

---

## Task 11: BYOC credential leak in queue worker (X-4)

**Files:**
- Modify: `packages/aero-platform/src/Jobs/ProvisionTenant.php:317` + add `restoreConfig()` in `failed()` and after success

- [ ] **Step 1: Write failing test (run two provision jobs sequentially, assert second doesn't inherit first's BYOC creds)**

- [ ] **Step 2: Capture+restore pattern**

```php
public function __construct(private Tenant $tenant)
{
    $this->originalConfig = [
        'tenant_connection' => config('database.connections.tenant'),
    ];
}

public function handle(): void
{
    try {
        // ... existing logic that overlays cPanel creds
    } finally {
        config(['database.connections.tenant' => $this->originalConfig['tenant_connection']]);
        DB::purge('tenant');
    }
}
```

- [ ] **Step 3: Run test (PASS) + commit**

```bash
git commit -am "fix(platform): restore tenant DB config after ProvisionTenant (closes X-4 BYOC leak)"
```

---

## Task 12: Harden `rollbackDatabase` DROP regex (X-5)

**Files:**
- Modify: `packages/aero-platform/src/Jobs/ProvisionTenant.php:1387,1393`
- Create: `packages/aero-platform/tests/Feature/Provisioning/DropDatabaseGuardTest.php`

- [ ] **Step 1: Write failing test**

```php
public function test_drop_database_refuses_name_without_tenant_prefix(): void
{
    $this->expectException(\InvalidArgumentException::class);
    (new ProvisionTenant(Tenant::factory()->make()))
        ->rollbackDatabase('production_data'); // no tenant_ prefix
}

public function test_drop_database_refuses_central_db_name(): void
{
    $this->expectException(\InvalidArgumentException::class);
    (new ProvisionTenant(Tenant::factory()->make()))
        ->rollbackDatabase(config('database.connections.central.database'));
}
```

- [ ] **Step 2: Run (FAIL — currently allows)**

- [ ] **Step 3: Add guard**

```php
protected function rollbackDatabase(string $databaseName): void
{
    $prefix = config('tenancy.database.prefix', 'tenant_');
    $centralDb = config('database.connections.central.database');

    if (! str_starts_with($databaseName, $prefix) || $databaseName === $centralDb) {
        throw new \InvalidArgumentException(
            "Refusing to DROP database '{$databaseName}': must start with '{$prefix}' and not equal central DB."
        );
    }

    DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    $this->audit->log('tenant.database.dropped', null, ['database' => $databaseName]);
}
```

- [ ] **Step 4: PASS + commit**

```bash
git commit -am "fix(platform): guard rollbackDatabase against dropping non-tenant DBs (closes X-5)"
```

---

## Task 13: Normalize HRMAC paths in `routes/admin.php` (L-2)

**Files:**
- Modify: `packages/aero-platform/routes/admin.php` (615 routes)

Per audit, routes mix `hrmac:platform-dashboard.overview` (2-level) and `hrmac:tenants.tenant-list.tenant-management.view` (4-level with phantom slot). Standardize on declared `module.submodule.component.action` from `config/module.php`.

- [ ] **Step 1: Run permission-key-mismatch test from aero-core Task 18 against platform routes**

```bash
php artisan test --filter=PermissionKeyMismatchTest
```

Expected: dozens of mismatches.

- [ ] **Step 2: Walk every route group, align middleware string with declared path**

- [ ] **Step 3: Re-run sync; verify `modules` table reflects new declarations**

- [ ] **Step 4: Re-run mismatch test (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "fix(platform): normalize HRMAC paths across routes/admin.php (closes L-2)"
```

---

## Task 14: Create missing policies (Invoice, Subscription, Refund, TenantDomain, TenantDatabase, LandlordUser)

Same shape as aero-core Task 13. Six new policies + controller wiring.

- [ ] **Step 1: Policy unit tests**
- [ ] **Step 2: Generate policies**
- [ ] **Step 3: Wire `$this->authorize()` in controllers**
- [ ] **Step 4: Commit per policy**

---

## Task 15: Consolidate drift (Onboarding, Plan, BulkTenant controllers; TenantProvisioner service)

**Files:**
- Decide canonical: `Admin/OnboardingController.php` OR `Admin/AdminOnboardingController.php` — pick one, delete the other
- Decide canonical: `Admin/PlanController.php` OR root `Http/Controllers/PlanController.php` (and remove `AdminP2PlanController` alias) — pick one
- Decide canonical: `BulkTenantController` OR `BulkTenantOperationsController` — pick one
- Decide canonical: `ProvisionTenant` job OR `Services/Monitoring/Tenant/TenantProvisioner.php` service — merge or delete
- Decide canonical: `ProvisionTenant::seedDefaultRoles()` OR `Services/Tenant/TenantRoleSeeder.php` — merge

- [ ] **Step 1: For each drift pair, grep usages**

- [ ] **Step 2: Pick canonical (newer/better-tested), delete the other, update imports**

- [ ] **Step 3: Run full test suite**

- [ ] **Step 4: Commit per consolidation**

```bash
git commit -m "refactor(platform): consolidate OnboardingController drift"
git commit -m "refactor(platform): consolidate PlanController drift"
git commit -m "refactor(platform): consolidate BulkTenantController drift"
git commit -m "refactor(platform): consolidate TenantProvisioner into ProvisionTenant job"
git commit -m "refactor(platform): consolidate role seeders"
```

---

## Task 16: Replace inline closures in `routes/admin.php` with controller methods (X-8)

Per audit, lines 182-201 (and others) are inline `function () { return Inertia::render(...); }`. Move each into a controller method for testability + route caching.

- [ ] **Step 1: Inventory closures**

```bash
grep -n "Route::.*function (.*)" packages/aero-platform/routes/admin.php
```

- [ ] **Step 2: For each, create controller method**

- [ ] **Step 3: Verify `php artisan route:cache` succeeds (closures break this)**

- [ ] **Step 4: Commit**

---

## Task 17: Add covering indexes (A-2, B-4 tail)

**Files:**
- Create: `packages/aero-platform/database/migrations/2026_05_28_002000_add_platform_covering_indexes.php`

```php
public function up(): void
{
    Schema::table('tenant_stats', fn ($t) => $t->index(['tenant_id', 'date'], 'tenant_stats_tenant_date_idx'));
    Schema::table('subscriptions', function ($t) {
        $t->index('next_billing_date', 'subs_next_billing_idx');
        $t->index(['stripe_status', 'next_retry_at'], 'subs_status_retry_idx');
    });
    Schema::table('invoices', fn ($t) => $t->index(['billable_id', 'billable_type', 'created_at'], 'invoices_billable_created_idx'));
}
```

- [ ] **Step 1: Migration**
- [ ] **Step 2: Run `EXPLAIN` on the analytics query — confirm index used**
- [ ] **Step 3: Commit**

---

## Task 18: Database-agnostic date_trunc helper (A-3)

**Files:**
- Modify: `packages/aero-platform/src/Services/PlatformAnalyticsService.php:91-97`

```php
$rows = Tenant::query()
    ->select('id', 'created_at')
    ->orderBy('created_at')
    ->get()
    ->groupBy(fn ($t) => $t->created_at->format('Y-m'));
```

(Move grouping to PHP — handles small/medium volumes. For very large tenant tables, use a database-specific `date_trunc` wrapped in a `DB::raw` switched by `config('database.default')`.)

- [ ] **Step 1: Refactor**
- [ ] **Step 2: Test on MySQL + Postgres**
- [ ] **Step 3: Commit**

---

## Task 19: Reverse-gap cleanup — declare or remove undeclared models

Per audit reverse gaps R-2, R-5:
- `Models/PartialRegistration.php` + flow — declare under `platform-onboarding.partial-registrations.*` OR move to private namespace
- `Models/UserDevice.php` + `ResetDevicesForSecurityUpdate` command — declare under `platform-security.device-management.*` OR move to aero-auth

- [ ] **Step 1: Decide per pair**
- [ ] **Step 2: Update `config/module.php` accordingly**
- [ ] **Step 3: Run `modules:sync`**
- [ ] **Step 4: Commit**

---

## Task 20: Add `Webhook*` model consolidation

Per audit R-6: 5 webhook-related models exist (`Webhook`, `WebhookEndpoint`, `WebhookDeliveryLog`, `WebhookEvent`, `WebhookLog`). Likely two of these are duplicates.

- [ ] **Step 1: Read all 5 models**
- [ ] **Step 2: Identify duplicates, deprecate one**
- [ ] **Step 3: Migration to drop deprecated table after data move**
- [ ] **Step 4: Commit**

---

## Task 21: Final verification + score recheck

- [ ] **Step 1: Run platform test suite**

```bash
php artisan test packages/aero-platform/tests
```

- [ ] **Step 2: Re-grep critical regressions**

```bash
grep -rn "DB::connection('mysql')" packages/aero-platform/src
grep -rn "DB::table('subscriptions')\|->plan_id" packages/aero-platform/src
grep -rn "IdentifyTenant" packages/aero-platform/src
```

Expected: empty (or with documented exceptions).

- [ ] **Step 3: Score recheck**

| Dimension | Target |
|---|---|
| Tenancy lifecycle (provision/rollback/purge) | 10/10 |
| Billing (renewal + retry actually work) | 10/10 |
| Landlord/Platform Admin isolation | 10/10 |
| Module discovery (race-free + consolidated) | 10/10 |
| Tenant analytics (queries match schema) | 10/10 |
| HRMAC consistency | 10/10 |
| Policy coverage | 10/10 |

- [ ] **Step 4: Tag**

```bash
git tag aero-platform-10-10
```

---

## Self-Review

- ✅ Every Critical (C-, B-, A-, L-, M-, X-prefixed) item has a task
- ✅ TDD shape (test first, fix, green)
- ✅ Phase 0 dependency called out explicitly
- ✅ Drift consolidation collected into one task block
- ✅ Tasks ordered by severity (data-loss/data-leak → drift → polish)

## Execution Handoff

Tasks 1, 2, 3, 5, 6, 7 are **production blockers**. Tackle these first — each is 1-3 hours of work. Task 8 (Subscription `tenant_id` removal) requires a **backfill migration on a staging clone first**, then production after backfill verified. Task 13 (HRMAC path normalization) is mechanical but high-volume — pair with the permission-mismatch CI test from `02-aero-core.md` Task 18.
