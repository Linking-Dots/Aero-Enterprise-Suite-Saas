# Plan P-1 — Tenant Lifecycle & Onboarding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a production-grade Platform Admin surface for tenant lifecycle management — list/detail/health, BYOC credentials, suspend/activate/purge, bulk operations (suspend, plan change, email), impersonation, provisioning queue (approve/reject/retry), and trial management (extend/convert).

**Architecture:** All domain code lives in `packages/aero-platform/src/{Models,Http,Services,Actions}/`. Models extend `Aero\Contracts\Models\CentralModel` (landlord/central DB). All admin routes mount inside the `landlord` guard group in `packages/aero-platform/routes/admin.php` and are HRMAC-gated via the codes declared in `packages/aero-platform/config/module.php`. All writes run in `DB::transaction()`; every business action calls `AuditServiceInterface::log()` which persists to `platform_audit_logs`. Tenant credentials (BYOC database creds, secrets) use `EncryptedField` casts. React pages live under `packages/aero-ui/resources/js/Pages/Platform/Admin/{Tenants,Onboarding}/`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Orchestra Testbench.

---

## 1. HRMAC Hierarchy

Declared in `packages/aero-platform/config/module.php`. Routes reference codes as `hrmac:{submodule}.{component}.{action}` (no module prefix at route layer).

**Submodule `tenants`**
- `tenants.tenant-list.view` / `.create` / `.edit` / `.delete` / `.suspend` / `.activate` / `.impersonate`
- `tenants.tenant-domains.view` / `.manage`
- `tenants.tenant-databases.view` / `.migrate` / `.backup`

**Submodule `platform-onboarding`**
- `platform-onboarding.pending-approvals.view` / `.approve` / `.reject`
- `platform-onboarding.provisioning.view` / `.retry`
- `platform-onboarding.trials.view` / `.extend` / `.convert`

**Submodule `tenant-operations`**
- `tenant-operations.bulk-actions.bulk-suspend` / `.bulk-plan-change` / `.bulk-email`
- `tenant-operations.tenant-clone.clone`
- `tenant-operations.tenant-archive.archive` / `.restore`

---

## 2. Data Model

### Task 1 — Migrations

- [ ] Create `packages/aero-platform/database/migrations/2026_05_20_010001_upgrade_tenants_table.php`
- [ ] Create `packages/aero-platform/database/migrations/2026_05_20_010002_create_tenant_health_snapshots_table.php`
- [ ] Create `packages/aero-platform/database/migrations/2026_05_20_010003_create_bulk_tenant_operations_table.php`

```php
// 2026_05_20_010001_upgrade_tenants_table.php
Schema::table('tenants', function (Blueprint $table) {
    if (!Schema::hasColumn('tenants', 'status')) {
        $table->string('status', 24)->default('active')->after('name'); // active, suspended, archived, provisioning, failed
    }
    if (!Schema::hasColumn('tenants', 'suspended_at')) {
        $table->timestamp('suspended_at')->nullable();
        $table->string('suspension_reason')->nullable();
    }
    if (!Schema::hasColumn('tenants', 'archived_at')) {
        $table->timestamp('archived_at')->nullable();
    }
    if (!Schema::hasColumn('tenants', 'trial_ends_at')) {
        $table->timestamp('trial_ends_at')->nullable();
    }
    if (!Schema::hasColumn('tenants', 'byoc_db_host')) {
        $table->text('byoc_db_host')->nullable();        // encrypted
        $table->text('byoc_db_port')->nullable();        // encrypted
        $table->text('byoc_db_name')->nullable();        // encrypted
        $table->text('byoc_db_username')->nullable();    // encrypted
        $table->text('byoc_db_password')->nullable();    // encrypted
        $table->boolean('byoc_enabled')->default(false);
    }
    $table->index(['status', 'trial_ends_at']);
});
```

```php
// 2026_05_20_010002_create_tenant_health_snapshots_table.php
Schema::create('tenant_health_snapshots', function (Blueprint $table) {
    $table->id();
    $table->string('tenant_id'); // tenants.id is string (UUID/slug)
    $table->decimal('cpu_pct', 5, 2)->nullable();
    $table->bigInteger('db_size_mb')->nullable();
    $table->bigInteger('storage_mb')->nullable();
    $table->integer('api_calls_today')->default(0);
    $table->integer('active_users')->default(0);
    $table->string('status', 16)->default('healthy'); // healthy, warning, critical
    $table->json('details')->nullable();
    $table->timestamp('checked_at');
    $table->timestamps();
    $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
    $table->index(['tenant_id', 'checked_at']);
});
```

```php
// 2026_05_20_010003_create_bulk_tenant_operations_table.php
Schema::create('bulk_tenant_operations', function (Blueprint $table) {
    $table->id();
    $table->string('type', 32); // bulk_suspend, bulk_activate, bulk_plan_change, bulk_email
    $table->json('tenant_ids');
    $table->json('payload')->nullable();
    $table->string('status', 24)->default('pending'); // pending, running, completed, failed
    $table->unsignedBigInteger('initiated_by'); // landlord admin user id
    $table->integer('total_count')->default(0);
    $table->integer('success_count')->default(0);
    $table->integer('failed_count')->default(0);
    $table->json('results')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('completed_at')->nullable();
    $table->text('error')->nullable();
    $table->timestamps();
    $table->index(['status', 'created_at']);
    $table->index('initiated_by');
});
```

### Task 2 — Models

- [ ] Upgrade `packages/aero-platform/src/Models/Tenant.php` (already exists)
- [ ] Create `packages/aero-platform/src/Models/TenantHealthSnapshot.php`
- [ ] Create `packages/aero-platform/src/Models/BulkTenantOperation.php`

```php
// packages/aero-platform/src/Models/Tenant.php (additions)
namespace Aero\Platform\Models;

use Aero\Contracts\Casts\EncryptedField;
use Aero\Contracts\Models\CentralModel;

class Tenant extends CentralModel
{
    protected $fillable = [
        'id', 'name', 'plan_id', 'status', 'suspended_at', 'suspension_reason',
        'archived_at', 'trial_ends_at', 'byoc_enabled',
        'byoc_db_host', 'byoc_db_port', 'byoc_db_name',
        'byoc_db_username', 'byoc_db_password',
    ];

    protected $casts = [
        'suspended_at' => 'datetime',
        'archived_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'byoc_enabled' => 'boolean',
        'byoc_db_host' => EncryptedField::class,
        'byoc_db_port' => EncryptedField::class,
        'byoc_db_name' => EncryptedField::class,
        'byoc_db_username' => EncryptedField::class,
        'byoc_db_password' => EncryptedField::class,
    ];

    protected $hidden = [
        'byoc_db_password', 'byoc_db_username',
    ];

    public function plan() { return $this->belongsTo(Plan::class); }
    public function subscription() { return $this->hasOne(Subscription::class); }
    public function healthSnapshots() { return $this->hasMany(TenantHealthSnapshot::class); }
    public function latestHealth() { return $this->hasOne(TenantHealthSnapshot::class)->latestOfMany('checked_at'); }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeSuspended($q) { return $q->where('status', 'suspended'); }
    public function scopeOnTrial($q) { return $q->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now()); }
}
```

```php
// packages/aero-platform/src/Models/TenantHealthSnapshot.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class TenantHealthSnapshot extends CentralModel
{
    protected $fillable = [
        'tenant_id', 'cpu_pct', 'db_size_mb', 'storage_mb',
        'api_calls_today', 'active_users', 'status', 'details', 'checked_at',
    ];

    protected $casts = [
        'cpu_pct' => 'decimal:2',
        'details' => 'array',
        'checked_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
}
```

```php
// packages/aero-platform/src/Models/BulkTenantOperation.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class BulkTenantOperation extends CentralModel
{
    protected $fillable = [
        'type', 'tenant_ids', 'payload', 'status', 'initiated_by',
        'total_count', 'success_count', 'failed_count', 'results',
        'started_at', 'completed_at', 'error',
    ];

    protected $casts = [
        'tenant_ids' => 'array',
        'payload' => 'array',
        'results' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function initiator() { return $this->belongsTo(\App\Models\User::class, 'initiated_by'); }
}
```

---

## 3. Services

### Task 3 — Service classes

- [ ] Create `packages/aero-platform/src/Services/TenantAdminService.php`
- [ ] Create `packages/aero-platform/src/Services/TenantHealthService.php`
- [ ] Create `packages/aero-platform/src/Services/BulkOperationService.php`
- [ ] Create `packages/aero-platform/src/Services/ImpersonationService.php`
- [ ] Create `packages/aero-platform/src/Services/ProvisioningAdminService.php`

```php
// packages/aero-platform/src/Services/TenantAdminService.php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Tenant::query()
            ->with(['plan', 'latestHealth', 'subscription'])
            ->when($filters['search'] ?? null, fn ($q, $s) =>
                $q->where(fn ($qq) => $qq->where('name', 'like', "%{$s}%")->orWhere('id', 'like', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['plan_id'] ?? null, fn ($q, $p) => $q->where('plan_id', $p))
            ->when($filters['on_trial'] ?? null, fn ($q) =>
                $q->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now()))
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();
    }

    public function show(Tenant $tenant): Tenant
    {
        $tenant->load(['plan', 'subscription.plan', 'latestHealth']);
        $this->audit->logAccess(subject: $tenant, description: "Viewed tenant {$tenant->id}");
        return $tenant;
    }

    public function suspend(Tenant $tenant, string $reason): Tenant
    {
        return DB::transaction(function () use ($tenant, $reason) {
            $tenant->lockForUpdate();
            $tenant->update([
                'status' => 'suspended',
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);
            $this->audit->log(
                event: 'tenant.suspended',
                action: 'suspend',
                subject: $tenant,
                description: "Suspended tenant {$tenant->name}: {$reason}",
            );
            return $tenant->fresh();
        });
    }

    public function activate(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->lockForUpdate();
            $tenant->update([
                'status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
            $this->audit->log(
                event: 'tenant.activated',
                action: 'activate',
                subject: $tenant,
                description: "Activated tenant {$tenant->name}",
            );
            return $tenant->fresh();
        });
    }

    public function purge(Tenant $tenant): void
    {
        if ($tenant->subscription && $tenant->subscription->status === 'active') {
            throw new \DomainException('Cannot purge tenant with an active subscription. Cancel the subscription first.');
        }
        DB::transaction(function () use ($tenant) {
            $this->audit->log(
                event: 'tenant.purged',
                action: 'delete',
                subject: $tenant,
                description: "Purged tenant {$tenant->name} (id={$tenant->id})",
            );
            $tenant->delete();
        });
    }

    public function updateByoc(Tenant $tenant, array $creds): Tenant
    {
        return DB::transaction(function () use ($tenant, $creds) {
            $tenant->lockForUpdate();
            $tenant->update([
                'byoc_enabled' => (bool) ($creds['byoc_enabled'] ?? false),
                'byoc_db_host' => $creds['byoc_db_host'] ?? null,
                'byoc_db_port' => $creds['byoc_db_port'] ?? null,
                'byoc_db_name' => $creds['byoc_db_name'] ?? null,
                'byoc_db_username' => $creds['byoc_db_username'] ?? null,
                'byoc_db_password' => $creds['byoc_db_password'] ?? null,
            ]);
            $this->audit->log(
                event: 'tenant.byoc.updated',
                action: 'update',
                subject: $tenant,
                description: "Updated BYOC credentials for {$tenant->name}",
            );
            return $tenant->fresh();
        });
    }
}
```

```php
// packages/aero-platform/src/Services/TenantHealthService.php
namespace Aero\Platform\Services;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantHealthSnapshot;

class TenantHealthService
{
    public function refresh(Tenant $tenant): TenantHealthSnapshot
    {
        // Stub: real probes (DB size query, API counter, storage) plug in here.
        return $tenant->healthSnapshots()->create([
            'cpu_pct' => 0,
            'db_size_mb' => 0,
            'storage_mb' => 0,
            'api_calls_today' => 0,
            'active_users' => 0,
            'status' => 'healthy',
            'checked_at' => now(),
        ]);
    }

    public function aggregate(): array
    {
        return [
            'healthy' => Tenant::whereHas('latestHealth', fn ($q) => $q->where('status', 'healthy'))->count(),
            'warning' => Tenant::whereHas('latestHealth', fn ($q) => $q->where('status', 'warning'))->count(),
            'critical' => Tenant::whereHas('latestHealth', fn ($q) => $q->where('status', 'critical'))->count(),
        ];
    }
}
```

```php
// packages/aero-platform/src/Services/BulkOperationService.php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\BulkTenantOperation;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkOperationService
{
    public function __construct(
        private AuditServiceInterface $audit,
        private TenantAdminService $tenantAdmin,
    ) {}

    public function execute(string $type, array $tenantIds, array $payload = []): BulkTenantOperation
    {
        return DB::transaction(function () use ($type, $tenantIds, $payload) {
            $op = BulkTenantOperation::create([
                'type' => $type,
                'tenant_ids' => $tenantIds,
                'payload' => $payload,
                'status' => 'running',
                'initiated_by' => Auth::guard('landlord')->id() ?? Auth::id(),
                'total_count' => count($tenantIds),
                'started_at' => now(),
            ]);

            $results = [];
            $success = 0; $failed = 0;
            foreach (Tenant::whereIn('id', $tenantIds)->get() as $tenant) {
                try {
                    match ($type) {
                        'bulk_suspend' => $this->tenantAdmin->suspend($tenant, $payload['reason'] ?? 'Bulk suspension'),
                        'bulk_activate' => $this->tenantAdmin->activate($tenant),
                        'bulk_plan_change' => $tenant->update(['plan_id' => $payload['plan_id']]),
                        'bulk_email' => null, // dispatched via notification job
                        default => throw new \InvalidArgumentException("Unknown bulk type: {$type}"),
                    };
                    $results[$tenant->id] = ['status' => 'ok'];
                    $success++;
                } catch (\Throwable $e) {
                    $results[$tenant->id] = ['status' => 'failed', 'error' => $e->getMessage()];
                    $failed++;
                }
            }

            $op->update([
                'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
                'success_count' => $success,
                'failed_count' => $failed,
                'results' => $results,
                'completed_at' => now(),
            ]);

            $this->audit->log(
                event: 'tenant.bulk_operation',
                action: $type,
                subject: $op,
                description: "Bulk {$type} on {$op->total_count} tenants: {$success} ok, {$failed} failed",
            );

            return $op->fresh();
        });
    }
}
```

```php
// packages/aero-platform/src/Services/ImpersonationService.php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function start(Tenant $tenant): string
    {
        $adminId = Auth::guard('landlord')->id();
        Session::put('impersonating_tenant_id', $tenant->id);
        Session::put('impersonator_admin_id', $adminId);

        $this->audit->log(
            event: 'tenant.impersonation.started',
            action: 'impersonate',
            subject: $tenant,
            description: "Admin #{$adminId} started impersonating tenant {$tenant->id}",
        );

        return $this->buildImpersonationUrl($tenant);
    }

    public function stop(): void
    {
        $tenantId = Session::pull('impersonating_tenant_id');
        $adminId = Session::pull('impersonator_admin_id');
        if ($tenantId) {
            $this->audit->log(
                event: 'tenant.impersonation.stopped',
                action: 'impersonate.stop',
                subject: Tenant::find($tenantId),
                description: "Admin #{$adminId} stopped impersonation of {$tenantId}",
            );
        }
    }

    private function buildImpersonationUrl(Tenant $tenant): string
    {
        // Signed URL into tenant subdomain handled by tenant-runtime middleware.
        return route('platform.admin.tenants.impersonate.target', ['tenant' => $tenant->id]);
    }
}
```

```php
// packages/aero-platform/src/Services/ProvisioningAdminService.php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;

class ProvisioningAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function listPending(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Tenant::where('status', 'pending_approval')
            ->orWhere('status', 'failed')
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function approve(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->lockForUpdate();
            $tenant->update(['status' => 'provisioning']);
            // Dispatch provisioning job here.
            $this->audit->log(
                event: 'tenant.provisioning.approved',
                action: 'approve',
                subject: $tenant,
                description: "Approved provisioning for {$tenant->name}",
            );
            return $tenant->fresh();
        });
    }

    public function reject(Tenant $tenant, string $reason): Tenant
    {
        return DB::transaction(function () use ($tenant, $reason) {
            $tenant->lockForUpdate();
            $tenant->update(['status' => 'rejected', 'suspension_reason' => $reason]);
            $this->audit->log(
                event: 'tenant.provisioning.rejected',
                action: 'reject',
                subject: $tenant,
                description: "Rejected {$tenant->name}: {$reason}",
            );
            return $tenant->fresh();
        });
    }

    public function retry(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->lockForUpdate();
            if ($tenant->status !== 'failed') {
                throw new \DomainException('Only failed tenants can be retried.');
            }
            $tenant->update(['status' => 'provisioning']);
            $this->audit->log(
                event: 'tenant.provisioning.retried',
                action: 'retry',
                subject: $tenant,
                description: "Retried provisioning for {$tenant->name}",
            );
            return $tenant->fresh();
        });
    }
}
```

---

## 4. Controllers

### Task 4 — Controller classes

- [ ] Create `packages/aero-platform/src/Http/Controllers/Admin/TenantListController.php`
- [ ] Create `packages/aero-platform/src/Http/Controllers/Admin/TenantByocController.php`
- [ ] Create `packages/aero-platform/src/Http/Controllers/Admin/TenantHealthController.php`
- [ ] Create `packages/aero-platform/src/Http/Controllers/Admin/BulkOperationController.php`
- [ ] Create `packages/aero-platform/src/Http/Controllers/Admin/ImpersonationController.php`
- [ ] Create `packages/aero-platform/src/Http/Controllers/Admin/ProvisioningController.php`
- [ ] Create `packages/aero-platform/src/Http/Controllers/Admin/TrialController.php`
- [ ] Create matching Form Request classes under `packages/aero-platform/src/Http/Requests/Admin/`

```php
// packages/aero-platform/src/Http/Controllers/Admin/TenantListController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\SuspendTenantRequest;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantAdminService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class TenantListController extends Controller
{
    public function __construct(private TenantAdminService $service) {}

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status', 'plan_id', 'on_trial']);
        return Inertia::render('Platform/Admin/Tenants/Index', [
            'tenants' => $this->service->list($filters),
            'filters' => $filters,
        ]);
    }

    public function show(Tenant $tenant)
    {
        return Inertia::render('Platform/Admin/Tenants/Show', [
            'tenant' => $this->service->show($tenant),
        ]);
    }

    public function suspend(SuspendTenantRequest $request, Tenant $tenant)
    {
        $this->service->suspend($tenant, $request->validated('reason'));
        return back()->with('success', 'Tenant suspended.');
    }

    public function activate(Tenant $tenant)
    {
        $this->service->activate($tenant);
        return back()->with('success', 'Tenant activated.');
    }

    public function purge(Tenant $tenant)
    {
        $this->service->purge($tenant);
        return redirect()->route('platform.admin.tenants.index')->with('success', 'Tenant purged.');
    }
}
```

```php
// packages/aero-platform/src/Http/Controllers/Admin/TenantByocController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\UpdateByocRequest;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantAdminService;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class TenantByocController extends Controller
{
    public function __construct(private TenantAdminService $service) {}

    public function show(Tenant $tenant)
    {
        return Inertia::render('Platform/Admin/Tenants/Byoc', [
            'tenant' => $tenant->only(['id', 'name', 'byoc_enabled', 'byoc_db_host', 'byoc_db_port', 'byoc_db_name']),
        ]);
    }

    public function update(UpdateByocRequest $request, Tenant $tenant)
    {
        $this->service->updateByoc($tenant, $request->validated());
        return back()->with('success', 'BYOC credentials updated.');
    }
}
```

```php
// packages/aero-platform/src/Http/Controllers/Admin/BulkOperationController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\BulkOperationRequest;
use Aero\Platform\Models\BulkTenantOperation;
use Aero\Platform\Services\BulkOperationService;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class BulkOperationController extends Controller
{
    public function __construct(private BulkOperationService $service) {}

    public function index()
    {
        return Inertia::render('Platform/Admin/Tenants/Bulk', [
            'operations' => BulkTenantOperation::orderByDesc('created_at')->paginate(15),
        ]);
    }

    public function store(BulkOperationRequest $request)
    {
        $op = $this->service->execute(
            $request->validated('type'),
            $request->validated('tenant_ids'),
            $request->validated('payload', []),
        );
        return back()->with('success', "Bulk operation #{$op->id} completed.");
    }

    public function show(BulkTenantOperation $operation)
    {
        return response()->json($operation);
    }
}
```

```php
// packages/aero-platform/src/Http/Controllers/Admin/ImpersonationController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\ImpersonationService;
use Illuminate\Routing\Controller;

class ImpersonationController extends Controller
{
    public function __construct(private ImpersonationService $service) {}

    public function store(Tenant $tenant)
    {
        $url = $this->service->start($tenant);
        return redirect()->away($url);
    }

    public function destroy()
    {
        $this->service->stop();
        return redirect()->route('platform.admin.tenants.index');
    }
}
```

```php
// packages/aero-platform/src/Http/Controllers/Admin/ProvisioningController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\RejectProvisioningRequest;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\ProvisioningAdminService;
use Illuminate\Routing\Controller;
use Inertia\Inertia;

class ProvisioningController extends Controller
{
    public function __construct(private ProvisioningAdminService $service) {}

    public function index()
    {
        return Inertia::render('Platform/Admin/Onboarding/Pending', [
            'pending' => $this->service->listPending(),
        ]);
    }

    public function approve(Tenant $tenant)
    {
        $this->service->approve($tenant);
        return back()->with('success', 'Provisioning approved.');
    }

    public function reject(RejectProvisioningRequest $request, Tenant $tenant)
    {
        $this->service->reject($tenant, $request->validated('reason'));
        return back()->with('success', 'Provisioning rejected.');
    }

    public function retry(Tenant $tenant)
    {
        $this->service->retry($tenant);
        return back()->with('success', 'Provisioning retried.');
    }
}
```

```php
// packages/aero-platform/src/Http/Controllers/Admin/TrialController.php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Requests\Admin\ExtendTrialRequest;
use Aero\Platform\Models\Tenant;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Aero\Contracts\AuditServiceInterface;

class TrialController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index()
    {
        return Inertia::render('Platform/Admin/Onboarding/Trials', [
            'trials' => Tenant::onTrial()->with('plan')->orderBy('trial_ends_at')->paginate(20),
        ]);
    }

    public function extend(ExtendTrialRequest $request, Tenant $tenant)
    {
        DB::transaction(function () use ($request, $tenant) {
            $tenant->lockForUpdate();
            $tenant->update([
                'trial_ends_at' => $tenant->trial_ends_at?->addDays((int) $request->validated('days')) ?? now()->addDays((int) $request->validated('days')),
            ]);
            $this->audit->log(
                event: 'tenant.trial.extended',
                action: 'extend',
                subject: $tenant,
                description: "Extended trial by {$request->validated('days')} days",
            );
        });
        return back()->with('success', 'Trial extended.');
    }

    public function convert(Tenant $tenant)
    {
        DB::transaction(function () use ($tenant) {
            $tenant->lockForUpdate();
            $tenant->update(['trial_ends_at' => null, 'status' => 'active']);
            $this->audit->log(
                event: 'tenant.trial.converted',
                action: 'convert',
                subject: $tenant,
                description: "Converted trial to paid for {$tenant->name}",
            );
        });
        return back()->with('success', 'Trial converted to paid.');
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/SuspendTenantRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SuspendTenantRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['reason' => 'required|string|max:500'];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/UpdateByocRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateByocRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'byoc_enabled' => 'required|boolean',
            'byoc_db_host' => 'nullable|string|max:255',
            'byoc_db_port' => 'nullable|string|max:10',
            'byoc_db_name' => 'nullable|string|max:255',
            'byoc_db_username' => 'nullable|string|max:255',
            'byoc_db_password' => 'nullable|string|max:255',
        ];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/BulkOperationRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkOperationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['bulk_suspend', 'bulk_activate', 'bulk_plan_change', 'bulk_email'])],
            'tenant_ids' => 'required|array|min:1',
            'tenant_ids.*' => 'string|exists:tenants,id',
            'payload' => 'nullable|array',
            'payload.reason' => 'nullable|string|max:500',
            'payload.plan_id' => 'nullable|exists:plans,id',
            'payload.subject' => 'nullable|string|max:255',
            'payload.message' => 'nullable|string',
        ];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/RejectProvisioningRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectProvisioningRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['reason' => 'required|string|max:500'];
    }
}
```

```php
// packages/aero-platform/src/Http/Requests/Admin/ExtendTrialRequest.php
namespace Aero\Platform\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ExtendTrialRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['days' => 'required|integer|min:1|max:90'];
    }
}
```

---

## 5. Routes

### Task 5 — Routes file

- [ ] Append to `packages/aero-platform/routes/admin.php` (already inside landlord guard + `as('platform.admin.')` group):

```php
use Aero\Platform\Http\Controllers\Admin\TenantListController;
use Aero\Platform\Http\Controllers\Admin\TenantByocController;
use Aero\Platform\Http\Controllers\Admin\BulkOperationController;
use Aero\Platform\Http\Controllers\Admin\ImpersonationController;
use Aero\Platform\Http\Controllers\Admin\ProvisioningController;
use Aero\Platform\Http\Controllers\Admin\TrialController;

Route::prefix('tenants')->name('tenants.')->group(function () {
    Route::get('/',                       [TenantListController::class, 'index'])->name('index')->middleware('hrmac:tenants.tenant-list.view');
    Route::get('{tenant}',                [TenantListController::class, 'show'])->name('show')->middleware('hrmac:tenants.tenant-list.view');
    Route::post('{tenant}/suspend',       [TenantListController::class, 'suspend'])->name('suspend')->middleware('hrmac:tenants.tenant-list.suspend');
    Route::post('{tenant}/activate',      [TenantListController::class, 'activate'])->name('activate')->middleware('hrmac:tenants.tenant-list.activate');
    Route::delete('{tenant}',             [TenantListController::class, 'purge'])->name('purge')->middleware('hrmac:tenants.tenant-list.delete');
    Route::get('{tenant}/byoc',           [TenantByocController::class, 'show'])->name('byoc.show')->middleware('hrmac:tenants.tenant-list.edit');
    Route::put('{tenant}/byoc',           [TenantByocController::class, 'update'])->name('byoc.update')->middleware('hrmac:tenants.tenant-list.edit');
    Route::post('{tenant}/impersonate',   [ImpersonationController::class, 'store'])->name('impersonate')->middleware('hrmac:tenants.tenant-list.impersonate');
    Route::delete('impersonate',          [ImpersonationController::class, 'destroy'])->name('impersonate.stop');
    Route::get('bulk',                    [BulkOperationController::class, 'index'])->name('bulk.index')->middleware('hrmac:tenant-operations.bulk-actions.bulk-suspend');
    Route::post('bulk',                   [BulkOperationController::class, 'store'])->name('bulk.store')->middleware('hrmac:tenant-operations.bulk-actions.bulk-suspend');
    Route::get('bulk/{operation}',        [BulkOperationController::class, 'show'])->name('bulk.show')->middleware('hrmac:tenant-operations.bulk-actions.bulk-suspend');
});

Route::prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('pending',                 [ProvisioningController::class, 'index'])->name('pending')->middleware('hrmac:platform-onboarding.pending-approvals.view');
    Route::post('{tenant}/approve',       [ProvisioningController::class, 'approve'])->name('approve')->middleware('hrmac:platform-onboarding.pending-approvals.approve');
    Route::post('{tenant}/reject',        [ProvisioningController::class, 'reject'])->name('reject')->middleware('hrmac:platform-onboarding.pending-approvals.reject');
    Route::post('{tenant}/retry',         [ProvisioningController::class, 'retry'])->name('retry')->middleware('hrmac:platform-onboarding.provisioning.retry');
    Route::get('trials',                  [TrialController::class, 'index'])->name('trials')->middleware('hrmac:platform-onboarding.trials.view');
    Route::post('trials/{tenant}/extend', [TrialController::class, 'extend'])->name('trials.extend')->middleware('hrmac:platform-onboarding.trials.extend');
    Route::post('trials/{tenant}/convert',[TrialController::class, 'convert'])->name('trials.convert')->middleware('hrmac:platform-onboarding.trials.convert');
});
```

---

## 6. React Pages

### Task 6 — Inertia pages in `packages/aero-ui/resources/js/Pages/Platform/Admin/`

Import-depth note (from `Pages/Platform/Admin/Feature/Page.jsx`): App = `'../../../App.jsx'`, hooks = `'../../../../hooks/useHRMAC.js'`, `@aero/ui` components imported by package name.

- [ ] `Tenants/Index.jsx` — table with plan/status/search filters, action menu (suspend/activate/impersonate)
- [ ] `Tenants/Show.jsx` — tabs: Profile · Health · BYOC · Operations
- [ ] `Tenants/Byoc.jsx` — encrypted credential form (host, port, db, user, password, enabled toggle)
- [ ] `Tenants/Bulk.jsx` — operation builder + history list (poll `show()` endpoint)
- [ ] `Onboarding/Pending.jsx` — queue with approve/reject buttons (reason modal)
- [ ] `Onboarding/Provisioning.jsx` — provisioning queue with retry on `failed` rows
- [ ] `Onboarding/Trials.jsx` — trial list with `extend(days)` + `convert` actions

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Tenants/Index.jsx
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';
import { Card, Table, Button, Badge, Input, Select, Modal } from '@aero/ui';

export default function TenantsIndex({ tenants, filters }) {
    const hr = useHRMAC();
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [suspendOpen, setSuspendOpen] = useState(null);
    const suspendForm = useForm({ reason: '' });

    const applyFilters = () => router.get(route('platform.admin.tenants.index'), { search, status }, { preserveState: true });

    return (
        <>
            <Head title="Tenants" />
            <Card>
                <div className="flex gap-3 mb-4">
                    <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search tenants…" />
                    <Select value={status} onChange={(e) => setStatus(e.target.value)}>
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="provisioning">Provisioning</option>
                        <option value="failed">Failed</option>
                    </Select>
                    <Button onClick={applyFilters}>Filter</Button>
                </div>

                <Table>
                    <Table.Head>
                        <Table.Row>
                            <Table.Cell>Name</Table.Cell>
                            <Table.Cell>Plan</Table.Cell>
                            <Table.Cell>Status</Table.Cell>
                            <Table.Cell>Trial ends</Table.Cell>
                            <Table.Cell>Actions</Table.Cell>
                        </Table.Row>
                    </Table.Head>
                    <Table.Body>
                        {tenants.data.map((t) => (
                            <Table.Row key={t.id}>
                                <Table.Cell><Link href={route('platform.admin.tenants.show', t.id)}>{t.name}</Link></Table.Cell>
                                <Table.Cell>{t.plan?.name ?? '—'}</Table.Cell>
                                <Table.Cell><Badge tone={t.status === 'active' ? 'success' : 'warning'}>{t.status}</Badge></Table.Cell>
                                <Table.Cell>{t.trial_ends_at ?? '—'}</Table.Cell>
                                <Table.Cell>
                                    {t.status === 'active' && hr.can('tenants.tenant-list.suspend') && (
                                        <Button size="sm" variant="ghost" onClick={() => setSuspendOpen(t)}>Suspend</Button>
                                    )}
                                    {t.status === 'suspended' && hr.can('tenants.tenant-list.activate') && (
                                        <Button size="sm" onClick={() => router.post(route('platform.admin.tenants.activate', t.id))}>Activate</Button>
                                    )}
                                </Table.Cell>
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Card>

            <Modal open={!!suspendOpen} onClose={() => setSuspendOpen(null)} title="Suspend tenant">
                <Input label="Reason" value={suspendForm.data.reason} onChange={(e) => suspendForm.setData('reason', e.target.value)} />
                <Button onClick={() => suspendForm.post(route('platform.admin.tenants.suspend', suspendOpen.id), { onSuccess: () => setSuspendOpen(null) })}>
                    Confirm suspension
                </Button>
            </Modal>
        </>
    );
}

TenantsIndex.layout = (page) => <App children={page} />;
```

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Tenants/Show.jsx
import { Head } from '@inertiajs/react';
import App from '../../../App.jsx';
import { useState } from 'react';
import { Card, Tabs, Badge } from '@aero/ui';

export default function TenantShow({ tenant }) {
    const [tab, setTab] = useState('profile');
    return (
        <>
            <Head title={tenant.name} />
            <Card title={tenant.name}>
                <Tabs value={tab} onChange={setTab}>
                    <Tabs.Tab value="profile" label="Profile" />
                    <Tabs.Tab value="health" label="Health" />
                    <Tabs.Tab value="byoc" label="BYOC" />
                    <Tabs.Tab value="ops" label="Operations" />
                </Tabs>

                {tab === 'profile' && (
                    <div className="mt-4 space-y-2">
                        <div>ID: {tenant.id}</div>
                        <div>Plan: {tenant.plan?.name ?? '—'}</div>
                        <div>Status: <Badge>{tenant.status}</Badge></div>
                        <div>Trial ends: {tenant.trial_ends_at ?? '—'}</div>
                    </div>
                )}
                {tab === 'health' && (
                    <div className="mt-4">
                        <p>CPU: {tenant.latest_health?.cpu_pct ?? '—'}%</p>
                        <p>DB size: {tenant.latest_health?.db_size_mb ?? '—'} MB</p>
                        <p>API calls today: {tenant.latest_health?.api_calls_today ?? 0}</p>
                    </div>
                )}
            </Card>
        </>
    );
}

TenantShow.layout = (page) => <App children={page} />;
```

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Tenants/Bulk.jsx
import { Head, useForm } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, Table, Select, Input, Button, Badge } from '@aero/ui';

export default function TenantsBulk({ operations }) {
    const form = useForm({ type: 'bulk_suspend', tenant_ids: [], payload: { reason: '' } });
    const submit = (e) => { e.preventDefault(); form.post(route('platform.admin.tenants.bulk.store')); };

    return (
        <>
            <Head title="Bulk Tenant Operations" />
            <Card title="New bulk operation">
                <form onSubmit={submit} className="space-y-3">
                    <Select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                        <option value="bulk_suspend">Bulk suspend</option>
                        <option value="bulk_activate">Bulk activate</option>
                        <option value="bulk_plan_change">Bulk plan change</option>
                        <option value="bulk_email">Bulk email</option>
                    </Select>
                    <Input label="Tenant IDs (comma-separated)" onChange={(e) =>
                        form.setData('tenant_ids', e.target.value.split(',').map((s) => s.trim()).filter(Boolean))} />
                    {form.data.type === 'bulk_suspend' && (
                        <Input label="Reason" onChange={(e) => form.setData('payload', { reason: e.target.value })} />
                    )}
                    <Button type="submit" disabled={form.processing}>Run</Button>
                </form>
            </Card>

            <Card title="History" className="mt-6">
                <Table>
                    <Table.Body>
                        {operations.data.map((op) => (
                            <Table.Row key={op.id}>
                                <Table.Cell>#{op.id} · {op.type}</Table.Cell>
                                <Table.Cell><Badge>{op.status}</Badge></Table.Cell>
                                <Table.Cell>{op.success_count}/{op.total_count} ok</Table.Cell>
                                <Table.Cell>{op.completed_at ?? '—'}</Table.Cell>
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Card>
        </>
    );
}

TenantsBulk.layout = (page) => <App children={page} />;
```

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Onboarding/Pending.jsx
import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import App from '../../../App.jsx';
import { Card, Table, Button, Modal, Input } from '@aero/ui';

export default function PendingApprovals({ pending }) {
    const [rejecting, setRejecting] = useState(null);
    const rejectForm = useForm({ reason: '' });
    return (
        <>
            <Head title="Pending approvals" />
            <Card>
                <Table>
                    <Table.Body>
                        {pending.data.map((t) => (
                            <Table.Row key={t.id}>
                                <Table.Cell>{t.name}</Table.Cell>
                                <Table.Cell>{t.status}</Table.Cell>
                                <Table.Cell>
                                    <Button size="sm" onClick={() => router.post(route('platform.admin.onboarding.approve', t.id))}>Approve</Button>
                                    <Button size="sm" variant="ghost" onClick={() => setRejecting(t)}>Reject</Button>
                                    {t.status === 'failed' && (
                                        <Button size="sm" variant="ghost" onClick={() => router.post(route('platform.admin.onboarding.retry', t.id))}>Retry</Button>
                                    )}
                                </Table.Cell>
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Card>

            <Modal open={!!rejecting} onClose={() => setRejecting(null)} title="Reject provisioning">
                <Input label="Reason" value={rejectForm.data.reason} onChange={(e) => rejectForm.setData('reason', e.target.value)} />
                <Button onClick={() => rejectForm.post(route('platform.admin.onboarding.reject', rejecting.id), { onSuccess: () => setRejecting(null) })}>
                    Confirm rejection
                </Button>
            </Modal>
        </>
    );
}

PendingApprovals.layout = (page) => <App children={page} />;
```

```jsx
// packages/aero-ui/resources/js/Pages/Platform/Admin/Onboarding/Trials.jsx
import { Head, router } from '@inertiajs/react';
import App from '../../../App.jsx';
import { Card, Table, Button } from '@aero/ui';

export default function Trials({ trials }) {
    return (
        <>
            <Head title="Trials" />
            <Card>
                <Table>
                    <Table.Body>
                        {trials.data.map((t) => (
                            <Table.Row key={t.id}>
                                <Table.Cell>{t.name}</Table.Cell>
                                <Table.Cell>{t.trial_ends_at}</Table.Cell>
                                <Table.Cell>
                                    <Button size="sm" onClick={() => router.post(route('platform.admin.onboarding.trials.extend', t.id), { days: 14 })}>
                                        Extend 14d
                                    </Button>
                                    <Button size="sm" onClick={() => router.post(route('platform.admin.onboarding.trials.convert', t.id))}>
                                        Convert
                                    </Button>
                                </Table.Cell>
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Card>
        </>
    );
}

Trials.layout = (page) => <App children={page} />;
```

---

## 7. Tests

### Task 7 — Feature tests in `packages/aero-platform/tests/Feature/`

- [ ] `TenantListTest.php`
- [ ] `TenantSuspensionTest.php`
- [ ] `TenantPurgeTest.php`
- [ ] `ProvisioningQueueTest.php`
- [ ] `ImpersonationAuditTest.php`
- [ ] `BulkOperationTest.php`
- [ ] `TrialManagementTest.php`

Base: `Orchestra\Testbench\TestCase`, providers `[AeroCoreServiceProvider::class, AeroPlatformServiceProvider::class]`, `Gate::before(fn () => true)`.

```php
// packages/aero-platform/tests/Feature/TenantSuspensionTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Core\Providers\AeroCoreServiceProvider;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Providers\AeroPlatformServiceProvider;
use Aero\Platform\Services\TenantAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class TenantSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [AeroCoreServiceProvider::class, AeroPlatformServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Gate::before(fn () => true);
    }

    public function test_admin_can_suspend_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        app(TenantAdminService::class)->suspend($tenant, 'Non-payment');

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'status' => 'suspended',
            'suspension_reason' => 'Non-payment',
        ]);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'tenant.suspended']);
    }

    public function test_cannot_purge_tenant_with_active_subscription(): void
    {
        $this->expectException(\DomainException::class);
        $tenant = Tenant::factory()->withActiveSubscription()->create();
        app(TenantAdminService::class)->purge($tenant);
    }
}
```

```php
// packages/aero-platform/tests/Feature/ProvisioningQueueTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\ProvisioningAdminService;

class ProvisioningQueueTest extends PlatformTestCase
{
    public function test_can_approve_pending_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'pending_approval']);
        app(ProvisioningAdminService::class)->approve($tenant);
        $this->assertSame('provisioning', $tenant->fresh()->status);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'tenant.provisioning.approved']);
    }

    public function test_retry_only_works_on_failed_tenants(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $this->expectException(\DomainException::class);
        app(ProvisioningAdminService::class)->retry($tenant);
    }
}
```

```php
// packages/aero-platform/tests/Feature/BulkOperationTest.php
namespace Aero\Platform\Tests\Feature;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\BulkOperationService;

class BulkOperationTest extends PlatformTestCase
{
    public function test_bulk_suspend_changes_statuses_and_records_results(): void
    {
        $a = Tenant::factory()->create(['status' => 'active']);
        $b = Tenant::factory()->create(['status' => 'active']);

        $op = app(BulkOperationService::class)->execute('bulk_suspend', [$a->id, $b->id], ['reason' => 'Maintenance']);

        $this->assertSame(2, $op->success_count);
        $this->assertSame('suspended', $a->fresh()->status);
        $this->assertSame('suspended', $b->fresh()->status);
    }
}
```

---

## 8. Tasks (execution order)

1. **DB & Models** — write 3 migrations, upgrade `Tenant`, create `TenantHealthSnapshot` + `BulkTenantOperation` + factories.
2. **Services** — `TenantAdminService`, `TenantHealthService`, `BulkOperationService`, `ImpersonationService`, `ProvisioningAdminService`.
3. **Controllers + FormRequests + Routes** — 7 controllers, 5 form requests, append route block to `admin.php`.
4. **React Pages** — 7 Inertia pages under `Pages/Platform/Admin/{Tenants,Onboarding}/`.
5. **Tests** — 7 feature tests under `tests/Feature/` (Orchestra Testbench + `Gate::before`).

---

## 9. Out of Scope

- Domain management UI (`tenant-domains` HRMAC codes are declared but full CRUD ships with networking workstream).
- Database migration/backup runner UI (`tenant-databases` declared; back-end tooling tracked separately).
- `tenant-clone` + `tenant-archive` HRMAC nodes declared but UX deferred to P-3.
- Real impersonation cross-subdomain SSO handshake — this plan stubs the URL builder; the JWT handshake belongs to the auth workstream.
- Provisioning worker/job implementation — services flag tenants but the actual queue worker is in Phase 0 follow-up `Plan-T-provisioning`.
- Bulk email transport (Mailgun/Postmark) — service records the job, real send dispatched by notification package.
