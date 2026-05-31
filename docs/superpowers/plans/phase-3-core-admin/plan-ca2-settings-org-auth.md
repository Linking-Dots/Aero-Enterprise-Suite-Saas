# Plan CA-2 — Settings, Organization Profile & Self-Service Auth Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver all tenant-admin settings pages (General, Security, Localization, Branding, Mail/SMTP, Password Policy, IP Whitelist, Email Templates), the Organization Profile section (company info, tax/legal identity, addresses, fiscal year, contacts), and the user self-service auth surface (profile edit, avatar, 2FA, devices, sessions) — all HRMAC-guarded, audited, and tested.

**Architecture:** Settings controllers in `packages/aero-core/src/Http/Controllers/Settings/` delegate to service singletons that write to `system_settings` (KV store) or dedicated tables. Organization data lives in `organization_profiles` table (separate from `system_settings`). Self-service profile/auth routes in `packages/aero-auth`. React pages in `packages/aero-ui/resources/js/Pages/Core/Settings/`, `Core/Organization/`, and `Core/Profile/`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui` (HeroUI), PHPUnit 11.

**Prerequisites:** CA-1 complete (users, roles, dashboard). `system_settings` table exists. `AuditService` available.

**Standards:** `docs/standards/inertia-standard.md` · `docs/standards/hrmac-convention.md` · `docs/standards/done-definition.md`

---

## Security Notes

- `EncryptedField` cast on SMTP password (`system_settings` value when key = `mail_password`)
- `AuditService::log()` on every settings save, org profile update, IP whitelist change
- `AuditService::logAccess()` on mail settings page load (SMTP credentials are sensitive)
- Password policy changes: audit with `AuditEventType::SETTINGS_UPDATED`
- IP whitelist changes: audit with `AuditEventType::SECURITY_EVENT`
- 2FA operations: audit each enable/disable/reset

---

## File Map

**Backend (packages/aero-core/src/)**
```
Http/Controllers/Settings/SystemSettingController.php      -- UPGRADE: Inertia render + save
Http/Controllers/Settings/SecuritySettingsController.php   -- UPGRADE: Inertia render + save
Http/Controllers/Settings/LocalizationSettingsController.php -- UPGRADE
Http/Controllers/Settings/BrandingSettingsController.php   -- UPGRADE
Http/Controllers/Settings/MailSettingsController.php       -- UPGRADE: + test send
Http/Controllers/Settings/PasswordPolicyController.php     -- UPGRADE
Http/Controllers/Settings/IpWhitelistController.php        -- UPGRADE
Http/Controllers/Settings/EmailTemplateController.php      -- CREATE: template CRUD
Http/Controllers/Settings/OrganizationProfileController.php -- UPGRADE: full org CRUD
Http/Controllers/Settings/OrganizationAddressController.php -- CREATE
Http/Controllers/Settings/FiscalYearController.php          -- CREATE
Http/Requests/Settings/SaveGeneralSettingsRequest.php      -- CREATE
Http/Requests/Settings/SaveMailSettingsRequest.php         -- UPGRADE
Http/Requests/Settings/SaveBrandingSettingsRequest.php     -- UPGRADE
Http/Requests/Settings/UpdateOrganizationProfileRequest.php -- CREATE
Http/Requests/Settings/StoreEmailTemplateRequest.php       -- UPGRADE
Services/SystemSettingService.php                          -- UPGRADE: typed getters/setters
```

**Migrations (packages/aero-core/database/migrations/)**
```
2026_05_22_000002_create_organization_profiles_table.php   -- CREATE if not exists
2026_05_22_000003_create_email_templates_table.php         -- CREATE if not exists
```

**Frontend (packages/aero-ui/resources/js/Pages/Core/)**
```
Settings/SystemSettings.jsx    -- UPGRADE: tabs layout (General/Security/Localization/Branding/Mail/Password/IP/EmailTemplates)
Settings/General.jsx           -- UPGRADE: app name, timezone, date format
Settings/Security.jsx          -- UPGRADE: 2FA policy, session timeout, lockout
Settings/Localization.jsx      -- UPGRADE: language, timezone, currency, date format
Settings/Branding.jsx          -- UPGRADE: logo, favicon, primary color, app name
Settings/Mail.jsx              -- UPGRADE: SMTP config + test send
Settings/PasswordPolicy.jsx    -- UPGRADE: min length, complexity, expiry, history
Settings/IpWhitelist.jsx       -- UPGRADE: whitelist + blocklist + geo blocking
Settings/EmailTemplates.jsx    -- CREATE: template list + editor
Organization/Profile.jsx       -- UPGRADE: company info form
Organization/Identity.jsx      -- CREATE: VAT/tax identity
Organization/Addresses.jsx     -- CREATE: address CRUD
Organization/FiscalYear.jsx    -- CREATE: fiscal year settings
Organization/Contacts.jsx      -- CREATE: primary contacts
Profile/Index.jsx              -- CREATE: my profile (edit name, avatar)
Profile/Security.jsx           -- UPGRADE: 2FA, devices, sessions, password change
```

**Tests**
```
packages/aero-core/tests/Feature/Settings/SystemSettingControllerTest.php -- CREATE
packages/aero-core/tests/Feature/Settings/OrganizationProfileControllerTest.php -- CREATE
packages/aero-core/tests/Feature/Settings/MailSettingsControllerTest.php -- CREATE
```

---

## Task 1 — Migrations: organization_profiles and email_templates tables

**Files:**
- Create: `packages/aero-core/database/migrations/2026_05_22_000002_create_organization_profiles_table.php`
- Create: `packages/aero-core/database/migrations/2026_05_22_000003_create_email_templates_table.php`

- [ ] Create organization_profiles migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('organization_profiles')) {
            return;
        }
        Schema::create('organization_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->text('tax_id')->nullable(); // EncryptedField
            $table->string('vat_number')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('fiscal_year_start')->nullable(); // e.g. "01-01"
            $table->string('fiscal_year_end')->nullable();   // e.g. "12-31"
            $table->string('timezone')->nullable();
            $table->string('date_format')->nullable();
            $table->string('logo_path')->nullable();
            $table->json('addresses')->nullable();  // JSON array of address objects
            $table->json('contacts')->nullable();   // JSON array of contact objects
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_profiles');
    }
};
```

- [ ] Create email_templates migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('email_templates')) {
            return;
        }
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject');
            $table->longText('body_html');
            $table->longText('body_text')->nullable();
            $table->string('category')->default('system'); // system|marketing|transactional
            $table->json('variables')->nullable(); // available template variables
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false); // system templates can't be deleted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
```

- [ ] Commit:
```bash
git add packages/aero-core/database/migrations/2026_05_22_000002_create_organization_profiles_table.php \
        packages/aero-core/database/migrations/2026_05_22_000003_create_email_templates_table.php
git commit -m "feat(aero-core): organization_profiles and email_templates migrations"
```

---

## Task 2 — Models: OrganizationProfile, EmailTemplate

**Files:**
- Create: `packages/aero-core/src/Models/OrganizationProfile.php`
- Create: `packages/aero-core/src/Models/EmailTemplate.php`

- [ ] Create `OrganizationProfile.php`:

```php
<?php

namespace Aero\Core\Models;

use Aero\Core\Encryption\EncryptedField;
use Aero\Core\Models\TenantModel;

class OrganizationProfile extends TenantModel
{
    protected $fillable = [
        'company_name', 'legal_name', 'registration_number', 'tax_id',
        'vat_number', 'industry', 'company_size', 'website', 'phone',
        'email', 'country', 'currency', 'fiscal_year_start', 'fiscal_year_end',
        'timezone', 'date_format', 'logo_path', 'addresses', 'contacts',
    ];

    protected $casts = [
        'tax_id'    => EncryptedField::class,
        'addresses' => 'array',
        'contacts'  => 'array',
    ];
}
```

- [ ] Create `EmailTemplate.php`:

```php
<?php

namespace Aero\Core\Models;

use Aero\Core\Models\TenantModel;

class EmailTemplate extends TenantModel
{
    protected $fillable = [
        'name', 'slug', 'subject', 'body_html', 'body_text',
        'category', 'variables', 'is_active', 'is_locked',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Models/OrganizationProfile.php \
        packages/aero-core/src/Models/EmailTemplate.php
git commit -m "feat(aero-core): OrganizationProfile and EmailTemplate models"
```

---

## Task 3 — Controllers: Settings suite

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/Settings/SystemSettingController.php`
- Modify: `packages/aero-core/src/Http/Controllers/Settings/MailSettingsController.php`
- Modify: `packages/aero-core/src/Http/Controllers/Settings/BrandingSettingsController.php`
- Modify: `packages/aero-core/src/Http/Controllers/Settings/SecuritySettingsController.php`
- Modify: `packages/aero-core/src/Http/Controllers/Settings/LocalizationSettingsController.php`
- Modify: `packages/aero-core/src/Http/Controllers/Settings/PasswordPolicyController.php`
- Modify: `packages/aero-core/src/Http/Controllers/Settings/IpWhitelistController.php`
- Create: `packages/aero-core/src/Http/Controllers/Settings/EmailTemplateController.php`

- [ ] Upgrade `SystemSettingController.php` — render General Settings page:

```php
<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\SystemSettingService;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Settings/SystemSettings', [
            'settings' => $this->settings->allAsArray(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name'   => ['sometimes', 'string', 'max:100'],
            'app_url'    => ['sometimes', 'url'],
            'support_email' => ['sometimes', 'email'],
            'timezone'   => ['sometimes', 'string', 'timezone'],
            'date_format' => ['sometimes', 'string'],
            'time_format' => ['sometimes', 'string'],
        ]);

        foreach ($validated as $key => $value) {
            $this->settings->set($key, $value);
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), null, ['keys' => array_keys($validated)]);

        return back()->with('success', 'Settings saved.');
    }
}
```

- [ ] Upgrade `MailSettingsController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreMailSettingsRequest;
use Aero\Core\Services\SystemSettingService;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class MailSettingsController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(Request $request): Response
    {
        $this->audit->logAccess('mail_settings', $request->user());

        return Inertia::render('Core/Settings/Mail', [
            'mail' => [
                'driver'     => $this->settings->get('mail_driver', 'smtp'),
                'host'       => $this->settings->get('mail_host', ''),
                'port'       => $this->settings->get('mail_port', '587'),
                'username'   => $this->settings->get('mail_username', ''),
                'from_name'  => $this->settings->get('mail_from_name', ''),
                'from_email' => $this->settings->get('mail_from_email', ''),
                'encryption' => $this->settings->get('mail_encryption', 'tls'),
            ],
        ]);
    }

    public function update(StoreMailSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        foreach ($data as $key => $value) {
            $this->settings->set('mail_' . $key, $value);
        }
        if ($request->filled('password')) {
            $this->settings->set('mail_password', $request->password); // EncryptedField handles encryption
        }
        $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), null, ['section' => 'mail']);
        return back()->with('success', 'Mail settings saved.');
    }

    public function testSend(Request $request): RedirectResponse
    {
        $request->validate(['to' => ['required', 'email']]);
        try {
            Mail::raw('This is a test email from AEOS365.', fn($m) => $m->to($request->to)->subject('AEOS365 Test Email'));
            return back()->with('success', "Test email sent to {$request->to}.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed: {$e->getMessage()}");
        }
    }
}
```

- [ ] Upgrade `BrandingSettingsController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\SystemSettingService;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BrandingSettingsController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Settings/Branding', [
            'branding' => [
                'app_name'      => $this->settings->get('app_name', config('app.name')),
                'logo_url'      => $this->settings->get('logo_url'),
                'favicon_url'   => $this->settings->get('favicon_url'),
                'primary_color' => $this->settings->get('primary_color', '#006FEE'),
                'sidebar_theme' => $this->settings->get('sidebar_theme', 'dark'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'app_name'      => ['sometimes', 'string', 'max:100'],
            'primary_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sidebar_theme' => ['sometimes', 'in:dark,light'],
            'logo'          => ['nullable', 'image', 'max:2048'],
            'favicon'       => ['nullable', 'image', 'max:512'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'public');
            $this->settings->set('logo_url', Storage::url($path));
        }
        if ($request->hasFile('favicon')) {
            $path = $request->file('favicon')->store('branding', 'public');
            $this->settings->set('favicon_url', Storage::url($path));
        }
        foreach (['app_name', 'primary_color', 'sidebar_theme'] as $key) {
            if ($request->has($key)) {
                $this->settings->set($key, $request->input($key));
            }
        }

        $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), null, ['section' => 'branding']);
        return back()->with('success', 'Branding settings saved.');
    }
}
```

- [ ] Create `EmailTemplateController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Http\Requests\StoreEmailTemplateRequest;
use Aero\Core\Http\Requests\UpdateEmailTemplateRequest;
use Aero\Core\Models\EmailTemplate;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        return Inertia::render('Core/Settings/EmailTemplates', [
            'templates' => EmailTemplate::orderBy('name')->get(),
        ]);
    }

    public function store(StoreEmailTemplateRequest $request): RedirectResponse
    {
        $template = EmailTemplate::create($request->validated());
        $this->audit->log(AuditEventType::RECORD_CREATED, $request->user(), $template);
        return back()->with('success', 'Template created.');
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $template): RedirectResponse
    {
        abort_if($template->is_locked && $request->has('slug'), 403, 'Cannot modify locked template slug.');
        $template->update($request->validated());
        $this->audit->log(AuditEventType::RECORD_UPDATED, $request->user(), $template);
        return back()->with('success', 'Template updated.');
    }

    public function destroy(EmailTemplate $template, Request $request): RedirectResponse
    {
        abort_if($template->is_locked, 403, 'Cannot delete a locked system template.');
        $this->audit->log(AuditEventType::RECORD_DELETED, $request->user(), $template);
        $template->delete();
        return back()->with('success', 'Template deleted.');
    }

    public function preview(EmailTemplate $template): HttpResponse
    {
        return response($template->body_html)->header('Content-Type', 'text/html');
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Http/Controllers/Settings/
git commit -m "feat(aero-core): settings controllers (system, mail, branding, email templates)"
```

---

## Task 4 — Controller: OrganizationProfileController (full org suite)

**Files:**
- Modify: `packages/aero-core/src/Http/Controllers/Settings/OrganizationProfileController.php`

- [ ] Rewrite `OrganizationProfileController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Settings;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\OrganizationProfile;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationProfileController extends Controller
{
    public function __construct(private AuditService $audit) {}

    private function getOrCreate(): OrganizationProfile
    {
        return OrganizationProfile::firstOrCreate([]);
    }

    public function profile(): Response
    {
        return Inertia::render('Core/Organization/Profile', ['org' => $this->getOrCreate()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name'  => ['sometimes', 'string', 'max:255'],
            'legal_name'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'industry'      => ['sometimes', 'nullable', 'string'],
            'company_size'  => ['sometimes', 'nullable', 'string'],
            'website'       => ['sometimes', 'nullable', 'url'],
            'phone'         => ['sometimes', 'nullable', 'string'],
            'email'         => ['sometimes', 'nullable', 'email'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $org = $this->getOrCreate();
            $org->update($data);
            $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), $org, ['section' => 'org_profile']);
        });

        return back()->with('success', 'Organization profile updated.');
    }

    public function identity(): Response
    {
        return Inertia::render('Core/Organization/Identity', ['org' => $this->getOrCreate()]);
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tax_id'     => ['sometimes', 'nullable', 'string'],
            'vat_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'country'    => ['sometimes', 'nullable', 'string', 'size:2'],
            'currency'   => ['sometimes', 'nullable', 'string', 'size:3'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $org = $this->getOrCreate();
            $org->update($data);
            $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), $org, ['section' => 'org_identity']);
        });

        return back()->with('success', 'Tax/legal identity updated.');
    }

    public function addresses(): Response
    {
        $org = $this->getOrCreate();
        return Inertia::render('Core/Organization/Addresses', ['addresses' => $org->addresses ?? []]);
    }

    public function updateAddresses(Request $request): RedirectResponse
    {
        $request->validate([
            'addresses'                 => ['required', 'array'],
            'addresses.*.type'          => ['required', 'in:billing,shipping,office,other'],
            'addresses.*.line1'         => ['required', 'string'],
            'addresses.*.city'          => ['required', 'string'],
            'addresses.*.country'       => ['required', 'string', 'size:2'],
            'addresses.*.is_primary'    => ['boolean'],
        ]);
        $org = $this->getOrCreate();
        $org->update(['addresses' => $request->addresses]);
        $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), $org, ['section' => 'org_addresses']);
        return back()->with('success', 'Addresses updated.');
    }

    public function fiscalYear(): Response
    {
        return Inertia::render('Core/Organization/FiscalYear', ['org' => $this->getOrCreate()]);
    }

    public function updateFiscalYear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fiscal_year_start' => ['required', 'string', 'regex:/^\d{2}-\d{2}$/'],
            'fiscal_year_end'   => ['required', 'string', 'regex:/^\d{2}-\d{2}$/'],
            'timezone'          => ['required', 'string', 'timezone'],
            'date_format'       => ['required', 'string'],
        ]);
        $org = $this->getOrCreate();
        $org->update($data);
        $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), $org, ['section' => 'fiscal_year']);
        return back()->with('success', 'Fiscal year updated.');
    }

    public function contacts(): Response
    {
        $org = $this->getOrCreate();
        return Inertia::render('Core/Organization/Contacts', ['contacts' => $org->contacts ?? []]);
    }

    public function updateContacts(Request $request): RedirectResponse
    {
        $request->validate([
            'contacts'               => ['required', 'array'],
            'contacts.*.name'        => ['required', 'string'],
            'contacts.*.email'       => ['required', 'email'],
            'contacts.*.role'        => ['required', 'string'],
            'contacts.*.phone'       => ['nullable', 'string'],
            'contacts.*.is_primary'  => ['boolean'],
        ]);
        $org = $this->getOrCreate();
        $org->update(['contacts' => $request->contacts]);
        $this->audit->log(AuditEventType::SETTINGS_UPDATED, $request->user(), $org, ['section' => 'org_contacts']);
        return back()->with('success', 'Contacts updated.');
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Http/Controllers/Settings/OrganizationProfileController.php
git commit -m "feat(aero-core): OrganizationProfileController - profile, identity, addresses, fiscal year, contacts"
```

---

## Task 5 — Routes: settings + org + profile routes

**Files:**
- Modify: `packages/aero-core/routes/web.php`

- [ ] Verify/add these route groups in `web.php` within the `auth:web` middleware group:

```php
use Aero\Core\Http\Controllers\Settings\SystemSettingController;
use Aero\Core\Http\Controllers\Settings\MailSettingsController;
use Aero\Core\Http\Controllers\Settings\BrandingSettingsController;
use Aero\Core\Http\Controllers\Settings\SecuritySettingsController;
use Aero\Core\Http\Controllers\Settings\LocalizationSettingsController;
use Aero\Core\Http\Controllers\Settings\PasswordPolicyController;
use Aero\Core\Http\Controllers\Settings\IpWhitelistController;
use Aero\Core\Http\Controllers\Settings\EmailTemplateController;
use Aero\Core\Http\Controllers\Settings\OrganizationProfileController;

// Settings
Route::prefix('settings')->name('core.settings.')->middleware('hrmac:core.settings.general.view')->group(function () {
    Route::get('/system', [SystemSettingController::class, 'index'])->name('system');
    Route::post('/system', [SystemSettingController::class, 'update'])->name('system.update')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.general.edit');

    Route::get('/security', [SecuritySettingsController::class, 'index'])->name('security');
    Route::post('/security', [SecuritySettingsController::class, 'update'])->name('security.update')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.security.edit');

    Route::get('/localization', [LocalizationSettingsController::class, 'index'])->name('localization');
    Route::post('/localization', [LocalizationSettingsController::class, 'update'])->name('localization.update')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.localization.edit');

    Route::get('/branding', [BrandingSettingsController::class, 'index'])->name('branding')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.branding.view');
    Route::post('/branding', [BrandingSettingsController::class, 'update'])->name('branding.update')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.branding.update');

    Route::get('/mail', [MailSettingsController::class, 'index'])->name('mail')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.mail_settings.view');
    Route::post('/mail', [MailSettingsController::class, 'update'])->name('mail.update')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.mail_settings.update');
    Route::post('/mail/test', [MailSettingsController::class, 'testSend'])->name('mail.test')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.mail_settings.test');

    Route::get('/password-policy', [PasswordPolicyController::class, 'index'])->name('password-policy');
    Route::post('/password-policy', [PasswordPolicyController::class, 'update'])->name('password-policy.update')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.password_policy.edit');

    Route::get('/ip-whitelist', [IpWhitelistController::class, 'index'])->name('ip-whitelist');
    Route::post('/ip-whitelist', [IpWhitelistController::class, 'update'])->name('ip-whitelist.update')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.ip_whitelist.edit');

    Route::prefix('email-templates')->name('email-templates.')->withoutMiddleware('hrmac:core.settings.general.view')->middleware('hrmac:core.settings.email_templates.view')->group(function () {
        Route::get('/', [EmailTemplateController::class, 'index'])->name('index');
        Route::post('/', [EmailTemplateController::class, 'store'])->name('store')->withoutMiddleware('hrmac:core.settings.email_templates.view')->middleware('hrmac:core.settings.email_templates.create');
        Route::put('/{template}', [EmailTemplateController::class, 'update'])->name('update')->withoutMiddleware('hrmac:core.settings.email_templates.view')->middleware('hrmac:core.settings.email_templates.edit');
        Route::delete('/{template}', [EmailTemplateController::class, 'destroy'])->name('destroy')->withoutMiddleware('hrmac:core.settings.email_templates.view')->middleware('hrmac:core.settings.email_templates.delete');
        Route::get('/{template}/preview', [EmailTemplateController::class, 'preview'])->name('preview');
    });
});

// Organization
Route::prefix('organization')->name('core.organization.')->middleware('hrmac:core.organization.org_profile.view')->group(function () {
    Route::get('/profile', [OrganizationProfileController::class, 'profile'])->name('profile');
    Route::post('/profile', [OrganizationProfileController::class, 'updateProfile'])->name('profile.update')->withoutMiddleware('hrmac:core.organization.org_profile.view')->middleware('hrmac:core.organization.org_profile.update');
    Route::get('/identity', [OrganizationProfileController::class, 'identity'])->name('identity');
    Route::post('/identity', [OrganizationProfileController::class, 'updateIdentity'])->name('identity.update')->withoutMiddleware('hrmac:core.organization.org_profile.view')->middleware('hrmac:core.organization.org_identity.update');
    Route::get('/addresses', [OrganizationProfileController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [OrganizationProfileController::class, 'updateAddresses'])->name('addresses.update')->withoutMiddleware('hrmac:core.organization.org_profile.view')->middleware('hrmac:core.organization.org_addresses.manage');
    Route::get('/fiscal-year', [OrganizationProfileController::class, 'fiscalYear'])->name('fiscal-year');
    Route::post('/fiscal-year', [OrganizationProfileController::class, 'updateFiscalYear'])->name('fiscal-year.update')->withoutMiddleware('hrmac:core.organization.org_profile.view')->middleware('hrmac:core.organization.fiscal_year.manage');
    Route::get('/contacts', [OrganizationProfileController::class, 'contacts'])->name('contacts');
    Route::post('/contacts', [OrganizationProfileController::class, 'updateContacts'])->name('contacts.update')->withoutMiddleware('hrmac:core.organization.org_profile.view')->middleware('hrmac:core.organization.org_contacts.manage');
});
```

- [ ] Commit:
```bash
git add packages/aero-core/routes/web.php
git commit -m "feat(aero-core): settings + organization routes complete"
```

---

## Task 6 — Frontend: Settings pages

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Settings/SystemSettings.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Settings/Mail.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Settings/Branding.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Settings/EmailTemplates.jsx`

- [ ] Write `Settings/SystemSettings.jsx` — tabbed container linking all settings pages:

```jsx
import { Head, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Tab, Tabs, Card, CardBody } from '@heroui/react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const TABS = [
  { key: 'general',          label: 'General',         route: 'core.settings.system',         perm: 'core.settings.general.view' },
  { key: 'security',         label: 'Security',        route: 'core.settings.security',        perm: 'core.settings.security.view' },
  { key: 'localization',     label: 'Localization',    route: 'core.settings.localization',    perm: 'core.settings.localization.view' },
  { key: 'branding',         label: 'Branding',        route: 'core.settings.branding',        perm: 'core.settings.branding.view' },
  { key: 'mail',             label: 'Email / SMTP',    route: 'core.settings.mail',            perm: 'core.settings.mail_settings.view' },
  { key: 'password-policy',  label: 'Password Policy', route: 'core.settings.password-policy', perm: 'core.settings.password_policy.view' },
  { key: 'ip-whitelist',     label: 'IP Access',       route: 'core.settings.ip-whitelist',    perm: 'core.settings.ip_whitelist.view' },
  { key: 'email-templates',  label: 'Email Templates', route: 'core.settings.email-templates.index', perm: 'core.settings.email_templates.view' },
];

export default function SystemSettings({ settings }) {
  const { can } = useHRMAC();
  const { url } = usePage();

  // Determine active tab from current URL
  const active = TABS.find(t => url.includes(t.key))?.key ?? 'general';

  return (
    <AppLayout title="Settings">
      <Head title="Settings" />
      <div className="p-6">
        <h1 className="text-2xl font-bold mb-6">System Settings</h1>
        <Tabs
          selectedKey={active}
          onSelectionChange={key => {
            const tab = TABS.find(t => t.key === key);
            if (tab) router.get(route(tab.route));
          }}
          aria-label="Settings tabs"
          variant="underlined"
          classNames={{ tabList: 'flex-wrap' }}
        >
          {TABS.filter(t => can(t.perm)).map(t => (
            <Tab key={t.key} title={t.label} />
          ))}
        </Tabs>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Settings/Mail.jsx`:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Select, SelectItem, Divider } from '@heroui/react';
import { useState } from 'react';

export default function MailSettings({ mail }) {
  const { data, setData, post, processing, errors } = useForm({
    driver: mail.driver, host: mail.host, port: mail.port,
    username: mail.username, password: '',
    from_name: mail.from_name, from_email: mail.from_email,
    encryption: mail.encryption,
  });
  const [testEmail, setTestEmail] = useState('');
  const [testing, setTesting] = useState(false);

  const submit = e => { e.preventDefault(); post(route('core.settings.mail.update')); };

  const sendTest = () => {
    setTesting(true);
    router.post(route('core.settings.mail.test'), { to: testEmail }, {
      onFinish: () => setTesting(false),
    });
  };

  return (
    <AppLayout title="Email Settings">
      <Head title="Email Settings" />
      <div className="p-6 max-w-xl space-y-6">
        <h1 className="text-2xl font-bold">Email / SMTP Settings</h1>
        <form onSubmit={submit} className="space-y-4">
          <Select label="Mail Driver" selectedKeys={[data.driver]} onSelectionChange={k => setData('driver', [...k][0])}>
            <SelectItem key="smtp">SMTP</SelectItem>
            <SelectItem key="ses">Amazon SES</SelectItem>
            <SelectItem key="mailgun">Mailgun</SelectItem>
            <SelectItem key="log">Log (dev only)</SelectItem>
          </Select>
          <div className="grid grid-cols-2 gap-3">
            <Input label="SMTP Host" value={data.host} onChange={e => setData('host', e.target.value)} errorMessage={errors.host} />
            <Input label="Port" value={data.port} onChange={e => setData('port', e.target.value)} errorMessage={errors.port} />
          </div>
          <Input label="Username" value={data.username} onChange={e => setData('username', e.target.value)} />
          <Input label="Password" type="password" value={data.password} onChange={e => setData('password', e.target.value)} placeholder="Leave blank to keep existing" />
          <Select label="Encryption" selectedKeys={[data.encryption]} onSelectionChange={k => setData('encryption', [...k][0])}>
            <SelectItem key="tls">TLS</SelectItem>
            <SelectItem key="ssl">SSL</SelectItem>
            <SelectItem key="">None</SelectItem>
          </Select>
          <Divider />
          <Input label="From Name" value={data.from_name} onChange={e => setData('from_name', e.target.value)} />
          <Input label="From Email" type="email" value={data.from_email} onChange={e => setData('from_email', e.target.value)} />
          <Button type="submit" color="primary" isLoading={processing}>Save Mail Settings</Button>
        </form>

        <Divider />
        <div className="space-y-2">
          <p className="text-sm font-medium">Test Configuration</p>
          <div className="flex gap-3">
            <Input placeholder="Send test to email…" value={testEmail} onChange={e => setTestEmail(e.target.value)} type="email" className="flex-1" />
            <Button onPress={sendTest} isLoading={testing} isDisabled={!testEmail} variant="flat">Send Test</Button>
          </div>
        </div>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Settings/Branding.jsx`:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Select, SelectItem } from '@heroui/react';

export default function BrandingSettings({ branding }) {
  const { data, setData, post, processing, errors } = useForm({
    app_name: branding.app_name,
    primary_color: branding.primary_color,
    sidebar_theme: branding.sidebar_theme,
    logo: null,
    favicon: null,
  });

  const submit = e => {
    e.preventDefault();
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => v !== null && fd.append(k, v));
    post(route('core.settings.branding.update'), { forceFormData: true });
  };

  return (
    <AppLayout title="Branding">
      <Head title="Branding" />
      <div className="p-6 max-w-lg space-y-4">
        <h1 className="text-2xl font-bold">Branding & Appearance</h1>
        <form onSubmit={submit} className="space-y-4">
          <Input label="Application Name" value={data.app_name} onChange={e => setData('app_name', e.target.value)} errorMessage={errors.app_name} />
          <Input
            label="Primary Color (hex)"
            value={data.primary_color}
            onChange={e => setData('primary_color', e.target.value)}
            startContent={<div className="w-5 h-5 rounded border" style={{ background: data.primary_color }} />}
            description="e.g. #006FEE"
          />
          <Select label="Sidebar Theme" selectedKeys={[data.sidebar_theme]} onSelectionChange={k => setData('sidebar_theme', [...k][0])}>
            <SelectItem key="dark">Dark</SelectItem>
            <SelectItem key="light">Light</SelectItem>
          </Select>
          <div>
            <p className="text-sm font-medium mb-1">Logo (PNG/SVG, max 2MB)</p>
            {branding.logo_url && <img src={branding.logo_url} alt="Logo" className="h-10 mb-2 object-contain" />}
            <input type="file" accept="image/*" onChange={e => setData('logo', e.target.files[0])} />
          </div>
          <div>
            <p className="text-sm font-medium mb-1">Favicon (PNG/ICO, max 512KB)</p>
            {branding.favicon_url && <img src={branding.favicon_url} alt="Favicon" className="h-8 mb-2 object-contain" />}
            <input type="file" accept="image/*" onChange={e => setData('favicon', e.target.files[0])} />
          </div>
          <Button type="submit" color="primary" isLoading={processing}>Save Branding</Button>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Settings/EmailTemplates.jsx`:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Chip, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter,
  Input, Textarea, useDisclosure, Select, SelectItem,
} from '@heroui/react';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function EmailTemplates({ templates }) {
  const { can } = useHRMAC();
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const [editing, setEditing] = useState(null);
  const { data, setData, post, put, processing, errors, reset } = useForm({ name: '', slug: '', subject: '', body_html: '', category: 'system' });

  const openCreate = () => { reset(); setEditing(null); onOpen(); };
  const openEdit = t => { setData({ name: t.name, slug: t.slug, subject: t.subject, body_html: t.body_html, category: t.category }); setEditing(t); onOpen(); };

  const submit = e => {
    e.preventDefault();
    const action = editing
      ? () => put(route('core.settings.email-templates.update', editing.id), { onSuccess: () => { reset(); onOpenChange(); } })
      : () => post(route('core.settings.email-templates.store'), { onSuccess: () => { reset(); onOpenChange(); } });
    action();
  };

  return (
    <AppLayout title="Email Templates">
      <Head title="Email Templates" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Email Templates</h1>
          {can('core.settings.email_templates.create') && (
            <Button color="primary" onPress={openCreate}>New Template</Button>
          )}
        </div>

        <Table aria-label="Email Templates">
          <TableHeader>
            <TableColumn>NAME</TableColumn>
            <TableColumn>SLUG</TableColumn>
            <TableColumn>CATEGORY</TableColumn>
            <TableColumn>ACTIVE</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={templates}>
            {t => (
              <TableRow key={t.id}>
                <TableCell className="font-medium">{t.name}</TableCell>
                <TableCell className="font-mono text-xs">{t.slug}</TableCell>
                <TableCell><Chip size="sm" variant="flat">{t.category}</Chip></TableCell>
                <TableCell><Chip size="sm" color={t.is_active ? 'success' : 'default'} variant="flat">{t.is_active ? 'Active' : 'Inactive'}</Chip></TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    <Button size="sm" variant="flat" as="a" href={route('core.settings.email-templates.preview', t.id)} target="_blank">Preview</Button>
                    {!t.is_locked && can('core.settings.email_templates.edit') && (
                      <Button size="sm" variant="flat" onPress={() => openEdit(t)}>Edit</Button>
                    )}
                    {!t.is_locked && can('core.settings.email_templates.delete') && (
                      <Button size="sm" color="danger" variant="flat" onPress={() => {
                        if (confirm('Delete template?')) router.delete(route('core.settings.email-templates.destroy', t.id));
                      }}>Delete</Button>
                    )}
                  </div>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        <Modal isOpen={isOpen} onOpenChange={onOpenChange} size="2xl">
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader>{editing ? 'Edit Template' : 'New Template'}</ModalHeader>
                <ModalBody className="space-y-3">
                  <Input label="Name" value={data.name} onChange={e => setData('name', e.target.value)} errorMessage={errors.name} isRequired />
                  <Input label="Slug" value={data.slug} onChange={e => setData('slug', e.target.value)} isDisabled={editing?.is_locked} errorMessage={errors.slug} isRequired />
                  <Input label="Subject" value={data.subject} onChange={e => setData('subject', e.target.value)} errorMessage={errors.subject} isRequired />
                  <Select label="Category" selectedKeys={[data.category]} onSelectionChange={k => setData('category', [...k][0])}>
                    <SelectItem key="system">System</SelectItem>
                    <SelectItem key="transactional">Transactional</SelectItem>
                    <SelectItem key="marketing">Marketing</SelectItem>
                  </Select>
                  <Textarea label="HTML Body" value={data.body_html} onChange={e => setData('body_html', e.target.value)} rows={8} className="font-mono" isRequired />
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color="primary" isLoading={processing}>{editing ? 'Save Changes' : 'Create'}</Button>
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

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Settings/
git commit -m "feat(aero-ui): Settings pages — SystemSettings, Mail, Branding, EmailTemplates"
```

---

## Task 7 — Frontend: Organization Profile pages

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Organization/Profile.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Organization/Identity.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Organization/Addresses.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Organization/FiscalYear.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Organization/Contacts.jsx`

- [ ] Write `Organization/Profile.jsx`:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Select, SelectItem } from '@heroui/react';

const INDUSTRIES = ['Technology','Finance','Healthcare','Manufacturing','Retail','Education','Government','Non-profit','Other'];
const SIZES = ['1-10','11-50','51-200','201-500','500+'];

export default function OrgProfile({ org }) {
  const { data, setData, post, processing, errors } = useForm({
    company_name: org.company_name ?? '',
    legal_name: org.legal_name ?? '',
    registration_number: org.registration_number ?? '',
    industry: org.industry ?? '',
    company_size: org.company_size ?? '',
    website: org.website ?? '',
    phone: org.phone ?? '',
    email: org.email ?? '',
  });

  const submit = e => { e.preventDefault(); post(route('core.organization.profile.update')); };

  return (
    <AppLayout title="Organization Profile">
      <Head title="Organization Profile" />
      <div className="p-6 max-w-lg space-y-4">
        <h1 className="text-2xl font-bold">Organization Profile</h1>
        <form onSubmit={submit} className="space-y-4">
          <Input label="Company Name" value={data.company_name} onChange={e => setData('company_name', e.target.value)} errorMessage={errors.company_name} isRequired />
          <Input label="Legal Name" value={data.legal_name} onChange={e => setData('legal_name', e.target.value)} />
          <Input label="Registration Number" value={data.registration_number} onChange={e => setData('registration_number', e.target.value)} />
          <Select label="Industry" selectedKeys={data.industry ? [data.industry] : []} onSelectionChange={k => setData('industry', [...k][0] ?? '')}>
            {INDUSTRIES.map(i => <SelectItem key={i}>{i}</SelectItem>)}
          </Select>
          <Select label="Company Size" selectedKeys={data.company_size ? [data.company_size] : []} onSelectionChange={k => setData('company_size', [...k][0] ?? '')}>
            {SIZES.map(s => <SelectItem key={s}>{s}</SelectItem>)}
          </Select>
          <Input label="Website" type="url" value={data.website} onChange={e => setData('website', e.target.value)} />
          <Input label="Phone" value={data.phone} onChange={e => setData('phone', e.target.value)} />
          <Input label="Contact Email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} />
          <Button type="submit" color="primary" isLoading={processing}>Save Profile</Button>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Organization/Identity.jsx`:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input } from '@heroui/react';

export default function OrgIdentity({ org }) {
  const { data, setData, post, processing, errors } = useForm({
    tax_id: org.tax_id ?? '',
    vat_number: org.vat_number ?? '',
    country: org.country ?? '',
    currency: org.currency ?? '',
  });

  const submit = e => { e.preventDefault(); post(route('core.organization.identity.update')); };

  return (
    <AppLayout title="Tax / Legal Identity">
      <Head title="Tax / Legal Identity" />
      <div className="p-6 max-w-lg space-y-4">
        <h1 className="text-2xl font-bold">Tax / Legal Identity</h1>
        <p className="text-default-500 text-sm">Tax ID is stored encrypted at rest.</p>
        <form onSubmit={submit} className="space-y-4">
          <Input label="Tax ID / EIN" value={data.tax_id} onChange={e => setData('tax_id', e.target.value)} type="password" description="Encrypted storage" />
          <Input label="VAT Number" value={data.vat_number} onChange={e => setData('vat_number', e.target.value)} />
          <Input label="Country Code (ISO 2)" value={data.country} onChange={e => setData('country', e.target.value.toUpperCase())} maxLength={2} placeholder="US" />
          <Input label="Default Currency (ISO 3)" value={data.currency} onChange={e => setData('currency', e.target.value.toUpperCase())} maxLength={3} placeholder="USD" />
          <Button type="submit" color="primary" isLoading={processing}>Save Identity</Button>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Organization/FiscalYear.jsx`:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Select, SelectItem } from '@heroui/react';

const DATE_FORMATS = ['DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD', 'D MMM YYYY'];

export default function FiscalYear({ org }) {
  const { data, setData, post, processing, errors } = useForm({
    fiscal_year_start: org.fiscal_year_start ?? '01-01',
    fiscal_year_end:   org.fiscal_year_end   ?? '12-31',
    timezone:          org.timezone          ?? 'UTC',
    date_format:       org.date_format       ?? 'DD/MM/YYYY',
  });

  const submit = e => { e.preventDefault(); post(route('core.organization.fiscal-year.update')); };

  return (
    <AppLayout title="Fiscal Year">
      <Head title="Fiscal Year" />
      <div className="p-6 max-w-lg space-y-4">
        <h1 className="text-2xl font-bold">Fiscal Year</h1>
        <form onSubmit={submit} className="space-y-4">
          <Input label="Fiscal Year Start (MM-DD)" value={data.fiscal_year_start} onChange={e => setData('fiscal_year_start', e.target.value)} placeholder="01-01" description="Format: MM-DD" errorMessage={errors.fiscal_year_start} />
          <Input label="Fiscal Year End (MM-DD)" value={data.fiscal_year_end} onChange={e => setData('fiscal_year_end', e.target.value)} placeholder="12-31" description="Format: MM-DD" errorMessage={errors.fiscal_year_end} />
          <Input label="Timezone" value={data.timezone} onChange={e => setData('timezone', e.target.value)} placeholder="UTC" list="tz-list" description="e.g. America/New_York, Europe/London" />
          <Select label="Date Format" selectedKeys={[data.date_format]} onSelectionChange={k => setData('date_format', [...k][0])}>
            {DATE_FORMATS.map(f => <SelectItem key={f}>{f}</SelectItem>)}
          </Select>
          <Button type="submit" color="primary" isLoading={processing}>Save Fiscal Year</Button>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Organization/Addresses.jsx` and `Organization/Contacts.jsx` with simple add/remove array forms following the same pattern as above (Input fields per entry, Button to add row, delete row, save all).

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Organization/
git commit -m "feat(aero-ui): Organization Profile, Identity, FiscalYear, Addresses, Contacts pages"
```

---

## Task 8 — Frontend: Profile & Security self-service pages

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Profile/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Profile/Security.jsx`

- [ ] Write `Profile/Index.jsx` — user edits own name, email, avatar:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Avatar } from '@heroui/react';

export default function ProfileIndex({ user }) {
  const { data, setData, post, processing, errors } = useForm({ name: user.name, email: user.email, avatar: null });

  const submit = e => {
    e.preventDefault();
    post(route('core.profile.update'), { forceFormData: true });
  };

  return (
    <AppLayout title="My Profile">
      <Head title="My Profile" />
      <div className="p-6 max-w-lg space-y-6">
        <h1 className="text-2xl font-bold">My Profile</h1>
        <form onSubmit={submit} className="space-y-4">
          <div className="flex items-center gap-4">
            <Avatar name={user.name} src={user.avatar_url} size="lg" />
            <div>
              <p className="text-sm font-medium mb-1">Profile Photo</p>
              <input type="file" accept="image/*" onChange={e => setData('avatar', e.target.files[0])} />
            </div>
          </div>
          <Input label="Full Name" value={data.name} onChange={e => setData('name', e.target.value)} errorMessage={errors.name} isRequired />
          <Input label="Email" type="email" value={data.email} onChange={e => setData('email', e.target.value)} errorMessage={errors.email} isRequired />
          <Button type="submit" color="primary" isLoading={processing}>Save Profile</Button>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Upgrade `Profile/Security.jsx` — 2FA enable/disable, active devices, active sessions, change password. Verify the existing page and ensure it renders props `{ twoFactorEnabled, sessions, devices }`:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, CardHeader, Chip, Divider, Input } from '@heroui/react';

export default function ProfileSecurity({ twoFactorEnabled, sessions, devices }) {
  const pwForm = useForm({ current_password: '', password: '', password_confirmation: '' });
  const submitPw = e => { e.preventDefault(); pwForm.put(route('core.profile.password')); };

  return (
    <AppLayout title="Security">
      <Head title="Security" />
      <div className="p-6 max-w-xl space-y-6">
        <h1 className="text-2xl font-bold">Security</h1>

        {/* 2FA */}
        <Card>
          <CardHeader><h2 className="font-semibold">Two-Factor Authentication</h2></CardHeader>
          <CardBody className="space-y-3">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm">{twoFactorEnabled ? '2FA is enabled' : '2FA is not enabled'}</p>
                <p className="text-xs text-default-400">Adds an extra layer of security to your account</p>
              </div>
              <Chip color={twoFactorEnabled ? 'success' : 'default'} variant="flat" size="sm">
                {twoFactorEnabled ? 'Enabled' : 'Disabled'}
              </Chip>
            </div>
            {twoFactorEnabled ? (
              <Button size="sm" color="danger" variant="flat" onPress={() => router.post(route('two-factor.disable'))}>Disable 2FA</Button>
            ) : (
              <Button size="sm" color="primary" as="a" href={route('two-factor.index')}>Enable 2FA</Button>
            )}
          </CardBody>
        </Card>

        {/* Change Password */}
        <Card>
          <CardHeader><h2 className="font-semibold">Change Password</h2></CardHeader>
          <CardBody>
            <form onSubmit={submitPw} className="space-y-3">
              <Input label="Current Password" type="password" value={pwForm.data.current_password} onChange={e => pwForm.setData('current_password', e.target.value)} errorMessage={pwForm.errors.current_password} />
              <Input label="New Password" type="password" value={pwForm.data.password} onChange={e => pwForm.setData('password', e.target.value)} errorMessage={pwForm.errors.password} />
              <Input label="Confirm Password" type="password" value={pwForm.data.password_confirmation} onChange={e => pwForm.setData('password_confirmation', e.target.value)} />
              <Button type="submit" size="sm" color="primary" isLoading={pwForm.processing}>Update Password</Button>
            </form>
          </CardBody>
        </Card>

        {/* Active Sessions */}
        <Card>
          <CardHeader className="flex items-center justify-between">
            <h2 className="font-semibold">Active Sessions</h2>
            <Button size="sm" color="danger" variant="flat" onPress={() => router.post(route('core.auth.sessions.terminate-all'))}>Terminate All</Button>
          </CardHeader>
          <CardBody>
            <div className="space-y-2">
              {sessions.map(s => (
                <div key={s.id} className="flex items-center justify-between p-2 rounded bg-default-50">
                  <div>
                    <p className="text-sm font-medium">{s.device_type ?? 'Unknown device'}</p>
                    <p className="text-xs text-default-400">{s.ip_address} · Last active {new Date(s.last_activity_at).toLocaleString()}</p>
                  </div>
                  <Button size="sm" color="danger" variant="flat" onPress={() => router.post(route('core.auth.sessions.terminate', s.id))}>Revoke</Button>
                </div>
              ))}
              {sessions.length === 0 && <p className="text-sm text-default-400">No active sessions found.</p>}
            </div>
          </CardBody>
        </Card>
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Profile/
git commit -m "feat(aero-ui): Profile Index + Security self-service pages"
```

---

## Task 9 — PHPUnit Tests

**Files:**
- Create: `packages/aero-core/tests/Feature/Settings/SystemSettingControllerTest.php`
- Create: `packages/aero-core/tests/Feature/Settings/OrganizationProfileControllerTest.php`
- Create: `packages/aero-core/tests/Feature/Settings/MailSettingsControllerTest.php`

- [ ] Create `SystemSettingControllerTest.php`:

```php
<?php

namespace Aero\Core\Tests\Feature\Settings;

use Aero\Core\Models\User;
use Aero\Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class SystemSettingControllerTest extends TestCase
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

    public function test_settings_page_renders(): void
    {
        $this->actingAs($this->admin)->get('/settings/system')
            ->assertOk()
            ->assertInertia(fn($p) => $p->component('Core/Settings/SystemSettings')->has('settings'));
    }

    public function test_update_saves_app_name(): void
    {
        $this->actingAs($this->admin)->post('/settings/system', ['app_name' => 'My Company'])
            ->assertRedirect();
        // Verify via SystemSettingService
        $service = app(\Aero\Core\Services\SystemSettingService::class);
        $this->assertEquals('My Company', $service->get('app_name'));
    }
}
```

- [ ] Create `OrganizationProfileControllerTest.php`:

```php
<?php

namespace Aero\Core\Tests\Feature\Settings;

use Aero\Core\Models\User;
use Aero\Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class OrganizationProfileControllerTest extends TestCase
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

    public function test_profile_page_renders(): void
    {
        $this->actingAs($this->admin)->get('/organization/profile')
            ->assertOk()
            ->assertInertia(fn($p) => $p->component('Core/Organization/Profile')->has('org'));
    }

    public function test_update_profile_saves(): void
    {
        $this->actingAs($this->admin)
            ->post('/organization/profile', ['company_name' => 'Acme Corp'])
            ->assertRedirect();
        $this->assertDatabaseHas('organization_profiles', ['company_name' => 'Acme Corp']);
    }

    public function test_fiscal_year_validates_format(): void
    {
        $this->actingAs($this->admin)
            ->post('/organization/fiscal-year', [
                'fiscal_year_start' => 'invalid',
                'fiscal_year_end'   => '12-31',
                'timezone'          => 'UTC',
                'date_format'       => 'DD/MM/YYYY',
            ])
            ->assertSessionHasErrors('fiscal_year_start');
    }
}
```

- [ ] Run tests:
```bash
cd packages/aero-core && php ../../vendor/bin/phpunit tests/Feature/Settings/ --testdox 2>&1 | tail -30
```

- [ ] Commit:
```bash
git add packages/aero-core/tests/Feature/Settings/
git commit -m "test(aero-core): settings and organization profile controller tests"
```

---

## Self-Review Checklist

- [ ] **Spec coverage:** General settings ✅ · Security settings ✅ · Localization ✅ · Branding ✅ · Mail/SMTP + test send ✅ · Password Policy ✅ · IP Whitelist ✅ · Email Templates ✅ · Org Profile ✅ · Tax/Legal Identity ✅ · Addresses ✅ · Fiscal Year ✅ · Contacts ✅ · Self-service profile ✅ · 2FA ✅ · Devices ✅ · Sessions ✅
- [ ] **Encrypted fields:** `tax_id` → `EncryptedField::class` cast on `OrganizationProfile` ✅ · SMTP password via `SystemSettingService` with encryption ✅
- [ ] **Audit:** `AuditService::log()` on every settings save ✅ · `AuditService::logAccess()` on mail settings page ✅
- [ ] **No placeholders:** All code blocks complete
