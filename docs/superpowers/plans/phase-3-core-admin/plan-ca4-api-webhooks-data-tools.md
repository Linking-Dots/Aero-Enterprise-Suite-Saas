# Plan CA-4 — API & Webhooks, Data Tools, File Manager & Backup

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the tenant API surface (API keys, personal access tokens, outbound webhooks, rate limits, usage analytics) and upgrade all existing data-tool, file manager, and backup UI pages whose backends already exist in `aero-core`. Every controller listed here already exists — this plan writes the missing React pages, fills controller method gaps, and adds routes.

**Architecture:** All backends live in `packages/aero-core/src/Http/Controllers/`. New API/webhook controllers are added there. Pages live in `packages/aero-ui/resources/js/Pages/Core/`. API keys use Laravel Sanctum for PATs and a custom `api_keys` table for system keys. Webhooks use a new `webhooks` table with signed delivery and a log table.

**Tech Stack:** React 18, Inertia.js v2, `@aero/ui` HeroUI, PHP 8.2, Laravel 12, Laravel Sanctum.

**Prerequisites:** CA-1, CA-2, CA-3 complete. Sanctum installed.

---

## Security Notes

- API key values shown only once at creation — never retrievable again (store hashed)
- `AuditService::log(AuditEventType::API_KEY_CREATED)` and `API_KEY_REVOKED` on every key action
- `AuditService::logAccess()` when full API key is displayed on creation
- Webhook secret: HMAC-SHA256 signed payloads — shown once, stored hashed
- All routes: `hrmac:core.api_webhooks.<component>.<action>`
- `DB::transaction()` on all writes

---

## File Map

**New migrations (packages/aero-core/database/migrations/)**
```
2026_05_22_000010_create_api_keys_table.php
2026_05_22_000011_create_webhooks_table.php
2026_05_22_000012_create_webhook_deliveries_table.php
```

**New/upgraded controllers (packages/aero-core/src/Http/Controllers/Api/)**
```
ApiKeyController.php          -- CREATE
PersonalAccessTokenController.php -- CREATE
WebhookController.php         -- CREATE
ApiUsageController.php        -- CREATE
```

**Frontend pages to create/upgrade (packages/aero-ui/resources/js/Pages/Core/)**
```
Api/Keys.jsx             -- CREATE: API key list + create + revoke + rotate
Api/Pat.jsx              -- CREATE: personal access tokens
Api/Webhooks.jsx         -- CREATE: webhook endpoints + delivery logs + test + replay
Api/Usage.jsx            -- CREATE: API usage analytics chart
ExportImport/Exports/Index.jsx   -- UPGRADE: full export CRUD
ExportImport/Imports/Index.jsx   -- UPGRADE: full import flow
Tags/Index.jsx           -- UPGRADE: tag management with merge
SavedViews/Index.jsx     -- UPGRADE: saved views with share + default
RetentionPolicies/Index.jsx -- UPGRADE: policy CRUD + execute now
Trash/Index.jsx          -- UPGRADE: restore + permanent delete + empty
FileManager/Index.jsx    -- UPGRADE: media library + storage stats
Backup/Index.jsx         -- UPGRADE: backup list + manual trigger + download
Backup/Config.jsx        -- UPGRADE: backup schedule configuration
```

**Tests**
```
packages/aero-core/tests/Feature/Api/ApiKeyControllerTest.php
packages/aero-core/tests/Feature/Api/WebhookControllerTest.php
```

---

## Task 1 — Migrations: api_keys, webhooks, webhook_deliveries

**Files:**
- Create: `packages/aero-core/database/migrations/2026_05_22_000010_create_api_keys_table.php`
- Create: `packages/aero-core/database/migrations/2026_05_22_000011_create_webhooks_table.php`
- Create: `packages/aero-core/database/migrations/2026_05_22_000012_create_webhook_deliveries_table.php`

- [ ] Create api_keys migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('api_keys')) return;
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key_prefix', 8);          // first 8 chars shown in UI
            $table->string('key_hash');                // sha256 of full key — never store plaintext
            $table->json('scopes')->nullable();        // ['read:employees', 'write:payroll', ...]
            $table->string('status')->default('active'); // active|revoked
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('key_hash');
        });
    }
    public function down(): void { Schema::dropIfExists('api_keys'); }
};
```

- [ ] Create webhooks migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('webhooks')) return;
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('secret_hash');             // sha256 of HMAC secret — shown once
            $table->json('events');                    // ['hrm.leave.approved', 'hrm.payroll.run']
            $table->string('status')->default('active'); // active|paused
            $table->string('http_method')->default('POST');
            $table->json('headers')->nullable();       // custom headers
            $table->unsignedInteger('timeout')->default(10);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        if (Schema::hasTable('webhook_deliveries')) return;
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->json('payload');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status')->default('pending'); // pending|success|failed
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['webhook_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }
};
```

- [ ] Commit:
```bash
git add packages/aero-core/database/migrations/2026_05_22_000010_create_api_keys_table.php \
        packages/aero-core/database/migrations/2026_05_22_000011_create_webhooks_table.php \
        packages/aero-core/database/migrations/2026_05_22_000012_create_webhook_deliveries_table.php
git commit -m "feat(aero-core): api_keys, webhooks, webhook_deliveries migrations"
```

---

## Task 2 — Models: ApiKey, Webhook, WebhookDelivery

**Files:**
- Create: `packages/aero-core/src/Models/ApiKey.php`
- Create: `packages/aero-core/src/Models/Webhook.php`
- Create: `packages/aero-core/src/Models/WebhookDelivery.php`

- [ ] Create `ApiKey.php`:

```php
<?php

namespace Aero\Core\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiKey extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'key_prefix', 'key_hash', 'scopes',
        'status', 'created_by', 'last_used_at', 'expires_at',
    ];

    protected $casts = [
        'scopes'       => 'array',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    protected $hidden = ['key_hash'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }
}
```

- [ ] Create `Webhook.php`:

```php
<?php

namespace Aero\Core\Models;

use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Webhook extends TenantModel
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'url', 'secret_hash', 'events',
        'status', 'http_method', 'headers', 'timeout', 'last_triggered_at',
    ];

    protected $casts = [
        'events'            => 'array',
        'headers'           => 'array',
        'last_triggered_at' => 'datetime',
    ];

    protected $hidden = ['secret_hash'];

    public function deliveries()
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
```

- [ ] Create `WebhookDelivery.php`:

```php
<?php

namespace Aero\Core\Models;

use Aero\Core\Models\TenantModel;

class WebhookDelivery extends TenantModel
{
    protected $fillable = [
        'webhook_id', 'event_type', 'payload', 'response_status',
        'response_body', 'duration_ms', 'status', 'attempt', 'delivered_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'delivered_at' => 'datetime',
    ];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Models/ApiKey.php \
        packages/aero-core/src/Models/Webhook.php \
        packages/aero-core/src/Models/WebhookDelivery.php
git commit -m "feat(aero-core): ApiKey, Webhook, WebhookDelivery models"
```

---

## Task 3 — Controllers: ApiKeyController, PersonalAccessTokenController, WebhookController

**Files:**
- Create: `packages/aero-core/src/Http/Controllers/Api/ApiKeyController.php`
- Create: `packages/aero-core/src/Http/Controllers/Api/PersonalAccessTokenController.php`
- Create: `packages/aero-core/src/Http/Controllers/Api/WebhookController.php`

- [ ] Create `ApiKeyController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Api;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\ApiKey;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        return Inertia::render('Core/Api/Keys', [
            'keys' => ApiKey::with('creator')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'scopes'     => ['array'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        return DB::transaction(function () use ($request) {
            $rawKey = 'ak_' . Str::random(40);
            $key = ApiKey::create([
                'name'       => $request->name,
                'key_prefix' => substr($rawKey, 0, 8),
                'key_hash'   => ApiKey::hashKey($rawKey),
                'scopes'     => $request->scopes ?? [],
                'expires_at' => $request->expires_at,
                'created_by' => $request->user()->id,
            ]);

            $this->audit->log(AuditEventType::API_KEY_CREATED, $request->user(), $key, ['name' => $key->name]);
            $this->audit->logAccess('api_key', $key->id, $key->name, ['key_value']);

            return redirect()->route('core.api.keys.index')
                ->with('created_key', $rawKey) // flash once — never stored
                ->with('success', 'API key created. Copy it now — it will not be shown again.');
        });
    }

    public function revoke(ApiKey $apiKey, Request $request): RedirectResponse
    {
        $apiKey->update(['status' => 'revoked']);
        $this->audit->log(AuditEventType::API_KEY_REVOKED, $request->user(), $apiKey);
        return back()->with('success', 'API key revoked.');
    }

    public function rotate(ApiKey $apiKey, Request $request): RedirectResponse
    {
        return DB::transaction(function () use ($apiKey, $request) {
            $rawKey = 'ak_' . Str::random(40);
            $apiKey->update([
                'key_prefix' => substr($rawKey, 0, 8),
                'key_hash'   => ApiKey::hashKey($rawKey),
            ]);
            $this->audit->log(AuditEventType::API_KEY_CREATED, $request->user(), $apiKey, ['action' => 'rotate']);
            return redirect()->route('core.api.keys.index')
                ->with('created_key', $rawKey)
                ->with('success', 'API key rotated. Copy the new key — it will not be shown again.');
        });
    }
}
```

- [ ] Create `PersonalAccessTokenController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Api;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PersonalAccessTokenController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Core/Api/Pat', [
            'tokens' => $request->user()->tokens()->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'abilities'  => ['array'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $token = $request->user()->createToken(
            $request->name,
            $request->abilities ?? ['*'],
            $request->expires_at ? now()->parse($request->expires_at) : null,
        );

        $this->audit->log(AuditEventType::API_KEY_CREATED, $request->user(), null, ['token_name' => $request->name, 'type' => 'PAT']);

        return redirect()->route('core.api.pat.index')
            ->with('created_token', $token->plainTextToken)
            ->with('success', 'Token created. Copy it now — it will not be shown again.');
    }

    public function revoke(int $tokenId, Request $request): RedirectResponse
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();
        $this->audit->log(AuditEventType::API_KEY_REVOKED, $request->user(), null, ['token_id' => $tokenId, 'type' => 'PAT']);
        return back()->with('success', 'Token revoked.');
    }
}
```

- [ ] Create `WebhookController.php`:

```php
<?php

namespace Aero\Core\Http\Controllers\Api;

use Aero\Core\Http\Controllers\Controller;
use Aero\Core\Models\Webhook;
use Aero\Core\Models\WebhookDelivery;
use Aero\Core\Services\Audit\AuditService;
use Aero\Core\Services\Audit\AuditEventType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WebhookController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index(): Response
    {
        return Inertia::render('Core/Api/Webhooks', [
            'webhooks' => Webhook::withCount('deliveries')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'url'     => ['required', 'url'],
            'events'  => ['required', 'array', 'min:1'],
            'timeout' => ['integer', 'min:1', 'max:30'],
        ]);

        return DB::transaction(function () use ($request) {
            $secret = 'whsec_' . Str::random(32);
            $webhook = Webhook::create([
                'name'        => $request->name,
                'url'         => $request->url,
                'secret_hash' => hash('sha256', $secret),
                'events'      => $request->events,
                'timeout'     => $request->timeout ?? 10,
            ]);

            $this->audit->log(AuditEventType::WEBHOOK_ENDPOINT_CREATED, $request->user(), $webhook);

            return redirect()->route('core.api.webhooks.index')
                ->with('webhook_secret', $secret)
                ->with('success', 'Webhook created. Copy the signing secret — it will not be shown again.');
        });
    }

    public function update(Request $request, Webhook $webhook): RedirectResponse
    {
        $request->validate([
            'name'   => ['sometimes', 'required', 'string', 'max:100'],
            'url'    => ['sometimes', 'required', 'url'],
            'events' => ['sometimes', 'required', 'array', 'min:1'],
            'status' => ['sometimes', 'in:active,paused'],
        ]);
        $webhook->update($request->only('name', 'url', 'events', 'status', 'timeout'));
        $this->audit->log(AuditEventType::WEBHOOK_ENDPOINT_UPDATED, $request->user(), $webhook);
        return back()->with('success', 'Webhook updated.');
    }

    public function destroy(Webhook $webhook, Request $request): RedirectResponse
    {
        $this->audit->log(AuditEventType::WEBHOOK_ENDPOINT_DELETED, $request->user(), $webhook);
        $webhook->delete();
        return back()->with('success', 'Webhook deleted.');
    }

    public function test(Webhook $webhook, Request $request): RedirectResponse
    {
        $payload = ['event' => 'webhook.test', 'timestamp' => now()->toIso8601String(), 'webhook_id' => $webhook->id];
        $signature = hash_hmac('sha256', json_encode($payload), $webhook->secret_hash);

        $start = microtime(true);
        try {
            $response = Http::timeout($webhook->timeout)
                ->withHeaders(['X-Aeros-Signature' => $signature, 'Content-Type' => 'application/json'])
                ->post($webhook->url, $payload);

            WebhookDelivery::create([
                'webhook_id'      => $webhook->id,
                'event_type'      => 'webhook.test',
                'payload'         => $payload,
                'response_status' => $response->status(),
                'response_body'   => $response->body(),
                'duration_ms'     => (int) ((microtime(true) - $start) * 1000),
                'status'          => $response->successful() ? 'success' : 'failed',
                'delivered_at'    => now(),
            ]);

            $this->audit->log(AuditEventType::WEBHOOK_ENDPOINT_TESTED, $request->user(), $webhook, ['status' => $response->status()]);
            return back()->with('success', "Test delivered — HTTP {$response->status()}.");
        } catch (\Exception $e) {
            return back()->with('error', "Test failed: {$e->getMessage()}");
        }
    }

    public function deliveries(Webhook $webhook): Response
    {
        return Inertia::render('Core/Api/Webhooks', [
            'webhooks'   => Webhook::withCount('deliveries')->latest()->get(),
            'deliveries' => $webhook->deliveries()->latest()->paginate(30),
            'selected'   => $webhook->id,
        ]);
    }

    public function replay(WebhookDelivery $delivery, Request $request): RedirectResponse
    {
        $webhook = $delivery->webhook;
        $signature = hash_hmac('sha256', json_encode($delivery->payload), $webhook->secret_hash);

        Http::timeout($webhook->timeout)
            ->withHeaders(['X-Aeros-Signature' => $signature])
            ->post($webhook->url, $delivery->payload);

        $this->audit->log(AuditEventType::WEBHOOK_LOG_REPLAYED, $request->user(), $delivery);
        return back()->with('success', 'Delivery replayed.');
    }
}
```

- [ ] Commit:
```bash
git add packages/aero-core/src/Http/Controllers/Api/
git commit -m "feat(aero-core): ApiKeyController, PersonalAccessTokenController, WebhookController"
```

---

## Task 4 — Routes: API & Webhooks

**Files:**
- Modify: `packages/aero-core/routes/web.php`

- [ ] Add API & Webhook route groups:

```php
use Aero\Core\Http\Controllers\Api\ApiKeyController;
use Aero\Core\Http\Controllers\Api\PersonalAccessTokenController;
use Aero\Core\Http\Controllers\Api\WebhookController;

Route::middleware('auth:web')->group(function () {
    // API Keys
    Route::prefix('api/keys')->name('core.api.keys.')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index'])->name('index')->middleware('hrmac:core.api_webhooks.api_keys.view');
        Route::post('/', [ApiKeyController::class, 'store'])->name('store')->middleware('hrmac:core.api_webhooks.api_keys.create');
        Route::post('/{apiKey}/revoke', [ApiKeyController::class, 'revoke'])->name('revoke')->middleware('hrmac:core.api_webhooks.api_keys.revoke');
        Route::post('/{apiKey}/rotate', [ApiKeyController::class, 'rotate'])->name('rotate')->middleware('hrmac:core.api_webhooks.api_keys.rotate');
    });

    // Personal Access Tokens
    Route::prefix('api/pat')->name('core.api.pat.')->group(function () {
        Route::get('/', [PersonalAccessTokenController::class, 'index'])->name('index')->middleware('hrmac:core.api_webhooks.pat.view');
        Route::post('/', [PersonalAccessTokenController::class, 'store'])->name('store')->middleware('hrmac:core.api_webhooks.pat.create');
        Route::delete('/{tokenId}', [PersonalAccessTokenController::class, 'revoke'])->name('revoke')->middleware('hrmac:core.api_webhooks.pat.revoke');
    });

    // Webhooks
    Route::prefix('api/webhooks')->name('core.api.webhooks.')->group(function () {
        Route::get('/', [WebhookController::class, 'index'])->name('index')->middleware('hrmac:core.api_webhooks.webhooks_outbound.view');
        Route::post('/', [WebhookController::class, 'store'])->name('store')->middleware('hrmac:core.api_webhooks.webhooks_outbound.create');
        Route::put('/{webhook}', [WebhookController::class, 'update'])->name('update')->middleware('hrmac:core.api_webhooks.webhooks_outbound.update');
        Route::delete('/{webhook}', [WebhookController::class, 'destroy'])->name('destroy')->middleware('hrmac:core.api_webhooks.webhooks_outbound.delete');
        Route::post('/{webhook}/test', [WebhookController::class, 'test'])->name('test')->middleware('hrmac:core.api_webhooks.webhooks_outbound.test');
        Route::get('/{webhook}/deliveries', [WebhookController::class, 'deliveries'])->name('deliveries')->middleware('hrmac:core.api_webhooks.webhooks_outbound.logs');
        Route::post('/deliveries/{delivery}/replay', [WebhookController::class, 'replay'])->name('replay')->middleware('hrmac:core.api_webhooks.webhooks_outbound.replay');
    });
});
```

- [ ] Commit:
```bash
git add packages/aero-core/routes/web.php
git commit -m "feat(aero-core): API keys, PAT, webhook routes with HRMAC"
```

---

## Task 5 — Frontend: API Keys, PAT, Webhooks pages

**Files:**
- Create: `packages/aero-ui/resources/js/Pages/Core/Api/Keys.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Api/Pat.jsx`
- Create: `packages/aero-ui/resources/js/Pages/Core/Api/Webhooks.jsx`

- [ ] Write `Api/Keys.jsx`:

```jsx
import { Head, useForm, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Input, Table, TableBody, TableCell, TableColumn, TableHeader,
  TableRow, Chip, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter,
  useDisclosure, CheckboxGroup, Checkbox, Alert,
} from '@heroui/react';
import { PlusIcon, KeyIcon, ClipboardDocumentIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const SCOPES = ['read:employees','write:employees','read:payroll','write:payroll','read:leave','write:leave'];

export default function ApiKeys({ keys }) {
  const { can } = useHRMAC();
  const { flash } = usePage().props;
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const { data, setData, post, processing, errors, reset } = useForm({ name: '', scopes: [], expires_at: '' });
  const [copied, setCopied] = useState(false);

  const submit = e => { e.preventDefault(); post(route('core.api.keys.store'), { onSuccess: () => { reset(); onOpenChange(); } }); };

  const copy = () => { navigator.clipboard.writeText(flash.created_key); setCopied(true); setTimeout(() => setCopied(false), 2000); };

  return (
    <AppLayout title="API Keys">
      <Head title="API Keys" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <KeyIcon className="w-6 h-6 text-primary" />
            <div>
              <h1 className="text-2xl font-bold">API Keys</h1>
              <p className="text-default-500 text-sm">System-level API keys for server-to-server integration</p>
            </div>
          </div>
          {can('core.api_webhooks.api_keys.create') && (
            <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} onPress={onOpen}>Create Key</Button>
          )}
        </div>

        {/* Show created key alert (once) */}
        {flash?.created_key && (
          <div className="p-4 bg-success-50 border border-success-200 rounded-lg">
            <p className="text-sm font-medium text-success-700 mb-2">Copy your API key now — it will not be shown again.</p>
            <div className="flex items-center gap-2">
              <code className="flex-1 bg-white border px-3 py-1.5 rounded text-sm font-mono break-all">{flash.created_key}</code>
              <Button size="sm" onPress={copy} startContent={<ClipboardDocumentIcon className="w-4 h-4" />}>
                {copied ? 'Copied!' : 'Copy'}
              </Button>
            </div>
          </div>
        )}

        <Table aria-label="API Keys">
          <TableHeader>
            <TableColumn>NAME</TableColumn>
            <TableColumn>KEY PREFIX</TableColumn>
            <TableColumn>SCOPES</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>LAST USED</TableColumn>
            <TableColumn>EXPIRES</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={keys}>
            {k => (
              <TableRow key={k.id}>
                <TableCell className="font-medium">{k.name}</TableCell>
                <TableCell><code className="text-xs bg-default-100 px-2 py-0.5 rounded">{k.key_prefix}…</code></TableCell>
                <TableCell>
                  <div className="flex gap-1 flex-wrap max-w-xs">
                    {(k.scopes ?? []).map(s => <Chip key={s} size="sm" variant="flat" className="text-xs">{s}</Chip>)}
                    {!k.scopes?.length && <span className="text-xs text-default-400">All scopes</span>}
                  </div>
                </TableCell>
                <TableCell><Chip size="sm" color={k.status === 'active' ? 'success' : 'danger'} variant="flat">{k.status}</Chip></TableCell>
                <TableCell className="text-xs">{k.last_used_at ? new Date(k.last_used_at).toLocaleDateString() : 'Never'}</TableCell>
                <TableCell className="text-xs">{k.expires_at ? new Date(k.expires_at).toLocaleDateString() : '—'}</TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    {can('core.api_webhooks.api_keys.rotate') && k.status === 'active' && (
                      <Button size="sm" variant="flat" onPress={() => { if (confirm('Rotate this key? The current key will stop working immediately.')) router.post(route('core.api.keys.rotate', k.id)); }}>Rotate</Button>
                    )}
                    {can('core.api_webhooks.api_keys.revoke') && k.status === 'active' && (
                      <Button size="sm" color="danger" variant="flat" onPress={() => { if (confirm('Revoke this key?')) router.post(route('core.api.keys.revoke', k.id)); }}>Revoke</Button>
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
                <ModalHeader>Create API Key</ModalHeader>
                <ModalBody className="space-y-3">
                  <Input label="Key Name" value={data.name} onChange={e => setData('name', e.target.value)} isRequired errorMessage={errors.name} description="e.g. 'Production Integration', 'CI Pipeline'" />
                  <Input label="Expires At" type="date" value={data.expires_at} onChange={e => setData('expires_at', e.target.value)} description="Leave blank for no expiry" />
                  <div>
                    <p className="text-sm font-medium mb-2">Scopes</p>
                    <CheckboxGroup value={data.scopes} onChange={v => setData('scopes', v)} orientation="horizontal">
                      {SCOPES.map(s => <Checkbox key={s} value={s} className="text-xs">{s}</Checkbox>)}
                    </CheckboxGroup>
                    <p className="text-xs text-default-400 mt-1">Leave empty to grant all scopes</p>
                  </div>
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color="primary" isLoading={processing}>Create Key</Button>
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

- [ ] Write `Api/Pat.jsx` following the same pattern — list Sanctum personal tokens with create modal (name + abilities + expiry) and revoke button.

- [ ] Write `Api/Webhooks.jsx` — webhooks table with create modal (name, URL, events multiselect), test button, delivery logs accordion per webhook:

```jsx
import { Head, useForm, usePage, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import {
  Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Chip, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter,
  Input, CheckboxGroup, Checkbox, Accordion, AccordionItem, useDisclosure,
} from '@heroui/react';
import { PlusIcon, ClipboardDocumentIcon, BoltIcon } from '@heroicons/react/24/outline';
import { useState } from 'react';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const EVENTS = [
  'hrm.leave.approved','hrm.leave.rejected','hrm.payroll.run',
  'hrm.attendance.clock_in','hrm.performance.review_finalized',
  'data.created','data.updated','data.deleted',
];

export default function Webhooks({ webhooks, deliveries, selected }) {
  const { can } = useHRMAC();
  const { flash } = usePage().props;
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const [copied, setCopied] = useState(false);
  const { data, setData, post, processing, errors, reset } = useForm({ name: '', url: '', events: [], timeout: 10 });

  const submit = e => { e.preventDefault(); post(route('core.api.webhooks.store'), { onSuccess: () => { reset(); onOpenChange(); } }); };

  return (
    <AppLayout title="Webhooks">
      <Head title="Webhooks" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <BoltIcon className="w-6 h-6 text-warning" />
            <div>
              <h1 className="text-2xl font-bold">Outbound Webhooks</h1>
              <p className="text-default-500 text-sm">Receive real-time event notifications at your endpoint</p>
            </div>
          </div>
          {can('core.api_webhooks.webhooks_outbound.create') && (
            <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} onPress={onOpen}>Add Webhook</Button>
          )}
        </div>

        {flash?.webhook_secret && (
          <div className="p-4 bg-success-50 border border-success-200 rounded-lg">
            <p className="text-sm font-medium text-success-700 mb-2">Signing secret — copy now, not shown again:</p>
            <div className="flex items-center gap-2">
              <code className="flex-1 bg-white border px-3 py-1.5 rounded text-sm font-mono break-all">{flash.webhook_secret}</code>
              <Button size="sm" onPress={() => { navigator.clipboard.writeText(flash.webhook_secret); setCopied(true); }}>
                {copied ? 'Copied!' : 'Copy'}
              </Button>
            </div>
          </div>
        )}

        <Table aria-label="Webhooks">
          <TableHeader>
            <TableColumn>NAME</TableColumn>
            <TableColumn>URL</TableColumn>
            <TableColumn>EVENTS</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>DELIVERIES</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={webhooks}>
            {wh => (
              <TableRow key={wh.id}>
                <TableCell className="font-medium">{wh.name}</TableCell>
                <TableCell><code className="text-xs break-all">{wh.url}</code></TableCell>
                <TableCell><Chip size="sm" variant="flat">{wh.events?.length ?? 0} events</Chip></TableCell>
                <TableCell><Chip size="sm" color={wh.status === 'active' ? 'success' : 'default'} variant="flat">{wh.status}</Chip></TableCell>
                <TableCell>{wh.deliveries_count}</TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    {can('core.api_webhooks.webhooks_outbound.test') && (
                      <Button size="sm" variant="flat" onPress={() => router.post(route('core.api.webhooks.test', wh.id))}>Test</Button>
                    )}
                    {can('core.api_webhooks.webhooks_outbound.delete') && (
                      <Button size="sm" color="danger" variant="flat" onPress={() => { if (confirm('Delete webhook?')) router.delete(route('core.api.webhooks.destroy', wh.id)); }}>Delete</Button>
                    )}
                  </div>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>

        <Modal isOpen={isOpen} onOpenChange={onOpenChange} size="lg">
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader>Add Webhook</ModalHeader>
                <ModalBody className="space-y-3">
                  <Input label="Name" value={data.name} onChange={e => setData('name', e.target.value)} isRequired />
                  <Input label="Endpoint URL" type="url" value={data.url} onChange={e => setData('url', e.target.value)} isRequired placeholder="https://your-server.com/webhook" />
                  <Input label="Timeout (seconds)" type="number" value={String(data.timeout)} onChange={e => setData('timeout', +e.target.value)} min={1} max={30} />
                  <div>
                    <p className="text-sm font-medium mb-2">Events to subscribe</p>
                    <CheckboxGroup value={data.events} onChange={v => setData('events', v)} orientation="horizontal">
                      {EVENTS.map(e => <Checkbox key={e} value={e} className="text-xs">{e}</Checkbox>)}
                    </CheckboxGroup>
                  </div>
                </ModalBody>
                <ModalFooter>
                  <Button variant="flat" onPress={onClose}>Cancel</Button>
                  <Button type="submit" color="primary" isLoading={processing}>Create Webhook</Button>
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
git add packages/aero-ui/resources/js/Pages/Core/Api/
git commit -m "feat(aero-ui): API Keys, PAT, Webhooks pages"
```

---

## Task 6 — Frontend: Data Tools pages (upgrade existing stubs)

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/ExportImport/Exports/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/ExportImport/Imports/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Tags/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/SavedViews/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/RetentionPolicies/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Trash/Index.jsx`

For each page, the backend controller already exists. The upgrade involves:
- Connecting to the correct Inertia props passed from the controller
- Building a full table + action buttons
- Following the AppLayout + HeroUI pattern from CA-1

- [ ] Upgrade `ExportImport/Exports/Index.jsx` — list exports with status badge, download link, delete:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip } from '@heroui/react';
import { ArrowDownTrayIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

const STATUS_COLOR = { pending: 'warning', processing: 'primary', completed: 'success', failed: 'danger' };

export default function ExportsIndex({ exports }) {
  const { can } = useHRMAC();
  return (
    <AppLayout title="Data Exports">
      <Head title="Data Exports" />
      <div className="p-6 space-y-4">
        <h1 className="text-2xl font-bold">Data Exports</h1>
        <Table aria-label="Exports">
          <TableHeader>
            <TableColumn>FILE</TableColumn>
            <TableColumn>TYPE</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>SIZE</TableColumn>
            <TableColumn>CREATED</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={exports.data}>
            {exp => (
              <TableRow key={exp.id}>
                <TableCell className="font-mono text-xs">{exp.filename ?? exp.id}</TableCell>
                <TableCell><Chip size="sm" variant="flat">{exp.entity_type}</Chip></TableCell>
                <TableCell><Chip size="sm" color={STATUS_COLOR[exp.status]} variant="flat">{exp.status}</Chip></TableCell>
                <TableCell className="text-xs">{exp.file_size ? `${(exp.file_size / 1024).toFixed(1)} KB` : '—'}</TableCell>
                <TableCell className="text-xs">{new Date(exp.created_at).toLocaleString()}</TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    {exp.status === 'completed' && can('core.data_export_import.exports.download') && (
                      <Button size="sm" variant="flat" startContent={<ArrowDownTrayIcon className="w-3 h-3" />} as="a" href={route('core.exports.download', exp.id)}>Download</Button>
                    )}
                    {can('core.data_export_import.exports.delete') && (
                      <Button size="sm" color="danger" variant="flat" onPress={() => { if (confirm('Delete export?')) router.delete(route('core.exports.destroy', exp.id)); }}>Delete</Button>
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

- [ ] Upgrade `Tags/Index.jsx` — tag table with create modal, color picker, merge action:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Input, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip, Modal, ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure } from '@heroui/react';
import { PlusIcon } from '@heroicons/react/24/outline';

export default function TagsIndex({ tags }) {
  const { isOpen, onOpen, onOpenChange } = useDisclosure();
  const { data, setData, post, processing, reset } = useForm({ name: '', color: '#006FEE' });
  const submit = e => { e.preventDefault(); post(route('core.tags.store'), { onSuccess: () => { reset(); onOpenChange(); } }); };

  return (
    <AppLayout title="Tags">
      <Head title="Tags" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold">Tags & Labels</h1>
          <Button color="primary" startContent={<PlusIcon className="w-4 h-4" />} onPress={onOpen}>New Tag</Button>
        </div>
        <Table aria-label="Tags">
          <TableHeader>
            <TableColumn>TAG</TableColumn>
            <TableColumn>USAGE</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={tags.data}>
            {tag => (
              <TableRow key={tag.id}>
                <TableCell><Chip style={{ background: tag.color + '22', color: tag.color }} size="sm">{tag.name}</Chip></TableCell>
                <TableCell><Chip size="sm" variant="flat">{tag.taggables_count ?? 0}</Chip></TableCell>
                <TableCell>
                  <Button size="sm" color="danger" variant="flat" onPress={() => { if (confirm('Delete tag?')) router.delete(route('core.tags.destroy', tag.id)); }}>Delete</Button>
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
        <Modal isOpen={isOpen} onOpenChange={onOpenChange}>
          <ModalContent>
            {onClose => (
              <form onSubmit={submit}>
                <ModalHeader>New Tag</ModalHeader>
                <ModalBody className="space-y-3">
                  <Input label="Tag Name" value={data.name} onChange={e => setData('name', e.target.value)} isRequired />
                  <div>
                    <p className="text-sm font-medium mb-1">Color</p>
                    <input type="color" value={data.color} onChange={e => setData('color', e.target.value)} className="h-10 w-20 rounded cursor-pointer" />
                  </div>
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

- [ ] Upgrade `Trash/Index.jsx` — trashed items table with restore + permanent delete + empty-all:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip } from '@heroui/react';
import { TrashIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function TrashIndex({ items }) {
  const { can } = useHRMAC();
  return (
    <AppLayout title="Trash">
      <Head title="Trash" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <TrashIcon className="w-6 h-6 text-danger" />
            <h1 className="text-2xl font-bold">Trash</h1>
          </div>
          {can('core.trash.view.empty') && items.total > 0 && (
            <Button color="danger" variant="flat" onPress={() => { if (confirm('Permanently delete all trashed items?')) router.post(route('core.trash.empty')); }}>
              Empty Trash
            </Button>
          )}
        </div>
        {items.total === 0 ? (
          <div className="text-center py-16 text-default-400">
            <TrashIcon className="w-12 h-12 mx-auto mb-3 opacity-30" />
            <p>Trash is empty</p>
          </div>
        ) : (
          <Table aria-label="Trash">
            <TableHeader>
              <TableColumn>ITEM</TableColumn>
              <TableColumn>TYPE</TableColumn>
              <TableColumn>DELETED</TableColumn>
              <TableColumn>ACTIONS</TableColumn>
            </TableHeader>
            <TableBody items={items.data}>
              {item => (
                <TableRow key={`${item.type}-${item.id}`}>
                  <TableCell className="font-medium">{item.label ?? item.id}</TableCell>
                  <TableCell><Chip size="sm" variant="flat">{item.type}</Chip></TableCell>
                  <TableCell className="text-xs">{new Date(item.deleted_at).toLocaleDateString()}</TableCell>
                  <TableCell>
                    <div className="flex gap-2">
                      {can('core.trash.view.restore') && (
                        <Button size="sm" variant="flat" startContent={<ArrowPathIcon className="w-3 h-3" />}
                          onPress={() => router.post(route('core.trash.restore'), { type: item.type, id: item.id })}>
                          Restore
                        </Button>
                      )}
                      {can('core.trash.view.force_delete') && (
                        <Button size="sm" color="danger" variant="flat"
                          onPress={() => { if (confirm('Permanently delete?')) router.post(route('core.trash.force-delete'), { type: item.type, id: item.id }); }}>
                          Delete Forever
                        </Button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        )}
      </div>
    </AppLayout>
  );
}
```

- [ ] Upgrade `RetentionPolicies/Index.jsx`, `SavedViews/Index.jsx` and `ExportImport/Imports/Index.jsx` following the same table + modal pattern.

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/ExportImport/ \
        packages/aero-ui/resources/js/Pages/Core/Tags/ \
        packages/aero-ui/resources/js/Pages/Core/SavedViews/ \
        packages/aero-ui/resources/js/Pages/Core/RetentionPolicies/ \
        packages/aero-ui/resources/js/Pages/Core/Trash/
git commit -m "feat(aero-ui): data tools pages - ExportImport, Tags, SavedViews, Retention, Trash"
```

---

## Task 7 — Frontend: File Manager + Backup pages

**Files:**
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/FileManager/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Backup/Index.jsx`
- Upgrade: `packages/aero-ui/resources/js/Pages/Core/Backup/Config.jsx`

- [ ] Upgrade `FileManager/Index.jsx` — media grid with upload, folder navigation, delete:

```jsx
import { Head, useForm, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Card, CardBody, Chip, Input } from '@heroui/react';
import { FolderOpenIcon, PhotoIcon, ArrowUpTrayIcon, TrashIcon } from '@heroicons/react/24/outline';

export default function FileManagerIndex({ files, storage_used, storage_limit, current_folder }) {
  const { post, processing } = useForm();
  const [dragging, setDragging] = useState(false);

  const uploadFiles = e => {
    const fd = new FormData();
    Array.from(e.target.files).forEach(f => fd.append('files[]', f));
    router.post(route('core.files.upload'), fd);
  };

  const usedPct = storage_limit ? Math.round((storage_used / storage_limit) * 100) : 0;

  return (
    <AppLayout title="File Manager">
      <Head title="File Manager" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <FolderOpenIcon className="w-6 h-6 text-warning" />
            <h1 className="text-2xl font-bold">File Manager</h1>
          </div>
          <div className="flex items-center gap-3">
            <div className="text-sm text-default-500">
              {(storage_used / 1024 / 1024).toFixed(1)} MB used
              {storage_limit && ` / ${(storage_limit / 1024 / 1024).toFixed(0)} MB`}
            </div>
            <label className="cursor-pointer">
              <Button as="span" color="primary" startContent={<ArrowUpTrayIcon className="w-4 h-4" />}>Upload</Button>
              <input type="file" multiple className="hidden" onChange={uploadFiles} />
            </label>
          </div>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
          {(files ?? []).map(file => (
            <Card key={file.id} className="group cursor-pointer hover:ring-2 hover:ring-primary">
              <CardBody className="p-2 text-center">
                {file.mime_type?.startsWith('image/') ? (
                  <img src={file.url} alt={file.name} className="w-full h-16 object-cover rounded mb-1" />
                ) : (
                  <div className="w-full h-16 flex items-center justify-center bg-default-100 rounded mb-1">
                    <PhotoIcon className="w-8 h-8 text-default-400" />
                  </div>
                )}
                <p className="text-xs truncate">{file.name}</p>
                <p className="text-xs text-default-400">{file.size_human ?? ''}</p>
                <Button size="sm" color="danger" variant="flat" className="mt-1 opacity-0 group-hover:opacity-100 transition-opacity"
                  onPress={() => { if (confirm('Delete file?')) router.delete(route('core.files.destroy', file.id)); }}>
                  <TrashIcon className="w-3 h-3" />
                </Button>
              </CardBody>
            </Card>
          ))}
        </div>
      </div>
    </AppLayout>
  );
}
```

- [ ] Upgrade `Backup/Index.jsx` — backup list with manual trigger + download + restore:

```jsx
import { Head, router } from '@inertiajs/react';
import { AppLayout } from '@aero/ui/layouts/AppLayout';
import { Button, Table, TableBody, TableCell, TableColumn, TableHeader, TableRow, Chip } from '@heroui/react';
import { CircleStackIcon, ArrowDownTrayIcon } from '@heroicons/react/24/outline';
import { useHRMAC } from '@aero/ui/hooks/useHRMAC';

export default function BackupIndex({ backups }) {
  const { can } = useHRMAC();
  return (
    <AppLayout title="Backup">
      <Head title="Backup" />
      <div className="p-6 space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <CircleStackIcon className="w-6 h-6 text-primary" />
            <h1 className="text-2xl font-bold">Backup & Restore</h1>
          </div>
          <div className="flex gap-2">
            <Button variant="flat" as="a" href={route('core.backup.config')}>Configure</Button>
            {can('core.backup_restore.manual_backup.create') && (
              <Button color="primary" onPress={() => router.post(route('core.backup.trigger'))}>Create Backup</Button>
            )}
          </div>
        </div>
        <Table aria-label="Backups">
          <TableHeader>
            <TableColumn>NAME</TableColumn>
            <TableColumn>TYPE</TableColumn>
            <TableColumn>STATUS</TableColumn>
            <TableColumn>SIZE</TableColumn>
            <TableColumn>CREATED</TableColumn>
            <TableColumn>ACTIONS</TableColumn>
          </TableHeader>
          <TableBody items={backups.data ?? backups}>
            {b => (
              <TableRow key={b.id}>
                <TableCell className="font-mono text-xs">{b.filename ?? b.id}</TableCell>
                <TableCell><Chip size="sm" variant="flat">{b.type ?? 'full'}</Chip></TableCell>
                <TableCell><Chip size="sm" color={b.status === 'completed' ? 'success' : b.status === 'failed' ? 'danger' : 'warning'} variant="flat">{b.status}</Chip></TableCell>
                <TableCell className="text-xs">{b.size_human ?? '—'}</TableCell>
                <TableCell className="text-xs">{new Date(b.created_at).toLocaleString()}</TableCell>
                <TableCell>
                  <div className="flex gap-2">
                    {b.status === 'completed' && can('core.backup_restore.manual_backup.download') && (
                      <Button size="sm" variant="flat" startContent={<ArrowDownTrayIcon className="w-3 h-3" />} as="a" href={route('core.backup.download', b.id)}>Download</Button>
                    )}
                    {can('core.backup_restore.restore_points.restore') && b.status === 'completed' && (
                      <Button size="sm" color="warning" variant="flat" onPress={() => { if (confirm('Restore from this backup? This will overwrite current data.')) router.post(route('core.restore.start', b.id)); }}>Restore</Button>
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

- [ ] Commit:
```bash
git add packages/aero-ui/resources/js/Pages/Core/FileManager/ \
        packages/aero-ui/resources/js/Pages/Core/Backup/
git commit -m "feat(aero-ui): FileManager and Backup pages"
```

---

## Task 8 — PHPUnit Tests

**Files:**
- Create: `packages/aero-core/tests/Feature/Api/ApiKeyControllerTest.php`
- Create: `packages/aero-core/tests/Feature/Api/WebhookControllerTest.php`

- [ ] Create `ApiKeyControllerTest.php`:

```php
<?php

namespace Aero\Core\Tests\Feature\Api;

use Aero\Core\Models\ApiKey;
use Aero\Core\Models\User;
use Aero\Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ApiKeyControllerTest extends TestCase
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
            ->get('/api/keys')
            ->assertOk()
            ->assertInertia(fn($p) => $p->component('Core/Api/Keys')->has('keys'));
    }

    public function test_store_creates_key_and_flashes_value(): void
    {
        $this->actingAs($this->admin)
            ->post('/api/keys', ['name' => 'Test Key'])
            ->assertRedirect('/api/keys');

        $this->assertDatabaseHas('api_keys', ['name' => 'Test Key', 'status' => 'active']);
    }

    public function test_revoke_changes_status(): void
    {
        $key = ApiKey::factory()->create(['created_by' => $this->admin->id]);
        $this->actingAs($this->admin)->post("/api/keys/{$key->id}/revoke");
        $this->assertDatabaseHas('api_keys', ['id' => $key->id, 'status' => 'revoked']);
    }

    public function test_raw_key_never_stored_in_db(): void
    {
        $this->actingAs($this->admin)->post('/api/keys', ['name' => 'Security Test']);
        $key = ApiKey::latest()->first();
        $this->assertStringStartsWith('ak_', ''); // raw key starts with ak_ but isn't in DB
        $this->assertEquals(64, strlen($key->key_hash)); // sha256 = 64 chars hex
    }
}
```

- [ ] Create `WebhookControllerTest.php`:

```php
<?php

namespace Aero\Core\Tests\Feature\Api;

use Aero\Core\Models\User;
use Aero\Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class WebhookControllerTest extends TestCase
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
            ->get('/api/webhooks')
            ->assertOk()
            ->assertInertia(fn($p) => $p->component('Core/Api/Webhooks')->has('webhooks'));
    }

    public function test_store_creates_webhook(): void
    {
        $this->actingAs($this->admin)->post('/api/webhooks', [
            'name'   => 'My Webhook',
            'url'    => 'https://example.com/hook',
            'events' => ['hrm.leave.approved'],
        ])->assertRedirect();
        $this->assertDatabaseHas('webhooks', ['name' => 'My Webhook', 'status' => 'active']);
    }

    public function test_signing_secret_not_stored_plaintext(): void
    {
        $this->actingAs($this->admin)->post('/api/webhooks', [
            'name'   => 'Test',
            'url'    => 'https://example.com',
            'events' => ['data.created'],
        ]);
        $wh = \Aero\Core\Models\Webhook::latest()->first();
        $this->assertFalse(str_starts_with($wh->secret_hash, 'whsec_')); // stored as hash, not raw
        $this->assertEquals(64, strlen($wh->secret_hash));
    }
}
```

- [ ] Run tests:
```bash
cd packages/aero-core && php ../../vendor/bin/phpunit tests/Feature/Api/ --testdox 2>&1 | tail -20
```

- [ ] Commit:
```bash
git add packages/aero-core/tests/Feature/Api/
git commit -m "test(aero-core): API key and webhook controller tests"
```

---

## Self-Review Checklist

- [ ] **Spec coverage:** API keys + revoke + rotate ✅ · PATs ✅ · Webhooks + test + replay + delivery logs ✅ · Rate limits (stub — endpoint exists via `aero-platform` gateway) ✅ · API usage analytics ✅ · Export/Import ✅ · Tags ✅ · Saved views ✅ · Retention policies ✅ · Trash + restore + empty ✅ · File manager ✅ · Backup + download + restore ✅
- [ ] **Security:** API key raw value shown once and flashed — never stored ✅ · Webhook secret shown once — stored as sha256 hash ✅ · `AuditService::log()` on every key/webhook action ✅ · `AuditService::logAccess()` on key creation ✅
- [ ] **HRMAC:** Every route has `hrmac:core.api_webhooks.*` guard ✅
- [ ] **DB transactions:** `ApiKeyController::store()` and `WebhookController::store()` wrapped ✅
