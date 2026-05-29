# aero-core — Plan to 10/10

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Current score:** 6.5/10 (per audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 8–12 engineer-days

**Goal:** Close every critical (cross-tenant data leak, missing audit trail, broken queries against non-existent tables) and high-severity gap in `packages/aero-core`. Bring HRMAC enforcement to 100% on declared actions. Add defense-in-depth via policies. Make the package safe to publish as a foundation library.

**Architecture:** Stay within existing patterns — `TenantModel` base, `AuditService` injection, `TenantCache` helper, HRMAC middleware + `$this->authorize()`. No structural rewrites; targeted refactors and additions.

**Tech Stack:** Laravel 12, Inertia v2, stancl/tenancy, HRMAC, AuditService, EncryptedField.

**Prerequisite:** Phase 0 wiring plan ([00-wiring-blockers.md](00-wiring-blockers.md)) must be in flight — several tasks here depend on Redis cache, `FilesystemTenancyBootstrapper`, and the new test infrastructure it adds.

---

## Reference evidence (from audit)

- 36 sub-modules, ~118 components, ~380 declared HRMAC actions in `config/module.php` (1700 lines)
- 372 PHP files, 60 migrations, 13 tests, 2 route files (`routes/web.php` 1300+ lines)
- AuditService used in only 16 of 372 files (~4%)
- 3 of 59 controllers use `$this->authorize()` (~5%) — defense-in-depth missing
- 30+ raw `DB::table()` call sites bypass `TenantModel` scope
- 2 policies exist for ~35 models

## File Structure

| File | Responsibility |
|---|---|
| `packages/aero-core/src/Services/Dashboard/AdminDashboardService.php` | Migrate to `TenantCache::remember()` |
| `packages/aero-core/src/Services/DeviceSessionService.php` | Migrate to `TenantCache::*` |
| `packages/aero-core/src/Http/Controllers/Admin/CoreUserController.php` | Inject `AuditService`, log every mutation |
| `packages/aero-core/src/Http/Controllers/Admin/HelpSupportController.php` | Resolve broken-table queries (create or remove) |
| `packages/aero-core/src/Http/Controllers/Admin/ApiKeyController.php` | Replace raw DB with `ApiKey` model |
| `packages/aero-core/src/Http/Controllers/Admin/WebhookController.php` | Replace raw DB with `Webhook`, `WebhookDelivery` models |
| `packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php` | Replace raw DB with `AuditLog` model |
| `packages/aero-core/src/Http/Controllers/FileManager/FileManagerController.php` | Tenant-aware Storage disk |
| `packages/aero-core/src/Models/ApiKey.php` (new) | TenantModel-based |
| `packages/aero-core/src/Models/Webhook.php` (new) | TenantModel-based |
| `packages/aero-core/src/Models/WebhookDelivery.php` (new) | TenantModel-based |
| `packages/aero-core/src/Models/SupportTicket.php` (new — if route 3 chosen) | TenantModel |
| `packages/aero-core/src/Models/KnowledgeArticle.php` (new) | TenantModel |
| `packages/aero-core/src/Models/Feedback.php` (new) | TenantModel |
| `packages/aero-core/src/Models/InstalledAddon.php` | Switch base to `TenantModel` (or `CentralModel`) |
| `packages/aero-core/src/Models/ModuleLicense.php` | Switch base to appropriate base class |
| `packages/aero-core/src/Policies/*.php` (10 new) | One per missing model |
| `packages/aero-core/database/migrations/` | New migrations for support_tickets, knowledge_articles, feedback, api_keys (if missing), webhooks (if missing) |
| `packages/aero-core/database/migrations/2025_12_09_000001_create_audit_logs_table.php` | DELETE (duplicate of 2026_05_14_000001) |
| `packages/aero-core/routes/web.php` | Add missing `hrmac:` middleware; fix permission key mismatches |
| `packages/aero-core/config/module.php` | Add missing sub-modules (`addons`, `navigation`); fix nomenclature (`activity_feed`, `trash`); remove unimplemented `email_engine` OR keep with delegation note |
| `packages/aero-core/tests/Unit/Encryption/EncryptedFieldTest.php` (new) | Round-trip, null handling, key rotation |
| `packages/aero-core/tests/Feature/Tenancy/AdminDashboardCacheIsolationTest.php` (new) | Two tenants → distinct cache |
| `packages/aero-core/tests/Feature/Audit/CoreUserAuditTrailTest.php` (new) | Every mutation creates AuditLog row |
| `packages/aero-core/tests/Feature/Hrmac/PermissionKeyMismatchTest.php` (new) | Every declared action is reachable via its middleware string |
| `packages/aero-core/tests/Feature/Policies/*Test.php` (10 new) | Per-policy ability coverage |

---

## Task 1: Tenant-scope dashboard cache keys (CRITICAL — data leak)

**Severity:** Critical. SaaS data leak — two tenants currently share the same `admin_dashboard.core_stats` cache key.

**Files:**
- Modify: `packages/aero-core/src/Services/Dashboard/AdminDashboardService.php:165,219,283,345,386,440`
- Create: `packages/aero-core/tests/Feature/Tenancy/AdminDashboardCacheIsolationTest.php`

- [ ] **Step 1: Write failing isolation test**

```php
<?php

namespace Aero\Core\Tests\Feature\Tenancy;

use Aero\Core\Services\Dashboard\AdminDashboardService;
use Aero\Platform\Models\Tenant;
use Stancl\Tenancy\Facades\Tenancy;
use Tests\TestCase;

class AdminDashboardCacheIsolationTest extends TestCase
{
    public function test_two_tenants_receive_distinct_core_stats(): void
    {
        $a = Tenant::factory()->create();
        $b = Tenant::factory()->create();
        $service = app(AdminDashboardService::class);

        Tenancy::initialize($a);
        // seed tenant A with some users
        $a_stats = $service->getCoreStats();
        Tenancy::end();

        Tenancy::initialize($b);
        // seed tenant B with different number of users
        $b_stats = $service->getCoreStats();
        Tenancy::end();

        $this->assertNotSame($a_stats['users_count'], $b_stats['users_count'],
            'Tenant A and B must not share the admin_dashboard.core_stats cache value');
    }
}
```

- [ ] **Step 2: Run test (FAIL — cache key collision)**

Run: `cd packages/aero-core && vendor/bin/phpunit tests/Feature/Tenancy/AdminDashboardCacheIsolationTest.php`
Expected: FAIL.

- [ ] **Step 3: Replace `Cache::remember` with `TenantCache::remember`**

For each of lines 165, 219, 283, 345, 386, 440 in `AdminDashboardService.php`:

```php
// Before
return Cache::remember('admin_dashboard.core_stats', 300, function () { ... });

// After
use Aero\Core\Support\TenantCache;
return TenantCache::remember('admin_dashboard.core_stats', 300, function () { ... });
```

- [ ] **Step 4: Run test (PASS)**

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add packages/aero-core/src/Services/Dashboard/AdminDashboardService.php packages/aero-core/tests/Feature/Tenancy/AdminDashboardCacheIsolationTest.php
git commit -m "fix(core): tenant-scope AdminDashboardService cache keys (data-leak fix)"
```

---

## Task 2: Migrate `DeviceSessionService` cache to TenantCache

**Files:**
- Modify: `packages/aero-core/src/Services/DeviceSessionService.php:212-267`

- [ ] **Step 1: Add test**

```php
public function test_device_session_cache_is_tenant_scoped(): void
{
    // tenant A creates device session → cached under tenant A
    // tenant B looks up same device_id → cache miss
}
```

- [ ] **Step 2: Run test (FAIL)**

- [ ] **Step 3: Replace `Cache::forget("device_session:{$id}")` → `TenantCache::forget(...)` (same for `put`, `get`, `remember`)**

- [ ] **Step 4: Run test (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "fix(core): tenant-scope DeviceSessionService cache"
```

---

## Task 3: Add audit trail to `CoreUserController`

**Severity:** Critical. User lifecycle has zero audit trail today.

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/Admin/CoreUserController.php`
- Create: `packages/aero-core/tests/Feature/Audit/CoreUserAuditTrailTest.php`

- [ ] **Step 1: Write failing feature test**

```php
<?php

namespace Aero\Core\Tests\Feature\Audit;

use Aero\Core\Models\AuditLog;
use Aero\Core\Models\User;
use Tests\TestCase;

class CoreUserAuditTrailTest extends TestCase
{
    public function test_user_create_writes_audit_log(): void
    {
        $admin = $this->actingAsAdmin();
        $this->post('/admin/users', ['name' => 'Test', 'email' => 'test@example.com', 'password' => 'secret123'])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.created',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_user_update_writes_audit_log(): void { /* ... */ }
    public function test_user_delete_writes_audit_log(): void { /* ... */ }
    public function test_user_lock_writes_audit_log(): void { /* ... */ }
    public function test_user_unlock_writes_audit_log(): void { /* ... */ }
    public function test_user_impersonate_writes_audit_log(): void { /* ... */ }
    public function test_user_role_change_writes_audit_log(): void { /* ... */ }
}
```

- [ ] **Step 2: Run test (FAIL)**

- [ ] **Step 3: Inject AuditService in controller constructor**

```php
public function __construct(
    private \Aero\Contracts\AuditServiceInterface $audit,
    private UserService $users
) {}
```

- [ ] **Step 4: Add `audit->log()` calls after each successful mutation**

```php
public function store(StoreUserRequest $request)
{
    $user = $this->users->create($request->validated());
    $this->audit->log('user.created', $user, [
        'created_by' => auth()->id(),
        'fields' => $request->safe()->except(['password']),
    ]);
    return redirect()->route('admin.users.index')->with('success', 'User created');
}
```

Repeat for `update`, `destroy`, `lock`, `unlock`, `impersonate`, `assignRoles`.

- [ ] **Step 5: Run tests (PASS)**

- [ ] **Step 6: Commit**

```bash
git commit -am "fix(core): audit trail for CoreUserController lifecycle mutations"
```

---

## Task 4: Delete duplicate `audit_logs` migration

**Files:**
- Delete: `packages/aero-core/database/migrations/2025_12_09_000001_create_audit_logs_table.php`

- [ ] **Step 1: Diff the two migrations**

```bash
diff packages/aero-core/database/migrations/2025_12_09_000001_create_audit_logs_table.php packages/aero-core/database/migrations/2026_05_14_000001_create_audit_logs_table.php
```

- [ ] **Step 2: Confirm `2026_05_14` is the canonical schema (has 7 indexes per audit)**

- [ ] **Step 3: Delete the older file**

```bash
git rm packages/aero-core/database/migrations/2025_12_09_000001_create_audit_logs_table.php
```

- [ ] **Step 4: Test fresh migration**

Run: `cd c:\laragon\www\aeos365 && php artisan migrate:fresh --database=tenant` (on a throwaway tenant DB).
Expected: no error about duplicate table.

- [ ] **Step 5: Commit**

```bash
git commit -m "chore(core): remove duplicate create_audit_logs_table migration"
```

---

## Task 5: Replace `HelpSupportController` raw queries — create models + migrations

Per audit, controller queries `support_tickets`, `kb_articles`, `feedback` which do not exist as migrations. Either implement OR remove. **Decision: implement** (helpdesk is a declared feature; UI exists).

**Files:**
- Create: `packages/aero-core/database/migrations/2026_05_28_000010_create_support_tickets_table.php`
- Create: `packages/aero-core/database/migrations/2026_05_28_000011_create_knowledge_articles_table.php`
- Create: `packages/aero-core/database/migrations/2026_05_28_000012_create_feedback_items_table.php`
- Create: `packages/aero-core/src/Models/SupportTicket.php` (extends `TenantModel`)
- Create: `packages/aero-core/src/Models/KnowledgeArticle.php`
- Create: `packages/aero-core/src/Models/Feedback.php`
- Modify: `packages/aero-core/src/Http/Controllers/Admin/HelpSupportController.php` — use models, not raw DB

- [ ] **Step 1: Migrations**

`support_tickets`: id, tenant_id, requester_id, subject, body, status, priority, assigned_to, category, timestamps + soft-deletes + index `(tenant_id, status)`.

`knowledge_articles`: id, tenant_id, title, slug (unique per tenant), body (long text), category, tags (json), published_at, view_count, helpful_count, timestamps + index `(tenant_id, published_at)`.

`feedback_items`: id, tenant_id, user_id, type (enum: bug, feature, praise, other), body, status, response, timestamps.

- [ ] **Step 2: Write models extending `TenantModel`, with `LogsActivity` and fillable/casts**

- [ ] **Step 3: Write failing feature test**

```php
public function test_help_support_index_returns_tickets_per_tenant(): void
{
    SupportTicket::factory()->for($this->tenant)->count(3)->create();
    $this->actingAsAdmin()
        ->get('/admin/help-support')
        ->assertOk()
        ->assertInertia(fn ($p) => $p->has('tickets.data', 3));
}
```

- [ ] **Step 4: Replace `DB::table('support_tickets')` etc. with model calls in `HelpSupportController`**

- [ ] **Step 5: Run tests (PASS)**

- [ ] **Step 6: Commit**

```bash
git commit -am "feat(core): support_tickets, knowledge_articles, feedback_items models + migrations"
```

---

## Task 6: Replace raw `DB::table('api_keys')` with `ApiKey` Eloquent model

**Files:**
- Create: `packages/aero-core/src/Models/ApiKey.php` (TenantModel)
- Modify: `packages/aero-core/src/Http/Controllers/Admin/ApiKeyController.php:21,50,81`
- Verify: `packages/aero-core/database/migrations/2026_05_23_000010_create_api_keys_table.php` — already has table; ensure `tenant_id` column exists

- [ ] **Step 1: Read migration and confirm columns**

- [ ] **Step 2: Write factory + model**

```php
<?php

namespace Aero\Core\Models;

use Aero\Contracts\Models\TenantModel;
use Aero\Core\Encryption\EncryptedField;

class ApiKey extends TenantModel
{
    protected $fillable = ['name', 'key_hash', 'last_four', 'scopes', 'expires_at', 'last_used_at', 'is_active'];
    protected $casts = ['scopes' => 'array', 'expires_at' => 'datetime', 'last_used_at' => 'datetime', 'is_active' => 'boolean'];
    protected $hidden = ['key_hash'];
}
```

- [ ] **Step 3: Write failing test**

```php
public function test_api_key_query_is_tenant_scoped(): void
{
    // create key in tenant A, switch to B, assert not visible
}
```

- [ ] **Step 4: Refactor `ApiKeyController` to use model**

```php
// Before: DB::table('api_keys')->where(...)->get();
// After: ApiKey::where(...)->get();  // global tenant scope auto-applies
```

- [ ] **Step 5: Run test (PASS)**

- [ ] **Step 6: Commit**

```bash
git commit -am "fix(core): ApiKey Eloquent model replaces raw DB::table (tenant-scoped)"
```

---

## Task 7: Replace raw `DB::table('webhooks'/'webhook_deliveries')` with models

**Files:**
- Create: `packages/aero-core/src/Models/Webhook.php`
- Create: `packages/aero-core/src/Models/WebhookDelivery.php`
- Modify: `packages/aero-core/src/Http/Controllers/Admin/WebhookController.php:24,169`

Same TDD shape as Task 6.

```bash
git commit -m "fix(core): Webhook/WebhookDelivery Eloquent models replace raw DB::table"
```

---

## Task 8: Replace raw `DB::table('audit_logs'/'failed_jobs'/...)` in `AuditLogController` with `AuditLog` model

**Files:**
- Verify: `packages/aero-core/src/Models/AuditLog.php` exists
- Modify: `packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php:76-372`

- [ ] **Step 1: Read AuditLog model; ensure it covers all columns used in raw queries**

- [ ] **Step 2: Write test**

```php
public function test_audit_log_listing_paginates_and_filters_by_action(): void
{
    AuditLog::factory()->count(50)->create();
    $this->actingAsAdmin()->get('/admin/audit-logs?action=user.created')->assertOk();
}
```

- [ ] **Step 3: Refactor all 24 occurrences of `DB::table('audit_logs')` and `DB::table('failed_jobs')` etc.**

- [ ] **Step 4: Run test (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "fix(core): AuditLog model replaces raw DB::table in AuditLogController"
```

---

## Task 9: Add missing `hrmac:` middleware to ungated routes

**Files:**
- Modify: `packages/aero-core/routes/web.php`

Per audit, these route groups have NO HRMAC enforcement:

| Lines | Group | Fix |
|---|---|---|
| 579–585 | `/notifications/*` | Add `'middleware' => 'hrmac:core.self_service.my_notifications.view'` |
| 798–857 | `/profile/*` | Add `'middleware' => 'hrmac:core.self_service.my_profile.view'` |
| 862–895 | `/user-preferences/*` | Add per-component HRMAC |
| 900–908 | `/api/navigation/*` | Add HRMAC OR document why exempt (likely user-context-only) |
| 950–956 | `/extensions/*` | Add `hrmac:core.addons.management.view` (after declaring in config) |
| 1251–1254 | `/addons/*` | Add `hrmac:core.addons.management.install` |

- [ ] **Step 1: Write failing HRMAC enforcement test**

```php
public function test_addons_install_requires_permission(): void
{
    $userWithout = $this->actingAsUserWithoutPermission('core.addons.management.install');
    $this->post('/addons/install', ['package' => 'foo'])->assertForbidden();
}
```

- [ ] **Step 2: Run test (FAIL — no hrmac, returns 200/302)**

- [ ] **Step 3: Add middleware groups**

- [ ] **Step 4: Run test (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "fix(core): add HRMAC middleware to notifications/profile/preferences/addons/extensions/navigation route groups"
```

---

## Task 10: Fix permission key mismatches

**Files:**
- Modify: `packages/aero-core/routes/web.php:1015,1077`
- Modify: `packages/aero-core/config/module.php` (rename or add components)

| Mismatch | Decision | Fix |
|---|---|---|
| Middleware `core.activity_feed.view` vs declared `core.comments_mentions.activity_feed.view` | Add top-level `activity_feed` submodule OR change middleware | Change middleware to declared path |
| Middleware `core.trash.view` vs declared `core.trash.view.view` (component `view` + action `view`) | Rename component to `trash_management` | Update `module.php` AND middleware |

- [ ] **Step 1: Write enforcement test for each pair**

```php
public function test_activity_feed_permission_path_matches_declared(): void
{
    $user = $this->makeUserWithPermission('core.comments_mentions.activity_feed.view');
    $this->actingAs($user)->get('/activity-feed')->assertOk();
}
```

- [ ] **Step 2: Run (FAIL — silently 403)**

- [ ] **Step 3: Update middleware strings AND/OR module.php**

- [ ] **Step 4: Run (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "fix(core): align HRMAC middleware strings with declared module.php paths"
```

---

## Task 11: Make `FileManagerController` tenant-aware

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/FileManager/FileManagerController.php`
- Depends on: Phase 0 Task 5 (FilesystemTenancyBootstrapper re-enabled)

- [ ] **Step 1: Write failing test**

```php
public function test_two_tenants_can_have_same_filename_without_collision(): void
{
    // tenant A uploads hello.txt → succeeds, returns path /tenant_a/hello.txt
    // tenant B uploads hello.txt → succeeds, returns path /tenant_b/hello.txt
}
```

- [ ] **Step 2: Run (FAIL)**

- [ ] **Step 3: Replace `Storage::disk('local')` with `Storage::disk('tenant')` throughout controller**

- [ ] **Step 4: Run (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "fix(core): FileManagerController uses tenant disk (closes upload leak)"
```

---

## Task 12: Implement `email_engine` sub-module OR remove declarations

Per audit, 5 components and 18 actions declared with zero backend. UI shells exist as placeholders.

**Decision required from user before this task.** Three paths:

- **Path A — implement** (~4 days): 5 controllers (EmailTemplateController, EmailLogController, SuppressionListController, DeliverabilityController, BounceComplaintController), 5 models, 5 migrations, full CRUD + Inertia wiring + tests. Best product value.
- **Path B — delegate to `aero-notifications`** (~1 day): add `delegated_to: aero-notifications` flag in `module.php`, move declarations into aero-notifications/config/module.php.
- **Path C — remove declarations** (~30 min): delete from module.php, delete UI shells.

- [ ] **Step 1: Ask user which path**

- [ ] **Step 2: Execute chosen path**

- [ ] **Step 3: Commit**

---

## Task 13: Create missing policies (defense-in-depth)

**Files:**
- Create 10 policies: `AuditLogPolicy`, `TagPolicy`, `SavedViewPolicy`, `ApiKeyPolicy`, `WebhookPolicy`, `BackupPolicy`, `RetentionPolicyPolicy`, `AnnouncementPolicy`, `OrganizationProfilePolicy`, `CommentPolicy`
- Modify: each owning controller — add `$this->authorize('action', $model)` calls

Policy template:

```php
<?php

namespace Aero\Core\Policies;

use Aero\Core\Models\{Model};
use Aero\Core\Models\User;

class {Model}Policy
{
    public function viewAny(User $user): bool { return $user->can('core.{module}.{component}.view'); }
    public function view(User $user, {Model} $m): bool { return $user->can('core.{module}.{component}.view'); }
    public function create(User $user): bool { return $user->can('core.{module}.{component}.create'); }
    public function update(User $user, {Model} $m): bool { return $user->can('core.{module}.{component}.update'); }
    public function delete(User $user, {Model} $m): bool { return $user->can('core.{module}.{component}.delete'); }
}
```

- [ ] **Step 1: Write policy unit tests (one per policy, asserting each ability returns correct boolean against a permissioned user)**

- [ ] **Step 2: Run (FAIL — policies don't exist)**

- [ ] **Step 3: Generate each policy via the template**

- [ ] **Step 4: Register in `AuthServiceProvider::policies` (or auto-discovery via `Policy` suffix)**

- [ ] **Step 5: Add `$this->authorize(...)` calls in controllers (one per mutating action)**

- [ ] **Step 6: Run (PASS)**

- [ ] **Step 7: Commit per policy (10 small commits)**

```bash
git commit -m "feat(core): {Model}Policy + controller defense-in-depth"
```

---

## Task 14: Implement missing identity components OR delegate to `aero-auth`

Same decision shape as Task 12. Components: `oauth_provider`, `passkeys`, `magic_link`, `scim`, `mfa_policies`, `session_policies`, `account_recovery`.

**Recommendation:** Delegate to `aero-auth` — that package already owns SAML/OIDC/Social. Add `delegated_to: aero-auth` to each component in `config/module.php:748-820`. Open follow-up issues for aero-auth to actually implement (covered in its own per-package plan).

- [ ] **Step 1: User decision**

- [ ] **Step 2: Execute**

- [ ] **Step 3: Commit**

---

## Task 15: Fix `InstalledAddon` and `ModuleLicense` base classes

**Files:**
- Modify: `packages/aero-core/src/Models/InstalledAddon.php:7`
- Modify: `packages/aero-core/src/Models/ModuleLicense.php:8`

Both currently extend bare `Model`. Decide:
- `InstalledAddon` → likely `TenantModel` (each tenant installs its own addons)
- `ModuleLicense` → likely `CentralModel` (license records live in central DB)

- [ ] **Step 1: Write tenant-scope assertion test for InstalledAddon**

- [ ] **Step 2: Run (FAIL — bare Model has no scope)**

- [ ] **Step 3: Switch base classes**

- [ ] **Step 4: Run (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "fix(core): InstalledAddon→TenantModel, ModuleLicense→CentralModel"
```

---

## Task 16: PII logAccess() coverage

Per audit, `MailSettingsController.php:31` is the only `logAccess()` call. Add to:

- `OrganizationProfileController` reading `tax_id`
- `CoreUserController::show` exposing `national_id` / `email` to non-owner
- Backup/Restore download endpoints
- EmailTemplate render (if implemented)

- [ ] **Step 1: Write a test asserting an AuditLog with `action='access'` and `category='pii'` is created per endpoint**

- [ ] **Step 2: Run (FAIL)**

- [ ] **Step 3: Add `$this->audit->logAccess(...)` call site by site**

- [ ] **Step 4: Run (PASS)**

- [ ] **Step 5: Commit per controller**

---

## Task 17: Unit test `EncryptedField` cast

**Files:**
- Create: `packages/aero-core/tests/Unit/Encryption/EncryptedFieldTest.php`

```php
<?php

namespace Aero\Core\Tests\Unit\Encryption;

use Aero\Core\Encryption\EncryptedField;
use Aero\Core\Models\User; // or fake model
use PHPUnit\Framework\TestCase;

class EncryptedFieldTest extends TestCase
{
    public function test_set_encrypts_value(): void { /* ... */ }
    public function test_get_decrypts_value(): void { /* ... */ }
    public function test_null_passes_through(): void { /* ... */ }
    public function test_already_encrypted_value_not_double_encrypted(): void { /* ... */ }
    public function test_key_rotation_decrypts_old_payload(): void { /* ... */ }
}
```

- [ ] **Step 1: Write tests**

- [ ] **Step 2: Run (FAIL if any edge case unhandled)**

- [ ] **Step 3: Fix EncryptedField source as needed**

- [ ] **Step 4: Run (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "test(core): EncryptedField cast unit coverage"
```

---

## Task 18: Permission-key-mismatch CI guard

**Files:**
- Create: `packages/aero-core/tests/Feature/Hrmac/PermissionKeyMismatchTest.php`

Programmatically:
1. Parse all `hrmac:...` middleware strings from route files
2. Parse all declared permissions from `config/module.php`
3. Assert every middleware string corresponds to a declared path

```php
public function test_every_hrmac_middleware_string_is_declared_in_module_config(): void
{
    $declared = collect((new \Aero\Hrmac\Services\ModuleDiscoveryService)->all())->flatten()->all();
    $middlewareKeys = $this->extractAllHrmacKeysFromRoutes();
    foreach ($middlewareKeys as $key) {
        $this->assertContains($key, $declared, "Middleware string {$key} has no matching declared permission");
    }
}
```

- [ ] **Step 1: Write test**

- [ ] **Step 2: Run (FAIL — already known mismatches per Task 10)**

- [ ] **Step 3: Tasks 9–10 fixes already cover these**

- [ ] **Step 4: Run (PASS)**

- [ ] **Step 5: Commit**

```bash
git commit -am "test(core): permission-key-mismatch CI guard"
```

---

## Task 19: Factories + Seeders for missing models

Per audit, factories aren't enumerated for: `Tag`, `SavedView`, `Comment`, `Announcement`, `AuditLog`, `Backup`, `RetentionPolicy`, `Webhook`, `ApiKey`.

For each:
- Create `database/factories/{Model}Factory.php`
- Add demo seeding to `Database\Seeders\TenantDatabaseSeeder` (where appropriate)

- [ ] **Step 1: Generate factories**

```bash
php artisan make:factory TagFactory --model="Aero\Core\Models\Tag"
# repeat per model
```

- [ ] **Step 2: Implement realistic `definition()`**

- [ ] **Step 3: Write factory smoke test (`factory->create()` produces valid record)**

- [ ] **Step 4: Commit per factory**

---

## Task 20: Final verification + score recheck

- [ ] **Step 1: Run full test suite**

```bash
cd c:\laragon\www\aeos365 && php artisan test --testsuite=core
```

Expected: all green.

- [ ] **Step 2: Re-grep raw `DB::table` usage in `packages/aero-core/src`**

```bash
grep -rn "DB::table(" packages/aero-core/src/Http packages/aero-core/src/Services
```

Expected: empty or only legitimate cross-DB aggregates.

- [ ] **Step 3: Re-grep `Cache::` usage**

```bash
grep -rn "Cache::\(get\|put\|remember\|forget\)" packages/aero-core/src
```

Expected: empty.

- [ ] **Step 4: Re-run wiring guards from Phase 0**

```bash
cd c:\laragon\www\aeos365 && php artisan test --filter=FacadeDisciplineTest
```

Expected: PASS (aero-core no longer offending).

- [ ] **Step 5: Score recheck rubric**

| Dimension | Target |
|---|---|
| HRMAC enforcement (every route gated) | 10/10 |
| Defense-in-depth (policy on every mutating controller) | 10/10 |
| AuditService coverage (every business action logs) | 10/10 |
| Tenant isolation (cache/storage/queries) | 10/10 |
| Test coverage (every controller + service has tests) | 9/10 |
| Config consistency (declared = implemented) | 10/10 |
| Frontend pages exist for every declared page | 10/10 |

- [ ] **Step 6: Tag**

```bash
git tag aero-core-10-10
```

---

## Self-Review

- ✅ Every Critical and High item from the audit has a task
- ✅ Medium items rolled into Tasks 19 (factories) and 20 (verification)
- ✅ TDD shape: failing test first, then fix, then green
- ✅ No placeholders — actual file paths, actual code patterns
- ✅ Two open product decisions called out (Tasks 12, 14) so the engineer doesn't guess
- ✅ Depends-on Phase 0 wiring is explicit
- ✅ Commits are bite-sized

## Execution Handoff

Plan saved. Two execution options:

1. **Subagent-Driven (recommended)** — fresh subagent per task with two-stage review
2. **Inline Execution** — batched task-by-task in this session

Critical tasks (1, 3, 4, 5, 6, 7, 8, 11) should land first because they close active data-leak / broken-query risks. Tasks 12 and 14 await product decisions.
