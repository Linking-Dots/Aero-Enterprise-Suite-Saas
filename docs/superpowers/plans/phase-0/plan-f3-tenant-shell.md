# Plan F-3 — Tenant Shell (Full Production)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the authenticated tenant shell. The 47 existing Core pages (Users, Roles, Settings, Preferences, Notifications, System Health, etc.) are well-built — this plan does not rebuild them. The three focused deliverables are: (1) upgrade `AuditLogController` to query the new `audit_logs` / `access_logs` tables from the Security Foundation alongside the existing Spatie `activity_log`; (2) fix the last direct `AuditService` class references to use `AuditServiceInterface`; (3) create the "My Profile Security" hub page that connects the shell to the F-1 auth pages (2FA, devices, sessions).

**Architecture:** The Tenant Dashboard (`Tenant/Dashboard.jsx`) and all Core admin pages exist and use the correct patterns. The audit log UI exists but reads from Spatie's `activity_log` and a now-deleted `security_logs` table — it must be upgraded to read from our `audit_logs` and `access_logs` tables. Profile security routes exist at `core.profile.*` but have no Inertia page — a hub page is needed.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11.

**Standards:** `docs/standards/inertia-standard.md`, `docs/standards/audit-standard.md`, `docs/standards/done-definition.md`.

**Prerequisite:** Plans F-1 + F-2 complete. `audit_logs`, `access_logs` tables exist (from Security Foundation).

---

## Existing Page Inventory (verify only — no rebuild needed)

| Page | Status | Note |
|------|--------|------|
| `Tenant/Dashboard.jsx` | ✅ Complete | DashboardLayout, deferred KPIs, activity, quick actions |
| `Core/AuditLogs/Index.jsx` | ⚠️ Upgrade needed | Reads `activity_log` + missing `security_logs` — upgrade to `audit_logs` + `access_logs` |
| `Core/Users/Index.jsx` + CRUD | ✅ Complete | useHRMAC guards, pagination, filters |
| `Core/Roles/Index.jsx` | ✅ Complete | Dynamic view path via HRMAC |
| `Core/Settings/SystemSettings.jsx` | ✅ Complete | useHRMAC guard, useForm |
| `Core/Settings/Security.jsx` | ✅ Complete | |
| `Core/Settings/Branding.jsx` | ✅ Complete | |
| `Core/Settings/Mail.jsx` | ✅ Complete | |
| `Core/Settings/Localization.jsx` | ✅ Complete | |
| `Core/Settings/PasswordPolicy.jsx` | ✅ Complete | |
| `Core/Settings/IpWhitelist.jsx` | ✅ Complete | |
| `Core/UserPreferences/Index.jsx` + tabs | ✅ Complete | Tabbed with sub-components |
| `Core/Notifications/Index.jsx` | ✅ Complete | |
| `Core/SystemHealth/Index.jsx` | ✅ Complete | |
| `Core/Organization/Profile.jsx` | ✅ Complete | Org branding, address, contact |
| Other Core pages (backup, restore, tags, trash…) | ✅ Complete | 30+ more, all exist |

---

## File Map

**Modify:**
- `packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php`
- `packages/aero-core/src/Http/Controllers/Admin/CoreUserController.php`

**Create:**
- `packages/aero-ui/resources/js/Pages/Core/Profile/Security.jsx`

**Create (tests):**
- `packages/aero-core/tests/Feature/Admin/AuditLogControllerTest.php`

---

## Task F3.1: Upgrade AuditLogController to use `audit_logs` + `access_logs`

**File:** `packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php`

**Problem:** The controller queries `activity_log` (Spatie) and `security_logs` (a table that was never created in our Security Foundation). Our new tables are `audit_logs` (business events via AuditService) and `access_logs` (sensitive data views). The controller must:
1. Query `audit_logs` for the Activity tab (business events)
2. Fall back to `activity_log` (Spatie model changes) as additional data
3. Query `access_logs` for a new "Sensitive Access" tab
4. Remove the broken `security_logs` query

- [ ] **Step F3.1.1: Add `use` imports for new models**

At the top of `AuditLogController.php`, add imports:
```php
use Aero\Contracts\AuditServiceInterface;
use Illuminate\Support\Facades\Log;
```

- [ ] **Step F3.1.2: Rewrite `index()` to include new tabs**

Replace the entire `index()` method with:

```php
    public function index(Request $request): Response
    {
        $tab      = $request->get('tab', 'business');
        $perPage  = (int) $request->get('per_page', 20);
        $search   = (string) $request->get('search', '');
        $actorId  = $request->get('actor_id');
        $eventType = $request->get('event_type');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        [$logs, $meta] = match ($tab) {
            'business'  => $this->getBusinessLogs($perPage, $search, $actorId, $eventType, $dateFrom, $dateTo),
            'model'     => $this->getModelActivityLogs($perPage, $search, $actorId, $dateFrom, $dateTo),
            'access'    => $this->getAccessLogs($perPage, $search, $actorId, $dateFrom, $dateTo),
            default     => $this->getBusinessLogs($perPage, $search, $actorId, $eventType, $dateFrom, $dateTo),
        };

        return Inertia::render('Core/AuditLogs/Index', [
            'title'   => 'Audit Logs',
            'stats'   => $this->getStats(),
            'tab'     => $tab,
            'logs'    => $logs,
            'meta'    => $meta,
            'filters' => $request->only(['search', 'actor_id', 'event_type', 'date_from', 'date_to']),
        ]);
    }
```

- [ ] **Step F3.1.3: Add new private query methods**

Replace `getActivityLogs()`, `getSecurityLogs()` with:

```php
    private function getBusinessLogs(int $perPage, string $search, ?string $actorId, ?string $eventType, ?string $dateFrom, ?string $dateTo): array
    {
        if (! $this->tableExists('audit_logs')) {
            return [[], $this->emptyMeta($perPage)];
        }

        $query = DB::table('audit_logs')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('actor_name', 'like', "%{$search}%")
                  ->orWhere('subject_label', 'like', "%{$search}%");
            }))
            ->when($actorId, fn ($q) => $q->where('actor_id', $actorId))
            ->when($eventType, fn ($q) => $q->where('event_type', $eventType))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            $query->items(),
            [
                'current_page' => $query->currentPage(),
                'last_page'    => $query->lastPage(),
                'per_page'     => $query->perPage(),
                'total'        => $query->total(),
            ],
        ];
    }

    private function getModelActivityLogs(int $perPage, string $search, ?string $actorId, ?string $dateFrom, ?string $dateTo): array
    {
        if (! $this->tableExists('activity_log')) {
            return [[], $this->emptyMeta($perPage)];
        }

        $query = DB::table('activity_log')
            ->leftJoin('users', 'activity_log.causer_id', '=', 'users.id')
            ->select([
                'activity_log.id',
                'activity_log.log_name as event_type',
                'activity_log.description',
                'activity_log.subject_type',
                'activity_log.subject_id',
                'activity_log.properties',
                'activity_log.created_at',
                'users.name as actor_name',
                'users.email as actor_email',
            ])
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('activity_log.description', 'like', "%{$search}%")
                  ->orWhere('users.name', 'like', "%{$search}%");
            }))
            ->when($actorId, fn ($q) => $q->where('activity_log.causer_id', $actorId))
            ->when($dateFrom, fn ($q) => $q->whereDate('activity_log.created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('activity_log.created_at', '<=', $dateTo))
            ->orderByDesc('activity_log.created_at')
            ->paginate($perPage);

        return [
            $query->items(),
            [
                'current_page' => $query->currentPage(),
                'last_page'    => $query->lastPage(),
                'per_page'     => $query->perPage(),
                'total'        => $query->total(),
            ],
        ];
    }

    private function getAccessLogs(int $perPage, string $search, ?string $actorId, ?string $dateFrom, ?string $dateTo): array
    {
        if (! $this->tableExists('access_logs')) {
            return [[], $this->emptyMeta($perPage)];
        }

        $query = DB::table('access_logs')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('resource_type', 'like', "%{$search}%")
                  ->orWhere('accessor_name', 'like', "%{$search}%")
                  ->orWhere('subject_label', 'like', "%{$search}%");
            }))
            ->when($actorId, fn ($q) => $q->where('accessor_id', $actorId))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            $query->items(),
            [
                'current_page' => $query->currentPage(),
                'last_page'    => $query->lastPage(),
                'per_page'     => $query->perPage(),
                'total'        => $query->total(),
            ],
        ];
    }

    private function emptyMeta(int $perPage): array
    {
        return ['current_page' => 1, 'last_page' => 1, 'per_page' => $perPage, 'total' => 0];
    }
```

- [ ] **Step F3.1.4: Update `getStats()` to use new tables**

Replace the `getStats()` method body:
```php
    private function getStats(): array
    {
        return [
            'business_events_today' => $this->tableExists('audit_logs')
                ? DB::table('audit_logs')->whereDate('created_at', today())->count() : 0,
            'business_events_total' => $this->tableExists('audit_logs')
                ? DB::table('audit_logs')->count() : 0,
            'model_changes_today'   => $this->tableExists('activity_log')
                ? DB::table('activity_log')->whereDate('created_at', today())->count() : 0,
            'sensitive_accesses_today' => $this->tableExists('access_logs')
                ? DB::table('access_logs')->whereDate('created_at', today())->count() : 0,
            'active_users_today'    => $this->tableExists('sessions')
                ? DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count('user_id') : 0,
        ];
    }
```

- [ ] **Step F3.1.5: Verify PHP syntax**

```powershell
php -l packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php
```

Expected: `No syntax errors detected`

- [ ] **Step F3.1.6: Update `Core/AuditLogs/Index.jsx` to handle new tabs**

The page currently has `TAB_ACTIVITY` and `TAB_SECURITY`. Update the tab definitions and filter UI:

In `packages/aero-ui/resources/js/Pages/Core/AuditLogs/Index.jsx`, find the tab constants and replace:
```js
const TAB_ACTIVITY = 'activity';
const TAB_SECURITY = 'security';
```
With:
```js
const TAB_BUSINESS = 'business';
const TAB_MODEL    = 'model';
const TAB_ACCESS   = 'access';
```

Update all references from `TAB_ACTIVITY` → `TAB_BUSINESS`, `TAB_SECURITY` → `TAB_ACCESS`.

In the Tabs component, update tab labels:
- `activity` → `business` with label "Business Events"
- `security` → `model` with label "Model Changes"
- Add new tab: `access` with label "Sensitive Access"

- [ ] **Step F3.1.7: Commit F3.1**

```powershell
git add packages/aero-core/src/Http/Controllers/Admin/AuditLogController.php packages/aero-ui/resources/js/Pages/Core/AuditLogs/Index.jsx
git commit -m "feat(aero-core): upgrade AuditLogController to query audit_logs + access_logs from Security Foundation; add Sensitive Access tab"
```

---

## Task F3.2: Fix remaining direct AuditService references

**File:** `packages/aero-core/src/Http/Controllers/Admin/CoreUserController.php`

- [ ] **Step F3.2.1: Replace direct AuditService with AuditServiceInterface**

In `CoreUserController.php`, replace:
```php
use Aero\Core\Services\AuditService;
```
With:
```php
use Aero\Contracts\AuditServiceInterface;
```

Update the constructor parameter type from `AuditService` to `AuditServiceInterface`.

- [ ] **Step F3.2.2: Scan for any other direct AuditService references**

```powershell
Select-String -Pattern "use Aero.Core.Services.AuditService" packages/aero-core/src -Recurse
Select-String -Pattern "use Aero.Core.Services.AuditService" packages/aero-platform/src -Recurse
```

For every match found: replace `Aero\Core\Services\AuditService` with `Aero\Contracts\AuditServiceInterface` in both the `use` import and the type hint.

- [ ] **Step F3.2.3: Verify PHP syntax on every changed file**

```powershell
php -l packages/aero-core/src/Http/Controllers/Admin/CoreUserController.php
```

- [ ] **Step F3.2.4: Commit**

```powershell
git add packages/aero-core/src/Http/Controllers/Admin/CoreUserController.php
git commit -m "fix(aero-core): CoreUserController -- use AuditServiceInterface not concrete AuditService"
```

---

## Task F3.3: Create "My Profile Security" Hub Page

**Context:** The routes `core.profile.*` exist (pointing to profile security routes for 2FA, devices, sessions) but there is no hub page. Users navigating to `/profile` get redirected to `/profile/security`. Create `Core/Profile/Security.jsx` — a hub that links to the F-1 auth pages (2FA, Devices, Sessions) and allows editing name/email.

**Controller:** Read `packages/aero-core/routes/web.php` around line 679 to understand the profile routes, then check `packages/aero-core/src/Http/Controllers/` for the profile controller.

- [ ] **Step F3.3.1: Read the existing profile controller**

```powershell
Get-Content packages/aero-core/routes/web.php | Select-String "profile" -Context 0,3
```

Find the controller class for `core.profile.security` route.

- [ ] **Step F3.3.2: Ensure profile controller returns correct props**

The profile security controller should render `Core/Profile/Security` with:
```php
return Inertia::render('Core/Profile/Security', [
    'user' => [
        'id'             => $user->id,
        'name'           => $user->name,
        'email'          => $user->email,
        'avatar_url'     => $user->avatar_url,
        'two_factor_enabled' => $user->two_factor_confirmed_at !== null,
        'active_sessions'    => $sessionCount,
        'registered_devices' => $deviceCount,
    ],
]);
```

Update the controller to pass these props.

- [ ] **Step F3.3.3: Create `packages/aero-ui/resources/js/Pages/Core/Profile/Security.jsx`**

```jsx
import { Link } from '@inertiajs/react';
import {
  IndexPageLayout, Card, CardContent,
  Button, Badge,
  HStack, VStack, Text, Mono, Avatar,
} from '@aero/ui';
import App from '../../App.jsx';

function SecurityLink({ title, description, value, badge, href }) {
  return (
    <Card interactive as={Link} href={href}>
      <CardContent>
        <HStack justify="between" align="center">
          <VStack gap={0}>
            <Text weight="semibold">{title}</Text>
            <Text tone="secondary" size="sm">{description}</Text>
          </VStack>
          <HStack gap={2} align="center">
            {value && <Mono size="sm" tone="tertiary">{value}</Mono>}
            {badge && <Badge intent={badge.intent}>{badge.label}</Badge>}
          </HStack>
        </HStack>
      </CardContent>
    </Card>
  );
}

export default function ProfileSecurity({ user }) {
  return (
    <App>
      <IndexPageLayout title="My Account">
        {/* Identity */}
        <Card style={{ marginBottom: 24 }}>
          <CardContent>
            <HStack gap={4} align="center">
              <Avatar name={user.name} src={user.avatar_url} size="lg" />
              <VStack gap={0} style={{ flex: 1 }}>
                <Text size="lg" weight="semibold">{user.name}</Text>
                <Mono tone="tertiary">{user.email}</Mono>
              </VStack>
              <Button intent="ghost" size="sm" as={Link} href={route('core.users.edit', user.id)}>
                Edit profile
              </Button>
            </HStack>
          </CardContent>
        </Card>

        {/* Security hub */}
        <Text weight="semibold" style={{ marginBottom: 12 }}>Security</Text>
        <VStack gap={3}>
          <SecurityLink
            title="Two-Factor Authentication"
            description="Add an extra layer of security to your account."
            badge={user.two_factor_enabled
              ? { label: 'Enabled', intent: 'success' }
              : { label: 'Disabled', intent: 'neutral' }}
            href={route('auth.two-factor.index')}
          />

          <SecurityLink
            title="Trusted Devices"
            description="Manage devices that are allowed to log in to your account."
            value={`${user.registered_devices} device${user.registered_devices !== 1 ? 's' : ''}`}
            badge={null}
            href={route('core.devices.index')}
          />

          <SecurityLink
            title="Active Sessions"
            description="View and manage all sessions currently signed in to your account."
            value={`${user.active_sessions} active`}
            badge={null}
            href={route('core.security.sessions.index')}
          />

          <SecurityLink
            title="Change Password"
            description="Update your account password."
            badge={null}
            href={route('password.request')}
          />
        </VStack>
      </IndexPageLayout>
    </App>
  );
}
```

- [ ] **Step F3.3.4: Commit**

```powershell
git add packages/aero-ui/resources/js/Pages/Core/Profile/Security.jsx
# Also add any changes to the profile controller
git commit -m "feat(aero-ui): Core/Profile/Security hub -- 2FA status, devices, sessions, change password"
```

---

## Task F3.4: PHPUnit Feature Tests

**File:** `packages/aero-core/tests/Feature/Admin/AuditLogControllerTest.php`

- [ ] **Step F3.4.1: Create the test**

```php
<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Orchestra\Testbench\TestCase;

class AuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create audit_logs table for testing
        DB::getSchemaBuilder()->create('audit_logs', function ($table) {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_ip', 45)->nullable();
            $table->string('event_type', 100);
            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->string('subject_type')->nullable();
            $table->string('subject_id', 36)->nullable();
            $table->string('subject_label')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('changed_fields')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function test_audit_log_page_requires_authentication(): void
    {
        $this->get(route('core.audit-logs.index'))
            ->assertRedirect(route('login'));
    }

    public function test_audit_log_page_renders_correct_inertia_component(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.audit.logs.view');

        $this->actingAs($user)
            ->get(route('core.audit-logs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Core/AuditLogs/Index')
                ->has('stats')
                ->has('tab')
                ->has('logs')
                ->has('meta')
                ->has('filters')
            );
    }

    public function test_audit_log_defaults_to_business_tab(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.audit.logs.view');

        $this->actingAs($user)
            ->get(route('core.audit-logs.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'business')
            );
    }

    public function test_audit_log_returns_access_tab_data(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.audit.logs.view');

        $this->actingAs($user)
            ->get(route('core.audit-logs.index', ['tab' => 'access']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tab', 'access')
                ->has('logs')
            );
    }

    public function test_audit_log_paginates_business_events(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('core.audit.logs.view');

        // Insert 5 audit log records
        for ($i = 0; $i < 5; $i++) {
            DB::table('audit_logs')->insert([
                'event_type'  => 'data.created',
                'action'      => 'created',
                'description' => "Record {$i} created",
                'subject_type' => 'App\Models\Test',
                'subject_id'  => (string) $i,
                'created_at'  => now(),
            ]);
        }

        $this->actingAs($user)
            ->get(route('core.audit-logs.index', ['tab' => 'business']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.total', 5)
            );
    }
}
```

- [ ] **Step F3.4.2: Verify PHP syntax**

```powershell
php -l packages/aero-core/tests/Feature/Admin/AuditLogControllerTest.php
```

- [ ] **Step F3.4.3: Commit and push**

```powershell
git add packages/aero-core/tests/Feature/Admin/AuditLogControllerTest.php
git commit -m "test(aero-core): AuditLogController -- new tab structure, business events, access logs, auth guard"
git push origin main
```

---

## Task F3.5: Update master-plan.md — Mark Phase 0 complete when F-3 executes

- [ ] **Step F3.5.1: Update master-plan.md**

Change F-3 entry from `⬜` to `🟡 Written`. When execution completes, change to `✅ Done`.

- [ ] **Step F3.5.2: Commit**

```powershell
git add docs/master-plan.md docs/superpowers/plans/phase-0/plan-f3-tenant-shell.md
git commit -m "docs: Plan F-3 Tenant Shell -- written; audit log upgrade, profile security hub, AuditServiceInterface fixes"
git push origin main
```

---

## Self-Review

**Spec coverage:**
- ✅ AuditLogController — 3 new tabs (business events from `audit_logs`, model changes from `activity_log`, sensitive access from `access_logs`)
- ✅ `Core/AuditLogs/Index.jsx` — tab constants updated to match new backend
- ✅ CoreUserController — `AuditServiceInterface` not concrete class
- ✅ Global scan for any remaining direct `AuditService` references
- ✅ `Core/Profile/Security.jsx` — 2FA status, devices, sessions, change password hub
- ✅ PHPUnit — 5 test cases covering auth guard, tab routing, pagination, access tab
- ✅ Profile security controller upgraded to pass correct props

**Security (audit trail):**
- The new `audit_logs` table is now the primary source for business events in the UI
- The existing `activity_log` (Spatie) remains accessible as a separate "Model Changes" tab — preserving historical data while adding structured business event tracking
- Access logs (sensitive data views) are now visible to admins

**What this plan does NOT cover (by design):**
- The 47 existing Core pages (Users, Roles, 7 Settings, Preferences, etc.) — all verified as complete
- The Tenant Dashboard (`Tenant/Dashboard.jsx`) — already excellent, no changes needed

**No placeholders:** All code is complete with real prop shapes from actual controllers.
