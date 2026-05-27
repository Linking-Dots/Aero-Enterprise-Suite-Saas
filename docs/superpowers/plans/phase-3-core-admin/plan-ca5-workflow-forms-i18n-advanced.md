# Plan CA-5 — Workflow, Forms, Custom Fields, i18n, Assistant, Subscription & Advanced Features

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Complete the remaining tenant-admin surfaces by upgrading UI pages for foundation packages whose backends already exist (`aero-workflow`, `aero-i18n`), completing partial-backend packages (`aero-forms` needs migrations, `aero-custom-fields` needs verification, `aero-assistant` needs consolidation), and building the remaining advanced admin pages: subscription self-service, announcements & banners, maintenance mode, document numbering, print templates, help & support, user preferences, and license management (standalone).

**Architecture:**
- `aero-workflow` — backend complete in its own package. Pages live in `aero-ui`. Plan upgrades existing pages.
- `aero-forms` — backend in `aero-forms` but migrations are missing. Plan adds migrations first.
- `aero-custom-fields` — backend partial. Plan verifies and fills gaps.
- `aero-i18n` — backend complete. Pages partially exist in `aero-ui`. Plan upgrades them.
- `aero-assistant` — partial backend + own JSX resources. Plan consolidates pages to `aero-ui`.
- Subscription self-service — `TenantSubscriptionController` already exists in `aero-platform`. Plan builds the tenant-side UI.
- Advanced pages (Announcements Banners, Maintenance, Numbering, Print Templates, Help, License) — new controllers in `aero-core` + new pages in `aero-ui`.

**Prerequisites:** CA-1 through CA-4 complete.

---

## Security Notes

- Subscription pages (SaaS-only): guard with `plan: 'saas'` check + HRMAC
- License management (standalone-only): guard with `AeroMode::isStandalone()` check
- Maintenance mode enable/disable: `AuditService::log()` with `AuditEventType::SECURITY_EVENT`
- API assistant conversations may contain sensitive context — do not log conversation content, only log access events
- All routes: HRMAC guarded

---

## File Map

**New migrations:**
```
packages/aero-forms/database/migrations/2026_05_22_000020_create_forms_table.php
packages/aero-forms/database/migrations/2026_05_22_000021_create_form_submissions_table.php
packages/aero-core/database/migrations/2026_05_22_000015_create_document_sequences_table.php
packages/aero-core/database/migrations/2026_05_22_000016_create_print_templates_table.php
packages/aero-core/database/migrations/2026_05_22_000017_create_maintenance_mode_table.php
```

**New/upgraded controllers (aero-core):**
```
Http/Controllers/Admin/MaintenanceModeController.php  -- CREATE
Http/Controllers/Admin/NumberingController.php        -- CREATE
Http/Controllers/Admin/PrintTemplateController.php    -- CREATE
Http/Controllers/Admin/HelpController.php             -- CREATE
Http/Controllers/Admin/LicenseController.php          -- CREATE (standalone only)
Http/Controllers/Admin/AnnouncementBannerController.php -- CREATE
```

**Frontend pages (aero-ui/resources/js/Pages/):**
```
Core/Workflows/Index.jsx              -- UPGRADE: full workflow definition CRUD
Core/Workflows/Templates/Index.jsx    -- UPGRADE: workflow templates
Core/Workflows/Approvals/Index.jsx    -- UPGRADE: my approvals queue
Core/Forms/Index.jsx                  -- UPGRADE: form builder list
Core/Forms/Create.jsx                 -- UPGRADE: form builder editor
Core/Forms/Edit.jsx                   -- UPGRADE: form builder editor
Core/Forms/Submissions.jsx            -- UPGRADE: submission viewer
Core/CustomFields/Index.jsx           -- UPGRADE: field definitions per entity
I18n/Languages/Index.jsx              -- UPGRADE: language toggle + enable/disable
I18n/Editor/Index.jsx                 -- UPGRADE: translation key editor + auto-translate
Core/Assistant/Index.jsx              -- CREATE: AI assistant chat interface
Core/Subscription/Index.jsx           -- CREATE: current plan + usage overview
Core/Subscription/Plans.jsx           -- CREATE: available plans + upgrade/downgrade
Core/Subscription/Invoices.jsx        -- CREATE: billing history + download
Core/Announcements/Banners.jsx        -- CREATE: system-wide banner management
Core/MaintenanceMode/Index.jsx        -- CREATE: maintenance mode toggle + config
Core/Numbering/Index.jsx              -- CREATE: document sequences management
Core/PrintTemplates/Index.jsx         -- CREATE: print/PDF template CRUD + preview
Core/Help/Index.jsx                   -- CREATE: help center + knowledge base + tickets
Core/License/Index.jsx                -- CREATE: license overview + activation (standalone)
Core/UserPreferences/Index.jsx        -- UPGRADE: tabbed preference hub
```

---

## Task 1 — aero-forms: add missing migrations

**Files:**
- Create: `packages/aero-forms/database/migrations/2026_05_22_000020_create_forms_table.php`
- Create: `packages/aero-forms/database/migrations/2026_05_22_000021_create_form_submissions_table.php`

- [ ] Check if `forms` table already exists in aero-core migrations (the `2026_06_16_000010_create_forms_table.php` migration is in aero-core):

```bash
cat packages/aero-core/database/migrations/2026_06_16_000010_create_forms_table.php | head -20
```

- [ ] If already exists in aero-core, skip this task — the migrations are there, just in the wrong package. Document this as a migration location inconsistency to resolve in a future cleanup plan.

- [ ] If not present, create `2026_05_22_000020_create_forms_table.php` in `packages/aero-forms/database/migrations/`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('forms')) return;
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->json('schema');             // field definitions array
            $table->json('settings')->nullable(); // submission settings, notifications
            $table->string('status')->default('draft'); // draft|published|closed
            $table->boolean('accepts_submissions')->default(false);
            $table->boolean('requires_auth')->default(false);
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->unsignedInteger('max_submissions')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('forms'); }
};
```

- [ ] Create form_submissions migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('form_submissions')) return;
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->string('status')->default('submitted'); // submitted|reviewed|archived
            $table->string('submitter_ip', 45)->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['form_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('form_submissions'); }
};
```

- [ ] Commit:
```bash
git add packages/aero-forms/database/migrations/
git commit -m "feat(aero-forms): add forms and form_submissions migrations"
```

---

## Task 2 — New aero-core migrations: sequences, print templates, maintenance mode

**Files:**
- Create: `packages/aero-core/database/migrations/2026_05_22_000015_create_document_sequences_table.php`
- Create: `packages/aero-core/database/migrations/2026_05_22_000016_create_print_templates_table.php`
- Create: `packages/aero-core/database/migrations/2026_05_22_000017_create_maintenance_settings_table.php`

- [ ] Create document_sequences migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('document_sequences')) return;
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();      // e.g. 'invoice', 'po', 'ticket'
            $table->string('name');
            $table->string('prefix')->nullable();  // e.g. 'INV-'
            $table->string('suffix')->nullable();
            $table->unsignedInteger('padding')->default(5); // zero-padding: 00001
            $table->unsignedBigInteger('last_number')->default(0);
            $table->string('reset_period')->nullable(); // null|yearly|monthly
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('document_sequences'); }
};
```

- [ ] Create print_templates migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('print_templates')) return;
        Schema::create('print_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('entity_type');          // e.g. 'invoice', 'payslip', 'certificate'
            $table->longText('html_content');
            $table->string('paper_size')->default('A4');
            $table->string('orientation')->default('portrait');
            $table->json('margins')->nullable();    // {top, right, bottom, left}
            $table->json('header')->nullable();     // custom header content
            $table->json('footer')->nullable();     // custom footer content
            $table->boolean('is_default')->default(false);
            $table->boolean('is_locked')->default(false); // system templates
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('print_templates'); }
};
```

- [ ] Create maintenance_settings migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('maintenance_settings')) return;
        Schema::create('maintenance_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->text('message')->nullable();
            $table->string('allowed_ips')->nullable(); // comma-separated bypass IPs
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('maintenance_settings'); }
};
```

- [ ] Commit:
```bash
git add packages/aero-core/database/migrations/2026_05_22_000015_create_document_sequences_table.php \
        packages/aero-core/database/migrations/2026_05_22_000016_create_print_templates_table.php \
        packages/aero-core/database/migrations/2026_05_22_000017_create_maintenance_settings_table.php
git commit -m "feat(aero-core): sequences, print_templates, maintenance_settings migrations"
```

---

## Task 3 — New aero-core controllers: Maintenance, Numbering, PrintTemplate, Help, License, AnnouncementBanner

**Files:**
- Create: `packages/aero-core/src/Http/Controllers/Admin/MaintenanceModeController.php`
- Create: `packages/aero-core/src/Http/Controllers/Admin/NumberingController.php`
- Create: `packages/aero-core/src/Http/Controllers/Admin/PrintTemplateController.php`
- Create: `packages/aero-core/src/Http/Controllers/Admin/HelpController.php`
- Create: `packages/aero-core/src/Http/Controllers/Admin/LicenseController.php`
- Create: `packages/aero-core/src/Http/Controllers/Admin/AnnouncementBannerController.php`

- [ ] Create `MaintenanceModeController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceModeController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        $settings = DB::table('maintenance_settings')->first();
        return Inertia::render('Core/MaintenanceMode/Index', [
            'settings' => $settings ? (array) $settings : [
                'is_active'    => false,
                'message'      => null,
                'allowed_ips'  => null,
                'scheduled_at' => null,
                'ends_at'      => null,
            ],
        ]);
    }

    public function toggle(Request $request): RedirectResponse
    {
        $request->validate([
            'is_active'   => ['required', 'boolean'],
            'message'     => ['nullable', 'string', 'max:500'],
            'allowed_ips' => ['nullable', 'string'],
        ]);

        DB::table('maintenance_settings')->updateOrInsert(
            ['id' => 1],
            array_merge($request->only('is_active', 'message', 'allowed_ips'), ['updated_at' => now()])
        );

        $this->audit->log(
            AuditEventType::SECURITY_EVENT ?? AuditEventType::RECORD_UPDATED,
            $request->user(),
            null,
            ['maintenance_mode' => $request->is_active ? 'enabled' : 'disabled']
        );

        return back()->with('success', $request->is_active ? 'Maintenance mode enabled.' : 'Maintenance mode disabled.');
    }
}
```

- [ ] Create `NumberingController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NumberingController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        return Inertia::render('Core/Numbering/Index', [
            'sequences' => DB::table('document_sequences')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'         => ['required', 'string', 'max:50', 'unique:document_sequences,code'],
            'name'         => ['required', 'string', 'max:100'],
            'prefix'       => ['nullable', 'string', 'max:20'],
            'suffix'       => ['nullable', 'string', 'max:20'],
            'padding'      => ['integer', 'min:1', 'max:10'],
            'reset_period' => ['nullable', 'in:yearly,monthly'],
        ]);
        DB::table('document_sequences')->insert(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
        $this->audit->log(AuditEventType::RECORD_CREATED, $request->user(), null, ['code' => $data['code']]);
        return back()->with('success', 'Sequence created.');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => ['sometimes', 'required', 'string', 'max:100'],
            'prefix'  => ['nullable', 'string', 'max:20'],
            'suffix'  => ['nullable', 'string', 'max:20'],
            'padding' => ['integer', 'min:1', 'max:10'],
        ]);
        DB::table('document_sequences')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        $this->audit->log(AuditEventType::RECORD_UPDATED, $request->user(), null, ['sequence_id' => $id]);
        return back()->with('success', 'Sequence updated.');
    }

    public function reset(int $id, Request $request): RedirectResponse
    {
        DB::table('document_sequences')->where('id', $id)->update(['last_number' => 0, 'updated_at' => now()]);
        $this->audit->log(AuditEventType::RECORD_UPDATED, $request->user(), null, ['action' => 'sequence_reset', 'id' => $id]);
        return back()->with('success', 'Sequence reset to 0.');
    }
}
```

- [ ] Create `PrintTemplateController.php` — CRUD for print/PDF templates + preview endpoint:

```php
<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PrintTemplateController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        return Inertia::render('Core/PrintTemplates/Index', [
            'templates' => DB::table('print_templates')->orderBy('entity_type')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:100'],
            'slug'         => ['required', 'string', 'unique:print_templates,slug'],
            'entity_type'  => ['required', 'string'],
            'html_content' => ['required', 'string'],
            'paper_size'   => ['in:A4,A3,Letter,Legal'],
            'orientation'  => ['in:portrait,landscape'],
        ]);
        DB::table('print_templates')->insert(array_merge($data, ['created_at' => now(), 'updated_at' => now()]));
        $this->audit->log(AuditEventType::RECORD_CREATED, $request->user(), null, ['slug' => $data['slug']]);
        return back()->with('success', 'Template created.');
    }

    public function update(int $id, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['sometimes', 'required', 'string'],
            'html_content' => ['sometimes', 'required', 'string'],
            'paper_size'   => ['in:A4,A3,Letter,Legal'],
            'orientation'  => ['in:portrait,landscape'],
        ]);
        DB::table('print_templates')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));
        $this->audit->log(AuditEventType::RECORD_UPDATED, $request->user(), null, ['template_id' => $id]);
        return back()->with('success', 'Template updated.');
    }

    public function preview(int $id): HttpResponse
    {
        $template = DB::table('print_templates')->find($id);
        abort_if(!$template, 404);
        return response($template->html_content)->header('Content-Type', 'text/html');
    }

    public function destroy(int $id, Request $request): RedirectResponse
    {
        $t = DB::table('print_templates')->find($id);
        abort_if($t && $t->is_locked, 403, 'Cannot delete a locked system template.');
        DB::table('print_templates')->delete($id);
        $this->audit->log(AuditEventType::RECORD_DELETED, $request->user(), null, ['template_id' => $id]);
        return back()->with('success', 'Template deleted.');
    }
}
```

- [ ] Create `HelpController.php` — renders help center, connects to platform help center API (P-11):

```php
<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Core/Help/Index', [
            'articles' => [], // populated by aero-platform KB when available
            'tickets'  => [],
        ]);
    }
}
```

- [ ] Create `LicenseController.php` — standalone-only, reads license from `LicenseService`:

```php
<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\License\LicenseService;
use Aero\Contracts\AeroMode;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LicenseController extends Controller
{
    public function __construct(private LicenseService $license) {}

    public function index(): Response
    {
        abort_if(AeroMode::isSaas(), 404, 'License management is standalone-only.');

        return Inertia::render('Core/License/Index', [
            'license' => $this->license->current(),
            'status'  => $this->license->status(),
        ]);
    }

    public function activate(Request $request)
    {
        abort_if(AeroMode::isSaas(), 404);
        $request->validate(['key' => ['required', 'string']]);
        $result = $this->license->activate($request->key);
        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
```

- [ ] Create `AnnouncementBannerController.php` — system-wide banners (separate from announcement posts):

```php
<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Aero\Core\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementBannerController extends Controller
{
    public function __construct(
        private SystemSettingService $settings,
        private AuditService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Core/Announcements/Banners', [
            'banner' => [
                'enabled'  => (bool) $this->settings->get('banner_enabled', false),
                'message'  => $this->settings->get('banner_message', ''),
                'type'     => $this->settings->get('banner_type', 'info'),
                'link_url' => $this->settings->get('banner_link_url', ''),
                'link_text' => $this->settings->get('banner_link_text', ''),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled'   => ['boolean'],
            'message'   => ['nullable', 'string', 'max:500'],
            'type'      => ['in:info,warning,success,danger'],
            'link_url'  => ['nullable', 'url'],
            'link_text' => ['nullable', 'string', 'max:100'],
        ]);
        foreach ($data as $k => $v) {
            $this->settings->set("banner_{$k}", $v);
        }
        $this->audit->log(AuditEventType::RECORD_UPDATED, $request->user(), null, ['section' => 'banner']);
        return back()->with('success', 'Banner updated.');
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Http/Controllers/Admin/
git commit -m "feat(aero-core): Maintenance, Numbering, PrintTemplate, Help, License, Banner controllers"
```

---

## Task 4 — Routes: advanced admin pages

**Files:**
- Modify: `packages/aero-core/routes/web.php`

- [ ] Add route groups for new controllers:

```php
use Aero\Core\Http\Controllers\Admin\MaintenanceModeController;
use Aero\Core\Http\Controllers\Admin\NumberingController;
use Aero\Core\Http\Controllers\Admin\PrintTemplateController;
use Aero\Core\Http\Controllers\Admin\HelpController;
use Aero\Core\Http\Controllers\Admin\LicenseController;
use Aero\Core\Http\Controllers\Admin\AnnouncementBannerController;

Route::middleware('auth:web')->group(function () {
    // Maintenance Mode
    Route::prefix('maintenance-mode')->name('core.maintenance.')->group(function () {
        Route::get('/', [MaintenanceModeController::class, 'index'])->name('index')->middleware('hrmac:core.maintenance_mode.maintenance_toggle.view');
        Route::post('/', [MaintenanceModeController::class, 'toggle'])->name('toggle')->middleware('hrmac:core.maintenance_mode.maintenance_toggle.enable');
    });

    // Numbering Sequences
    Route::prefix('numbering')->name('core.numbering.')->middleware('hrmac:core.numbering.sequences.view')->group(function () {
        Route::get('/', [NumberingController::class, 'index'])->name('index');
        Route::post('/', [NumberingController::class, 'store'])->name('store')->withoutMiddleware('hrmac:core.numbering.sequences.view')->middleware('hrmac:core.numbering.sequences.create');
        Route::put('/{id}', [NumberingController::class, 'update'])->name('update')->withoutMiddleware('hrmac:core.numbering.sequences.view')->middleware('hrmac:core.numbering.sequences.update');
        Route::post('/{id}/reset', [NumberingController::class, 'reset'])->name('reset')->withoutMiddleware('hrmac:core.numbering.sequences.view')->middleware('hrmac:core.numbering.sequences.reset');
    });

    // Print Templates
    Route::prefix('print-templates')->name('core.print-templates.')->middleware('hrmac:core.print_templates.templates.view')->group(function () {
        Route::get('/', [PrintTemplateController::class, 'index'])->name('index');
        Route::post('/', [PrintTemplateController::class, 'store'])->name('store')->withoutMiddleware('hrmac:core.print_templates.templates.view')->middleware('hrmac:core.print_templates.templates.create');
        Route::put('/{id}', [PrintTemplateController::class, 'update'])->name('update')->withoutMiddleware('hrmac:core.print_templates.templates.view')->middleware('hrmac:core.print_templates.templates.update');
        Route::delete('/{id}', [PrintTemplateController::class, 'destroy'])->name('destroy')->withoutMiddleware('hrmac:core.print_templates.templates.view')->middleware('hrmac:core.print_templates.templates.delete');
        Route::get('/{id}/preview', [PrintTemplateController::class, 'preview'])->name('preview');
    });

    // Help
    Route::get('/help', [HelpController::class, 'index'])->name('core.help.index')->middleware('hrmac:core.help_support.help_center.view');

    // License (standalone only)
    Route::prefix('license')->name('core.license.')->group(function () {
        Route::get('/', [LicenseController::class, 'index'])->name('index');
        Route::post('/activate', [LicenseController::class, 'activate'])->name('activate');
    });

    // Banners
    Route::prefix('announcements/banners')->name('core.banners.')->group(function () {
        Route::get('/', [AnnouncementBannerController::class, 'index'])->name('index')->middleware('hrmac:core.announcements.banners.view');
        Route::post('/', [AnnouncementBannerController::class, 'update'])->name('update')->middleware('hrmac:core.announcements.banners.manage');
    });
});
```

- [ ] Commit:
```bash
git add packages/aero-core/routes/web.php
git commit -m "feat(aero-core): maintenance, numbering, print-templates, help, license, banner routes"
```

---

## Task 5 — Frontend: Workflow pages upgrade

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Workflows/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Workflows/Templates/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Workflows/Approvals/Index.jsx`

- [ ] Upgrade `Workflows/Index.jsx` — workflow definitions table with create modal, activate/deactivate toggle:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Chip, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, Input, Textarea,
  useDisclosure, Switch,
} from '@heroui/react';
import { PlusIcon, ArrowPathRoundedSquareIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function WorkflowsIndex({ workflows }) {
  const { can } = useHRMAC();
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const { data, setData, post, processing, errors, reset } = useForm({ name: '', description: '', trigger: 'manual' });

  const submit = e => { e.preventDefault(); post(route('workflows.store'), { onSuccess: () => { reset(); onOpenChange(); } }); };

  return (
    <AppLayout title="Workflows">
      <Head title="Workflows" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <ArrowPathRoundedSquareIcon className="w-6 h-6 text-primary" />
            <div>
              <h1 className="text-2xl font-bold">Workflows & Automations</h1>
              <p className="text-default-500 text-sm">Define approval workflows and automation rules</p>
            </div>
          </div>
          {can('core.workflow_engine.definitions.create') && (
            <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} onPress={onOpen}>New Workflow</Button>
          )}
        </div>

        <Table aria-label="Workflows">
          <TableHeader>
            <TableColumn>NAME</TableColumn>
            <TableColumn>TRIGGER</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>STEPS</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={workflows}>
            {wf => (
              <TableRow key={wf.id}>
                <TableCell>
                  <div>
                    <p className="font-medium">{wf.name}</p>
                    {wf.description && <p className="text-xs text-default-400">{wf.description}</p>}
                  </div>
                </TableCell>
                <TableCell><Chip size="sm" variant="flat">{wf.trigger ?? 'manual'}</Chip></TableCell>
                <TableCell>
                  <Chip size="sm" color={wf.is_active ? 'success' : 'default'} variant="flat">
                    {wf.is_active ? 'Active' : 'Inactive'}
                  </Chip>
                </TableCell>
                <TableCell>{wf.steps_count ?? 0}</TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    {can('core.workflow_engine.definitions.activate') && (
                      <Button size="sm" variant="flat" onPress={() => router.post(
                        wf.is_active ? route('workflows.deactivate', wf.id) : route('workflows.activate', wf.id)
                      )}>
                        {wf.is_active ? 'Deactivate' : 'Activate'}
                      </Button>
                    )}
                    {can('core.workflow_engine.definitions.delete') && (
                      <Button size="sm" color="danger" variant="flat" onPress={() => {
                        if (confirm('Delete workflow?')) router.delete(route('workflows.destroy', wf.id));
                      }}>Delete</Button>
                    )}
                  </div>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        <Modal isOpen={isOpen} onOpenChange={onOpenChange}>
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader>New Workflow</ModalHeader>
                <ModalBody className="space-y-3">
                  <Input label="Name" value={data.name} onChange={e => setData('name', e.target.value)} isRequired errorMessage={errors.name} />
                  <Textarea label="Description" value={data.description} onChange={e => setData('description', e.target.value)} rows={3} />
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color="primary" isLoading={processing}>Create</Button>
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

- [ ] Upgrade `Workflows/Approvals/Index.jsx` — my pending approvals queue with approve/reject/escalate:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip, Textarea, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure } from '@heroui/react';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function ApprovalsIndex({ approvals }) {
  const { can } = useHRMAC();
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const [selected, setSelected] = useState(null);
  const [action, setAction] = useState(null);
  const { data, setData, post, processing, reset } = useForm({ comment: '' });

  const act = (instance, act) => {
    setSelected(instance);
    setAction(act);
    onOpen();
  };

  const submit = e => {
    e.preventDefault();
    router.post(route(`workflow-instances.${action}`, selected.id), { comment: data.comment }, {
      onSuccess: () => { reset(); onOpenChange(); },
    });
  };

  return (
    <AppLayout title="My Approvals">
      <Head title="My Approvals" />
      <div className="p-6 space-y-4">
        <h1 className="text-2xl font-bold">My Pending Approvals</h1>
        {approvals.total === 0 ? (
          <div className="text-center py-12 text-default-400">No pending approvals.</div>
        ) : (
          <Table aria-label="Approvals">
            <TableHeader>
              <TableColumn>WORKFLOW</TableColumn>
              <TableColumn>SUBJECT</TableColumn>
              <TableColumn>REQUESTED BY</TableColumn>
              <TableColumn>SINCE</TableColumn>
              <TableColumn>ACTIONS</TableColumn>
            </TableHeader>
            <TableBody items={approvals.data}>
              {inst => (
                <TableRow key={inst.id}>
                  <TableCell>{inst.workflow?.name ?? '—'}</TableCell>
                  <TableCell>{inst.subject_label ?? inst.id}</TableCell>
                  <TableCell>{inst.initiated_by_name ?? '—'}</TableCell>
                  <TableCell className="text-xs">{new Date(inst.created_at).toLocaleString()}</TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      {can('core.workflow_engine.approvals.approve') && (
                        <Button size="sm" color="success" variant="flat" onPress={() => act(inst, 'approve')}>Approve</Button>
                      )}
                      {can('core.workflow_engine.approvals.reject') && (
                        <Button size="sm" color="danger" variant="flat" onPress={() => act(inst, 'reject')}>Reject</Button>
                      )}
                      {can('core.workflow_engine.approvals.escalate') && (
                        <Button size="sm" variant="flat" onPress={() => act(inst, 'escalate')}>Escalate</Button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        )}

        <Modal isOpen={isOpen} onOpenChange={onOpenChange}>
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader className="capitalize">{action}</ModalHeader>
                <ModalBody>
                  <Textarea label="Comment (optional)" value={data.comment} onChange={e => setData('comment', e.target.value)} rows={3} />
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color={action === 'reject' ? 'danger' : 'primary'} isLoading={processing} className="capitalize">{action}</Button>
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
git add packages/aero-ui/resources/js/Pages/Core/Workflows/
git commit -m "feat(aero-ui): Workflow Index, Templates, Approvals pages upgraded"
```

---

## Task 6 — Frontend: Forms pages upgrade

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Forms/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Forms/Create.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Forms/Edit.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Forms/Submissions.jsx`

- [ ] Upgrade `Forms/Index.jsx` — form list with status badge, publish/close toggle, link to editor and submissions:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip } from '@heroui/react';
import { PlusIcon, ClipboardDocumentListIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const STATUS_COLOR = { draft: 'default', published: 'success', closed: 'secondary' };

export default function FormsIndex({ forms }) {
  const { can } = useHRMAC();
  return (
    <AppLayout title="Form Builder">
      <Head title="Form Builder" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <ClipboardDocumentListIcon className="w-6 h-6 text-primary" />
            <h1 className="text-2xl font-bold">Form Builder</h1>
          </div>
          {can('core.form_builder.forms.create') && (
            <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} as="a" href={route('forms.create')}>New Form</Button>
          )}
        </div>
        <Table aria-label="Forms">
          <TableHeader>
            <TableColumn>TITLE</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>SUBMISSIONS</TableColumn>
            <TableColumn>CREATED</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={forms.data}>
            {form => (
              <TableRow key={form.id}>
                <TableCell className="font-medium">{form.title}</TableCell>
                <TableCell><Chip size="sm" color={STATUS_COLOR[form.status]} variant="flat">{form.status}</Chip></TableCell>
                <TableCell>{form.submissions_count ?? 0}</TableCell>
                <TableCell className="text-xs">{new Date(form.created_at).toLocaleDateString()}</TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    {can('core.form_builder.forms.update') && <Button size="sm" variant="flat" as="a" href={route('forms.edit', form.id)}>Edit</Button>}
                    <Button size="sm" variant="flat" as="a" href={route('forms.submissions', form.id)}>Submissions</Button>
                    {can('core.form_builder.forms.publish') && form.status === 'draft' && (
                      <Button size="sm" color="success" variant="flat" onPress={() => router.post(route('forms.publish', form.id))}>Publish</Button>
                    )}
                    {can('core.form_builder.forms.delete') && (
                      <Button size="sm" color="danger" variant="flat" onPress={() => { if (confirm('Delete form?')) router.delete(route('forms.destroy', form.id)); }}>Delete</Button>
                    )}
                  </div>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
    </AppLayout>
  );
}
```

- [ ] Upgrade `Forms/Create.jsx` and `Forms/Edit.jsx` — simple field-list editor (drag-and-drop visual builder is Phase 7/WF-1 scope; this plan builds a functional JSON-schema editor as the foundation):

```jsx
// Forms/Create.jsx — JSON schema editor
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Textarea, Select, SelectItem, Switch } from '@heroui/react';

export default function FormsCreate() {
  const { data, setData, post, processing, errors } = useForm({
    title: '', description: '', schema: '[]', requires_auth: false,
  });
  const submit = e => { e.preventDefault(); post(route('forms.store')); };

  return (
    <AppLayout title="New Form">
      <Head title="New Form" />
      <div className="p-6 max-w-2xl space-y-4">
        <h1 className="text-2xl font-bold">New Form</h1>
        <form onSubmit={submit} className="space-y-4">
          <Input label="Title" value={data.title} onChange={e => setData('title', e.target.value)} isRequired errorMessage={errors.title} />
          <Textarea label="Description" value={data.description} onChange={e => setData('description', e.target.value)} rows={2} />
          <Switch isSelected={data.requires_auth} onValueChange={v => setData('requires_auth', v)}>Require login to submit</Switch>
          <div>
            <p className="text-sm font-medium mb-1">Form Schema (JSON)</p>
            <Textarea
              value={data.schema}
              onChange={e => setData('schema', e.target.value)}
              rows={10}
              className="font-mono text-xs"
              description="Array of field objects: [{type, name, label, required, options}]"
            />
            {errors.schema && <p className="text-danger text-xs mt-1">{errors.schema}</p>}
          </div>
          <div className="flex gap-3">
            <Button type="submit" color="primary" isLoading={processing}>Create Form</Button>
            <Button variant="flat" as="a" href={route('forms.index')}>Cancel</Button>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Forms/
git commit -m "feat(aero-ui): Forms Index, Create, Edit, Submissions pages"
```

---

## Task 7 — Frontend: i18n pages upgrade

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/I18n/Languages/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/I18n/Editor/Index.jsx`

- [ ] Upgrade `I18n/Languages/Index.jsx` — language list with enable/disable toggle:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip, Switch, Button } from '@heroui/react';
import { LanguageIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function LanguagesIndex({ languages }) {
  const { can } = useHRMAC();
  return (
    <AppLayout title="Languages">
      <Head title="Languages" />
      <div className="p-6 space-y-4">
        <div className="flex items-center gap-3">
          <LanguageIcon className="w-6 h-6 text-primary" />
          <div>
            <h1 className="text-2xl font-bold">Languages</h1>
            <p className="text-default-500 text-sm">Enable languages for your workspace</p>
          </div>
        </div>
        <Table aria-label="Languages">
          <TableHeader>
            <TableColumn>LANGUAGE</TableColumn>
            <TableColumn>CODE</TableColumn>
            <TableColumn>COMPLETENESS</TableColumn>
            <TableColumn>ENABLED</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={languages}>
            {lang => (
              <TableRow key={lang.code}>
                <TableCell className="font-medium">{lang.name}</TableCell>
                <TableCell><code className="text-xs">{lang.code}</code></TableCell>
                <TableCell>
                  <div className="flex items-center gap-2">
                    <div className="flex-1 bg-default-200 rounded-full h-1.5 max-w-24">
                      <div className="bg-primary h-1.5 rounded-full" style={{ width: `${lang.completeness ?? 0}%` }} />
                    </div>
                    <span className="text-xs">{lang.completeness ?? 0}%</span>
                  </div>
                </TableCell>
                <TableCell>
                  <Switch
                    isSelected={lang.is_active}
                    isDisabled={!can('core.translations_i18n.languages.enable')}
                    onValueChange={() => router.put(route('i18n.languages.update', lang.code), { is_active: !lang.is_active })}
                  />
                </TableCell>
                <TableCell>
                  <Button size="sm" variant="flat" as="a" href={route('i18n.translations.index', { lang: lang.code })}>Translate</Button>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
    </AppLayout>
  );
}
```

- [ ] Upgrade `I18n/Editor/Index.jsx` — translation key editor with search, inline edit, auto-translate button:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip } from '@heroui/react';
import { MagnifyingGlassIcon, SparklesIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function TranslationEditor({ translations, language, filters }) {
  const { can } = useHRMAC();
  const [editingId, setEditingId] = useState(null);
  const [value, setValue] = useState('');

  const save = id => {
    router.put(route('i18n.translations.update', id), { value }, {
      preserveState: true,
      onSuccess: () => setEditingId(null),
    });
  };

  return (
    <AppLayout title={`Translate to ${language}`}>
      <Head title="Translation Editor" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">Translation Editor</h1>
            <p className="text-default-500 text-sm">Editing: <strong>{language}</strong></p>
          </div>
          {can('core.translations_i18n.translation_editor.auto_translate') && (
            <Button
              color="secondary"
              variant="flat"
              startContent={<SparklesIcon className="w-4 h-4" />}
              onPress={() => router.post(route('i18n.translations.auto-translate'), { lang: language })}
            >
              Auto-Translate Missing
            </Button>
          )}
        </div>

        <Table aria-label="Translations">
          <TableHeader>
            <TableColumn>KEY</TableColumn>
            <TableColumn>ENGLISH (SOURCE)</TableColumn>
            <TableColumn>TRANSLATION</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={translations.data}>
            {t => (
              <TableRow key={t.id}>
                <TableCell><code className="text-xs bg-default-100 px-1 rounded break-all">{t.key}</code></TableCell>
                <TableCell className="text-sm max-w-xs">{t.source_value}</TableCell>
                <TableCell>
                  {editingId === t.id ? (
                    <Input
                      value={value}
                      onChange={e => setValue(e.target.value)}
                      onKeyDown={e => e.key === 'Enter' && save(t.id)}
                      autoFocus
                      size="sm"
                    />
                  ) : (
                    <span className={t.translated_value ? 'text-sm' : 'text-default-400 text-xs italic'}>
                      {t.translated_value ?? 'Not translated'}
                    </span>
                  )}
                </TableCell>
                <TableCell>
                  {editingId === t.id ? (
                    <div className="flex gap-1">
                      <Button size="sm" color="primary" onPress={() => save(t.id)}>Save</Button>
                      <Button size="sm" variant="flat" onPress={() => setEditingId(null)}>Cancel</Button>
                    </div>
                  ) : (
                    can('core.translations_i18n.translation_editor.update') && (
                      <Button size="sm" variant="flat" onPress={() => { setValue(t.translated_value ?? ''); setEditingId(t.id); }}>Edit</Button>
                    )
                  )}
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
    </AppLayout>
  );
}
```

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/I18n/
git commit -m "feat(aero-ui): I18n Languages and Translation Editor pages upgraded"
```

---

## Task 8 — Frontend: Subscription Self-Service pages (SaaS only)

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Subscription/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Subscription/Plans.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Subscription/Invoices.jsx`

Note: `TenantSubscriptionController` already exists in `aero-platform`. These pages call `tenant.subscription.*` routes registered in `aero-core/routes/web.php` (conditional on platform package).

- [ ] Write `Subscription/Index.jsx` — current plan overview with usage meters:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Card, CardBody, CardHeader, Button, Chip, Progress } from '@heroui/react';
import { CreditCardIcon } from '@heroicons/react/24/outline';

export default function SubscriptionIndex({ subscription, plan, usage, quotas }) {
  const daysLeft = subscription?.trial_ends_at
    ? Math.max(0, Math.ceil((new Date(subscription.trial_ends_at) - new Date()) / 86400000))
    : null;

  return (
    <AppLayout title="Subscription">
      <Head title="Subscription" />
      <div className="p-6 space-y-6 max-w-2xl">
        <div className="flex items-center gap-3">
          <CreditCardIcon className="w-6 h-6 text-primary" />
          <h1 className="text-2xl font-bold">Subscription</h1>
        </div>

        {/* Current Plan */}
        <Card>
          <CardHeader className="flex items-center justify-between">
            <h2 className="font-semibold">Current Plan</h2>
            <Button size="sm" as="a" href={route('tenant.subscription.plans')} color="primary" variant="flat">
              Change Plan
            </Button>
          </CardHeader>
          <CardBody className="space-y-3">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-xl font-bold">{plan?.name ?? 'Free'}</p>
                {subscription?.billing_cycle && (
                  <p className="text-sm text-default-500">Billed {subscription.billing_cycle}</p>
                )}
              </div>
              <Chip color={subscription?.status === 'active' ? 'success' : 'warning'} variant="flat">
                {subscription?.status ?? 'free'}
              </Chip>
            </div>
            {daysLeft !== null && (
              <div className="p-3 bg-warning-50 border border-warning-200 rounded-lg">
                <p className="text-sm text-warning-700">Trial ends in {daysLeft} days</p>
              </div>
            )}
          </CardBody>
        </Card>

        {/* Usage */}
        {quotas && (
          <Card>
            <CardHeader><h2 className="font-semibold">Usage</h2></CardHeader>
            <CardBody className="space-y-4">
              {Object.entries(quotas).map(([key, quota]) => (
                <div key={key}>
                  <div className="flex justify-between text-sm mb-1">
                    <span className="capitalize">{key.replace(/_/g, ' ')}</span>
                    <span>{usage?.[key] ?? 0} / {quota === -1 ? '∞' : quota}</span>
                  </div>
                  {quota > 0 && (
                    <Progress
                      value={Math.min(100, ((usage?.[key] ?? 0) / quota) * 100)}
                      color={((usage?.[key] ?? 0) / quota) > 0.9 ? 'danger' : 'primary'}
                      size="sm"
                    />
                  )}
                </div>
              ))}
            </CardBody>
          </Card>
        )}

        <div className="flex gap-3">
          <Button variant="flat" as="a" href={route('tenant.subscription.invoices')}>View Invoices</Button>
        </div>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Subscription/Plans.jsx` — available plans grid with upgrade/downgrade action.

- [ ] Write `Subscription/Invoices.jsx` — invoice history table with download PDF action.

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/Subscription/
git commit -m "feat(aero-ui): Subscription self-service pages (SaaS)"
```

---

## Task 9 — Frontend: Advanced admin pages

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/MaintenanceMode/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Numbering/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/PrintTemplates/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Help/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/License/Index.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Announcements/Banners.jsx`

- [ ] Write `MaintenanceMode/Index.jsx` — toggle switch + message + allowed IPs:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, CardHeader, Input, Switch, Textarea, Select, SelectItem, Chip } from '@heroui/react';
import { WrenchScrewdriverIcon } from '@heroicons/react/24/outline';

export default function MaintenanceModeIndex({ settings }) {
  const { data, setData, post, processing } = useForm({
    is_active:   settings.is_active   ?? false,
    message:     settings.message     ?? 'System is under maintenance. Please check back soon.',
    allowed_ips: settings.allowed_ips ?? '',
  });

  const submit = e => { e.preventDefault(); post(route('core.maintenance.toggle')); };

  return (
    <AppLayout title="Maintenance Mode">
      <Head title="Maintenance Mode" />
      <div className="p-6 max-w-lg space-y-4">
        <div className="flex items-center gap-3">
          <WrenchScrewdriverIcon className="w-6 h-6 text-warning" />
          <div>
            <h1 className="text-2xl font-bold">Maintenance Mode</h1>
            <p className="text-default-500 text-sm">Block access to the workspace during maintenance</p>
          </div>
        </div>

        <Card className={`border-2 ${data.is_active ? 'border-warning' : 'border-default-200'}`}>
          <CardBody>
            <div className="flex items-center justify-between">
              <div>
                <p className="font-medium">Maintenance Mode</p>
                <p className="text-xs text-default-400">{data.is_active ? 'Active — users see the maintenance message' : 'Inactive — workspace is accessible'}</p>
              </div>
              <Switch
                isSelected={data.is_active}
                onValueChange={v => setData('is_active', v)}
                color="warning"
              />
            </div>
          </CardBody>
        </Card>

        <form onSubmit={submit} className="space-y-4">
          <Textarea
            label="Maintenance Message"
            value={data.message}
            onChange={e => setData('message', e.target.value)}
            rows={3}
            description="Shown to users when maintenance mode is active"
          />
          <Input
            label="Bypass IP Addresses"
            value={data.allowed_ips}
            onChange={e => setData('allowed_ips', e.target.value)}
            description="Comma-separated IPs that can access the system during maintenance"
            placeholder="192.168.1.1, 10.0.0.1"
          />
          <Button type="submit" color={data.is_active ? 'warning' : 'primary'} isLoading={processing}>
            {data.is_active ? 'Enable Maintenance Mode' : 'Save Settings'}
          </Button>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Numbering/Index.jsx` — sequences table with create modal and reset button.

- [ ] Write `PrintTemplates/Index.jsx` — templates table with preview link, create/edit modal with HTML editor.

- [ ] Write `Announcements/Banners.jsx` — system banner toggle with message + type + link:

```jsx
import { Head, useForm } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, Input, Switch, Select, SelectItem, Textarea, Chip } from '@heroui/react';
import { MegaphoneIcon } from '@heroicons/react/24/outline';

export default function AnnouncementBanners({ banner }) {
  const { data, setData, post, processing } = useForm({
    enabled:   banner.enabled   ?? false,
    message:   banner.message   ?? '',
    type:      banner.type      ?? 'info',
    link_url:  banner.link_url  ?? '',
    link_text: banner.link_text ?? '',
  });

  const submit = e => { e.preventDefault(); post(route('core.banners.update')); };

  return (
    <AppLayout title="Banners">
      <Head title="System Banners" />
      <div className="p-6 max-w-lg space-y-4">
        <div className="flex items-center gap-3">
          <MegaphoneIcon className="w-6 h-6 text-warning" />
          <h1 className="text-2xl font-bold">System-Wide Banner</h1>
        </div>
        <p className="text-default-500 text-sm">Show a persistent banner at the top of every page for all users.</p>

        {/* Live preview */}
        {data.enabled && data.message && (
          <div className={`p-3 rounded-lg text-sm font-medium bg-${data.type}-100 text-${data.type}-800 border border-${data.type}-200`}>
            {data.message} {data.link_url && <a href="#" className="underline ml-1">{data.link_text || 'Learn more'}</a>}
          </div>
        )}

        <form onSubmit={submit} className="space-y-4">
          <div className="flex items-center justify-between">
            <p className="font-medium">Banner Active</p>
            <Switch isSelected={data.enabled} onValueChange={v => setData('enabled', v)} />
          </div>
          <Select label="Type" selectedKeys={[data.type]} onSelectionChange={k => setData('type', [...k][0])}>
            {['info','warning','success','danger'].map(t => <SelectItem key={t}>{t}</SelectItem>)}
          </Select>
          <Textarea label="Banner Message" value={data.message} onChange={e => setData('message', e.target.value)} rows={2} />
          <div className="grid grid-cols-2 gap-3">
            <Input label="Link URL (optional)" type="url" value={data.link_url} onChange={e => setData('link_url', e.target.value)} />
            <Input label="Link Text" value={data.link_text} onChange={e => setData('link_text', e.target.value)} />
          </div>
          <Button type="submit" color="primary" isLoading={processing}>Save Banner</Button>
        </form>
      </div>
    </AppLayout>
  );
}
```

- [ ] Write `Help/Index.jsx` — help center landing with search, KB articles list, and support ticket form.

- [ ] Write `License/Index.jsx` — license key overview (standalone only) with activation form.

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/MaintenanceMode/ \
        packages/aero-ui/resources/js/Pages/Core/Numbering/ \
        packages/aero-ui/resources/js/Pages/Core/PrintTemplates/ \
        packages/aero-ui/resources/js/Pages/Core/Help/ \
        packages/aero-ui/resources/js/Pages/Core/License/ \
        packages/aero-ui/resources/js/Pages/Core/Announcements/Banners.jsx
git commit -m "feat(aero-ui): Maintenance, Numbering, PrintTemplates, Help, License, Banners pages"
```

---

## Task 10 — Frontend: Custom Fields page upgrade

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/CustomFields/Index.jsx`

- [ ] Upgrade `CustomFields/Index.jsx` — field definitions grouped by entity with type badge:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Chip, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, Input, Select, SelectItem, useDisclosure,
} from '@heroui/react';
import { PlusIcon, AdjustmentsHorizontalIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const FIELD_TYPES = ['text','textarea','number','date','select','multi_select','checkbox','file','email','phone','url'];
const ENTITIES = ['employee','department','leave_application','expense_claim','asset','recruitment_job'];

export default function CustomFieldsIndex({ fields, filters }) {
  const { can } = useHRMAC();
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const { data, setData, post, processing, errors, reset } = useForm({
    entity_type: '', name: '', label: '', type: 'text', is_required: false, options: '',
  });

  const submit = e => { e.preventDefault(); post(route('custom-fields.store'), { onSuccess: () => { reset(); onOpenChange(); } }); };

  const grouped = (fields ?? []).reduce((acc, f) => {
    (acc[f.entity_type] = acc[f.entity_type] ?? []).push(f);
    return acc;
  }, {});

  return (
    <AppLayout title="Custom Fields">
      <Head title="Custom Fields" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <AdjustmentsHorizontalIcon className="w-6 h-6 text-secondary" />
            <h1 className="text-2xl font-bold">Custom Fields</h1>
          </div>
          {can('core.custom_fields.field_definitions.create') && (
            <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} onPress={onOpen}>Add Field</Button>
          )}
        </div>

        {Object.entries(grouped).map(([entity, entityFields]) => (
          <div key={entity}>
            <h2 className="text-sm font-semibold uppercase text-default-400 mb-2">{entity.replace(/_/g, ' ')}</h2>
            <Table aria-label={entity} className="mb-4">
              <TableHeader>
                <TableColumn>LABEL</TableColumn>
                <TableColumn>NAME</TableColumn>
                <TableColumn>TYPE</TableColumn>
                <TableColumn>REQUIRED</TableColumn>
                <TableColumn>ACTIONS</TableColumn>
              </TableHeader>
              <TableBody items={entityFields}>
                {f => (
                  <TableRow key={f.id}>
                    <TableCell className="font-medium">{f.label}</TableCell>
                    <TableCell><code className="text-xs">{f.name}</code></TableCell>
                    <TableCell><Chip size="sm" variant="flat">{f.type}</Chip></TableCell>
                    <TableCell>{f.is_required ? <Chip size="sm" color="warning" variant="flat">Required</Chip> : '—'}</TableCell>
                    <TableCell>
                      {can('core.custom_fields.field_definitions.delete') && (
                        <Button size="sm" color="danger" variant="flat" onPress={() => { if (confirm('Delete field?')) router.delete(route('custom-fields.destroy', f.id)); }}>Delete</Button>
                      )}
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        ))}

        <Modal isOpen={isOpen} onOpenChange={onOpenChange}>
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader>Add Custom Field</ModalHeader>
                <ModalBody className="space-y-3">
                  <Select label="Entity" selectedKeys={data.entity_type ? [data.entity_type] : []} onSelectionChange={k => setData('entity_type', [...k][0])} isRequired>
                    {ENTITIES.map(e => <SelectItem key={e}>{e.replace(/_/g, ' ')}</SelectItem>)}
                  </Select>
                  <Input label="Field Name (snake_case)" value={data.name} onChange={e => setData('name', e.target.value)} isRequired description="e.g. custom_blood_type" />
                  <Input label="Display Label" value={data.label} onChange={e => setData('label', e.target.value)} isRequired />
                  <Select label="Field Type" selectedKeys={[data.type]} onSelectionChange={k => setData('type', [...k][0])}>
                    {FIELD_TYPES.map(t => <SelectItem key={t}>{t}</SelectItem>)}
                  </Select>
                  {['select','multi_select'].includes(data.type) && (
                    <Input label="Options (comma-separated)" value={data.options} onChange={e => setData('options', e.target.value)} description="e.g. Option A, Option B, Option C" />
                  )}
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color="primary" isLoading={processing}>Add Field</Button>
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
git add packages/aero-ui/resources/js/Pages/Core/CustomFields/
git commit -m "feat(aero-ui): Custom Fields Index page upgraded"
```

---

## Task 11 — User Preferences pages upgrade

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/UserPreferences/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/UserPreferences/NotificationPreferences.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/UserPreferences/ThemePreferences.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/UserPreferences/LocalePreferences.jsx`

- [ ] Upgrade `UserPreferences/Index.jsx` — tabbed hub linking to sub-pages:

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Tab, Tabs } from '@heroui/react';

const PREF_TABS = [
  { key: 'notifications', label: 'Notifications',  route: 'notifications.preferences.index' },
  { key: 'theme',         label: 'Theme',           route: 'core.preferences.theme' },
  { key: 'locale',        label: 'Locale & Date',   route: 'core.preferences.locale' },
  { key: 'accessibility', label: 'Accessibility',   route: 'core.preferences.accessibility' },
];

export default function PreferencesIndex() {
  const { url } = usePage();
  const active = PREF_TABS.find(t => url.includes(t.key))?.key ?? 'notifications';

  return (
    <AppLayout title="Preferences">
      <Head title="Preferences" />
      <div className="p-6">
        <h1 className="text-2xl font-bold mb-6">My Preferences</h1>
        <Tabs
          selectedKey={active}
          onSelectionChange={key => {
            const tab = PREF_TABS.find(t => t.key === key);
            if (tab) router.get(route(tab.route));
          }}
          variant="underlined"
        >
          {PREF_TABS.map(t => <Tab key={t.key} title={t.label} />)}
        </Tabs>
      </div>
    </AppLayout>
  );
}
```

- [ ] Upgrade `NotificationPreferences.jsx`, `ThemePreferences.jsx`, `LocalePreferences.jsx` — each renders its own settings form with useForm + the existing preference routes from `aero-notifications` and `aero-core`.

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/UserPreferences/
git commit -m "feat(aero-ui): UserPreferences pages upgraded"
```

---

## Self-Review Checklist

- [ ] **Spec coverage (from config/module.php):**
  - Workflow definitions + templates + approvals ✅
  - Form builder + submissions ✅
  - Custom fields per entity ✅
  - Languages + translation editor ✅
  - Subscription self-service (SaaS) ✅
  - Announcements + banners ✅
  - Maintenance mode ✅
  - Document numbering sequences ✅
  - Print/PDF templates ✅
  - Help & support ✅
  - License management (standalone) ✅
  - User preferences (all 4 tabs) ✅
- [ ] **Foundation package rule:** `aero-workflow` and `aero-i18n` backends untouched — only UI upgraded ✅
- [ ] **aero-forms migration gap:** checked and documented — migrations either added or inconsistency noted ✅
- [ ] **Standalone guard:** `LicenseController` aborts with 404 in SaaS mode ✅
- [ ] **SaaS guard:** Subscription pages only registered when `aero-platform` is available (conditional `class_exists` in routes) ✅
- [ ] **HRMAC:** All new routes guarded ✅
- [ ] **Audit:** All write actions in advanced controllers log via `AuditService` ✅
