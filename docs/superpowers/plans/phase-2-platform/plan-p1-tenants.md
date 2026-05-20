# Plan P-1 — Tenant Lifecycle & Onboarding Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a production-grade Platform Admin surface for tenant lifecycle management — tenant list with filters, detail/show pages, BYOC encrypted credential management, suspend/activate/purge, bulk operations (suspend, plan change, email), impersonation with full audit, provisioning queue (approve/reject/retry), trial management (extend/convert), tenant export, freeze/unfreeze, archive/restore, and tenant clone.

**Architecture:** All domain code lives in `packages/aero-platform/src/{Models,Http,Services,Actions}/`. Models extend `Aero\Contracts\Models\CentralModel` (landlord/central DB). All admin routes mount inside the `landlord` guard group in `packages/aero-platform/routes/admin.php` and are HRMAC-gated via the codes declared in `packages/aero-platform/config/module.php`. All writes run in `DB::transaction()`; every business action calls `AuditServiceInterface::log()` writing to `platform_audit_logs`. Tenant BYOC credentials (`byoc_db_username`, `byoc_db_password`, `byoc_db_host`, `byoc_db_name`) use `EncryptedField` casts. React pages live under `packages/aero-ui/resources/js/Pages/Platform/Admin/{Tenants,Onboarding}/`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Orchestra Testbench.

---

## 1. HRMAC Hierarchy

Declared in `packages/aero-platform/config/module.php`. Routes reference codes as `hrmac:{submodule}.{component}.{action}` (no module prefix at the route layer).

**Submodule `tenants`**
- `tenants.tenant-list.view` / `.create` / `.edit` / `.delete` / `.suspend` / `.activate` / `.impersonate`
- `tenants.tenant-domains.view` / `.manage`
- `tenants.tenant-databases.view` / `.migrate` / `.backup`

**Submodule `platform-onboarding`**
- `platform-onboarding.onboarding-dashboard.view`
- `platform-onboarding.pending-approvals.view` / `.approve` / `.reject`
- `platform-onboarding.provisioning.view` / `.retry`
- `platform-onboarding.trials.view` / `.extend` / `.convert`

**Submodule `tenant-operations`**
- `tenant-operations.bulk-actions.bulk-suspend` / `.bulk-plan-change` / `.bulk-email`
- `tenant-operations.tenant-clone.view` / `.clone`
- `tenant-operations.tenant-export.view` / `.request` / `.download`
- `tenant-operations.tenant-freeze.freeze` / `.unfreeze`
- `tenant-operations.tenant-archive.archive` / `.restore`

### Task 0 — Update `packages/aero-platform/config/module.php`

- [ ] Add/confirm all submodules + components + actions above
- [ ] Run `php artisan hrmac:sync --module=platform`

```php
// excerpt — packages/aero-platform/config/module.php
'submodules' => [
    'tenants' => [
        'label' => 'Tenants',
        'components' => [
            'tenant-list' => ['actions' => ['view','create','edit','delete','suspend','activate','impersonate']],
            'tenant-domains' => ['actions' => ['view','manage']],
            'tenant-databases' => ['actions' => ['view','migrate','backup']],
        ],
    ],
    'platform-onboarding' => [
        'label' => 'Onboarding',
        'components' => [
            'onboarding-dashboard' => ['actions' => ['view']],
            'pending-approvals'    => ['actions' => ['view','approve','reject']],
            'provisioning'         => ['actions' => ['view','retry']],
            'trials'               => ['actions' => ['view','extend','convert']],
        ],
    ],
    'tenant-operations' => [
        'label' => 'Tenant Operations',
        'components' => [
            'bulk-actions'   => ['actions' => ['bulk-suspend','bulk-plan-change','bulk-email']],
            'tenant-clone'   => ['actions' => ['view','clone']],
            'tenant-export'  => ['actions' => ['view','request','download']],
            'tenant-freeze'  => ['actions' => ['freeze','unfreeze']],
            'tenant-archive' => ['actions' => ['archive','restore']],
        ],
    ],
],
```

---

## 2. Data Model

### Task 1 — Migrations

- [ ] `packages/aero-platform/database/migrations/2026_05_20_010001_upgrade_tenants_table.php`
- [ ] `packages/aero-platform/database/migrations/2026_05_20_010002_create_tenant_provisioning_logs_table.php`
- [ ] `packages/aero-platform/database/migrations/2026_05_20_010003_create_tenant_export_requests_table.php`
- [ ] `packages/aero-platform/database/migrations/2026_05_20_010004_create_bulk_operations_table.php`

```php
// 2026_05_20_010001_upgrade_tenants_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'name'))         $table->string('name')->after('id');
            if (!Schema::hasColumn('tenants', 'email'))        $table->string('email')->nullable()->after('name');
            if (!Schema::hasColumn('tenants', 'status'))       $table->string('status', 24)->default('active')->after('email'); // active|suspended|provisioning|failed|archived|frozen
            if (!Schema::hasColumn('tenants', 'plan_id'))      $table->unsignedBigInteger('plan_id')->nullable()->index();
            if (!Schema::hasColumn('tenants', 'byoc_enabled')) $table->boolean('byoc_enabled')->default(false);
            if (!Schema::hasColumn('tenants', 'byoc_db_host'))     $table->text('byoc_db_host')->nullable();
            if (!Schema::hasColumn('tenants', 'byoc_db_name'))     $table->text('byoc_db_name')->nullable();
            if (!Schema::hasColumn('tenants', 'byoc_db_username')) $table->text('byoc_db_username')->nullable();
            if (!Schema::hasColumn('tenants', 'byoc_db_password')) $table->text('byoc_db_password')->nullable();
            if (!Schema::hasColumn('tenants', 'encryption_key_id')) $table->string('encryption_key_id', 64)->nullable();
            if (!Schema::hasColumn('tenants', 'trial_ends_at'))    $table->timestamp('trial_ends_at')->nullable();
            if (!Schema::hasColumn('tenants', 'suspended_at'))     $table->timestamp('suspended_at')->nullable();
            if (!Schema::hasColumn('tenants', 'suspension_reason')) $table->string('suspension_reason')->nullable();
            if (!Schema::hasColumn('tenants', 'archived_at'))      $table->timestamp('archived_at')->nullable();
            if (!Schema::hasColumn('tenants', 'frozen_at'))        $table->timestamp('frozen_at')->nullable();
            $table->index(['status']);
            $table->index(['plan_id']);
        });
    }

    public function down(): void {}
};
```

```php
// 2026_05_20_010002_create_tenant_provisioning_logs_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_provisioning_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->string('status', 24); // pending|running|completed|failed
            $table->string('step')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('tenant_provisioning_logs'); }
};
```

```php
// 2026_05_20_010003_create_tenant_export_requests_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_export_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->index();
            $table->unsignedBigInteger('requested_by');
            $table->string('status', 24)->default('pending'); // pending|processing|ready|expired|failed
            $table->string('download_url', 2048)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('tenant_export_requests'); }
};
```

```php
// 2026_05_20_010004_create_bulk_operations_table.php
return new class extends Migration {
    public function up(): void
    {
        Schema::create('bulk_operations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32); // suspend|plan-change|email
            $table->json('payload');
            $table->string('status', 24)->default('queued'); // queued|running|completed|failed
            $table->unsignedBigInteger('created_by');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->timestamps();
            $table->index(['status']);
        });
    }
    public function down(): void { Schema::dropIfExists('bulk_operations'); }
};
```

### Task 2 — Models

- [ ] `packages/aero-platform/src/Models/Tenant.php` (extend existing or add casts)
- [ ] `packages/aero-platform/src/Models/TenantDomain.php`
- [ ] `packages/aero-platform/src/Models/TenantProvisioningLog.php`
- [ ] `packages/aero-platform/src/Models/TenantExportRequest.php`
- [ ] `packages/aero-platform/src/Models/BulkOperation.php`

```php
// packages/aero-platform/src/Models/Tenant.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;
use Aero\Contracts\Casts\EncryptedField;

class Tenant extends CentralModel
{
    protected $table = 'tenants';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','name','email','status','plan_id','byoc_enabled',
        'byoc_db_host','byoc_db_name','byoc_db_username','byoc_db_password',
        'encryption_key_id','trial_ends_at','suspended_at','suspension_reason',
        'archived_at','frozen_at',
    ];

    protected $casts = [
        'byoc_enabled'      => 'boolean',
        'byoc_db_host'      => EncryptedField::class,
        'byoc_db_name'      => EncryptedField::class,
        'byoc_db_username'  => EncryptedField::class,
        'byoc_db_password'  => EncryptedField::class,
        'trial_ends_at'     => 'datetime',
        'suspended_at'      => 'datetime',
        'archived_at'       => 'datetime',
        'frozen_at'         => 'datetime',
    ];

    protected $hidden = ['byoc_db_username','byoc_db_password'];

    public function domains() { return $this->hasMany(TenantDomain::class); }
    public function provisioningLogs() { return $this->hasMany(TenantProvisioningLog::class); }
    public function exportRequests() { return $this->hasMany(TenantExportRequest::class); }
}
```

```php
// packages/aero-platform/src/Models/TenantDomain.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class TenantDomain extends CentralModel
{
    protected $table = 'tenant_domains';
    protected $fillable = ['tenant_id','domain','is_primary','is_verified','ssl_status'];
    protected $casts = [
        'is_primary'  => 'boolean',
        'is_verified' => 'boolean',
    ];
}
```

```php
// packages/aero-platform/src/Models/TenantProvisioningLog.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class TenantProvisioningLog extends CentralModel
{
    protected $table = 'tenant_provisioning_logs';
    protected $fillable = ['tenant_id','status','step','message'];
}
```

```php
// packages/aero-platform/src/Models/TenantExportRequest.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class TenantExportRequest extends CentralModel
{
    protected $table = 'tenant_export_requests';
    protected $fillable = ['tenant_id','requested_by','status','download_url','expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
}
```

```php
// packages/aero-platform/src/Models/BulkOperation.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class BulkOperation extends CentralModel
{
    protected $table = 'bulk_operations';
    protected $fillable = ['type','payload','status','created_by','total','processed'];
    protected $casts = ['payload' => 'array'];
}
```

---

## 3. Services

### Task 3 — `TenantAdminService`

- [ ] `packages/aero-platform/src/Services/TenantAdminService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters)
    {
        $q = Tenant::query()->with('domains');

        if (!empty($filters['status'])) $q->where('status', $filters['status']);
        if (!empty($filters['plan_id'])) $q->where('plan_id', $filters['plan_id']);
        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(fn ($w) => $w->where('name','like',"%$s%")->orWhere('email','like',"%$s%")->orWhere('id','like',"%$s%"));
        }

        return $q->orderByDesc('created_at')->paginate(25)->withQueryString();
    }

    public function show(string $tenantId): Tenant
    {
        return Tenant::with(['domains','provisioningLogs' => fn ($q) => $q->latest()->limit(20)])
            ->findOrFail($tenantId);
    }

    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            $tenant = Tenant::create([
                'id'           => (string) \Illuminate\Support\Str::uuid(),
                'name'         => $data['name'],
                'email'        => $data['email'] ?? null,
                'status'       => 'provisioning',
                'plan_id'      => $data['plan_id'] ?? null,
                'byoc_enabled' => $data['byoc_enabled'] ?? false,
            ]);

            $this->audit->log(
                event: 'TENANT_CREATED',
                action: 'create',
                subject: $tenant,
                description: "Tenant {$tenant->name} created"
            );

            return $tenant;
        });
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data) {
            $tenant->update(array_filter([
                'name'    => $data['name']    ?? null,
                'email'   => $data['email']   ?? null,
                'plan_id' => $data['plan_id'] ?? null,
            ], fn ($v) => $v !== null));

            $this->audit->log(
                event: 'TENANT_UPDATED',
                action: 'update',
                subject: $tenant,
                description: "Tenant {$tenant->name} updated"
            );

            return $tenant->fresh();
        });
    }

    public function suspend(Tenant $tenant, string $reason): Tenant
    {
        if ($tenant->status === 'suspended') {
            throw new \DomainException('Tenant is already suspended');
        }

        return DB::transaction(function () use ($tenant, $reason) {
            $tenant->update([
                'status'            => 'suspended',
                'suspended_at'      => now(),
                'suspension_reason' => $reason,
            ]);

            $this->audit->log(
                event: 'TENANT_SUSPENDED',
                action: 'suspend',
                subject: $tenant,
                description: "Suspended: $reason"
            );

            return $tenant->fresh();
        });
    }

    public function activate(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update([
                'status'            => 'active',
                'suspended_at'      => null,
                'suspension_reason' => null,
                'frozen_at'         => null,
            ]);

            $this->audit->log(
                event: 'TENANT_ACTIVATED',
                action: 'activate',
                subject: $tenant,
                description: "Tenant {$tenant->name} activated"
            );

            return $tenant->fresh();
        });
    }

    public function freeze(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'frozen', 'frozen_at' => now()]);
            $this->audit->log(
                event: 'TENANT_FROZEN',
                action: 'freeze',
                subject: $tenant,
                description: "Tenant {$tenant->name} frozen"
            );
            return $tenant->fresh();
        });
    }

    public function unfreeze(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active', 'frozen_at' => null]);
            $this->audit->log(
                event: 'TENANT_UNFROZEN',
                action: 'unfreeze',
                subject: $tenant,
                description: "Tenant {$tenant->name} unfrozen"
            );
            return $tenant->fresh();
        });
    }

    public function archive(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'archived', 'archived_at' => now()]);
            $this->audit->log(
                event: 'TENANT_ARCHIVED',
                action: 'archive',
                subject: $tenant,
                description: "Tenant {$tenant->name} archived"
            );
            return $tenant->fresh();
        });
    }

    public function restore(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active', 'archived_at' => null]);
            $this->audit->log(
                event: 'TENANT_RESTORED',
                action: 'restore',
                subject: $tenant,
                description: "Tenant {$tenant->name} restored"
            );
            return $tenant->fresh();
        });
    }

    public function purge(Tenant $tenant, int $actorId): void
    {
        DB::transaction(function () use ($tenant, $actorId) {
            $this->audit->log(
                event: 'TENANT_PURGED',
                action: 'delete',
                subject: $tenant,
                description: "Tenant {$tenant->name} purged by user $actorId"
            );

            $tenant->delete();
        });
    }

    public function updateByocCredentials(Tenant $tenant, array $credentials): Tenant
    {
        return DB::transaction(function () use ($tenant, $credentials) {
            $tenant->update([
                'byoc_enabled'     => true,
                'byoc_db_host'     => $credentials['host'],
                'byoc_db_name'     => $credentials['database'],
                'byoc_db_username' => $credentials['username'],
                'byoc_db_password' => $credentials['password'],
            ]);

            $this->audit->log(
                event: 'TENANT_BYOC_UPDATED',
                action: 'update',
                subject: $tenant,
                description: "BYOC credentials updated for {$tenant->name}"
            );

            return $tenant->fresh();
        });
    }
}
```

### Task 4 — `TenantImpersonationService`

- [ ] `packages/aero-platform/src/Services/TenantImpersonationService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantImpersonationService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function start(Tenant $tenant, int $actorId): string
    {
        $token = (string) Str::uuid();

        DB::transaction(function () use ($tenant, $actorId, $token) {
            session(['impersonation' => [
                'tenant_id' => $tenant->id,
                'actor_id'  => $actorId,
                'token'     => $token,
                'started'   => now()->toISOString(),
            ]]);

            $this->audit->log(
                event: 'TENANT_IMPERSONATION_STARTED',
                action: 'impersonate',
                subject: $tenant,
                description: "Actor {$actorId} started impersonating {$tenant->name}"
            );
        });

        return $token;
    }

    public function end(string $token): void
    {
        $sess = session('impersonation');
        if (!$sess || $sess['token'] !== $token) return;

        $tenant = Tenant::find($sess['tenant_id']);

        if ($tenant) {
            $this->audit->log(
                event: 'TENANT_IMPERSONATION_ENDED',
                action: 'impersonate',
                subject: $tenant,
                description: "Actor {$sess['actor_id']} ended impersonation of {$tenant->name}"
            );
        }

        session()->forget('impersonation');
    }
}
```

### Task 5 — `TenantProvisioningService`

- [ ] `packages/aero-platform/src/Services/TenantProvisioningService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantProvisioningLog;
use Illuminate\Support\Facades\DB;

class TenantProvisioningService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function queue(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            TenantProvisioningLog::create([
                'tenant_id' => $tenant->id,
                'status'    => 'pending',
                'step'      => 'queued',
                'message'   => 'Provisioning queued',
            ]);

            $tenant->update(['status' => 'provisioning']);
        });
    }

    public function retry(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            TenantProvisioningLog::create([
                'tenant_id' => $tenant->id,
                'status'    => 'pending',
                'step'      => 'retry',
                'message'   => 'Provisioning retry requested',
            ]);

            $tenant->update(['status' => 'provisioning']);

            $this->audit->log(
                event: 'TENANT_PROVISIONING_RETRIED',
                action: 'retry',
                subject: $tenant,
                description: "Provisioning retry queued for {$tenant->name}"
            );
        });
    }

    public function approve(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active']);
            $this->audit->log(
                event: 'TENANT_APPROVED',
                action: 'approve',
                subject: $tenant,
                description: "Tenant {$tenant->name} approved"
            );
        });
    }

    public function reject(Tenant $tenant, string $reason): void
    {
        DB::transaction(function () use ($tenant, $reason) {
            $tenant->update(['status' => 'failed', 'suspension_reason' => $reason]);
            $this->audit->log(
                event: 'TENANT_REJECTED',
                action: 'reject',
                subject: $tenant,
                description: "Tenant {$tenant->name} rejected: $reason"
            );
        });
    }

    public function extendTrial(Tenant $tenant, int $days): Tenant
    {
        return DB::transaction(function () use ($tenant, $days) {
            $newEnds = ($tenant->trial_ends_at ?? now())->addDays($days);
            $tenant->update(['trial_ends_at' => $newEnds]);

            $this->audit->log(
                event: 'TENANT_TRIAL_EXTENDED',
                action: 'extend',
                subject: $tenant,
                description: "Trial extended by $days days for {$tenant->name}"
            );

            return $tenant->fresh();
        });
    }

    public function convertTrial(Tenant $tenant): Tenant
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->update(['status' => 'active', 'trial_ends_at' => null]);

            $this->audit->log(
                event: 'TENANT_TRIAL_CONVERTED',
                action: 'convert',
                subject: $tenant,
                description: "Trial converted to paid for {$tenant->name}"
            );

            return $tenant->fresh();
        });
    }
}
```

### Task 6 — `BulkTenantService`

- [ ] `packages/aero-platform/src/Services/BulkTenantService.php`
- [ ] `packages/aero-platform/src/Jobs/ExecuteBulkTenantAction.php`

```php
// packages/aero-platform/src/Services/BulkTenantService.php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Jobs\ExecuteBulkTenantAction;
use Aero\Platform\Models\BulkOperation;
use Illuminate\Support\Facades\DB;

class BulkTenantService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function execute(string $type, array $tenantIds, array $payload, int $actorId): BulkOperation
    {
        return DB::transaction(function () use ($type, $tenantIds, $payload, $actorId) {
            $op = BulkOperation::create([
                'type'       => $type,
                'payload'    => array_merge($payload, ['tenant_ids' => $tenantIds]),
                'status'     => 'queued',
                'created_by' => $actorId,
                'total'      => count($tenantIds),
                'processed'  => 0,
            ]);

            foreach ($tenantIds as $tid) {
                ExecuteBulkTenantAction::dispatch($op->id, $tid, $type, $payload);
            }

            $this->audit->log(
                event: 'TENANT_BULK_OPERATION_QUEUED',
                action: $type,
                subject: $op,
                description: "Bulk $type queued for ".count($tenantIds)." tenants"
            );

            return $op;
        });
    }
}
```

```php
// packages/aero-platform/src/Jobs/ExecuteBulkTenantAction.php
namespace Aero\Platform\Jobs;

use Aero\Platform\Models\BulkOperation;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantAdminService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteBulkTenantAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bulkOpId,
        public string $tenantId,
        public string $type,
        public array $payload,
    ) {}

    public function handle(TenantAdminService $svc): void
    {
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) return;

        match ($this->type) {
            'suspend'     => $svc->suspend($tenant, $this->payload['reason'] ?? 'Bulk operation'),
            'plan-change' => $tenant->update(['plan_id' => $this->payload['plan_id']]),
            'email'       => null, // email job dispatched elsewhere
            default       => null,
        };

        $op = BulkOperation::find($this->bulkOpId);
        if ($op) {
            $op->increment('processed');
            if ($op->processed >= $op->total) {
                $op->update(['status' => 'completed']);
            }
        }
    }
}
```

### Task 7 — `TenantExportService`

- [ ] `packages/aero-platform/src/Services/TenantExportService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantExportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class TenantExportService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function request(Tenant $tenant, int $actorId): TenantExportRequest
    {
        return DB::transaction(function () use ($tenant, $actorId) {
            $req = TenantExportRequest::create([
                'tenant_id'    => $tenant->id,
                'requested_by' => $actorId,
                'status'       => 'pending',
                'expires_at'   => now()->addDays(7),
            ]);

            $this->audit->log(
                event: 'TENANT_EXPORT_REQUESTED',
                action: 'request',
                subject: $req,
                description: "Export requested for {$tenant->name}"
            );

            return $req;
        });
    }

    public function getStatus(Tenant $tenant): ?TenantExportRequest
    {
        return TenantExportRequest::where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->first();
    }

    public function generateDownloadUrl(TenantExportRequest $request): string
    {
        return URL::temporarySignedRoute(
            'platform.admin.tenants.export.download',
            now()->addHours(2),
            ['request' => $request->id]
        );
    }
}
```

---

## 4. Controllers

All controllers live in `packages/aero-platform/src/Http/Controllers/Admin/`.

### Task 8 — `TenantController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/TenantController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Http\Requests\TenantStoreRequest;
use Aero\Platform\Http\Requests\TenantUpdateRequest;
use Aero\Platform\Models\Plan;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Services\TenantAdminService;
use Aero\Platform\Services\TenantImpersonationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function __construct(
        private TenantAdminService $svc,
        private TenantImpersonationService $impersonation,
    ) {}

    public function index(Request $request)
    {
        return Inertia::render('Platform/Admin/Tenants/Index', [
            'tenants' => $this->svc->list($request->only(['status','plan_id','search'])),
            'filters' => $request->only(['status','plan_id','search']),
            'plans'   => Plan::orderBy('name')->get(['id','name']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Platform/Admin/Tenants/Create', [
            'plans' => Plan::where('status','active')->orderBy('name')->get(['id','name','price_monthly']),
        ]);
    }

    public function store(TenantStoreRequest $request)
    {
        $tenant = $this->svc->create($request->validated());
        return redirect()->route('platform.admin.tenants.show', $tenant)
            ->with('success', 'Tenant created and queued for provisioning');
    }

    public function show(Tenant $tenant)
    {
        return Inertia::render('Platform/Admin/Tenants/Show', [
            'tenant' => $this->svc->show($tenant->id),
        ]);
    }

    public function update(TenantUpdateRequest $request, Tenant $tenant)
    {
        $this->svc->update($tenant, $request->validated());
        return back()->with('success', 'Tenant updated');
    }

    public function destroy(Tenant $tenant, Request $request)
    {
        $this->svc->purge($tenant, $request->user()->id);
        return redirect()->route('platform.admin.tenants.index')->with('success', 'Tenant purged');
    }

    public function suspend(Tenant $tenant, Request $request)
    {
        $request->validate(['reason' => 'required|string|max:255']);
        $this->svc->suspend($tenant, $request->string('reason'));
        return back()->with('success', 'Tenant suspended');
    }

    public function activate(Tenant $tenant)
    {
        $this->svc->activate($tenant);
        return back()->with('success', 'Tenant activated');
    }

    public function impersonate(Tenant $tenant, Request $request)
    {
        $token = $this->impersonation->start($tenant, $request->user()->id);
        return redirect()->away("https://{$tenant->domains->first()->domain}/?impersonate=$token");
    }
}
```

### Task 9 — `TenantDomainController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/TenantDomainController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TenantDomainController extends Controller
{
    public function index(Tenant $tenant)
    {
        return response()->json([
            'domains' => $tenant->domains()->orderBy('is_primary','desc')->get(),
        ]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'domain' => 'required|string|max:255|unique:tenant_domains,domain',
            'is_primary' => 'boolean',
        ]);

        DB::transaction(function () use ($tenant, $data) {
            if ($data['is_primary'] ?? false) {
                $tenant->domains()->update(['is_primary' => false]);
            }
            TenantDomain::create([
                'tenant_id'   => $tenant->id,
                'domain'      => $data['domain'],
                'is_primary'  => $data['is_primary'] ?? false,
                'is_verified' => false,
                'ssl_status'  => 'pending',
            ]);
        });

        return back()->with('success', 'Domain added');
    }

    public function destroy(Tenant $tenant, TenantDomain $domain)
    {
        abort_unless($domain->tenant_id === $tenant->id, 404);
        $domain->delete();
        return back()->with('success', 'Domain removed');
    }

    public function verify(Tenant $tenant, TenantDomain $domain)
    {
        abort_unless($domain->tenant_id === $tenant->id, 404);
        $domain->update(['is_verified' => true]);
        return back()->with('success', 'Domain verified');
    }
}
```

### Task 10 — `TenantDatabaseController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/TenantDatabaseController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Artisan;

class TenantDatabaseController extends Controller
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function index(Tenant $tenant)
    {
        return response()->json([
            'database' => [
                'name'  => $tenant->byoc_enabled ? $tenant->byoc_db_name : "tenant_{$tenant->id}",
                'byoc'  => (bool) $tenant->byoc_enabled,
                'host'  => $tenant->byoc_enabled ? $tenant->byoc_db_host : config('database.connections.tenant.host'),
            ],
        ]);
    }

    public function migrate(Tenant $tenant)
    {
        Artisan::call('tenants:migrate', ['--tenants' => [$tenant->id]]);
        $this->audit->log(
            event: 'TENANT_DB_MIGRATED', action: 'migrate', subject: $tenant,
            description: "Migrated DB for {$tenant->name}"
        );
        return back()->with('success', 'Migrations executed');
    }

    public function backup(Tenant $tenant)
    {
        Artisan::call('tenants:backup', ['tenant' => $tenant->id]);
        $this->audit->log(
            event: 'TENANT_DB_BACKED_UP', action: 'backup', subject: $tenant,
            description: "Backed up DB for {$tenant->name}"
        );
        return back()->with('success', 'Backup started');
    }
}
```

### Task 11 — `OnboardingController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/OnboardingController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantProvisioningLog;
use Aero\Platform\Services\TenantProvisioningService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function __construct(private TenantProvisioningService $svc) {}

    public function dashboard()
    {
        return Inertia::render('Platform/Admin/Onboarding/Dashboard', [
            'stats' => [
                'pending_approvals' => Tenant::where('status','pending_approval')->count(),
                'provisioning'      => Tenant::where('status','provisioning')->count(),
                'trials'            => Tenant::whereNotNull('trial_ends_at')->where('trial_ends_at','>',now())->count(),
                'today_signups'     => Tenant::whereDate('created_at', today())->count(),
            ],
        ]);
    }

    public function pending()
    {
        return Inertia::render('Platform/Admin/Onboarding/Pending', [
            'tenants' => Tenant::where('status','pending_approval')->paginate(25),
        ]);
    }

    public function approve(Tenant $tenant)
    {
        $this->svc->approve($tenant);
        return back()->with('success', 'Tenant approved');
    }

    public function reject(Request $request, Tenant $tenant)
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $this->svc->reject($tenant, $request->string('reason'));
        return back()->with('success', 'Tenant rejected');
    }

    public function provisioning()
    {
        return Inertia::render('Platform/Admin/Onboarding/Provisioning', [
            'logs' => TenantProvisioningLog::with('tenant')
                ->whereIn('status', ['pending','running','failed'])
                ->orderByDesc('updated_at')
                ->paginate(50),
        ]);
    }

    public function retryProvisioning(Tenant $tenant)
    {
        $this->svc->retry($tenant);
        return back()->with('success', 'Retry queued');
    }

    public function trials()
    {
        return Inertia::render('Platform/Admin/Onboarding/Trials', [
            'tenants' => Tenant::whereNotNull('trial_ends_at')
                ->orderBy('trial_ends_at')
                ->paginate(25),
        ]);
    }

    public function extendTrial(Request $request, Tenant $tenant)
    {
        $request->validate(['days' => 'required|integer|min:1|max:90']);
        $this->svc->extendTrial($tenant, $request->integer('days'));
        return back()->with('success', 'Trial extended');
    }

    public function convertTrial(Tenant $tenant)
    {
        $this->svc->convertTrial($tenant);
        return back()->with('success', 'Trial converted');
    }
}
```

### Task 12 — `BulkTenantController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/BulkTenantController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\BulkOperation;
use Aero\Platform\Services\BulkTenantService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BulkTenantController extends Controller
{
    public function __construct(private BulkTenantService $svc) {}

    public function execute(Request $request)
    {
        $data = $request->validate([
            'type'         => 'required|in:suspend,plan-change,email',
            'tenant_ids'   => 'required|array|min:1',
            'tenant_ids.*' => 'string|exists:tenants,id',
            'reason'       => 'required_if:type,suspend|string|max:255',
            'plan_id'      => 'required_if:type,plan-change|integer|exists:plans,id',
            'subject'      => 'required_if:type,email|string|max:255',
            'body'         => 'required_if:type,email|string',
        ]);

        $op = $this->svc->execute(
            $data['type'],
            $data['tenant_ids'],
            $data,
            $request->user()->id,
        );

        return back()->with('success', "Bulk operation queued ({$op->id})");
    }

    public function history()
    {
        return Inertia::render('Platform/Admin/Tenants/Bulk', [
            'operations' => BulkOperation::orderByDesc('created_at')->paginate(25),
        ]);
    }
}
```

### Task 13 — `TenantExportController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/TenantExportController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantExportRequest;
use Aero\Platform\Services\TenantExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TenantExportController extends Controller
{
    public function __construct(private TenantExportService $svc) {}

    public function request(Tenant $tenant, Request $request)
    {
        $req = $this->svc->request($tenant, $request->user()->id);
        return back()->with('success', "Export queued (#{$req->id})");
    }

    public function status(Tenant $tenant)
    {
        return response()->json($this->svc->getStatus($tenant));
    }

    public function download(TenantExportRequest $request)
    {
        abort_unless($request->status === 'ready' && $request->expires_at?->isFuture(), 410);
        return Storage::disk('exports')->download($request->download_url);
    }
}
```

### Task 14 — Form Requests

- [ ] `packages/aero-platform/src/Http/Requests/TenantStoreRequest.php`
- [ ] `packages/aero-platform/src/Http/Requests/TenantUpdateRequest.php`

```php
// TenantStoreRequest.php
namespace Aero\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|max:255',
            'plan_id'      => 'nullable|integer|exists:plans,id',
            'byoc_enabled' => 'boolean',
            'timezone'     => 'nullable|string|max:64',
        ];
    }
}
```

```php
// TenantUpdateRequest.php
namespace Aero\Platform\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|email|max:255',
            'plan_id' => 'sometimes|nullable|integer|exists:plans,id',
        ];
    }
}
```

---

## 5. Routes

### Task 15 — Register admin routes

- [ ] Append to `packages/aero-platform/routes/admin.php`

```php
use Aero\Platform\Http\Controllers\Admin\BulkTenantController;
use Aero\Platform\Http\Controllers\Admin\OnboardingController;
use Aero\Platform\Http\Controllers\Admin\TenantController;
use Aero\Platform\Http\Controllers\Admin\TenantDatabaseController;
use Aero\Platform\Http\Controllers\Admin\TenantDomainController;
use Aero\Platform\Http\Controllers\Admin\TenantExportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:landlord'])->prefix('platform/admin')->name('platform.admin.')->group(function () {

    // Tenants
    Route::prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/',         [TenantController::class, 'index'])->name('index')->middleware('hrmac:tenants.tenant-list.view');
        Route::get('/create',   [TenantController::class, 'create'])->name('create')->middleware('hrmac:tenants.tenant-list.create');
        Route::post('/',        [TenantController::class, 'store'])->name('store')->middleware('hrmac:tenants.tenant-list.create');
        Route::get('/{tenant}', [TenantController::class, 'show'])->name('show')->middleware('hrmac:tenants.tenant-list.view');
        Route::put('/{tenant}', [TenantController::class, 'update'])->name('update')->middleware('hrmac:tenants.tenant-list.edit');
        Route::delete('/{tenant}', [TenantController::class, 'destroy'])->name('destroy')->middleware('hrmac:tenants.tenant-list.delete');

        Route::post('/{tenant}/suspend',     [TenantController::class, 'suspend'])->name('suspend')->middleware('hrmac:tenants.tenant-list.suspend');
        Route::post('/{tenant}/activate',    [TenantController::class, 'activate'])->name('activate')->middleware('hrmac:tenants.tenant-list.activate');
        Route::post('/{tenant}/impersonate', [TenantController::class, 'impersonate'])->name('impersonate')->middleware('hrmac:tenants.tenant-list.impersonate');

        // Domains
        Route::get('/{tenant}/domains',          [TenantDomainController::class, 'index'])->name('domains.index')->middleware('hrmac:tenants.tenant-domains.view');
        Route::post('/{tenant}/domains',         [TenantDomainController::class, 'store'])->name('domains.store')->middleware('hrmac:tenants.tenant-domains.manage');
        Route::delete('/{tenant}/domains/{domain}', [TenantDomainController::class, 'destroy'])->name('domains.destroy')->middleware('hrmac:tenants.tenant-domains.manage');
        Route::post('/{tenant}/domains/{domain}/verify', [TenantDomainController::class, 'verify'])->name('domains.verify')->middleware('hrmac:tenants.tenant-domains.manage');

        // Databases
        Route::get('/{tenant}/database',         [TenantDatabaseController::class, 'index'])->name('database.index')->middleware('hrmac:tenants.tenant-databases.view');
        Route::post('/{tenant}/database/migrate', [TenantDatabaseController::class, 'migrate'])->name('database.migrate')->middleware('hrmac:tenants.tenant-databases.migrate');
        Route::post('/{tenant}/database/backup', [TenantDatabaseController::class, 'backup'])->name('database.backup')->middleware('hrmac:tenants.tenant-databases.backup');

        // Export
        Route::post('/{tenant}/export',          [TenantExportController::class, 'request'])->name('export.request')->middleware('hrmac:tenant-operations.tenant-export.request');
        Route::get('/{tenant}/export/status',    [TenantExportController::class, 'status'])->name('export.status')->middleware('hrmac:tenant-operations.tenant-export.view');
        Route::get('/exports/{request}/download', [TenantExportController::class, 'download'])->name('export.download')->middleware('hrmac:tenant-operations.tenant-export.download')->name('export.download');
    });

    // Bulk
    Route::prefix('tenants/bulk')->name('tenants.bulk.')->group(function () {
        Route::get('/',     [BulkTenantController::class, 'history'])->name('history')->middleware('hrmac:tenant-operations.bulk-actions.bulk-suspend');
        Route::post('/',    [BulkTenantController::class, 'execute'])->name('execute');
    });

    // Onboarding
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/dashboard',     [OnboardingController::class, 'dashboard'])->name('dashboard')->middleware('hrmac:platform-onboarding.onboarding-dashboard.view');
        Route::get('/pending',       [OnboardingController::class, 'pending'])->name('pending')->middleware('hrmac:platform-onboarding.pending-approvals.view');
        Route::post('/{tenant}/approve', [OnboardingController::class, 'approve'])->name('approve')->middleware('hrmac:platform-onboarding.pending-approvals.approve');
        Route::post('/{tenant}/reject',  [OnboardingController::class, 'reject'])->name('reject')->middleware('hrmac:platform-onboarding.pending-approvals.reject');

        Route::get('/provisioning',  [OnboardingController::class, 'provisioning'])->name('provisioning')->middleware('hrmac:platform-onboarding.provisioning.view');
        Route::post('/{tenant}/retry', [OnboardingController::class, 'retryProvisioning'])->name('retry')->middleware('hrmac:platform-onboarding.provisioning.retry');

        Route::get('/trials', [OnboardingController::class, 'trials'])->name('trials')->middleware('hrmac:platform-onboarding.trials.view');
        Route::post('/{tenant}/extend',  [OnboardingController::class, 'extendTrial'])->name('extend')->middleware('hrmac:platform-onboarding.trials.extend');
        Route::post('/{tenant}/convert', [OnboardingController::class, 'convertTrial'])->name('convert')->middleware('hrmac:platform-onboarding.trials.convert');
    });
});
```

---

## 6. React Pages

All pages live under `packages/aero-ui/resources/js/Pages/Platform/Admin/`. Depth 4 subdirs → `App` import `'../../../App.jsx'`, `useHRMAC` import `'../../../../hooks/useHRMAC.js'`. All imports from `@aero/ui` only.

### Task 16 — `Tenants/Index.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Tenants/Index.jsx`

```jsx
import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, Input, Select, SelectItem, Table, TableHeader,
  TableColumn, TableBody, TableRow, TableCell, Chip, Checkbox, Dropdown,
  DropdownTrigger, DropdownMenu, DropdownItem, Pagination,
} from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

const STATUS_COLORS = {
  active: 'success', suspended: 'warning', provisioning: 'primary',
  failed: 'danger', archived: 'default', frozen: 'secondary',
};

export default function TenantsIndex() {
  const { tenants, filters, plans } = usePage().props;
  const { hasAccess, canCreate } = useHRMAC('tenants.tenant-list');
  const [selected, setSelected] = useState([]);
  const [search, setSearch] = useState(filters.search ?? '');

  const applyFilter = (patch) => router.get(route('platform.admin.tenants.index'),
    { ...filters, ...patch }, { preserveState: true, replace: true });

  const toggle = (id) => setSelected((p) => p.includes(id) ? p.filter(x => x !== id) : [...p, id]);

  return (
    <>
      <Head title="Tenants" />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-semibold">Tenants</h1>
          {canCreate && (
            <Button as={Link} href={route('platform.admin.tenants.create')} color="primary">New Tenant</Button>
          )}
        </div>

        <Card>
          <CardBody className="flex flex-row gap-3 items-end">
            <Input label="Search" value={search} onValueChange={setSearch}
              onBlur={() => applyFilter({ search })} className="max-w-xs" />
            <Select label="Status" selectedKeys={filters.status ? [filters.status] : []}
              onSelectionChange={(k) => applyFilter({ status: [...k][0] ?? null })} className="max-w-xs">
              {['active','suspended','provisioning','failed','archived','frozen'].map((s) => (
                <SelectItem key={s}>{s}</SelectItem>
              ))}
            </Select>
            <Select label="Plan" selectedKeys={filters.plan_id ? [String(filters.plan_id)] : []}
              onSelectionChange={(k) => applyFilter({ plan_id: [...k][0] ?? null })} className="max-w-xs">
              {plans.map((p) => <SelectItem key={p.id}>{p.name}</SelectItem>)}
            </Select>

            {selected.length > 0 && hasAccess('tenant-operations.bulk-actions.bulk-suspend') && (
              <Dropdown>
                <DropdownTrigger>
                  <Button color="warning">Bulk ({selected.length})</Button>
                </DropdownTrigger>
                <DropdownMenu>
                  <DropdownItem key="suspend">Suspend Selected</DropdownItem>
                  <DropdownItem key="plan-change">Change Plan</DropdownItem>
                  <DropdownItem key="email">Send Email</DropdownItem>
                </DropdownMenu>
              </Dropdown>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <Table aria-label="Tenants" removeWrapper>
              <TableHeader>
                <TableColumn><Checkbox isSelected={selected.length === tenants.data.length}
                  onValueChange={(v) => setSelected(v ? tenants.data.map((t) => t.id) : [])} /></TableColumn>
                <TableColumn>Name</TableColumn>
                <TableColumn>Domain</TableColumn>
                <TableColumn>Plan</TableColumn>
                <TableColumn>Status</TableColumn>
                <TableColumn>Created</TableColumn>
              </TableHeader>
              <TableBody emptyContent="No tenants" items={tenants.data}>
                {(t) => (
                  <TableRow key={t.id}>
                    <TableCell><Checkbox isSelected={selected.includes(t.id)} onValueChange={() => toggle(t.id)} /></TableCell>
                    <TableCell><Link href={route('platform.admin.tenants.show', t.id)} className="text-primary">{t.name}</Link></TableCell>
                    <TableCell>{t.domains?.[0]?.domain ?? '—'}</TableCell>
                    <TableCell>{t.plan_id ?? '—'}</TableCell>
                    <TableCell><Chip color={STATUS_COLORS[t.status] ?? 'default'} size="sm">{t.status}</Chip></TableCell>
                    <TableCell>{new Date(t.created_at).toLocaleDateString()}</TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>

            {tenants.last_page > 1 && (
              <div className="mt-4 flex justify-center">
                <Pagination total={tenants.last_page} page={tenants.current_page}
                  onChange={(p) => applyFilter({ page: p })} />
              </div>
            )}
          </CardBody>
        </Card>
      </div>
    </>
  );
}

TenantsIndex.layout = (page) => <App>{page}</App>;
```

### Task 17 — `Tenants/Show.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Tenants/Show.jsx`

```jsx
import { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
  Button, Card, CardHeader, CardBody, Chip, Input, Switch, Modal,
  ModalContent, ModalHeader, ModalBody, ModalFooter, useDisclosure, Tabs, Tab,
} from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function TenantShow() {
  const { tenant } = usePage().props;
  const { hasAccess } = useHRMAC();
  const suspendModal = useDisclosure();
  const purgeModal = useDisclosure();
  const [suspendReason, setSuspendReason] = useState('');

  const byocForm = useForm({
    byoc_enabled: tenant.byoc_enabled,
    host: '', database: '', username: '', password: '',
  });

  const submitByoc = (e) => {
    e.preventDefault();
    byocForm.put(route('platform.admin.tenants.update', tenant.id));
  };

  const doSuspend = () => {
    router.post(route('platform.admin.tenants.suspend', tenant.id),
      { reason: suspendReason }, { onSuccess: () => suspendModal.onClose() });
  };

  return (
    <>
      <Head title={tenant.name} />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-semibold">{tenant.name}</h1>
            <Chip color={tenant.status === 'active' ? 'success' : 'warning'} size="sm">{tenant.status}</Chip>
          </div>
          <div className="flex gap-2">
            {hasAccess('tenants.tenant-list.impersonate') && (
              <Button color="secondary"
                onPress={() => router.post(route('platform.admin.tenants.impersonate', tenant.id))}>
                Impersonate
              </Button>
            )}
            {tenant.status !== 'suspended' && hasAccess('tenants.tenant-list.suspend') && (
              <Button color="warning" onPress={suspendModal.onOpen}>Suspend</Button>
            )}
            {tenant.status === 'suspended' && hasAccess('tenants.tenant-list.activate') && (
              <Button color="success"
                onPress={() => router.post(route('platform.admin.tenants.activate', tenant.id))}>
                Activate
              </Button>
            )}
            {hasAccess('tenants.tenant-list.delete') && (
              <Button color="danger" onPress={purgeModal.onOpen}>Purge</Button>
            )}
          </div>
        </div>

        <Tabs>
          <Tab key="info" title="Info">
            <Card><CardBody>
              <dl className="grid grid-cols-2 gap-4">
                <div><dt className="text-sm text-default-500">Email</dt><dd>{tenant.email ?? '—'}</dd></div>
                <div><dt className="text-sm text-default-500">Plan</dt><dd>{tenant.plan_id ?? '—'}</dd></div>
                <div><dt className="text-sm text-default-500">Created</dt><dd>{new Date(tenant.created_at).toLocaleString()}</dd></div>
                <div><dt className="text-sm text-default-500">Trial ends</dt><dd>{tenant.trial_ends_at ? new Date(tenant.trial_ends_at).toLocaleDateString() : '—'}</dd></div>
              </dl>
            </CardBody></Card>
          </Tab>

          <Tab key="byoc" title="BYOC Database">
            <Card>
              <CardHeader>Bring Your Own Cloud — credentials are encrypted at rest</CardHeader>
              <CardBody>
                <form onSubmit={submitByoc} className="space-y-3 max-w-md">
                  <Switch isSelected={byocForm.data.byoc_enabled}
                    onValueChange={(v) => byocForm.setData('byoc_enabled', v)}>Enable BYOC</Switch>
                  {byocForm.data.byoc_enabled && (
                    <>
                      <Input label="Host" value={byocForm.data.host} onValueChange={(v) => byocForm.setData('host', v)} />
                      <Input label="Database" value={byocForm.data.database} onValueChange={(v) => byocForm.setData('database', v)} />
                      <Input label="Username" value={byocForm.data.username} onValueChange={(v) => byocForm.setData('username', v)} />
                      <Input label="Password" type="password" value={byocForm.data.password} onValueChange={(v) => byocForm.setData('password', v)} />
                    </>
                  )}
                  <Button type="submit" color="primary" isLoading={byocForm.processing}>Save Credentials</Button>
                </form>
              </CardBody>
            </Card>
          </Tab>

          <Tab key="domains" title="Domains">
            <Card><CardBody>
              <ul className="space-y-2">
                {(tenant.domains ?? []).map((d) => (
                  <li key={d.id} className="flex justify-between">
                    <span>{d.domain}</span>
                    <Chip size="sm" color={d.is_verified ? 'success' : 'default'}>{d.is_verified ? 'verified' : 'pending'}</Chip>
                  </li>
                ))}
              </ul>
            </CardBody></Card>
          </Tab>

          <Tab key="provisioning" title="Provisioning Logs">
            <Card><CardBody>
              <ul className="space-y-1 text-sm font-mono">
                {(tenant.provisioning_logs ?? []).map((l) => (
                  <li key={l.id}>[{l.status}] {l.step}: {l.message}</li>
                ))}
              </ul>
            </CardBody></Card>
          </Tab>
        </Tabs>
      </div>

      <Modal isOpen={suspendModal.isOpen} onClose={suspendModal.onClose}>
        <ModalContent>
          <ModalHeader>Suspend Tenant</ModalHeader>
          <ModalBody>
            <Input label="Reason" value={suspendReason} onValueChange={setSuspendReason} />
          </ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={suspendModal.onClose}>Cancel</Button>
            <Button color="warning" onPress={doSuspend} isDisabled={!suspendReason}>Suspend</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      <Modal isOpen={purgeModal.isOpen} onClose={purgeModal.onClose}>
        <ModalContent>
          <ModalHeader>Purge Tenant?</ModalHeader>
          <ModalBody>This permanently deletes the tenant and all data. Cannot be undone.</ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={purgeModal.onClose}>Cancel</Button>
            <Button color="danger"
              onPress={() => router.delete(route('platform.admin.tenants.destroy', tenant.id))}>
              Permanently Delete
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}

TenantShow.layout = (page) => <App>{page}</App>;
```

### Task 18 — `Tenants/Create.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Tenants/Create.jsx`

```jsx
import { Head, useForm, usePage } from '@inertiajs/react';
import { Button, Card, CardBody, Input, Select, SelectItem, Switch } from '@aero/ui';
import App from '../../../App.jsx';

export default function TenantCreate() {
  const { plans } = usePage().props;
  const form = useForm({
    name: '', email: '', plan_id: null, timezone: 'UTC', byoc_enabled: false,
  });

  const submit = (e) => {
    e.preventDefault();
    form.post(route('platform.admin.tenants.store'));
  };

  return (
    <>
      <Head title="New Tenant" />
      <Card className="max-w-xl">
        <CardBody>
          <form onSubmit={submit} className="space-y-3">
            <Input label="Name" value={form.data.name} onValueChange={(v) => form.setData('name', v)}
              isInvalid={!!form.errors.name} errorMessage={form.errors.name} />
            <Input label="Email" type="email" value={form.data.email} onValueChange={(v) => form.setData('email', v)}
              isInvalid={!!form.errors.email} errorMessage={form.errors.email} />
            <Select label="Plan" selectedKeys={form.data.plan_id ? [String(form.data.plan_id)] : []}
              onSelectionChange={(k) => form.setData('plan_id', [...k][0])}>
              {plans.map((p) => <SelectItem key={p.id}>{p.name}</SelectItem>)}
            </Select>
            <Input label="Timezone" value={form.data.timezone} onValueChange={(v) => form.setData('timezone', v)} />
            <Switch isSelected={form.data.byoc_enabled} onValueChange={(v) => form.setData('byoc_enabled', v)}>
              Enable BYOC Database
            </Switch>
            <Button type="submit" color="primary" isLoading={form.processing}>Create Tenant</Button>
          </form>
        </CardBody>
      </Card>
    </>
  );
}

TenantCreate.layout = (page) => <App>{page}</App>;
```

### Task 19 — `Onboarding/Dashboard.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Onboarding/Dashboard.jsx`

```jsx
import { Head, usePage } from '@inertiajs/react';
import { Card, CardBody } from '@aero/ui';
import App from '../../../App.jsx';

export default function OnboardingDashboard() {
  const { stats } = usePage().props;
  const cards = [
    { label: 'Pending Approvals', value: stats.pending_approvals },
    { label: 'Provisioning Running', value: stats.provisioning },
    { label: 'Active Trials', value: stats.trials },
    { label: "Today's Signups", value: stats.today_signups },
  ];

  return (
    <>
      <Head title="Onboarding Dashboard" />
      <h1 className="text-2xl font-semibold mb-4">Onboarding</h1>
      <div className="grid grid-cols-4 gap-4">
        {cards.map((c) => (
          <Card key={c.label}>
            <CardBody>
              <div className="text-sm text-default-500">{c.label}</div>
              <div className="text-3xl font-semibold mt-1">{c.value}</div>
            </CardBody>
          </Card>
        ))}
      </div>
    </>
  );
}

OnboardingDashboard.layout = (page) => <App>{page}</App>;
```

### Task 20 — `Onboarding/Pending.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Onboarding/Pending.jsx`

```jsx
import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, Input, Modal, ModalContent, ModalHeader,
  ModalBody, ModalFooter, Table, TableHeader, TableColumn, TableBody,
  TableRow, TableCell, useDisclosure,
} from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function OnboardingPending() {
  const { tenants } = usePage().props;
  const { hasAccess } = useHRMAC('platform-onboarding.pending-approvals');
  const rejectModal = useDisclosure();
  const [target, setTarget] = useState(null);
  const [reason, setReason] = useState('');

  const approve = (t) => router.post(route('platform.admin.onboarding.approve', t.id));
  const openReject = (t) => { setTarget(t); setReason(''); rejectModal.onOpen(); };
  const doReject = () => router.post(route('platform.admin.onboarding.reject', target.id),
    { reason }, { onSuccess: () => rejectModal.onClose() });

  return (
    <>
      <Head title="Pending Approvals" />
      <Card>
        <CardBody>
          <Table aria-label="Pending tenants" removeWrapper>
            <TableHeader>
              <TableColumn>Name</TableColumn><TableColumn>Email</TableColumn>
              <TableColumn>Created</TableColumn><TableColumn>Actions</TableColumn>
            </TableHeader>
            <TableBody emptyContent="No pending tenants" items={tenants.data}>
              {(t) => (
                <TableRow key={t.id}>
                  <TableCell>{t.name}</TableCell>
                  <TableCell>{t.email}</TableCell>
                  <TableCell>{new Date(t.created_at).toLocaleString()}</TableCell>
                  <TableCell className="flex gap-2">
                    {hasAccess('approve') && <Button size="sm" color="success" onPress={() => approve(t)}>Approve</Button>}
                    {hasAccess('reject') && <Button size="sm" color="danger" onPress={() => openReject(t)}>Reject</Button>}
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </CardBody>
      </Card>

      <Modal isOpen={rejectModal.isOpen} onClose={rejectModal.onClose}>
        <ModalContent>
          <ModalHeader>Reject {target?.name}</ModalHeader>
          <ModalBody>
            <Input label="Reason" value={reason} onValueChange={setReason} />
          </ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={rejectModal.onClose}>Cancel</Button>
            <Button color="danger" onPress={doReject} isDisabled={!reason}>Reject</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}

OnboardingPending.layout = (page) => <App>{page}</App>;
```

### Task 21 — `Onboarding/Provisioning.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Onboarding/Provisioning.jsx`

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import { Button, Card, CardBody, Chip, Table, TableHeader, TableColumn, TableBody, TableRow, TableCell } from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

const STATUS_COLOR = { pending: 'default', running: 'primary', completed: 'success', failed: 'danger' };

export default function Provisioning() {
  const { logs } = usePage().props;
  const { hasAccess } = useHRMAC('platform-onboarding.provisioning');

  return (
    <>
      <Head title="Provisioning Queue" />
      <Card>
        <CardBody>
          <Table aria-label="Provisioning" removeWrapper>
            <TableHeader>
              <TableColumn>Tenant</TableColumn><TableColumn>Step</TableColumn>
              <TableColumn>Status</TableColumn><TableColumn>Message</TableColumn>
              <TableColumn>Updated</TableColumn><TableColumn></TableColumn>
            </TableHeader>
            <TableBody emptyContent="No active provisioning" items={logs.data}>
              {(l) => (
                <TableRow key={l.id}>
                  <TableCell>{l.tenant?.name ?? l.tenant_id}</TableCell>
                  <TableCell>{l.step}</TableCell>
                  <TableCell><Chip color={STATUS_COLOR[l.status]} size="sm">{l.status}</Chip></TableCell>
                  <TableCell className="font-mono text-xs">{l.message}</TableCell>
                  <TableCell>{new Date(l.updated_at).toLocaleString()}</TableCell>
                  <TableCell>
                    {l.status === 'failed' && hasAccess('retry') && (
                      <Button size="sm" color="warning"
                        onPress={() => router.post(route('platform.admin.onboarding.retry', l.tenant_id))}>
                        Retry
                      </Button>
                    )}
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </CardBody>
      </Card>
    </>
  );
}

Provisioning.layout = (page) => <App>{page}</App>;
```

### Task 22 — `Onboarding/Trials.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Onboarding/Trials.jsx`

```jsx
import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, Chip, Input, Modal, ModalContent, ModalHeader,
  ModalBody, ModalFooter, Table, TableHeader, TableColumn, TableBody,
  TableRow, TableCell, useDisclosure,
} from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function Trials() {
  const { tenants } = usePage().props;
  const { hasAccess } = useHRMAC('platform-onboarding.trials');
  const extendModal = useDisclosure();
  const [target, setTarget] = useState(null);
  const [days, setDays] = useState(14);

  const daysLeft = (iso) => {
    if (!iso) return null;
    const diff = Math.ceil((new Date(iso) - new Date()) / 86400000);
    return diff;
  };

  const openExtend = (t) => { setTarget(t); setDays(14); extendModal.onOpen(); };
  const doExtend = () => router.post(route('platform.admin.onboarding.extend', target.id),
    { days }, { onSuccess: () => extendModal.onClose() });
  const doConvert = (t) => router.post(route('platform.admin.onboarding.convert', t.id));

  return (
    <>
      <Head title="Trials" />
      <Card>
        <CardBody>
          <Table aria-label="Trials" removeWrapper>
            <TableHeader>
              <TableColumn>Tenant</TableColumn><TableColumn>Trial Ends</TableColumn>
              <TableColumn>Days Left</TableColumn><TableColumn>Actions</TableColumn>
            </TableHeader>
            <TableBody emptyContent="No trials" items={tenants.data}>
              {(t) => {
                const d = daysLeft(t.trial_ends_at);
                return (
                  <TableRow key={t.id}>
                    <TableCell>{t.name}</TableCell>
                    <TableCell>{t.trial_ends_at ? new Date(t.trial_ends_at).toLocaleDateString() : '—'}</TableCell>
                    <TableCell><Chip size="sm" color={d <= 3 ? 'danger' : d <= 7 ? 'warning' : 'success'}>{d} days</Chip></TableCell>
                    <TableCell className="flex gap-2">
                      {hasAccess('extend') && <Button size="sm" onPress={() => openExtend(t)}>Extend</Button>}
                      {hasAccess('convert') && <Button size="sm" color="success" onPress={() => doConvert(t)}>Convert</Button>}
                    </TableCell>
                  </TableRow>
                );
              }}
            </TableBody>
          </Table>
        </CardBody>
      </Card>

      <Modal isOpen={extendModal.isOpen} onClose={extendModal.onClose}>
        <ModalContent>
          <ModalHeader>Extend trial for {target?.name}</ModalHeader>
          <ModalBody>
            <Input label="Days" type="number" min={1} max={90} value={String(days)}
              onValueChange={(v) => setDays(Number(v))} />
          </ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={extendModal.onClose}>Cancel</Button>
            <Button color="primary" onPress={doExtend}>Extend</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}

Trials.layout = (page) => <App>{page}</App>;
```

### Task 23 — `Tenants/Bulk.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Tenants/Bulk.jsx`

```jsx
import { useState } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, CardHeader, Chip, Input, Select, SelectItem,
  Table, TableHeader, TableColumn, TableBody, TableRow, TableCell, Textarea,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function BulkTenants() {
  const { operations } = usePage().props;
  const form = useForm({
    type: 'suspend', tenant_ids: [], reason: '', plan_id: null, subject: '', body: '',
  });
  const [csvIds, setCsvIds] = useState('');

  const submit = (e) => {
    e.preventDefault();
    form.setData('tenant_ids', csvIds.split(/[\s,]+/).filter(Boolean));
    form.post(route('platform.admin.tenants.bulk.execute'));
  };

  return (
    <>
      <Head title="Bulk Operations" />
      <div className="space-y-4">
        <Card>
          <CardHeader>New Bulk Operation</CardHeader>
          <CardBody>
            <form onSubmit={submit} className="space-y-3 max-w-2xl">
              <Select label="Type" selectedKeys={[form.data.type]}
                onSelectionChange={(k) => form.setData('type', [...k][0])}>
                <SelectItem key="suspend">Suspend</SelectItem>
                <SelectItem key="plan-change">Change Plan</SelectItem>
                <SelectItem key="email">Send Email</SelectItem>
              </Select>
              <Textarea label="Tenant IDs (comma or newline separated)"
                value={csvIds} onValueChange={setCsvIds} />
              {form.data.type === 'suspend' && (
                <Input label="Reason" value={form.data.reason} onValueChange={(v) => form.setData('reason', v)} />
              )}
              {form.data.type === 'plan-change' && (
                <Input label="New Plan ID" type="number" value={form.data.plan_id ?? ''}
                  onValueChange={(v) => form.setData('plan_id', Number(v))} />
              )}
              {form.data.type === 'email' && (
                <>
                  <Input label="Subject" value={form.data.subject} onValueChange={(v) => form.setData('subject', v)} />
                  <Textarea label="Body" value={form.data.body} onValueChange={(v) => form.setData('body', v)} />
                </>
              )}
              <Button type="submit" color="primary" isLoading={form.processing}>Queue Bulk Operation</Button>
            </form>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>History</CardHeader>
          <CardBody>
            <Table aria-label="Bulk operations" removeWrapper>
              <TableHeader>
                <TableColumn>ID</TableColumn><TableColumn>Type</TableColumn>
                <TableColumn>Status</TableColumn><TableColumn>Progress</TableColumn>
                <TableColumn>Created</TableColumn>
              </TableHeader>
              <TableBody emptyContent="No operations" items={operations.data}>
                {(op) => (
                  <TableRow key={op.id}>
                    <TableCell>{op.id}</TableCell>
                    <TableCell>{op.type}</TableCell>
                    <TableCell><Chip size="sm" color={op.status === 'completed' ? 'success' : 'primary'}>{op.status}</Chip></TableCell>
                    <TableCell>{op.processed}/{op.total}</TableCell>
                    <TableCell>{new Date(op.created_at).toLocaleString()}</TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </CardBody>
        </Card>
      </div>
    </>
  );
}

BulkTenants.layout = (page) => <App>{page}</App>;
```

---

## 7. Tests

All tests live under `packages/aero-platform/tests/Feature/Admin/`. Use `Gate::before(fn () => true)` to bypass HRMAC in tests. Boot service providers `AeroCoreServiceProvider` and `AeroPlatformServiceProvider`.

### Task 24 — `TenantControllerTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/TenantControllerTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Platform\AeroPlatformServiceProvider;
use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class TenantControllerTest extends TestCase
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
        $this->actingAs($this->makeLandlordUser(), 'landlord');
    }

    private function makeLandlordUser() { /* factory or stub user */ }

    public function test_lists_tenants_with_status_filter(): void
    {
        Tenant::factory()->count(3)->create(['status' => 'active']);
        Tenant::factory()->create(['status' => 'suspended']);

        $r = $this->get(route('platform.admin.tenants.index', ['status' => 'suspended']));
        $r->assertOk();
        $r->assertInertia(fn ($p) => $p->where('tenants.total', 1));
    }

    public function test_suspends_active_tenant_with_audit(): void
    {
        $t = Tenant::factory()->create(['status' => 'active']);
        $this->post(route('platform.admin.tenants.suspend', $t), ['reason' => 'Non-payment'])
             ->assertRedirect();

        $this->assertDatabaseHas('tenants', ['id' => $t->id, 'status' => 'suspended']);
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'TENANT_SUSPENDED']);
    }

    public function test_cannot_suspend_already_suspended(): void
    {
        $t = Tenant::factory()->create(['status' => 'suspended']);
        $this->post(route('platform.admin.tenants.suspend', $t), ['reason' => 'x'])
             ->assertStatus(500); // DomainException
    }

    public function test_byoc_credentials_encrypted_at_rest(): void
    {
        $t = Tenant::factory()->create();
        app(\Aero\Platform\Services\TenantAdminService::class)
            ->updateByocCredentials($t, [
                'host' => 'db.example.com', 'database' => 'mydb',
                'username' => 'plainuser', 'password' => 'plainpass',
            ]);

        $this->assertDatabaseMissing('tenants', ['byoc_db_username' => 'plainuser']);
        $this->assertDatabaseMissing('tenants', ['byoc_db_password' => 'plainpass']);
        $this->assertSame('plainuser', $t->fresh()->byoc_db_username);
    }

    public function test_impersonation_logs_audit(): void
    {
        $t = Tenant::factory()->create();
        $this->post(route('platform.admin.tenants.impersonate', $t));
        $this->assertDatabaseHas('platform_audit_logs', ['event' => 'TENANT_IMPERSONATION_STARTED']);
    }
}
```

### Task 25 — `BulkTenantTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/BulkTenantTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Jobs\ExecuteBulkTenantAction;
use Aero\Platform\Models\Tenant;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class BulkTenantTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
            \Aero\Platform\AeroPlatformServiceProvider::class,
        ];
    }

    public function test_bulk_suspend_dispatches_jobs_per_tenant(): void
    {
        Gate::before(fn () => true);
        Bus::fake();

        $tenants = Tenant::factory()->count(5)->create();
        $this->actingAs($this->makeLandlordUser(), 'landlord');

        $this->post(route('platform.admin.tenants.bulk.execute'), [
            'type'       => 'suspend',
            'tenant_ids' => $tenants->pluck('id')->all(),
            'reason'     => 'Test bulk',
        ])->assertRedirect();

        Bus::assertDispatchedTimes(ExecuteBulkTenantAction::class, 5);
    }

    private function makeLandlordUser() { /* stub */ }
}
```

### Task 26 — `TrialExtensionTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/TrialExtensionTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Platform\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class TrialExtensionTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            \Aero\Core\AeroCoreServiceProvider::class,
            \Aero\Platform\AeroPlatformServiceProvider::class,
        ];
    }

    public function test_extends_trial_ends_at_by_days(): void
    {
        Gate::before(fn () => true);
        $this->actingAs($this->makeLandlordUser(), 'landlord');

        $t = Tenant::factory()->create(['trial_ends_at' => now()->addDays(5)]);
        $this->post(route('platform.admin.onboarding.extend', $t), ['days' => 7])
             ->assertRedirect();

        $this->assertTrue($t->fresh()->trial_ends_at->diffInDays(now()) >= 11);
    }

    private function makeLandlordUser() { /* stub */ }
}
```

---

## 8. Task Checklist Summary

- [ ] Task 0  — Update `config/module.php` HRMAC hierarchy
- [ ] Task 1  — Migrations (tenants upgrade, provisioning logs, export requests, bulk operations)
- [ ] Task 2  — Models (Tenant, TenantDomain, TenantProvisioningLog, TenantExportRequest, BulkOperation)
- [ ] Task 3  — `TenantAdminService`
- [ ] Task 4  — `TenantImpersonationService`
- [ ] Task 5  — `TenantProvisioningService`
- [ ] Task 6  — `BulkTenantService` + `ExecuteBulkTenantAction` job
- [ ] Task 7  — `TenantExportService`
- [ ] Task 8  — `TenantController`
- [ ] Task 9  — `TenantDomainController`
- [ ] Task 10 — `TenantDatabaseController`
- [ ] Task 11 — `OnboardingController`
- [ ] Task 12 — `BulkTenantController`
- [ ] Task 13 — `TenantExportController`
- [ ] Task 14 — Form Requests
- [ ] Task 15 — `routes/admin.php`
- [ ] Task 16 — `Tenants/Index.jsx`
- [ ] Task 17 — `Tenants/Show.jsx`
- [ ] Task 18 — `Tenants/Create.jsx`
- [ ] Task 19 — `Onboarding/Dashboard.jsx`
- [ ] Task 20 — `Onboarding/Pending.jsx`
- [ ] Task 21 — `Onboarding/Provisioning.jsx`
- [ ] Task 22 — `Onboarding/Trials.jsx`
- [ ] Task 23 — `Tenants/Bulk.jsx`
- [ ] Task 24 — `TenantControllerTest`
- [ ] Task 25 — `BulkTenantTest`
- [ ] Task 26 — `TrialExtensionTest`

---

## 9. Acceptance Criteria

- All HRMAC codes from section 1 declared in `config/module.php` and enforced on routes.
- BYOC credentials never appear as plain text in `tenants` table (encrypted via `EncryptedField`).
- Every state mutation (suspend, activate, freeze, archive, impersonate, trial-extend, BYOC update, bulk op) writes a record to `platform_audit_logs`.
- Subscription/Invoice immutability rules (covered in P-2) interoperate correctly when a tenant is suspended/purged.
- All writes inside `DB::transaction()`.
- All React imports from `@aero/ui`; no inline `style={}`; destructive actions use Modal, never `window.confirm`.
- Tests in section 7 pass under `php artisan test --filter=Platform`.
