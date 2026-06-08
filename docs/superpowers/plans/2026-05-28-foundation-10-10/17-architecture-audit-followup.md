# Architecture Audit Follow-up Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Source:** [ARCHITECTURE_AUDIT.md](./ARCHITECTURE_AUDIT.md) walk-through, 2026-05-30
**Goal:** Apply the architectural decisions surfaced during the audit. Every task here corresponds to a decision the operator explicitly made in the audit Q&A.
**Estimated effort:** ~8-12 engineer-days

## Decisions captured (from the audit)

| # | Area | Decision |
|---|---|---|
| D1 | Tenancy library | ✅ stancl/tenancy v3 + DB-per-tenant + subdomain — as-is |
| D2 | Mode resolver | ✅ AeroMode singleton + AERO_MODE env — as-is |
| D3 | BYOC | ✅ EncryptedField + finally{} restore — as-is |
| D4 | Tenant migrations | ⚠️ **vendor/aero/* paths only** (canonical; standalone uses composer-path symlinks) |
| D5 | Bootstrappers | ⚠️ **add S3 per-tenant prefix** + **fail-closed queue tenancy** |
| D6 | Reserved subdomains | ✅ ~50-entry list across 3 enforcement sites — as-is |
| D7 | Tenant retention | ⚠️ **add GDPR immediate-purge endpoint** separate from retention |
| D8 | Auth guards | ✅ web + landlord, guard-scoped super-admin — as-is (no 3rd 'api' guard) |
| D9 | PII encryption | ✅ AES-256 via APP_KEY — as-is (KMS deferred) |
| D10 | Audit coverage | ✅ AuditService contract + per-package expansion — as-is |
| D11 | Immutability | ✅ per-model booted listeners — as-is |
| D12 | Facade discipline | ⚠️ **convert to budget ratchet** (like inline-style) |
| D13 | Module discovery paths | ⚠️ **vendor + modules only** (revert monorepo dev path) |
| D14 | Module declarations | ✅ trimmed config + roadmap key — as-is |
| D15 | Per-tenant module catalog | ⚠️ **major rework — driven by product_subscriptions** |
| D16 | Sync command UX | ✅ existing `--scope=platform\|tenant` flag — as-is |
| D17 | Subscription downgrade | ⚠️ **30-day suspended grace → hard-delete role_module_access** |
| D18 | Frontend stack | ✅ React 18 + Inertia v2 + HeroUI — as-is |
| D19 | Inline-style discipline | ✅ ESLint + PHP ratchet at 165 — as-is |
| D20 | Queue driver | ✅ Redis + operator Horizon install — as-is |
| D21 | Queue tenancy | ✅ Stancl QueueTenancyBootstrapper — as-is |
| D22 | Billing engine | ✅ Cashier + polymorphic Subscription — as-is |
| D23 | HRM tier licensing | ⚠️ **REVERSE — no submodule licensing, only module/product level** |
| D24 | HRM API surface | ⚠️ **extend v1: Payslip + Department + Designation read** |
| D25 | API response shape | ✅ data/meta/links — as-is |
| D26 | Installation | ✅ step-based orchestrator + dirty-schema guard — as-is |
| D27 | Step idempotency | ✅ foundation laid; per-step as-needed — as-is |
| D28 | Logging | ✅ stack daily+stderr + Sentry opt-in — as-is |
| D29 | Health checks | ✅ /health + /health/detailed rate-limited — as-is |
| D30 | Audit channels | ✅ 4 separate tables — as-is (consolidation deferred) |
| D31 | Host repos | ✅ separate aeos365 + aeos365-standalone repos — as-is |
| D32 | HRM packaging | ✅ 8-package standalone — as-is |
| D33 | Payroll-Finance bridge | ✅ deferred until customer demand — as-is |
| D34 | Permission payload | ✅ per-tenant catalog keeps it small — as-is |

**⚠️ items become tasks below. ✅ items confirm the current implementation.**

---

## File Structure (net-new files)

| File | Responsibility |
|---|---|
| `packages/aero-hrmac/src/Services/ModuleDiscoveryService.php` | Discovery paths: drop monorepo |
| `packages/aero-hrmac/src/Console/Commands/SyncModuleHierarchy.php` | Tenant scope filters by product_subscriptions |
| `packages/aero-hrmac/config/hrmac.php` | discovery.paths config |
| `packages/aero-platform/database/migrations/2026_05_30_000001_add_status_to_role_module_access.php` (new) | Soft-suspend column for downgrade grace |
| `packages/aero-platform/src/Events/ProductSubscriptionChanged.php` (new) | Fired on product-sub create/cancel |
| `packages/aero-platform/src/Listeners/ResyncTenantModuleCatalog.php` (new) | Listens + invokes tenant-scope sync |
| `packages/aero-platform/src/Listeners/SuspendUnsubscribedRoleAccess.php` (new) | Marks role grants `suspended` on unsubscribe |
| `packages/aero-platform/src/Console/Commands/PurgeSuspendedRoleAccess.php` (new) | Hard-deletes after 30 days |
| `packages/aero-platform/src/Http/Controllers/Admin/TenantForgetController.php` (new) | POST `/admin/tenants/{id}/forget` for GDPR |
| `packages/aero-platform/src/Services/TenantForgetService.php` (new) | Bypasses retention, immediate purge |
| `packages/aero-platform/src/Bootstrappers/FailClosedQueueTenancyBootstrapper.php` (new) | Extends Stancl with absent/suspended check |
| `packages/aero-platform/config/tenancy.php` | filesystem.disks += s3 |
| `packages/aero-hrm/config/module.php` | DELETE tier_licensing block + roadmap note |
| `packages/aero-hrm/src/Services/HrmTierLicenseService.php` | DELETE — replaced by catalog-driven |
| `packages/aero-hrm/tests/Unit/Services/HrmTierLicenseServiceTest.php` | DELETE |
| `packages/aero-hrm/src/Http/Controllers/Api/PayslipApiController.php` (new) | Own payslips read |
| `packages/aero-hrm/src/Http/Controllers/Api/DepartmentApiController.php` (new) | Lookup list |
| `packages/aero-hrm/src/Http/Controllers/Api/DesignationApiController.php` (new) | Lookup list |
| `packages/aero-hrm/routes/api.php` | + payslips/departments/designations routes |
| `aeos365/tests/Feature/Wiring/FacadeDisciplineTest.php` | Convert to ratchet (current-count budget) |

---

## Task 1: Revert monorepo discovery path (D13)

**Files:**
- Modify: `packages/aero-hrmac/config/hrmac.php` (the discovery.paths array)
- Create: `packages/aero-hrmac/tests/Unit/Services/DiscoveryPathsTest.php`

- [ ] **Step 1: Write failing test**

```php
public function test_discovery_paths_exclude_monorepo_packages(): void
{
    $config = require dirname(__DIR__, 3).'/config/hrmac.php';
    $paths = $config['discovery']['paths'];

    $this->assertContains('vendor/aero/*/config/module.php', $paths);
    $this->assertContains('modules/*/config/module.php', $paths);
    $this->assertNotContains('packages/aero-*/config/module.php', $paths,
        'Monorepo dev path removed (Plan 04 T4 → Audit D13): discovery uses vendor-only in production. '.
        'Standalone deployments use composer-path symlinks which resolve vendor/aero/* → packages/aero-*.');
}
```

- [ ] **Step 2: Update config**

Remove the line `'packages/aero-*/config/module.php',` from `config/hrmac.php['discovery']['paths']`.

- [ ] **Step 3: Update the comment** explaining the choice (mention standalone uses modules/ for operator-installed add-ons).

- [ ] **Step 4: Commit**

```bash
git commit -am "fix(hrmac): vendor + modules only for discovery (revert Plan 04 T4 monorepo path)

Per architecture audit D13: production discovery is vendor-only.
Standalone deployments use composer-path symlinks which resolve
vendor/aero/* → packages/aero-* at install time. The packages/
glob was a dev-time convenience that risked leaking unreleased
modules into a production modules:sync run."
```

---

## Task 2: Per-tenant module catalog (D15 — major architectural rework)

The catalog of which modules a tenant can use comes from `product_subscriptions` (NOT from plan). Plan provides tier limits + base modules; product subscriptions add specific HRM/Finance/CRM grants.

**Files:**
- Modify: `packages/aero-platform/src/Models/Tenant.php` — add `subscribedProductModules()` accessor
- Modify: `packages/aero-hrmac/src/Console/Commands/SyncModuleHierarchy.php` — filter by tenant catalog when scope=tenant
- Create: `packages/aero-platform/src/Events/ProductSubscriptionChanged.php`
- Create: `packages/aero-platform/src/Listeners/ResyncTenantModuleCatalog.php`

- [ ] **Step 1: Add the accessor on Tenant**

```php
/**
 * Module codes this tenant is subscribed to via product_subscriptions.
 * Includes platform-baseline ('core', 'auth', 'ui', 'i18n', 'notifications',
 * 'hrmac') plus every active product subscription's module code.
 *
 * @return array<int, string>
 */
public function getSubscribedProductModulesAttribute(): array
{
    $baseline = config('hrmac.baseline_modules', ['core', 'auth', 'ui', 'i18n', 'notifications', 'hrmac']);

    $productCodes = DB::connection('central')->table('product_subscriptions')
        ->join('products', 'product_subscriptions.product_id', '=', 'products.id')
        ->where('product_subscriptions.billable_id', $this->id)
        ->where('product_subscriptions.billable_type', static::class)
        ->where('product_subscriptions.status', 'active')
        ->pluck('products.module_code')
        ->toArray();

    return array_values(array_unique(array_merge($baseline, $productCodes)));
}
```

- [ ] **Step 2: SyncModuleHierarchy --scope=tenant filters by catalog**

Locate the loop in `SyncModuleHierarchy::handle()` (already discovers all module configs). When `--scope=tenant` is set AND `tenancy()->initialized()`, filter the discovered modules to only those whose `code` is in `tenant()->subscribed_product_modules`.

```php
if ($scope === 'tenant') {
    $allowed = tenant()->subscribed_product_modules;
    $modules = $modules->filter(fn ($def) => in_array($def['code'], $allowed, true));
}
```

- [ ] **Step 3: Fire event on subscription change**

```php
class ProductSubscriptionChanged
{
    public function __construct(
        public Tenant $tenant,
        public string $action, // 'created' | 'cancelled' | 'reactivated'
        public string $moduleCode,
    ) {}
}
```

Wire `Subscription` model observers to dispatch this. (Cashier doesn't have this event; we add it.)

- [ ] **Step 4: Listener invokes re-sync**

```php
class ResyncTenantModuleCatalog
{
    public function handle(ProductSubscriptionChanged $event): void
    {
        tenancy()->initialize($event->tenant);
        try {
            Artisan::call('hrmac:sync-modules', ['--scope' => 'tenant']);
        } finally {
            tenancy()->end();
        }
    }
}
```

- [ ] **Step 5: Tests**

Per-tenant integration test (factory: tenant with 2 product subscriptions → sync → assert only 2 product module codes + baseline in modules table).

- [ ] **Step 6: Commit**

---

## Task 3: Soft-suspend role grants on product unsubscribe (D17)

Schema add: `role_module_access.status` enum {`active`, `suspended`} with `suspended_at` timestamp.

**Files:**
- Create: `database/migrations/.../add_status_to_role_module_access.php`
- Create: `packages/aero-platform/src/Listeners/SuspendUnsubscribedRoleAccess.php`
- Create: `packages/aero-platform/src/Console/Commands/PurgeSuspendedRoleAccess.php` (scheduled daily)
- Modify: `packages/aero-hrmac/src/Services/RoleModuleAccessService.php` — filter `status='active'`

- [ ] **Step 1: Migration**

```php
Schema::table('role_module_access', function (Blueprint $t) {
    $t->enum('status', ['active', 'suspended'])->default('active')->after('access_scope');
    $t->timestamp('suspended_at')->nullable();
    $t->index(['status', 'suspended_at']);
});
```

- [ ] **Step 2: On unsubscribe, listener marks rows suspended**

```php
class SuspendUnsubscribedRoleAccess
{
    public function handle(ProductSubscriptionChanged $event): void
    {
        if ($event->action !== 'cancelled') return;

        tenancy()->initialize($event->tenant);
        try {
            // Find the module row for this product
            $module = Module::where('code', $event->moduleCode)->first();
            if (! $module) return;

            // Suspend every role_module_access entry referencing this module (direct OR via sub-modules)
            RoleModuleAccess::where('module_id', $module->id)
                ->orWhereIn('sub_module_id', $module->subModules()->pluck('id'))
                ->update(['status' => 'suspended', 'suspended_at' => now()]);
        } finally {
            tenancy()->end();
        }
    }
}
```

- [ ] **Step 3: RoleModuleAccessService filters by status**

Every `where('role_id', ...)` query also `->where('status', 'active')` so suspended rows don't grant access at runtime.

- [ ] **Step 4: PurgeSuspendedRoleAccess command**

Scheduled daily. Hard-deletes rows where `status='suspended' AND suspended_at < now()->subDays(30)`.

- [ ] **Step 5: Reactivation on re-subscribe**

If `ProductSubscriptionChanged::action === 'reactivated'`, listener flips back to `active` + clears `suspended_at` for rows where `suspended_at >= now()->subDays(30)`. After 30 days they're gone — no recovery.

- [ ] **Step 6: Tests + commit**

---

## Task 4: HRM tier-licensing reversal (D23)

Reverses H.T3. Replace per-submodule tier with product-subscription catalog.

**Files:**
- Modify: `packages/aero-hrm/config/module.php` — delete `tier_licensing` block; replace with `product_code` field at module-top-level
- Delete: `packages/aero-hrm/src/Services/HrmTierLicenseService.php`
- Delete: `packages/aero-hrm/tests/Unit/Services/HrmTierLicenseServiceTest.php`

- [ ] **Step 1: Update config/module.php**

```php
'code' => 'hrm',
'product_code' => 'hrm',      // ← NEW: links module to product_subscriptions
'name' => '...',
// REMOVE: 'tier_licensing' => [...]
```

- [ ] **Step 2: Delete the service + test**

```bash
git rm packages/aero-hrm/src/Services/HrmTierLicenseService.php
git rm packages/aero-hrm/tests/Unit/Services/HrmTierLicenseServiceTest.php
```

- [ ] **Step 3: Commit**

```bash
git commit -m "refactor(hrm): replace per-submodule tier licensing with product-subscription catalog

Per architecture audit D23: licensing is at MODULE (product) level only,
not submodule level. Subscribe to the HRM product = get ALL HRM submodules.
This unifies with the product_subscriptions catalog set up in Task 2 —
modules:sync writes only subscribed product modules; HRMAC enforcement
checks the per-tenant catalog rather than a separate tier map."
```

---

## Task 5: S3 per-tenant prefix (D5a)

**Files:**
- Modify: `packages/aero-platform/config/tenancy.php` — add `'s3'` to filesystem.disks
- Modify: `packages/aero-core/config/filesystems.php` (publish if needed) — confirm s3 disk shape
- Add: `packages/aero-platform/tests/Unit/Wiring/S3TenancyTest.php`

- [ ] **Step 1: Add s3 to bootstrapper config**

```php
'filesystem' => [
    'suffix_base' => 'tenant',
    'disks' => ['local', 'public', 's3'],
    'root_override' => [
        'local'  => '%storage_path%/app/',
        'public' => '%storage_path%/app/public/',
        // s3 uses prefix not root_override
    ],
],
```

- [ ] **Step 2: Document the per-tenant S3 strategy**

Add a `tenancy.s3_strategy` config:
- `'prefix'` (default) — single bucket, key path = `tenants/{id}/...`
- `'bucket'` — per-tenant bucket (more isolation, more provisioning)

- [ ] **Step 3: Test that S3 disk is in the bootstrapped list**

```php
public function test_s3_disk_is_listed_for_tenancy(): void
{
    $config = require dirname(__DIR__, 3).'/config/tenancy.php';
    $this->assertContains('s3', $config['filesystem']['disks']);
}
```

- [ ] **Step 4: Commit**

---

## Task 6: Fail-closed queue tenancy (D5c)

**Files:**
- Create: `packages/aero-platform/src/Bootstrappers/FailClosedQueueTenancyBootstrapper.php`
- Modify: `packages/aero-platform/config/tenancy.php` — replace stock QueueTenancyBootstrapper

- [ ] **Step 1: Implement the wrapper**

```php
class FailClosedQueueTenancyBootstrapper extends \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper
{
    public function bootstrap(\Stancl\Tenancy\Contracts\Tenant $tenant): void
    {
        // Reject the job if the tenant is suspended, deleted, or has no DB
        $status = $tenant->status ?? null;
        if (in_array($status, ['suspended', 'deleted', 'failed'], true)) {
            throw new \RuntimeException(
                "Refusing to run queue job for tenant {$tenant->id} with status={$status}. ".
                "Mark the job as failed manually if it should retain its slot."
            );
        }

        parent::bootstrap($tenant);
    }
}
```

- [ ] **Step 2: Swap in tenancy config**

```php
'bootstrappers' => [
    \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
    \Aero\Platform\Bootstrappers\FailClosedQueueTenancyBootstrapper::class, // replaces stock
],
```

- [ ] **Step 3: Test + commit**

---

## Task 7: GDPR immediate-purge endpoint (D7)

**Files:**
- Create: `packages/aero-platform/src/Http/Controllers/Admin/TenantForgetController.php`
- Create: `packages/aero-platform/src/Services/TenantForgetService.php`
- Modify: `packages/aero-platform/routes/admin.php` — add the route
- Modify: `packages/aero-platform/config/module.php` — declare `platform.tenants.forget` HRMAC action

- [ ] **Step 1: Service implementation**

```php
class TenantForgetService
{
    public function forget(Tenant $tenant, string $reason, ?int $requestedByUserId): void
    {
        DB::transaction(function () use ($tenant, $reason, $requestedByUserId) {
            // 1. Audit BEFORE deletion (otherwise the audit row referencing the tenant_id is orphaned)
            app(AuditServiceInterface::class)->log(
                event: 'platform.tenant.forgotten',
                action: 'forgotten',
                subject: $tenant,
                description: "GDPR right-to-be-forgotten executed for tenant {$tenant->subdomain}",
                metadata: ['reason' => $reason, 'requested_by' => $requestedByUserId],
            );

            // 2. Drop tenant DB
            $tenant->database()->manager()->deleteDatabase($tenant);

            // 3. Hard-delete the tenant row (not soft-delete — that's the retention path)
            $tenant->forceDelete();
        });
    }
}
```

- [ ] **Step 2: Controller with HRMAC gate**

```php
class TenantForgetController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant, TenantForgetService $service)
    {
        $this->authorize('platform.tenants.forget');

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'confirm' => ['required', 'accepted'],
        ]);

        $service->forget($tenant, $data['reason'], $request->user()?->id);

        return response()->json([
            'message' => "Tenant {$tenant->subdomain} permanently purged. This action is irreversible.",
        ]);
    }
}
```

- [ ] **Step 3: Route + HRMAC declaration**

```php
// routes/admin.php
Route::post('/tenants/{tenant}/forget', TenantForgetController::class)
    ->middleware(['auth:landlord', 'hrmac:platform.tenants.tenant-list.forget'])
    ->name('admin.tenants.forget');
```

- [ ] **Step 4: Add the action to config/module.php tenants component actions** (`['code' => 'forget', 'name' => 'GDPR Right to be Forgotten']`)

- [ ] **Step 5: Tests + commit**

---

## Task 8: FacadeDiscipline budget ratchet (D12)

**Files:**
- Modify: `c:\laragon\www\aeos365\tests\Feature\Wiring\FacadeDisciplineTest.php`

- [ ] **Step 1: Capture current counts**

```bash
cd c:/laragon/www/aeos365
php artisan test --filter=FacadeDisciplineTest 2>&1 | grep -oP '\d+ offender'
```

Note the per-category counts (Cache::, Storage::disk('local'), Session::).

- [ ] **Step 2: Convert to ratchet pattern**

```php
private const BUDGET_CACHE   = 23;   // ← current count
private const BUDGET_STORAGE = 4;    // ← current count
private const BUDGET_SESSION = 0;    // ← current count

public function test_cache_facade_below_budget(): void
{
    $offenders = $this->scan('/\bCache::(get|put|forever|remember|forget|flush|tags)\(/');
    $this->assertLessThanOrEqual(self::BUDGET_CACHE, count($offenders),
        "Cache:: facade usage in feature packages exceeded budget.\n".
        "Budget: ".self::BUDGET_CACHE.", current: ".count($offenders).".\n".
        "Either migrate one to TenantCache (lower the budget) OR don't add new ones.\n".
        "Offenders:\n  ".implode("\n  ", array_slice($offenders, 0, 10))
    );
}
```

Same shape for Storage::disk('local') and Session::.

- [ ] **Step 3: Commit**

---

## Task 9: HRM API v1 — Payslip + Department + Designation (D24)

**Files:**
- Create: `packages/aero-hrm/src/Http/Controllers/Api/PayslipApiController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/Api/DepartmentApiController.php`
- Create: `packages/aero-hrm/src/Http/Controllers/Api/DesignationApiController.php`
- Modify: `packages/aero-hrm/routes/api.php` — add routes

### 9a. PayslipApiController (own-scope by default)

```php
class PayslipApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->firstOrFail();

        $payslips = Payslip::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->paginate($this->boundedPerPage($request, 20, 100))
            ->withQueryString();

        return response()->json([
            'data' => array_map(fn ($p) => $this->transform($p), $payslips->items()),
            'meta' => ['current_page' => $payslips->currentPage(), 'last_page' => $payslips->lastPage(), 'total' => $payslips->total()],
        ]);
    }

    public function show(Payslip $payslip, Request $request): JsonResponse
    {
        $employee = Employee::where('user_id', $request->user()->id)->first();
        if (! $employee || $payslip->employee_id !== $employee->id) {
            $this->authorize('hrm.payroll.payslips.list.view'); // admin override
        }

        return response()->json(['data' => $this->transformDetailed($payslip)]);
    }

    private function transform(Payslip $p): array
    {
        return [
            'id' => $p->id,
            'period' => $p->run?->period,
            'gross' => $p->gross,
            'tax' => $p->tax,
            'deductions' => $p->deductions_total,
            'net' => $p->net,
            'paid_at' => optional($p->run?->paid_at)->toDateString(),
        ];
    }

    private function transformDetailed(Payslip $p): array
    {
        return array_merge($this->transform($p), [
            'line_items' => $p->line_items,
            // bank_account_number stays masked: show only last 4
            'bank_last_four' => $p->bank_account_number ? substr($p->bank_account_number, -4) : null,
        ]);
    }
}
```

### 9b. DepartmentApiController + DesignationApiController

Simple read-only lookups returning all active records (no pagination — these are small dropdown-list datasets).

- [ ] **Step 1-5: Controller + routes + tests + commit per controller**

---

## Task 10: Verification + tag

- [ ] **Step 1: Run all foundation tests**

```bash
cd c:/laragon/www/aeos365
php artisan test
```

- [ ] **Step 2: Verify all audit follow-up tests pass**

- [ ] **Step 3: Tag**

```bash
git tag architecture-audit-followup-complete
```

- [ ] **Step 4: Update EXECUTION_SUMMARY.md** to reference this plan

---

## Self-Review

- ✅ Every ⚠️ decision in the table maps to a task
- ✅ Every ✅ decision is documented as the recorded current state
- ✅ Task ordering minimizes risk: discovery/catalog rework BEFORE the licensing reversal (which depends on it); fail-closed queue + S3 prefix are independent and can interleave
- ✅ GDPR purge endpoint is a real legal requirement, not optional
- ✅ FacadeDiscipline ratchet is consistent with the inline-style ratchet pattern from Plan 06 T1

## Execution Handoff

**Recommended order:**
1. Task 1 (revert monorepo path) — small prep
2. Task 2 (per-tenant catalog) — biggest architectural change; everything else builds on this
3. Task 3 (soft-suspend) — completes the lifecycle from Task 2
4. Task 4 (tier licensing reversal) — cleanup, depends on Task 2's catalog being in place
5. Task 5 (S3 prefix) — independent security fix
6. Task 6 (fail-closed queue) — independent security fix
7. Task 7 (GDPR purge) — independent compliance feature
8. Task 8 (facade ratchet) — independent cleanup
9. Task 9 (HRM API v1 completion) — independent feature
10. Task 10 (verify + tag)

~8-12 engineer-days total. Subagent-driven execution recommended for Tasks 2 + 3 (multi-file architectural changes); inline execution fine for Tasks 1, 5, 6, 8.
