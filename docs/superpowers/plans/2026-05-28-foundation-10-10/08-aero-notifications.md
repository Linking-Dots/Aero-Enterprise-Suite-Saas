# aero-notifications — Plan to 10/10

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Current score:** 7/10 (per inline audit, 2026-05-28)
**Target score:** 10/10
**Estimated effort:** 5–7 engineer-days

**Goal:** Declare the package's substantial implementation surface (currently zero submodules declared but Email/SMS/Push/Database/Broadcast channels all exist). Resolve the **email_engine ownership question from aero-core plan Task 12** by claiming it here. Build test coverage from zero. Verify channel adapter idempotency.

**Architecture:** Stay with the excellent Pipeline+Adapter pattern (`NotificationPipeline` orchestrates `AbstractChannelAdapter` subclasses: Mail, SMS, Push, Database, Broadcast). Add submodule declarations to `config/module.php` (currently 54 lines, zero submodules). Add tests.

**Tech Stack:** Laravel 12 Notifications + Mail + Queue, FCM (push), pluggable SMS gateways, Spatie ActivityLog (for templates).

**Prerequisite:** Phase 0 wiring (Redis queue + Horizon).

---

## Reference

- 53 PHP files, 54-line `config/module.php`, 5 migrations, 0 tests
- 9 controllers: Admin (Bounce, Deliverability, EmailLog, Suppression), EmailTemplate, NotificationApi, Notification, NotificationPreference, NotificationSetting
- Services: MailService, EmailDigestService, NotificationLoggingService, NotificationPreferenceService, FcmNotificationService, SmsService, SmsGatewayService, EmailTemplateService
- Pipeline pattern: NotificationPipeline + 5 channel adapters (Broadcast, Database, Mail, Push, SMS)
- Contracts: BrandingResolver, MailContextResolver, SmsContextResolver, PushNotificationService, NotificationRoutingContract
- Jobs: SendEmailJob, SendSmsJob
- Models: EmailTemplate, NotificationLog, NotificationSetting, UserNotificationPreference
- **`config/module.php` declares NO submodules** — yet implementation has Email/SMS/Push/InApp/Suppression/Bounce/Deliverability surfaces

## File Structure

| File | Responsibility |
|---|---|
| `packages/aero-notifications/config/module.php` | Declare submodules (Email, SMS, Push, InApp, Suppression, Bounce, Deliverability, Templates, Preferences, EmailLog) |
| `packages/aero-core/config/module.php` | Remove `email_engine` declaration (moved here — closes aero-core Task 12) |
| `packages/aero-notifications/src/Services/Pipeline/NotificationPipeline.php` | Idempotency key + retry tracking |
| `packages/aero-notifications/src/Models/NotificationLog.php` | Add `idempotency_key` column + unique index |
| `packages/aero-notifications/database/migrations/2026_05_28_000200_add_idempotency_to_notification_logs.php` (new) |  |
| `packages/aero-notifications/src/Models/EmailTemplate.php` | Replace raw `'updated_at' => now()` with Eloquent (Phase 1 flagged) |
| `packages/aero-notifications/src/Policies/*Policy.php` (new — 8) | Per-controller policies |
| `packages/aero-notifications/tests/Feature/Pipeline/IdempotencyTest.php` (new) |  |
| `packages/aero-notifications/tests/Feature/Channels/MailChannelAdapterTest.php` (new) |  |
| `packages/aero-notifications/tests/Feature/Channels/SmsChannelAdapterTest.php` (new) |  |
| `packages/aero-notifications/tests/Feature/Channels/PushChannelAdapterTest.php` (new) |  |
| `packages/aero-notifications/tests/Feature/Admin/SuppressionListTest.php` (new) |  |
| `packages/aero-notifications/tests/Feature/Admin/BounceHandlingTest.php` (new) |  |
| `packages/aero-notifications/tests/Unit/Jobs/SendEmailJobTest.php` (new) |  |
| `packages/aero-notifications/tests/Unit/Jobs/SendSmsJobTest.php` (new) |  |
| `packages/aero-notifications/tests/Unit/Models/EmailTemplateTest.php` (new) |  |

---

## Task 1: Declare submodules in `config/module.php`

**Severity:** High. Substantial surface is implemented but undeclared → HRMAC has nothing to enforce, sidebar/navigation can't pick it up, module sync writes nothing.

**Files:**
- Modify: `packages/aero-notifications/config/module.php`
- Modify: `packages/aero-core/config/module.php` — remove `email_engine` block (lines ~1323-1373)

- [ ] **Step 1: Add submodules block**

```php
'submodules' => [
    [
        'code' => 'email_engine',
        'name' => 'Email Engine',
        'icon' => 'EnvelopeIcon',
        'components' => [
            ['code' => 'templates', 'route' => '/notifications/email/templates', 'actions' => [['code'=>'view'],['code'=>'create'],['code'=>'update'],['code'=>'delete'],['code'=>'duplicate']]],
            ['code' => 'logs', 'route' => '/notifications/email/logs', 'actions' => [['code'=>'view'],['code'=>'resend']]],
            ['code' => 'suppression_list', 'route' => '/notifications/email/suppression', 'actions' => [['code'=>'view'],['code'=>'add'],['code'=>'remove'],['code'=>'export']]],
            ['code' => 'deliverability', 'route' => '/notifications/email/deliverability', 'actions' => [['code'=>'view'],['code'=>'test_smtp']]],
            ['code' => 'bounces', 'route' => '/notifications/email/bounces', 'actions' => [['code'=>'view']]],
        ],
    ],
    [
        'code' => 'sms_engine',
        'name' => 'SMS Engine',
        'icon' => 'ChatBubbleLeftIcon',
        'components' => [
            ['code' => 'gateways', 'route' => '/notifications/sms/gateways', 'actions' => [['code'=>'view'],['code'=>'configure'],['code'=>'test']]],
            ['code' => 'logs', 'route' => '/notifications/sms/logs', 'actions' => [['code'=>'view'],['code'=>'resend']]],
            ['code' => 'templates', 'route' => '/notifications/sms/templates', 'actions' => [['code'=>'view'],['code'=>'create'],['code'=>'update'],['code'=>'delete']]],
        ],
    ],
    [
        'code' => 'push_engine',
        'name' => 'Push Notification Engine',
        'icon' => 'BellAlertIcon',
        'components' => [
            ['code' => 'fcm_config', 'route' => '/notifications/push/fcm', 'actions' => [['code'=>'view'],['code'=>'configure'],['code'=>'test']]],
            ['code' => 'topics', 'route' => '/notifications/push/topics', 'actions' => [['code'=>'view'],['code'=>'create'],['code'=>'subscribe']]],
        ],
    ],
    [
        'code' => 'in_app',
        'name' => 'In-App Notifications',
        'components' => [
            ['code' => 'inbox', 'route' => '/notifications/inbox', 'actions' => [['code'=>'view'],['code'=>'mark_read'],['code'=>'delete']]],
        ],
    ],
    [
        'code' => 'preferences',
        'name' => 'User Notification Preferences',
        'components' => [
            ['code' => 'channels', 'route' => '/profile/notifications', 'actions' => [['code'=>'view'],['code'=>'update']]],
            ['code' => 'digest', 'route' => '/profile/notifications/digest', 'actions' => [['code'=>'view'],['code'=>'update']]],
        ],
    ],
    [
        'code' => 'settings',
        'name' => 'Notification Settings',
        'components' => [
            ['code' => 'global', 'route' => '/admin/notifications/settings', 'actions' => [['code'=>'view'],['code'=>'update']]],
        ],
    ],
],
```

- [ ] **Step 2: Remove `email_engine` from `packages/aero-core/config/module.php`**

- [ ] **Step 3: Run `php artisan modules:sync`**

- [ ] **Step 4: Update route middleware strings** — `hrmac:core.email_engine.*` → `hrmac:notifications.email_engine.*` across `packages/aero-notifications/routes/`

- [ ] **Step 5: Commit**

```bash
git commit -am "feat(notifications): declare full submodule surface (closes aero-core Task 12 email_engine ownership)"
```

---

## Task 2: Notification idempotency

**Severity:** High. If a job retries (Horizon will retry on failure), the same notification can be sent twice without idempotency.

**Files:**
- Create: `packages/aero-notifications/database/migrations/2026_05_28_000200_add_idempotency_to_notification_logs.php`
- Modify: `packages/aero-notifications/src/Models/NotificationLog.php`
- Modify: `packages/aero-notifications/src/Services/Pipeline/NotificationPipeline.php`
- Create: `packages/aero-notifications/tests/Feature/Pipeline/IdempotencyTest.php`

- [ ] **Step 1: Migration**

```php
Schema::table('notification_logs', function (Blueprint $t) {
    $t->string('idempotency_key', 64)->nullable()->after('id');
    $t->unique(['idempotency_key']);
});
```

- [ ] **Step 2: Pipeline generates idempotency_key (sha256 of recipient + channel + payload)**

```php
public function dispatch(Notifiable $to, AbstractNotification $notification): void
{
    $key = hash('sha256', $to->getKey().'|'.$notification::class.'|'.json_encode($notification->payload()));

    if (NotificationLog::where('idempotency_key', $key)->exists()) {
        Log::info('Duplicate notification suppressed', ['key' => $key]);
        return;
    }

    NotificationLog::create([
        'idempotency_key' => $key,
        // ...
    ]);

    $this->channelAdapter($notification->via())->send($to, $notification);
}
```

- [ ] **Step 3: Write failing test**

```php
public function test_duplicate_notification_in_idempotency_window_is_suppressed(): void
{
    $user = User::factory()->create();
    $notification = new TestNotification();

    app(NotificationPipeline::class)->dispatch($user, $notification);
    app(NotificationPipeline::class)->dispatch($user, $notification);

    $this->assertSame(1, NotificationLog::count());
}
```

- [ ] **Step 4: PASS + commit**

```bash
git commit -am "feat(notifications): idempotency key prevents duplicate sends on retry"
```

---

## Task 3: Channel adapter tests

**Files:**
- Create: `tests/Feature/Channels/MailChannelAdapterTest.php`
- Create: `tests/Feature/Channels/SmsChannelAdapterTest.php`
- Create: `tests/Feature/Channels/PushChannelAdapterTest.php`
- Create: `tests/Feature/Channels/DatabaseChannelAdapterTest.php`
- Create: `tests/Feature/Channels/BroadcastChannelAdapterTest.php`

For each:
- Happy path send
- User preferences honored (user opted out → no send)
- Suppression list check (mail only)
- Tenant context preserved (queue job tenancy)
- Failure path logs to NotificationLog with error

- [ ] **Step 1: Mail::fake() / Notification::fake() / Http::fake()**
- [ ] **Step 2: Per-adapter tests**
- [ ] **Step 3: Commit per file**

---

## Task 4: Job tests (SendEmailJob, SendSmsJob)

**Files:**
- Create: `tests/Unit/Jobs/SendEmailJobTest.php`
- Create: `tests/Unit/Jobs/SendSmsJobTest.php`

Cases:
- Tenant context restored
- Retry on failure honors $tries
- Idempotency check before send
- Logs success to NotificationLog
- Logs failure

- [ ] **Step 1: Tests** — [ ] **Step 2: Commit**

---

## Task 5: EmailTemplate model cleanup

Per Phase 1 audit, `EmailTemplate` uses `'updated_at' => now()` in some places without Eloquent.

**Files:**
- Modify: `packages/aero-notifications/src/Models/EmailTemplate.php`

- [ ] **Step 1: Identify all sites of raw timestamp writes**
- [ ] **Step 2: Replace with Eloquent `$model->update([...])`**
- [ ] **Step 3: Tests + commit**

---

## Task 6: Suppression + bounce flow tests

**Files:**
- Create: `tests/Feature/Admin/SuppressionListTest.php`
- Create: `tests/Feature/Admin/BounceHandlingTest.php`

Verify:
- Bounce webhook (e.g., from SES/Mailgun) adds email to suppression
- Suppressed email → Mail channel skips send + logs `suppressed`
- Manual unsuppress works + writes audit
- Export suppression list as CSV

- [ ] **Step 1: Tests** — [ ] **Step 2: Fix gaps** — [ ] **Step 3: Commit**

---

## Task 7: Policies for admin controllers

**Files:**
- 8 new policies in `src/Policies/`: `EmailTemplatePolicy`, `EmailLogPolicy`, `SuppressionPolicy`, `BouncePolicy`, `DeliverabilityPolicy`, `NotificationSettingPolicy`, `NotificationPreferencePolicy`, `NotificationPolicy`

- [ ] **Step 1: Policy unit tests**
- [ ] **Step 2: Generate + wire controllers**
- [ ] **Step 3: Commit per policy**

---

## Task 8: Tenant cache + facade discipline

**Files:**
- Modify: any service using `Cache::` — switch to `TenantCache`

- [ ] **Step 1: grep `Cache::` in `packages/aero-notifications/src`**
- [ ] **Step 2: Replace**
- [ ] **Step 3: Commit**

---

## Task 9: Final verification

- [ ] **Step 1: Run tests**

```bash
php artisan test packages/aero-notifications/tests
```

- [ ] **Step 2: Verify modules:sync picks up new declarations**

```bash
php artisan modules:sync && php artisan tinker --execute="dd(\\Aero\\Hrmac\\Models\\SubModule::where('module_code','notifications')->pluck('code'));"
```

- [ ] **Step 3: Score recheck**

| Dimension | Target |
|---|---|
| Submodule declaration parity | 10/10 |
| Idempotency | 10/10 |
| Channel test coverage | 9/10 |
| Job test coverage | 10/10 |
| Policy coverage | 10/10 |
| email_engine ownership (closes aero-core Task 12) | 10/10 |

- [ ] **Step 4: Tag**

```bash
git tag aero-notifications-10-10
```

---

## Self-Review

- ✅ Resolves aero-core plan Task 12 (email_engine delegation)
- ✅ Idempotency closes duplicate-send risk
- ✅ Test pyramid built from zero
- ✅ TDD shape

## Execution Handoff

Order: Task 1 (declare) → Task 2 (idempotency) → Tasks 3-6 (test buildup) → Task 7 (policies) → Tasks 8-9 (cleanup + verify).
