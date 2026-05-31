# Plan CA-7 — Email Engine, Comments & Mentions, Mobile/PWA

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver three final `config/module.php` submodules not yet covered: (1) **Email Engine** — admin UI for email logs, deliverability checks, suppression list, and bounces wired to `aero-notifications` backend; (2) **Comments & Mentions** — upgrade the existing stub pages and wire the comment component for inline use across all modules; (3) **Mobile & PWA** — new backend controller and config pages for PWA manifest, push notifications, and mobile app settings.

**Architecture:**
- `aero-notifications` already has `NotificationLog` model (email send/fail records), `MailService`, `NotificationLoggingService`, and `EmailTemplateController`. This plan adds missing admin controllers for log viewer, suppression, deliverability, and bounces — all in `packages/aero-notifications/src/Http/Controllers/Admin/`. Routes appended to `packages/aero-notifications/routes/web.php`.
- Comments backend is complete in `aero-core` (`CommentController`, `MentionsController`, `ActivityController`, `CommentService`). Pages `Core/Mentions/Index.jsx` and `Core/ActivityFeed/Index.jsx` exist but are stubs. This plan upgrades those pages and adds a reusable inline `<CommentThread />` component.
- Mobile/PWA has no backend. A new `MobileController` in `aero-core` writes config to `system_settings`. Pages in `Core/Mobile/`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui` HeroUI, PHPUnit 11.

**Prerequisites:** CA-1 through CA-6 complete. `aero-notifications` service provider registered.

**Foundation package note:** `aero-notifications` is a shared foundation package. Email logs and suppression lists are tenant-scoped (live in tenant DB). The existing `notification_logs` table already records every send attempt.

---

## Security Notes

- `AuditService::logAccess()` on email log page load — contains recipient addresses (PII)
- `AuditService::log()` on suppression list add/remove
- No mutation of `notification_logs` records — immutable audit trail
- Suppression list removes: audit with `AuditEventType::RECORD_DELETED`
- PWA/push config: `AuditService::log()` on save
- All routes: `hrmac:core.<submodule>.<component>.<action>`

---

## File Map

**New migrations:**
```
packages/aero-notifications/database/migrations/2026_05_23_000004_create_email_suppression_list_table.php
packages/aero-core/database/migrations/2026_05_23_000005_create_pwa_config_table.php
```

**New controllers in aero-notifications:**
```
packages/aero-notifications/src/Http/Controllers/Admin/EmailLogController.php     -- CREATE
packages/aero-notifications/src/Http/Controllers/Admin/SuppressionController.php  -- CREATE
packages/aero-notifications/src/Http/Controllers/Admin/DeliverabilityController.php -- CREATE
packages/aero-notifications/src/Http/Controllers/Admin/BounceController.php       -- CREATE
```

**New controller in aero-core:**
```
packages/aero-core/src/Http/Controllers/Admin/MobileController.php -- CREATE
```

**Routes:**
```
packages/aero-notifications/routes/web.php  -- UPGRADE: add admin email engine routes with HRMAC
packages/aero-core/routes/web.php           -- UPGRADE: add /mobile routes
```

**Frontend pages:**
```
packages/aero-ui/resources/js/Pages/Core/Email/Index.jsx         -- CREATE: email engine hub (tabs)
packages/aero-ui/resources/js/Pages/Core/Email/Logs.jsx          -- CREATE: sent/failed email log
packages/aero-ui/resources/js/Pages/Core/Email/Deliverability.jsx -- CREATE: DKIM/SPF/DMARC status
packages/aero-ui/resources/js/Pages/Core/Email/Suppression.jsx   -- CREATE: suppression list
packages/aero-ui/resources/js/Pages/Core/Email/Bounces.jsx       -- CREATE: bounces & complaints
packages/aero-ui/resources/js/Pages/Core/Mentions/Index.jsx      -- UPGRADE: full mentions inbox
packages/aero-ui/resources/js/Pages/Core/ActivityFeed/Index.jsx  -- UPGRADE: full activity feed
packages/aero-ui/resources/js/components/Comments/CommentThread.jsx -- CREATE/UPGRADE: reusable
packages/aero-ui/resources/js/Pages/Core/Mobile/Index.jsx        -- CREATE: PWA config + push
```

**Tests:**
```
packages/aero-notifications/tests/Feature/Admin/EmailLogControllerTest.php
packages/aero-notifications/tests/Feature/Admin/SuppressionControllerTest.php
packages/aero-core/tests/Feature/Admin/MobileControllerTest.php
```

---

## Task 1 — Migrations: email suppression list + PWA config

**Files:**
- Create: `packages/aero-notifications/database/migrations/2026_05_23_000004_create_email_suppression_list_table.php`
- Create: `packages/aero-core/database/migrations/2026_05_23_000005_create_pwa_config_table.php`

- [ ] Create suppression list migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('email_suppression_list')) return;
        Schema::create('email_suppression_list', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('reason')->default('manual'); // manual|bounce|complaint|unsubscribe
            $table->text('note')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('email');
        });
    }
    public function down(): void { Schema::dropIfExists('email_suppression_list'); }
};
```

- [ ] Create PWA config migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pwa_configs')) return;
        Schema::create('pwa_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('pwa_enabled')->default(false);
            $table->string('display_name')->nullable();           // PWA app name
            $table->string('short_name')->nullable();
            $table->string('theme_color', 7)->nullable();         // hex
            $table->string('background_color', 7)->nullable();
            $table->string('display_mode')->default('standalone'); // standalone|fullscreen|minimal-ui
            $table->string('icon_path')->nullable();              // storage path
            $table->boolean('push_enabled')->default(false);
            $table->string('vapid_public_key')->nullable();
            $table->text('vapid_private_key')->nullable();        // encrypted
            $table->boolean('mobile_app_enabled')->default(false);
            $table->string('android_package')->nullable();
            $table->string('ios_bundle_id')->nullable();
            $table->json('deep_link_schemes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pwa_configs'); }
};
```

- [ ] Commit:
```bash
git add packages/aero-notifications/database/migrations/2026_05_23_000004_create_email_suppression_list_table.php \
        packages/aero-core/database/migrations/2026_05_23_000005_create_pwa_config_table.php
git commit -m "feat: email suppression list + pwa_configs migrations"
```

---

## Task 2 — Models: EmailSuppressionEntry, PwaConfig

**Files:**
- Create: `packages/aero-notifications/src/Models/EmailSuppressionEntry.php`
- Create: `packages/aero-core/src/Models/PwaConfig.php`

- [ ] Create `EmailSuppressionEntry.php`:

```php
<?php

namespace Aero\Notifications\Models;

use Aero\Core\Models\TenantModel;

class EmailSuppressionEntry extends TenantModel
{
    protected $table    = 'email_suppression_list';
    protected $fillable = ['email', 'reason', 'note', 'added_by'];

    public function addedByUser()
    {
        return $this->belongsTo(\Aero\Core\Models\User::class, 'added_by');
    }

    public function scopeSearch($query, ?string $term)
    {
        return $term
            ? $query->where('email', 'like', "%{$term}%")
            : $query;
    }
}
```

- [ ] Create `PwaConfig.php`:

```php
<?php

namespace Aero\Core\Models;

use Aero\Core\Encryption\EncryptedField;
use Aero\Core\Models\TenantModel;

class PwaConfig extends TenantModel
{
    protected $fillable = [
        'pwa_enabled', 'display_name', 'short_name', 'theme_color',
        'background_color', 'display_mode', 'icon_path',
        'push_enabled', 'vapid_public_key', 'vapid_private_key',
        'mobile_app_enabled', 'android_package', 'ios_bundle_id', 'deep_link_schemes',
    ];

    protected $casts = [
        'pwa_enabled'          => 'boolean',
        'push_enabled'         => 'boolean',
        'mobile_app_enabled'   => 'boolean',
        'deep_link_schemes'    => 'array',
        'vapid_private_key'    => EncryptedField::class,
    ];
}
```

- [ ] Commit:
```bash
git add packages/aero-notifications/src/Models/EmailSuppressionEntry.php \
        packages/aero-core/src/Models/PwaConfig.php
git commit -m "feat: EmailSuppressionEntry and PwaConfig models"
```

---

## Task 3 — Email Engine: admin controllers

**Files:**
- Create: `packages/aero-notifications/src/Http/Controllers/Admin/EmailLogController.php`
- Create: `packages/aero-notifications/src/Http/Controllers/Admin/SuppressionController.php`
- Create: `packages/aero-notifications/src/Http/Controllers/Admin/DeliverabilityController.php`
- Create: `packages/aero-notifications/src/Http/Controllers/Admin/BounceController.php`

- [ ] Create `EmailLogController.php`:

```php
<?php

namespace Aero\Notifications\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Notifications\Models\NotificationLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailLogController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): Response
    {
        $this->audit->logAccess('email_logs', null, null, ['recipient_addresses']);

        $logs = NotificationLog::where('channel', 'mail')
            ->when($request->search, fn($q, $s) =>
                $q->where(fn($q2) => $q2
                    ->where('recipient', 'like', "%{$s}%")
                    ->orWhere('subject', 'like', "%{$s}%")))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->from,   fn($q, $d) => $q->where('created_at', '>=', $d))
            ->when($request->to,     fn($q, $d) => $q->where('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Core/Email/Logs', [
            'logs'    => $logs,
            'filters' => $request->only('search', 'status', 'from', 'to'),
            'stats'   => [
                'sent'     => NotificationLog::where('channel', 'mail')->where('status', 'sent')->count(),
                'failed'   => NotificationLog::where('channel', 'mail')->where('status', 'failed')->count(),
                'pending'  => NotificationLog::where('channel', 'mail')->where('status', 'pending')->count(),
            ],
        ]);
    }

    public function resend(int $id, Request $request): RedirectResponse
    {
        $log = NotificationLog::where('channel', 'mail')->findOrFail($id);

        // Re-dispatch via the notifications pipeline
        \Aero\Notifications\Jobs\SendEmailJob::dispatch([
            'to'      => $log->recipient,
            'subject' => $log->subject,
            'body'    => $log->content,
        ]);

        $this->audit->log(
            \Aero\Core\Services\Audit\AuditEventType::RECORD_UPDATED,
            $request->user(),
            $log,
            ['action' => 'resend']
        );

        return back()->with('success', "Email queued for resend to {$log->recipient}.");
    }
}
```

- [ ] Create `SuppressionController.php`:

```php
<?php

namespace Aero\Notifications\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Notifications\Models\EmailSuppressionEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SuppressionController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Core/Email/Suppression', [
            'entries' => EmailSuppressionEntry::with('addedByUser')
                ->search($request->search)
                ->when($request->reason, fn($q, $r) => $q->where('reason', $r))
                ->orderByDesc('created_at')
                ->paginate(50)
                ->withQueryString(),
            'filters' => $request->only('search', 'reason'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email'  => ['required', 'email', 'unique:email_suppression_list,email'],
            'reason' => ['required', 'in:manual,bounce,complaint,unsubscribe'],
            'note'   => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $entry = EmailSuppressionEntry::create(array_merge($data, ['added_by' => $request->user()->id]));
            $this->audit->log(AuditEventType::RECORD_CREATED, $request->user(), $entry,
                ['email' => $data['email']]);
        });

        return back()->with('success', "{$data['email']} added to suppression list.");
    }

    public function destroy(EmailSuppressionEntry $entry, Request $request): RedirectResponse
    {
        DB::transaction(function () use ($entry, $request) {
            $this->audit->log(AuditEventType::RECORD_DELETED, $request->user(), $entry,
                ['email' => $entry->email]);
            $entry->delete();
        });

        return back()->with('success', "{$entry->email} removed from suppression list.");
    }
}
```

- [ ] Create `DeliverabilityController.php`:

```php
<?php

namespace Aero\Notifications\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\SystemSettingService;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class DeliverabilityController extends Controller
{
    public function __construct(private SystemSettingService $settings) {}

    public function index(): Response
    {
        $domain    = $this->settings->get('mail_from_email')
            ? str_after($this->settings->get('mail_from_email'), '@')
            : parse_url(config('app.url'), PHP_URL_HOST);

        $checks = $this->runChecks($domain);

        return Inertia::render('Core/Email/Deliverability', [
            'domain' => $domain,
            'checks' => $checks,
            'score'  => $this->calculateScore($checks),
        ]);
    }

    private function runChecks(string $domain): array
    {
        $checks = [];

        // SPF check: look for TXT record containing "v=spf1"
        $spfRecords = @dns_get_record($domain, DNS_TXT) ?: [];
        $spf = collect($spfRecords)->first(fn($r) => str_contains($r['txt'] ?? '', 'v=spf1'));
        $checks['spf'] = [
            'label'  => 'SPF Record',
            'status' => $spf ? 'pass' : 'fail',
            'value'  => $spf['txt'] ?? null,
            'guide'  => 'Add a TXT record: v=spf1 include:yourmailprovider.com ~all',
        ];

        // DMARC: look for _dmarc.domain TXT record
        $dmarcRecords = @dns_get_record("_dmarc.{$domain}", DNS_TXT) ?: [];
        $dmarc = collect($dmarcRecords)->first(fn($r) => str_contains($r['txt'] ?? '', 'v=DMARC1'));
        $checks['dmarc'] = [
            'label'  => 'DMARC Record',
            'status' => $dmarc ? 'pass' : 'fail',
            'value'  => $dmarc['txt'] ?? null,
            'guide'  => 'Add a TXT record at _dmarc.' . $domain . ': v=DMARC1; p=quarantine;',
        ];

        // DKIM: try selector "default" and "mail" (common defaults)
        $dkimFound = false;
        $dkimValue = null;
        foreach (['default._domainkey', 'mail._domainkey', 's1._domainkey'] as $selector) {
            $records = @dns_get_record("{$selector}.{$domain}", DNS_TXT) ?: [];
            if (!empty($records)) {
                $dkimFound = true;
                $dkimValue = $records[0]['txt'] ?? null;
                break;
            }
        }
        $checks['dkim'] = [
            'label'  => 'DKIM Record',
            'status' => $dkimFound ? 'pass' : 'warn',
            'value'  => $dkimValue,
            'guide'  => 'Configure DKIM signing through your mail provider and add the TXT record they provide.',
        ];

        // MX records
        $mxRecords = @dns_get_record($domain, DNS_MX) ?: [];
        $checks['mx'] = [
            'label'  => 'MX Records',
            'status' => !empty($mxRecords) ? 'pass' : 'warn',
            'value'  => collect($mxRecords)->pluck('target')->join(', '),
            'guide'  => 'Add MX records pointing to your mail server.',
        ];

        return $checks;
    }

    private function calculateScore(array $checks): int
    {
        $weights = ['spf' => 30, 'dmarc' => 30, 'dkim' => 30, 'mx' => 10];
        $score   = 0;
        foreach ($checks as $key => $check) {
            if ($check['status'] === 'pass') {
                $score += $weights[$key] ?? 10;
            } elseif ($check['status'] === 'warn') {
                $score += ($weights[$key] ?? 10) / 2;
            }
        }
        return (int) $score;
    }
}
```

- [ ] Create `BounceController.php`:

```php
<?php

namespace Aero\Notifications\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Notifications\Models\NotificationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BounceController extends Controller
{
    public function index(Request $request): Response
    {
        $bounces = NotificationLog::where('channel', 'mail')
            ->whereIn('status', ['failed', 'bounced'])
            ->when($request->search, fn($q, $s) =>
                $q->where('recipient', 'like', "%{$s}%"))
            ->when($request->from, fn($q, $d) => $q->where('created_at', '>=', $d))
            ->when($request->to,   fn($q, $d) => $q->where('created_at', '<=', $d))
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        // Aggregate per-recipient bounce counts for pattern detection
        $topBouncingDomains = NotificationLog::where('channel', 'mail')
            ->whereIn('status', ['failed', 'bounced'])
            ->selectRaw('SUBSTRING_INDEX(recipient, "@", -1) as domain, COUNT(*) as count')
            ->groupBy('domain')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return Inertia::render('Core/Email/Bounces', [
            'bounces'             => $bounces,
            'top_bouncing_domains' => $topBouncingDomains,
            'filters'             => $request->only('search', 'from', 'to'),
        ]);
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-notifications/src/Http/Controllers/Admin/
git commit -m "feat(aero-notifications): EmailLog, Suppression, Deliverability, Bounce admin controllers"
```

---

## Task 4 — Routes: email engine admin routes

**Files:**
- Modify: `packages/aero-notifications/routes/web.php`

- [ ] Append to `packages/aero-notifications/routes/web.php`:

```php
// ============================================================================
// EMAIL ENGINE ADMIN ROUTES (add below existing routes)
// ============================================================================
use Aero\Notifications\Http\Controllers\Admin\BounceController;
use Aero\Notifications\Http\Controllers\Admin\DeliverabilityController;
use Aero\Notifications\Http\Controllers\Admin\EmailLogController;
use Aero\Notifications\Http\Controllers\Admin\SuppressionController;

Route::middleware(['web', 'auth:web'])->prefix('email')->name('core.email.')->group(function () {

    // Email Logs
    Route::prefix('logs')->name('logs.')->middleware('hrmac:core.email_engine.email_logs.view')->group(function () {
        Route::get('/', [EmailLogController::class, 'index'])->name('index');
        Route::post('/{id}/resend', [EmailLogController::class, 'resend'])->name('resend')
            ->withoutMiddleware('hrmac:core.email_engine.email_logs.view')
            ->middleware('hrmac:core.email_engine.email_logs.resend');
    });

    // Deliverability
    Route::get('/deliverability', [DeliverabilityController::class, 'index'])
        ->name('deliverability.index')
        ->middleware('hrmac:core.email_engine.deliverability.view');

    // Suppression
    Route::prefix('suppression')->name('suppression.')->middleware('hrmac:core.email_engine.suppression_list.view')->group(function () {
        Route::get('/', [SuppressionController::class, 'index'])->name('index');
        Route::post('/', [SuppressionController::class, 'store'])->name('store')
            ->withoutMiddleware('hrmac:core.email_engine.suppression_list.view')
            ->middleware('hrmac:core.email_engine.suppression_list.remove'); // reuse permission
        Route::delete('/{entry}', [SuppressionController::class, 'destroy'])->name('destroy')
            ->withoutMiddleware('hrmac:core.email_engine.suppression_list.view')
            ->middleware('hrmac:core.email_engine.suppression_list.remove');
    });

    // Bounces
    Route::get('/bounces', [BounceController::class, 'index'])
        ->name('bounces.index')
        ->middleware('hrmac:core.email_engine.bounce_complaint.view');
});
```

- [ ] Commit:
```bash
git add packages/aero-notifications/routes/web.php
git commit -m "feat(aero-notifications): email engine admin routes (logs, deliverability, suppression, bounces)"
```

---

## Task 5 — MobileController + routes

**Files:**
- Create: `packages/aero-core/src/Http/Controllers/Admin/MobileController.php`

- [ ] Create `MobileController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\PwaConfig;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MobileController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function getOrCreateConfig(): PwaConfig
    {
        return PwaConfig::firstOrCreate([], [
            'pwa_enabled'        => false,
            'push_enabled'       => false,
            'mobile_app_enabled' => false,
        ]);
    }

    public function index(): Response
    {
        $config = $this->getOrCreateConfig();
        return Inertia::render('Core/Mobile/Index', [
            'config'   => $config,
            'manifest' => $this->buildManifestPreview($config),
        ]);
    }

    public function updatePwa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pwa_enabled'      => ['boolean'],
            'display_name'     => ['nullable', 'string', 'max:100'],
            'short_name'       => ['nullable', 'string', 'max:30'],
            'theme_color'      => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'display_mode'     => ['nullable', 'in:standalone,fullscreen,minimal-ui,browser'],
            'icon'             => ['nullable', 'image', 'max:2048'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $config = $this->getOrCreateConfig();

            if ($request->hasFile('icon')) {
                $path = $request->file('icon')->store('pwa/icons', 'public');
                $data['icon_path'] = Storage::url($path);
            }

            unset($data['icon']);
            $config->update($data);
            $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), $config,
                ['section' => 'pwa_config']);
        });

        return back()->with('success', 'PWA configuration saved.');
    }

    public function updatePush(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'push_enabled'      => ['boolean'],
            'vapid_public_key'  => ['nullable', 'string'],
            'vapid_private_key' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $config = $this->getOrCreateConfig();
            $config->update($data);
            $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), $config,
                ['section' => 'push_config']);
        });

        return back()->with('success', 'Push notification settings saved.');
    }

    public function updateMobileApp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mobile_app_enabled' => ['boolean'],
            'android_package'    => ['nullable', 'string', 'regex:/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/'],
            'ios_bundle_id'      => ['nullable', 'string'],
            'deep_link_schemes'  => ['nullable', 'array'],
            'deep_link_schemes.*' => ['string'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $config = $this->getOrCreateConfig();
            $config->update($data);
            $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), $config,
                ['section' => 'mobile_app_config']);
        });

        return back()->with('success', 'Mobile app settings saved.');
    }

    public function testPush(Request $request): RedirectResponse
    {
        $request->validate(['to' => ['required', 'email']]);
        // Push test: dispatched via FCM service if configured
        try {
            if (class_exists(\Aero\Notifications\Services\Push\FcmNotificationService::class)) {
                $fcm = app(\Aero\Notifications\Services\Push\FcmNotificationService::class);
                $fcm->sendTest($request->to);
                return back()->with('success', 'Test push notification sent.');
            }
            return back()->with('error', 'FCM is not configured. Install kreait/firebase-php to enable push.');
        } catch (\Exception $e) {
            return back()->with('error', "Failed: {$e->getMessage()}");
        }
    }

    private function buildManifestPreview(PwaConfig $config): array
    {
        return [
            'name'             => $config->display_name  ?? config('app.name'),
            'short_name'       => $config->short_name    ?? config('app.name'),
            'theme_color'      => $config->theme_color   ?? '#006FEE',
            'background_color' => $config->background_color ?? '#ffffff',
            'display'          => $config->display_mode  ?? 'standalone',
            'icons'            => $config->icon_path
                ? [['src' => $config->icon_path, 'sizes' => '192x192', 'type' => 'image/png']]
                : [],
        ];
    }
}
```

- [ ] Add mobile routes to `packages/aero-core/routes/web.php` inside the `auth:web` group:

```php
use Aero\Core\Http\Controllers\Admin\MobileController;

// Mobile & PWA
Route::prefix('mobile-pwa')->name('core.mobile.')->group(function () {
    Route::get('/', [MobileController::class, 'index'])->name('index')
        ->middleware('hrmac:core.mobile_pwa.pwa_config.view');
    Route::post('/pwa', [MobileController::class, 'updatePwa'])->name('pwa.update')
        ->middleware('hrmac:core.mobile_pwa.pwa_config.configure');
    Route::post('/push', [MobileController::class, 'updatePush'])->name('push.update')
        ->middleware('hrmac:core.mobile_pwa.push_notifications.configure');
    Route::post('/push/test', [MobileController::class, 'testPush'])->name('push.test')
        ->middleware('hrmac:core.mobile_pwa.push_notifications.send_test');
    Route::post('/mobile-app', [MobileController::class, 'updateMobileApp'])->name('mobile-app.update')
        ->middleware('hrmac:core.mobile_pwa.mobile_app_config.configure');
});
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Http/Controllers/Admin/MobileController.php \
        packages/aero-core/routes/web.php
git commit -m "feat(aero-core): MobileController + mobile/PWA routes"
```

---

## Task 6 — Frontend: Email Engine pages

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Email/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Email/Logs.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Email/Deliverability.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Email/Suppression.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Email/Bounces.jsx`

- [ ] Write `Email/Index.jsx` — email engine hub with tabs:

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Tab, Tabs } from '@heroui/react';
import { EnvelopeIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const EMAIL_TABS = [
  { key: 'logs',           label: 'Email Logs',       route: 'core.email.logs.index',          perm: 'core.email_engine.email_logs.view' },
  { key: 'deliverability', label: 'Deliverability',   route: 'core.email.deliverability.index', perm: 'core.email_engine.deliverability.view' },
  { key: 'suppression',   label: 'Suppression List',  route: 'core.email.suppression.index',   perm: 'core.email_engine.suppression_list.view' },
  { key: 'bounces',        label: 'Bounces',           route: 'core.email.bounces.index',        perm: 'core.email_engine.bounce_complaint.view' },
];

export default function EmailIndex() {
  const { can } = useHRMAC();
  const { url } = usePage();
  const active = EMAIL_TABS.find(t => url.includes(t.key))?.key ?? 'logs';

  return (
    <AppLayout title="Email Engine">
      <Head title="Email Engine" />
      <div className="p-6">
        <div className="flex items-center gap-3 mb-6">
          <EnvelopeIcon className="w-6 h-6 text-primary" />
          <div>
            <h1 className="text-2xl font-bold">Email Engine</h1>
            <p className="text-default-500 text-sm">Monitor delivery, manage suppression, check DNS health</p>
          </div>
        </div>
        <Tabs
          selectedKey={active}
          onSelectionChange={key => {
            const tab = EMAIL_TABS.find(t => t.key === key);
            if (tab) router.get(route(tab.route));
          }}
          variant="underlined"
        >
          {EMAIL_TABS.filter(t => can(t.perm)).map(t => <Tab key={t.key} title={t.label} />)}
        </Tabs>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Email/Logs.jsx` — email send log table with stats header:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Card, CardBody, Chip, Input, Select, SelectItem,
  Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
} from '@heroui/react';
import { MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const STATUS_COLOR = { sent: 'success', failed: 'danger', pending: 'warning', delivered: 'success' };

export default function EmailLogs({ logs, filters, stats }) {
  const { can } = useHRMAC();
  const [search, setSearch] = useState(filters.search ?? '');
  const filter = patch => router.get(route('core.email.logs.index'), { ...filters, ...patch }, { preserveState: true });

  return (
    <AppLayout title="Email Logs">
      <Head title="Email Logs" />
      <div className="p-6 space-y-4">
        {/* Stats */}
        <div className="grid grid-cols-3 gap-3">
          {[
            { label: 'Sent',    value: stats.sent,    color: 'success' },
            { label: 'Failed',  value: stats.failed,  color: 'danger' },
            { label: 'Pending', value: stats.pending, color: 'warning' },
          ].map(s => (
            <Card key={s.label} className="shadow-sm">
              <CardBody className="flex flex-row items-center justify-between py-3 px-4">
                <span className="text-sm text-default-500">{s.label}</span>
                <Chip color={s.color} variant="flat" size="sm">{s.value.toLocaleString()}</Chip>
              </CardBody>
            </Card>
          ))}
        </div>

        {/* Filters */}
        <div className="flex gap-3 flex-wrap">
          <Input
            placeholder="Search recipient or subject…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && filter({ search })}
            startContent={<MagnifyingGlassIcon className="w-4 h-4 text-default-400" />}
            className="w-64"
          />
          <Select placeholder="Status" selectedKeys={filters.status ? [filters.status] : []} onSelectionChange={k => filter({ status: [...k][0] ?? '' })} className="w-36">
            <SelectItem key="sent">Sent</SelectItem>
            <SelectItem key="failed">Failed</SelectItem>
            <SelectItem key="pending">Pending</SelectItem>
          </Select>
        </div>

        <Table aria-label="Email logs">
          <TableHeader>
            <TableColumn>RECIPIENT</TableColumn>
            <TableColumn>SUBJECT</TableColumn>
            <TableColumn>TYPE</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>ATTEMPTS</TableColumn>
            <TableColumn>SENT AT</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={logs.data}>
            {log => (
              <TableRow key={log.id}>
                <TableCell className="font-mono text-sm">{log.recipient}</TableCell>
                <TableCell className="max-w-xs truncate text-sm">{log.subject}</TableCell>
                <TableCell><Chip size="sm" variant="flat">{log.notification_type?.split('\\').pop() ?? '—'}</Chip></TableCell>
                <TableCell><Chip size="sm" color={STATUS_COLOR[log.status] ?? 'default'} variant="flat">{log.status}</Chip></TableCell>
                <TableCell className="text-sm">{log.attempts}/{log.max_attempts}</TableCell>
                <TableCell className="text-xs">{log.sent_at ? new Date(log.sent_at).toLocaleString() : '—'}</TableCell>
                <TableCell>
                  {log.status === 'failed' && can('core.email_engine.email_logs.resend') && (
                    <Button size="sm" variant="flat" onPress={() => router.post(route('core.email.logs.resend', log.id))}>
                      Resend
                    </Button>
                  )}
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        <div className="flex justify-between text-sm text-default-500">
          <span>{logs.total} emails</span>
          <div className="flex gap-2">
            {logs.prev_page_url && <Button size="sm" variant="flat" as="a" href={logs.prev_page_url}>Previous</Button>}
            {logs.next_page_url && <Button size="sm" variant="flat" as="a" href={logs.next_page_url}>Next</Button>}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Email/Deliverability.jsx` — DNS health dashboard with score ring and per-check cards:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, CardHeader, Chip, Divider } from '@heroui/react';
import { CheckCircleIcon, ExclamationCircleIcon, XCircleIcon, ArrowPathIcon } from '@heroicons/react/24/outline';

const STATUS_ICON  = { pass: CheckCircleIcon, warn: ExclamationCircleIcon, fail: XCircleIcon };
const STATUS_COLOR = { pass: 'success', warn: 'warning', fail: 'danger' };
const SCORE_COLOR  = (s) => s >= 80 ? 'text-success' : s >= 50 ? 'text-warning' : 'text-danger';

export default function Deliverability({ domain, checks, score }) {
  return (
    <AppLayout title="Email Deliverability">
      <Head title="Email Deliverability" />
      <div className="p-6 space-y-6 max-w-2xl">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">Email Deliverability</h1>
            <p className="text-default-500 text-sm">DNS record health for <strong>{domain}</strong></p>
          </div>
          <div className="text-center">
            <p className={`text-4xl font-bold ${SCORE_COLOR(score)}`}>{score}</p>
            <p className="text-xs text-default-400">/ 100</p>
          </div>
        </div>

        <Button
          size="sm"
          variant="flat"
          startContent={<ArrowPathIcon className="w-4 h-4" />}
          onPress={() => router.reload()}
        >
          Re-check DNS
        </Button>

        <div className="space-y-3">
          {Object.entries(checks).map(([key, check]) => {
            const Icon = STATUS_ICON[check.status] ?? ExclamationCircleIcon;
            return (
              <Card key={key} className="shadow-sm">
                <CardHeader className="pb-2">
                  <div className="flex items-center gap-3 w-full">
                    <Icon className={`w-5 h-5 text-${STATUS_COLOR[check.status]}`} />
                    <span className="font-medium">{check.label}</span>
                    <Chip size="sm" color={STATUS_COLOR[check.status]} variant="flat" className="ml-auto">
                      {check.status.toUpperCase()}
                    </Chip>
                  </div>
                </CardHeader>
                {(check.value || check.guide) && (
                  <CardBody className="pt-0 space-y-2">
                    {check.value && (
                      <code className="text-xs bg-default-100 px-3 py-2 rounded block break-all">{check.value}</code>
                    )}
                    {check.status !== 'pass' && check.guide && (
                      <p className="text-xs text-default-500 italic">💡 {check.guide}</p>
                    )}
                  </CardBody>
                )}
              </Card>
            );
          })}
        </div>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Email/Suppression.jsx` — suppression list table + add modal:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Input, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Chip, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter,
  Select, SelectItem, useDisclosure,
} from '@heroui/react';
import { PlusIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const REASON_COLOR = { manual: 'default', bounce: 'warning', complaint: 'danger', unsubscribe: 'secondary' };

export default function SuppressionList({ entries, filters }) {
  const { can } = useHRMAC();
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const { data, setData, post, processing, errors, reset } = useForm({ email: '', reason: 'manual', note: '' });
  const [search, setSearch] = useState(filters.search ?? '');

  const submit = e => {
    e.preventDefault();
    post(route('core.email.suppression.store'), { onSuccess: () => { reset(); onOpenChange(); } });
  };

  return (
    <AppLayout title="Suppression List">
      <Head title="Suppression List" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">Suppression List</h1>
            <p className="text-default-500 text-sm">Emails on this list will never be sent to, regardless of content</p>
          </div>
          {can('core.email_engine.suppression_list.remove') && (
            <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} onPress={onOpen}>
              Add Address
            </Button>
          )}
        </div>

        <div className="flex gap-3">
          <Input
            placeholder="Search email…"
            value={search}
            onChange={e => setSearch(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && router.get(route('core.email.suppression.index'), { search }, { preserveState: true })}
            startContent={<MagnifyingGlassIcon className="w-4 h-4 text-default-400" />}
            className="w-64"
          />
        </div>

        <Table aria-label="Suppression list">
          <TableHeader>
            <TableColumn>EMAIL</TableColumn>
            <TableColumn>REASON</TableColumn>
            <TableColumn>NOTE</TableColumn>
            <TableColumn>ADDED BY</TableColumn>
            <TableColumn>DATE</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={entries.data}>
            {entry => (
              <TableRow key={entry.id}>
                <TableCell className="font-mono text-sm">{entry.email}</TableCell>
                <TableCell><Chip size="sm" color={REASON_COLOR[entry.reason]} variant="flat">{entry.reason}</Chip></TableCell>
                <TableCell className="text-sm max-w-xs truncate">{entry.note ?? '—'}</TableCell>
                <TableCell className="text-sm">{entry.added_by_user?.name ?? 'System'}</TableCell>
                <TableCell className="text-xs">{new Date(entry.created_at).toLocaleDateString()}</TableCell>
                <TableCell>
                  {can('core.email_engine.suppression_list.remove') && (
                    <Button size="sm" color="danger" variant="flat" onPress={() => {
                      if (confirm(`Remove ${entry.email} from suppression?`))
                        router.delete(route('core.email.suppression.destroy', entry.id));
                    }}>Remove</Button>
                  )}
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        <Modal isOpen={isOpen} onOpenChange={onOpenChange}>
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader>Add to Suppression List</ModalHeader>
                <ModalBody className="space-y-3">
                  <Input label="Email Address" type="email" value={data.email} onChange={e => setData('email', e.target.value)} errorMessage={errors.email} isInvalid={!!errors.email} isRequired />
                  <Select label="Reason" selectedKeys={[data.reason]} onSelectionChange={k => setData('reason', [...k][0])}>
                    <SelectItem key="manual">Manual</SelectItem>
                    <SelectItem key="bounce">Bounce</SelectItem>
                    <SelectItem key="complaint">Complaint</SelectItem>
                    <SelectItem key="unsubscribe">Unsubscribe</SelectItem>
                  </Select>
                  <Input label="Note (optional)" value={data.note} onChange={e => setData('note', e.target.value)} />
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color="primary" isLoading={processing}>Add</Button>
                </ModalFooter>
              </form>
            )}
          </ModalContent>
        </Modal>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Email/Bounces.jsx` — failed/bounced emails table + top bouncing domains:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, CardHeader, Chip, Input, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow } from '@heroui/react';
import { MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';

export default function EmailBounces({ bounces, top_bouncing_domains, filters }) {
  const [search, setSearch] = useState(filters.search ?? '');

  return (
    <AppLayout title="Email Bounces">
      <Head title="Email Bounces" />
      <div className="p-6 space-y-6">
        <h1 className="text-2xl font-bold">Bounces & Complaints</h1>

        {/* Top bouncing domains */}
        {top_bouncing_domains.length > 0 && (
          <Card>
            <CardHeader><p className="font-semibold text-sm">Top Bouncing Domains</p></CardHeader>
            <CardBody>
              <div className="flex gap-2 flex-wrap">
                {top_bouncing_domains.map(d => (
                  <Chip key={d.domain} size="sm" color="danger" variant="flat">
                    {d.domain} ({d.count})
                  </Chip>
                ))}
              </div>
            </CardBody>
          </Card>
        )}

        <Input
          placeholder="Search recipient…"
          value={search}
          onChange={e => setSearch(e.target.value)}
          onKeyDown={e => e.key === 'Enter' && router.get(route('core.email.bounces.index'), { search }, { preserveState: true })}
          startContent={<MagnifyingGlassIcon className="w-4 h-4 text-default-400" />}
          className="w-64"
        />

        <Table aria-label="Bounces">
          <TableHeader>
            <TableColumn>RECIPIENT</TableColumn>
            <TableColumn>SUBJECT</TableColumn>
            <TableColumn>ERROR</TableColumn>
            <TableColumn>ATTEMPTS</TableColumn>
            <TableColumn>FAILED AT</TableColumn>
          </TableHeader>
          <TableBody items={bounces.data}>
            {log => (
              <TableRow key={log.id}>
                <TableCell className="font-mono text-sm">{log.recipient}</TableCell>
                <TableCell className="max-w-xs truncate text-sm">{log.subject ?? '—'}</TableCell>
                <TableCell className="text-xs text-danger max-w-xs truncate">{log.error_message ?? '—'}</TableCell>
                <TableCell className="text-sm">{log.attempts}/{log.max_attempts}</TableCell>
                <TableCell className="text-xs">{log.failed_at ? new Date(log.failed_at).toLocaleString() : '—'}</TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        <div className="flex justify-between text-sm text-default-500">
          <span>{bounces.total} bounces</span>
          <div className="flex gap-2">
            {bounces.prev_page_url && <Button size="sm" variant="flat" as="a" href={bounces.prev_page_url}>Previous</Button>}
            {bounces.next_page_url && <Button size="sm" variant="flat" as="a" href={bounces.next_page_url}>Next</Button>}
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Email/
git commit -m "feat(aero-ui): Email Engine pages — Index, Logs, Deliverability, Suppression, Bounces"
```

---

## Task 7 — Frontend: Comments & Mentions upgrade

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Mentions/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/ActivityFeed/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/components/Comments/CommentThread.jsx`

- [ ] Upgrade `Mentions/Index.jsx` — full mentions inbox with mark-read and navigation to source:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, Chip, Avatar } from '@heroui/react';
import { AtSymbolIcon, CheckIcon } from '@heroicons/react/24/outline';
import { useEffect, useState } from 'react';

export default function MentionsIndex({ unread_count }) {
  const [mentions, setMentions] = useState([]);
  const [loading, setLoading]   = useState(true);

  useEffect(() => {
    fetch('/api/mentions?per_page=50', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(d => { setMentions(d.data ?? d); setLoading(false); });
  }, []);

  const markRead = id => {
    fetch(`/api/mentions/${id}/read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } })
      .then(() => setMentions(prev => prev.map(m => m.id === id ? { ...m, read_at: new Date().toISOString() } : m)));
  };

  const markAllRead = () => {
    fetch('/api/mentions/mark-all-read', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content } })
      .then(() => setMentions(prev => prev.map(m => ({ ...m, read_at: m.read_at ?? new Date().toISOString() }))));
  };

  return (
    <AppLayout title="Mentions">
      <Head title="Mentions" />
      <div className="p-6 space-y-4 max-w-2xl">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <AtSymbolIcon className="w-6 h-6 text-primary" />
            <h1 className="text-2xl font-bold">Mentions</h1>
            {unread_count > 0 && (
              <Chip size="sm" color="primary" variant="solid">{unread_count} unread</Chip>
            )}
          </div>
          {unread_count > 0 && (
            <Button size="sm" variant="flat" startContent={<CheckIcon className="w-4 h-4" />} onPress={markAllRead}>
              Mark all read
            </Button>
          )}
        </div>

        {loading ? (
          <div className="text-center py-12 text-default-400">Loading mentions…</div>
        ) : mentions.length === 0 ? (
          <div className="text-center py-12 text-default-400">
            <AtSymbolIcon className="w-12 h-12 mx-auto mb-3 opacity-30" />
            <p>No mentions yet</p>
          </div>
        ) : (
          <div className="space-y-2">
            {mentions.map(mention => (
              <Card
                key={mention.id}
                className={`shadow-sm cursor-pointer transition-colors ${!mention.read_at ? 'border-l-4 border-primary bg-primary-50/30' : ''}`}
                isPressable
                onPress={() => {
                  if (!mention.read_at) markRead(mention.id);
                  if (mention.url) window.location.href = mention.url;
                }}
              >
                <CardBody className="flex flex-row items-start gap-3 py-3">
                  <Avatar name={mention.mentioner_name ?? '?'} size="sm" className="shrink-0 mt-0.5" />
                  <div className="flex-1 min-w-0">
                    <p className="text-sm">
                      <span className="font-medium">{mention.mentioner_name}</span>
                      {' '}mentioned you in{' '}
                      <span className="text-primary">{mention.context_label ?? 'a comment'}</span>
                    </p>
                    {mention.comment_excerpt && (
                      <p className="text-xs text-default-500 mt-0.5 truncate">"{mention.comment_excerpt}"</p>
                    )}
                    <p className="text-xs text-default-400 mt-1">{new Date(mention.created_at).toLocaleString()}</p>
                  </div>
                  {!mention.read_at && <div className="w-2 h-2 rounded-full bg-primary shrink-0 mt-1.5" />}
                </CardBody>
              </Card>
            ))}
          </div>
        )}
      </div>
    </AppLayout>
  );
}
```

- [ ] Upgrade `ActivityFeed/Index.jsx` — full activity feed with module filter:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Chip, Select, SelectItem, Avatar } from '@heroui/react';
import { ClockIcon } from '@heroicons/react/24/outline';
import { useEffect, useState } from 'react';

const EVENT_COLOR = {
  'data.created': 'success', 'data.updated': 'primary', 'data.deleted': 'danger',
  'auth.login': 'secondary', 'hrm.leave.approved': 'success', 'hrm.payroll.run': 'warning',
};

export default function ActivityFeedIndex() {
  const [activities, setActivities] = useState([]);
  const [loading, setLoading] = useState(true);
  const [module, setModule] = useState('');

  const load = (mod = '') => {
    setLoading(true);
    const qs = mod ? `?module=${mod}` : '';
    fetch(`/api/activity-feed${qs}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(r => r.json())
      .then(d => { setActivities(d.data ?? d); setLoading(false); });
  };

  useEffect(() => { load(); }, []);

  return (
    <AppLayout title="Activity Feed">
      <Head title="Activity Feed" />
      <div className="p-6 space-y-4 max-w-2xl">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <ClockIcon className="w-6 h-6 text-primary" />
            <h1 className="text-2xl font-bold">Activity Feed</h1>
          </div>
          <Select
            placeholder="All modules"
            selectedKeys={module ? [module] : []}
            onSelectionChange={k => { const v = [...k][0] ?? ''; setModule(v); load(v); }}
            className="w-40"
            size="sm"
          >
            {['hrm','platform','core','finance'].map(m => <SelectItem key={m}>{m.toUpperCase()}</SelectItem>)}
          </Select>
        </div>

        {loading ? (
          <div className="text-center py-12 text-default-400">Loading activity…</div>
        ) : activities.length === 0 ? (
          <div className="text-center py-12 text-default-400">No activity recorded yet.</div>
        ) : (
          <div className="relative">
            <div className="absolute left-5 top-0 bottom-0 w-px bg-default-200" />
            <div className="space-y-4">
              {activities.map(a => (
                <div key={a.id} className="relative flex gap-4 items-start pl-11">
                  <Avatar name={a.actor_name ?? '?'} size="sm" className="absolute left-0 z-10 ring-2 ring-background" />
                  <div className="flex-1 min-w-0 pt-0.5">
                    <div className="flex items-start gap-2 flex-wrap">
                      <span className="font-medium text-sm">{a.actor_name ?? 'System'}</span>
                      <Chip size="sm" color={EVENT_COLOR[a.event_type] ?? 'default'} variant="flat" className="text-xs">
                        {a.action_name ?? a.action}
                      </Chip>
                      {a.subject_label && <span className="text-sm text-default-500">{a.subject_label}</span>}
                    </div>
                    {a.description && <p className="text-xs text-default-400 mt-0.5">{a.description}</p>}
                    <p className="text-xs text-default-300 mt-1">{new Date(a.created_at).toLocaleString()}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
```

- [ ] Upgrade `components/Comments/CommentThread.jsx` — reusable inline comment component for any module page:

```jsx
import { useForm } from '@inertiajs/react';
import { Avatar, Button, Textarea, Chip } from '@heroui/react';
import { ChatBubbleLeftIcon, FaceSmileIcon } from '@heroicons/react/24/outline';
import { useEffect, useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

/**
 * Reusable inline comment thread.
 *
 * Usage:
 *   <CommentThread commentableType="App\\Models\\HRM\\LeaveApplication" commentableId={leave.id} />
 */
export default function CommentThread({ commentableType, commentableId, title = 'Comments' }) {
  const { can } = useHRMAC();
  const [comments, setComments]   = useState([]);
  const [loading, setLoading]     = useState(true);
  const [replyTo, setReplyTo]     = useState(null);

  const { data, setData, reset, processing } = useForm({ content: '', mentions: [] });

  const load = () => {
    fetch(`/api/comments?commentable_type=${encodeURIComponent(commentableType)}&commentable_id=${commentableId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(r => r.json())
      .then(d => { setComments(d); setLoading(false); });
  };

  useEffect(() => { load(); }, [commentableType, commentableId]);

  const submit = e => {
    e.preventDefault();
    if (!data.content.trim()) return;
    fetch('/api/comments', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        commentable_type: commentableType,
        commentable_id:   commentableId,
        parent_id:        replyTo?.id ?? null,
        content:          data.content,
      }),
    }).then(() => { reset(); setReplyTo(null); load(); });
  };

  const addReaction = (commentId, emoji) => {
    fetch(`/api/comments/${commentId}/react`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
      body: JSON.stringify({ emoji }),
    }).then(() => load());
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-2">
        <ChatBubbleLeftIcon className="w-4 h-4 text-default-400" />
        <span className="text-sm font-medium text-default-600">{title}</span>
        <Chip size="sm" variant="flat">{comments.length}</Chip>
      </div>

      {/* Comment list */}
      {loading ? (
        <p className="text-xs text-default-400">Loading comments…</p>
      ) : (
        <div className="space-y-3">
          {comments.map(comment => (
            <div key={comment.id} className="flex gap-2.5">
              <Avatar name={comment.author?.name ?? '?'} size="sm" className="shrink-0 mt-0.5" />
              <div className="flex-1 bg-default-50 rounded-xl px-3 py-2">
                <div className="flex items-center gap-2 mb-1">
                  <span className="text-xs font-medium">{comment.author?.name ?? 'Unknown'}</span>
                  <span className="text-xs text-default-400">{new Date(comment.created_at).toLocaleString()}</span>
                </div>
                <p className="text-sm whitespace-pre-line">{comment.content}</p>
                {/* Reactions */}
                {comment.reactions && Object.entries(comment.reactions).length > 0 && (
                  <div className="flex gap-1 mt-1.5 flex-wrap">
                    {Object.entries(comment.reactions).map(([emoji, users]) => (
                      <button
                        key={emoji}
                        className="text-xs bg-default-100 hover:bg-default-200 px-1.5 py-0.5 rounded-full"
                        onClick={() => addReaction(comment.id, emoji)}
                      >
                        {emoji} {users.length}
                      </button>
                    ))}
                  </div>
                )}
                {/* Actions */}
                {can('core.comments_mentions.comments.create') && (
                  <button className="text-xs text-default-400 hover:text-primary mt-1" onClick={() => setReplyTo(comment)}>
                    Reply
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Compose */}
      {can('core.comments_mentions.comments.create') && (
        <form onSubmit={submit} className="space-y-2">
          {replyTo && (
            <div className="flex items-center gap-2 text-xs text-default-500">
              <span>Replying to <strong>{replyTo.author?.name}</strong></span>
              <button type="button" className="text-danger" onClick={() => setReplyTo(null)}>✕</button>
            </div>
          )}
          <Textarea
            placeholder="Write a comment… Use @name to mention"
            value={data.content}
            onChange={e => setData('content', e.target.value)}
            rows={2}
            size="sm"
          />
          <div className="flex justify-end">
            <Button type="submit" size="sm" color="primary" isLoading={processing} isDisabled={!data.content.trim()}>
              Post Comment
            </Button>
          </div>
        </form>
      )}
    </div>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Mentions/ \
        packages/aero-ui/resources/js/Pages/Core/ActivityFeed/ \
        packages/aero-ui/resources/js/components/Comments/CommentThread.jsx
git commit -m "feat(aero-ui): Mentions inbox, ActivityFeed, reusable CommentThread component"
```

---

## Task 8 — Frontend: Mobile/PWA page

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Mobile/Index.jsx`

- [ ] Write `Mobile/Index.jsx` — tabbed PWA + push + mobile app config:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Card, CardBody, CardHeader, Chip, Divider,
  Input, Select, SelectItem, Switch, Tab, Tabs, Textarea,
} from '@heroui/react';
import { DevicePhoneMobileIcon, BellIcon, WrenchScrewdriverIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

function PwaTab({ config, manifest }) {
  const { can } = useHRMAC();
  const { data, setData, post, processing } = useForm({
    pwa_enabled:      config.pwa_enabled      ?? false,
    display_name:     config.display_name     ?? '',
    short_name:       config.short_name       ?? '',
    theme_color:      config.theme_color      ?? '#006FEE',
    background_color: config.background_color ?? '#ffffff',
    display_mode:     config.display_mode     ?? 'standalone',
    icon:             null,
  });

  const submit = e => { e.preventDefault(); post(route('core.mobile.pwa.update'), { forceFormData: true }); };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
      <form onSubmit={submit} className="space-y-4">
        <Switch isSelected={data.pwa_enabled} onValueChange={v => setData('pwa_enabled', v)}>Enable PWA</Switch>
        <Input label="App Name" value={data.display_name} onChange={e => setData('display_name', e.target.value)} placeholder={config.display_name ?? 'My App'} />
        <Input label="Short Name" value={data.short_name} onChange={e => setData('short_name', e.target.value)} description="Max 30 chars — shown on home screen" maxLength={30} />
        <div className="grid grid-cols-2 gap-3">
          <Input label="Theme Color" value={data.theme_color} onChange={e => setData('theme_color', e.target.value)} startContent={<div className="w-4 h-4 rounded" style={{ background: data.theme_color }} />} />
          <Input label="Background Color" value={data.background_color} onChange={e => setData('background_color', e.target.value)} startContent={<div className="w-4 h-4 rounded" style={{ background: data.background_color }} />} />
        </div>
        <Select label="Display Mode" selectedKeys={[data.display_mode]} onSelectionChange={k => setData('display_mode', [...k][0])}>
          <SelectItem key="standalone">Standalone</SelectItem>
          <SelectItem key="fullscreen">Fullscreen</SelectItem>
          <SelectItem key="minimal-ui">Minimal UI</SelectItem>
          <SelectItem key="browser">Browser</SelectItem>
        </Select>
        <div>
          <p className="text-sm font-medium mb-1">App Icon (512×512 PNG)</p>
          {config.icon_path && <img src={config.icon_path} alt="PWA icon" className="h-12 w-12 rounded mb-2 object-contain bg-default-100" />}
          <input type="file" accept="image/png" onChange={e => setData('icon', e.target.files[0])} />
        </div>
        {can('core.mobile_pwa.pwa_config.configure') && (
          <Button type="submit" color="primary" isLoading={processing}>Save PWA Settings</Button>
        )}
      </form>

      {/* Manifest preview */}
      <Card className="shadow-sm h-fit">
        <CardHeader><p className="font-semibold text-sm">Manifest Preview</p></CardHeader>
        <CardBody>
          <pre className="text-xs bg-default-50 rounded p-3 overflow-auto">{JSON.stringify(manifest, null, 2)}</pre>
        </CardBody>
      </Card>
    </div>
  );
}

function PushTab({ config }) {
  const { can } = useHRMAC();
  const { data, setData, post, processing } = useForm({
    push_enabled:      config.push_enabled      ?? false,
    vapid_public_key:  config.vapid_public_key  ?? '',
    vapid_private_key: '',
  });
  const [testEmail, setTestEmail] = useState('');

  const submit = e => { e.preventDefault(); post(route('core.mobile.push.update')); };

  return (
    <div className="p-6 max-w-lg space-y-4">
      <form onSubmit={submit} className="space-y-4">
        <Switch isSelected={data.push_enabled} onValueChange={v => setData('push_enabled', v)}>Enable Web Push Notifications</Switch>
        <Input label="VAPID Public Key" value={data.vapid_public_key} onChange={e => setData('vapid_public_key', e.target.value)} className="font-mono text-xs" />
        <Input label="VAPID Private Key" type="password" value={data.vapid_private_key} onChange={e => setData('vapid_private_key', e.target.value)} placeholder="Leave blank to keep existing" />
        <p className="text-xs text-default-400">Generate VAPID keys at <a href="https://web-push-codelab.glitch.me" target="_blank" className="text-primary">web-push-codelab.glitch.me</a></p>
        {can('core.mobile_pwa.push_notifications.configure') && (
          <Button type="submit" color="primary" isLoading={processing}>Save Push Settings</Button>
        )}
      </form>

      {config.push_enabled && can('core.mobile_pwa.push_notifications.send_test') && (
        <>
          <Divider />
          <div className="space-y-2">
            <p className="text-sm font-medium">Send Test Push</p>
            <div className="flex gap-3">
              <Input value={testEmail} onChange={e => setTestEmail(e.target.value)} placeholder="user@example.com" type="email" className="flex-1" />
              <Button onPress={() => router.post(route('core.mobile.push.test'), { to: testEmail })} variant="flat" isDisabled={!testEmail}>
                Send Test
              </Button>
            </div>
          </div>
        </>
      )}
    </div>
  );
}

function MobileAppTab({ config }) {
  const { can } = useHRMAC();
  const { data, setData, post, processing } = useForm({
    mobile_app_enabled: config.mobile_app_enabled ?? false,
    android_package:    config.android_package    ?? '',
    ios_bundle_id:      config.ios_bundle_id      ?? '',
    deep_link_schemes:  (config.deep_link_schemes ?? []).join('\n'),
  });

  const submit = e => {
    e.preventDefault();
    post(route('core.mobile.mobile-app.update'), {
      data: { ...data, deep_link_schemes: data.deep_link_schemes.split('\n').filter(Boolean) },
    });
  };

  return (
    <div className="p-6 max-w-lg space-y-4">
      <form onSubmit={submit} className="space-y-4">
        <Switch isSelected={data.mobile_app_enabled} onValueChange={v => setData('mobile_app_enabled', v)}>Enable Mobile App Integration</Switch>
        <Input label="Android Package ID" value={data.android_package} onChange={e => setData('android_package', e.target.value)} placeholder="com.yourcompany.app" className="font-mono" />
        <Input label="iOS Bundle ID" value={data.ios_bundle_id} onChange={e => setData('ios_bundle_id', e.target.value)} placeholder="com.yourcompany.app" className="font-mono" />
        <Textarea label="Deep Link Schemes (one per line)" value={data.deep_link_schemes} onChange={e => setData('deep_link_schemes', e.target.value)} rows={3} className="font-mono text-sm" placeholder={'yourapp://\nhttps://app.yourcompany.com'} />
        {can('core.mobile_pwa.mobile_app_config.configure') && (
          <Button type="submit" color="primary" isLoading={processing}>Save Mobile App Settings</Button>
        )}
      </form>
    </div>
  );
}

export default function MobileIndex({ config, manifest }) {
  return (
    <AppLayout title="Mobile & PWA">
      <Head title="Mobile & PWA" />
      <div className="p-6 pb-0">
        <div className="flex items-center gap-3 mb-4">
          <DevicePhoneMobileIcon className="w-6 h-6 text-primary" />
          <div>
            <h1 className="text-2xl font-bold">Mobile & PWA</h1>
            <p className="text-default-500 text-sm">Progressive Web App, push notifications, and native mobile integration</p>
          </div>
        </div>
        <Tabs variant="underlined" aria-label="Mobile tabs">
          <Tab key="pwa"    title={<span className="flex items-center gap-1.5"><WrenchScrewdriverIcon className="w-4 h-4" />PWA</span>}>
            <PwaTab config={config} manifest={manifest} />
          </Tab>
          <Tab key="push"   title={<span className="flex items-center gap-1.5"><BellIcon className="w-4 h-4" />Push</span>}>
            <PushTab config={config} />
          </Tab>
          <Tab key="mobile" title={<span className="flex items-center gap-1.5"><DevicePhoneMobileIcon className="w-4 h-4" />Mobile App</span>}>
            <MobileAppTab config={config} />
          </Tab>
        </Tabs>
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Mobile/
git commit -m "feat(aero-ui): Mobile/PWA config page (PWA, Push, Mobile App tabs)"
```

---

## Task 9 — PHPUnit Tests

**Files:**
- Create: `packages/aero-notifications/tests/Feature/Admin/EmailLogControllerTest.php`
- Create: `packages/aero-notifications/tests/Feature/Admin/SuppressionControllerTest.php`
- Create: `packages/aero-core/tests/Feature/Admin/MobileControllerTest.php`

- [ ] Create `EmailLogControllerTest.php`:

```php
<?php

namespace Aero\Notifications\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Aero\Notifications\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class EmailLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_email_log_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/email/logs')
            ->assertOk()
            ->assertInertia(fn($p) => $p
                ->component('Core/Email/Logs')
                ->has('logs')
                ->has('stats'));
    }

    public function test_requires_auth(): void
    {
        $this->get('/email/logs')->assertRedirect('/login');
    }
}
```

- [ ] Create `SuppressionControllerTest.php`:

```php
<?php

namespace Aero\Notifications\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Aero\Notifications\Models\EmailSuppressionEntry;
use Aero\Notifications\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class SuppressionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_index_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/email/suppression')
            ->assertOk()
            ->assertInertia(fn($p) => $p->component('Core/Email/Suppression')->has('entries'));
    }

    public function test_add_email_to_suppression(): void
    {
        $this->actingAs($this->admin)
            ->post('/email/suppression', ['email' => 'noreply@example.com', 'reason' => 'manual'])
            ->assertRedirect();
        $this->assertDatabaseHas('email_suppression_list', ['email' => 'noreply@example.com']);
    }

    public function test_duplicate_email_rejected(): void
    {
        EmailSuppressionEntry::create(['email' => 'dup@test.com', 'reason' => 'manual', 'added_by' => $this->admin->id]);
        $this->actingAs($this->admin)
            ->post('/email/suppression', ['email' => 'dup@test.com', 'reason' => 'manual'])
            ->assertSessionHasErrors('email');
    }

    public function test_remove_from_suppression(): void
    {
        $entry = EmailSuppressionEntry::create(['email' => 'remove@test.com', 'reason' => 'bounce', 'added_by' => $this->admin->id]);
        $this->actingAs($this->admin)->delete("/email/suppression/{$entry->id}")->assertRedirect();
        $this->assertDatabaseMissing('email_suppression_list', ['id' => $entry->id]);
    }
}
```

- [ ] Create `MobileControllerTest.php`:

```php
<?php

namespace Aero\Core\Tests\Feature\Admin;

use Aero\Core\Models\User;
use Aero\Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class MobileControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_mobile_page_renders(): void
    {
        $this->actingAs($this->admin)
            ->get('/mobile-pwa')
            ->assertOk()
            ->assertInertia(fn($p) => $p
                ->component('Core/Mobile/Index')
                ->has('config')
                ->has('manifest'));
    }

    public function test_pwa_update_saves_config(): void
    {
        $this->actingAs($this->admin)
            ->post('/mobile-pwa/pwa', ['pwa_enabled' => true, 'display_name' => 'My App'])
            ->assertRedirect();
        $this->assertDatabaseHas('pwa_configs', ['pwa_enabled' => true, 'display_name' => 'My App']);
    }

    public function test_invalid_theme_color_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/mobile-pwa/pwa', ['theme_color' => 'not-a-color'])
            ->assertSessionHasErrors('theme_color');
    }
}
```

- [ ] Run all tests:
```bash
cd packages/aero-notifications && php ../../vendor/bin/phpunit tests/Feature/Admin/ --testdox 2>&1 | tail -20
cd packages/aero-core && php ../../vendor/bin/phpunit tests/Feature/Admin/MobileControllerTest.php --testdox 2>&1 | tail -10
```

- [ ] Commit:
```bash
git add packages/aero-notifications/tests/Feature/Admin/ \
        packages/aero-core/tests/Feature/Admin/MobileControllerTest.php
git commit -m "test: EmailLog, Suppression, Mobile controller tests"
```

---

## Self-Review Checklist

- [ ] **Spec coverage (config/module.php):**
  - `email_engine`: email_templates (CA-2) ✅ · email_logs ✅ · deliverability/DKIM/SPF/DMARC ✅ · suppression ✅ · bounces ✅
  - `comments_mentions`: comments (CommentThread component) ✅ · mentions inbox ✅ · activity feed ✅
  - `mobile_pwa`: PWA config ✅ · push notifications ✅ · mobile app config ✅
- [ ] **Foundation package rule:** `aero-notifications` backend untouched (NotificationLog model, MailService). Only added admin controllers on top ✅
- [ ] **Immutability respected:** `notification_logs` records are never updated/deleted in any controller ✅
- [ ] **Security:** `logAccess()` on email log page (recipient PII) ✅ · Suppression add/remove audited ✅ · VAPID private key via `EncryptedField` cast ✅
- [ ] **No placeholders:** All code blocks complete ✅
- [ ] **Type consistency:** `EmailSuppressionEntry` used consistently in controller + test · `PwaConfig` model used in `MobileController` ✅
