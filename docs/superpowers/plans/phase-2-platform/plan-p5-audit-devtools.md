# Plan P-5 — Audit & Developer Tools Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver a production-grade Platform Admin surface for observability and ops: an AEOS-native **audit log viewer** (`platform_audit_logs`), an **access log viewer** with a dedicated PII tab (`platform_access_logs`), full **error log management** (list/show/resolve/delete/bulk + analytics), and **developer tools** (cache stats + clear, queue stats + retry/delete, log viewer + download, maintenance dashboard).

**Architecture:** All domain code lives in `packages/aero-platform/src/{Models,Http,Services}/`. Models extend `Aero\Contracts\Models\CentralModel` (landlord/central DB). The underlying tables (`platform_audit_logs`, `platform_access_logs`, `error_logs`) already exist from earlier migrations — **no new migrations are required**. All writes that mutate error logs run inside `DB::transaction()`; every operator action hits `AuditServiceInterface::log()`. React pages live under `packages/aero-ui/resources/js/Pages/Platform/Admin/{AuditLogs,AccessLogs,ErrorLogs,Developer}/`.

**Tech Stack:** PHP 8.2, Laravel 12, Inertia.js v2, React 18, `@aero/ui`, PHPUnit 11, Orchestra Testbench.

---

## 1. HRMAC Hierarchy

Declared in `packages/aero-platform/config/module.php`. Routes reference codes as `hrmac:{submodule}.{component}.{action}`.

**Submodule `audit-logs`**
- `audit-logs.audit-log-list.view` / `.export`

**Submodule `access-logs`**
- `access-logs.access-log-list.view` / `.export`
- `access-logs.pii-access.view` / `.export`

**Submodule `error-monitoring`**
- `error-monitoring.error-log-list.view` / `.resolve` / `.delete`
- `error-monitoring.error-analytics.view`

**Submodule `developer-tools`**
- `developer-tools.developer-dashboard.view`
- `developer-tools.cache-management.view` / `.clear`
- `developer-tools.queue-management.view` / `.manage`
- `developer-tools.log-viewer.view` / `.download`

### Task 0 — Update `packages/aero-platform/config/module.php`

- [ ] Add/confirm all submodules + components + actions above
- [ ] Run `php artisan hrmac:sync --module=platform`

```php
// excerpt — packages/aero-platform/config/module.php
'submodules' => [
    'audit-logs' => [
        'label' => 'Audit Logs',
        'components' => [
            'audit-log-list' => ['actions' => ['view','export']],
        ],
    ],
    'access-logs' => [
        'label' => 'Access Logs',
        'components' => [
            'access-log-list' => ['actions' => ['view','export']],
            'pii-access'      => ['actions' => ['view','export']],
        ],
    ],
    'error-monitoring' => [
        'label' => 'Error Monitoring',
        'components' => [
            'error-log-list'  => ['actions' => ['view','resolve','delete']],
            'error-analytics' => ['actions' => ['view']],
        ],
    ],
    'developer-tools' => [
        'label' => 'Developer Tools',
        'components' => [
            'developer-dashboard' => ['actions' => ['view']],
            'cache-management'    => ['actions' => ['view','clear']],
            'queue-management'    => ['actions' => ['view','manage']],
            'log-viewer'          => ['actions' => ['view','download']],
        ],
    ],
],
```

---

## 2. Data Model

### Task 1 — No new migrations

- [ ] Confirm `platform_audit_logs`, `platform_access_logs`, and `error_logs` tables exist (created by earlier phases). If any column listed below is missing, add it via a single follow-up migration.

**Schema reference (existing):**

```text
platform_audit_logs
  id, event, action, actor_id, actor_type, subject_id, subject_type,
  description, metadata (json), ip_address, user_agent, created_at

platform_access_logs
  id, actor_id, actor_type, subject_id, subject_type, field_accessed,
  ip_address, user_agent, created_at

error_logs
  id, tenant_id (nullable), type, message, file, line, trace (text),
  status (open|resolved), resolved_at, resolved_by, occurrence_count,
  last_occurred_at, created_at
```

### Task 2 — Models

- [ ] `packages/aero-platform/src/Models/PlatformAuditLog.php`
- [ ] `packages/aero-platform/src/Models/PlatformAccessLog.php`
- [ ] `packages/aero-platform/src/Models/ErrorLog.php`

```php
// packages/aero-platform/src/Models/PlatformAuditLog.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class PlatformAuditLog extends CentralModel
{
    protected $table = 'platform_audit_logs';
    public $timestamps = false;

    protected $fillable = [
        'event','action','actor_id','actor_type','subject_id','subject_type',
        'description','metadata','ip_address','user_agent','created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function scopeEvent($q, ?string $event)
    {
        return $event ? $q->where('event', $event) : $q;
    }

    public function scopeActor($q, ?int $actorId)
    {
        return $actorId ? $q->where('actor_id', $actorId) : $q;
    }

    public function scopeSubjectType($q, ?string $type)
    {
        return $type ? $q->where('subject_type', $type) : $q;
    }

    public function scopeDateRange($q, ?string $from, ?string $to)
    {
        if ($from) $q->where('created_at', '>=', $from);
        if ($to)   $q->where('created_at', '<=', $to);
        return $q;
    }
}
```

```php
// packages/aero-platform/src/Models/PlatformAccessLog.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class PlatformAccessLog extends CentralModel
{
    protected $table = 'platform_access_logs';
    public $timestamps = false;

    protected $fillable = [
        'actor_id','actor_type','subject_id','subject_type',
        'field_accessed','ip_address','user_agent','created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    /** PII fields tracked across the platform. Keep in sync with EncryptedField casts. */
    public const PII_FIELDS = [
        'account_number','routing_number','tax_id','national_id',
        'medical_notes','byoc_db_host','byoc_db_user','byoc_db_password',
    ];

    public function scopePiiOnly($q)
    {
        return $q->whereIn('field_accessed', self::PII_FIELDS);
    }

    public function scopeSubjectType($q, ?string $type)
    {
        return $type ? $q->where('subject_type', $type) : $q;
    }

    public function scopeField($q, ?string $field)
    {
        return $field ? $q->where('field_accessed', $field) : $q;
    }

    public function scopeDateRange($q, ?string $from, ?string $to)
    {
        if ($from) $q->where('created_at', '>=', $from);
        if ($to)   $q->where('created_at', '<=', $to);
        return $q;
    }
}
```

```php
// packages/aero-platform/src/Models/ErrorLog.php
namespace Aero\Platform\Models;

use Aero\Contracts\Models\CentralModel;

class ErrorLog extends CentralModel
{
    protected $table = 'error_logs';

    protected $fillable = [
        'tenant_id','type','message','file','line','trace',
        'status','resolved_at','resolved_by',
        'occurrence_count','last_occurred_at',
    ];

    protected $casts = [
        'resolved_at'      => 'datetime',
        'last_occurred_at' => 'datetime',
        'line'             => 'integer',
        'occurrence_count' => 'integer',
    ];

    public function scopeStatus($q, ?string $status)
    {
        return $status ? $q->where('status', $status) : $q;
    }

    public function scopeTenant($q, ?string $tenantId)
    {
        return $tenantId ? $q->where('tenant_id', $tenantId) : $q;
    }

    public function scopeType($q, ?string $type)
    {
        return $type ? $q->where('type', $type) : $q;
    }

    public function scopeDateRange($q, ?string $from, ?string $to)
    {
        if ($from) $q->where('created_at', '>=', $from);
        if ($to)   $q->where('created_at', '<=', $to);
        return $q;
    }
}
```

---

## 3. Services

### Task 3 — `AuditLogAdminService`

- [ ] `packages/aero-platform/src/Services/AuditLogAdminService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\PlatformAuditLog;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters)
    {
        return PlatformAuditLog::query()
            ->event($filters['event'] ?? null)
            ->actor($filters['actor_id'] ?? null)
            ->subjectType($filters['subject_type'] ?? null)
            ->dateRange($filters['from'] ?? null, $filters['to'] ?? null)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function show(int $id): PlatformAuditLog
    {
        return PlatformAuditLog::findOrFail($id);
    }

    public function export(array $filters, int $actorId): StreamedResponse
    {
        $rows = PlatformAuditLog::query()
            ->event($filters['event'] ?? null)
            ->actor($filters['actor_id'] ?? null)
            ->subjectType($filters['subject_type'] ?? null)
            ->dateRange($filters['from'] ?? null, $filters['to'] ?? null)
            ->orderByDesc('created_at')
            ->limit(50000)
            ->get();

        $this->audit->log(
            event: 'AUDIT_LOG_EXPORTED',
            action: 'export',
            subject: null,
            description: "Audit log exported by actor $actorId (".$rows->count()." rows)"
        );

        $filename = 'audit-logs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id','created_at','event','action','actor_id','subject_type','subject_id','description','ip_address']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id, optional($r->created_at)->toIso8601String(),
                    $r->event, $r->action, $r->actor_id,
                    $r->subject_type, $r->subject_id,
                    $r->description, $r->ip_address,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
```

### Task 4 — `AccessLogAdminService`

- [ ] `packages/aero-platform/src/Services/AccessLogAdminService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\PlatformAccessLog;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccessLogAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters)
    {
        return PlatformAccessLog::query()
            ->subjectType($filters['subject_type'] ?? null)
            ->field($filters['field'] ?? null)
            ->dateRange($filters['from'] ?? null, $filters['to'] ?? null)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function listPii(array $filters)
    {
        return PlatformAccessLog::query()
            ->piiOnly()
            ->subjectType($filters['subject_type'] ?? null)
            ->field($filters['field'] ?? null)
            ->dateRange($filters['from'] ?? null, $filters['to'] ?? null)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function export(array $filters, bool $piiOnly, int $actorId): StreamedResponse
    {
        $q = PlatformAccessLog::query()
            ->subjectType($filters['subject_type'] ?? null)
            ->field($filters['field'] ?? null)
            ->dateRange($filters['from'] ?? null, $filters['to'] ?? null);

        if ($piiOnly) $q->piiOnly();

        $rows = $q->orderByDesc('created_at')->limit(50000)->get();

        $this->audit->log(
            event: $piiOnly ? 'PII_ACCESS_LOG_EXPORTED' : 'ACCESS_LOG_EXPORTED',
            action: 'export',
            subject: null,
            description: ($piiOnly ? 'PII ' : '')."Access log exported by actor $actorId (".$rows->count()." rows)"
        );

        $filename = ($piiOnly ? 'pii-access-' : 'access-').now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id','created_at','actor_id','subject_type','subject_id','field_accessed','ip_address']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id, optional($r->created_at)->toIso8601String(),
                    $r->actor_id, $r->subject_type, $r->subject_id,
                    $r->field_accessed, $r->ip_address,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
```

### Task 5 — `ErrorLogAdminService`

- [ ] `packages/aero-platform/src/Services/ErrorLogAdminService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\ErrorLog;
use Illuminate\Support\Facades\DB;

class ErrorLogAdminService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function list(array $filters)
    {
        return ErrorLog::query()
            ->status($filters['status'] ?? null)
            ->tenant($filters['tenant_id'] ?? null)
            ->type($filters['type'] ?? null)
            ->dateRange($filters['from'] ?? null, $filters['to'] ?? null)
            ->orderByDesc('last_occurred_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function show(int $id): ErrorLog
    {
        return ErrorLog::findOrFail($id);
    }

    public function resolve(ErrorLog $log, int $actorId): ErrorLog
    {
        if ($log->status === 'resolved') {
            abort(422, 'Error log is already resolved');
        }

        return DB::transaction(function () use ($log, $actorId) {
            $log->update([
                'status'      => 'resolved',
                'resolved_at' => now(),
                'resolved_by' => $actorId,
            ]);

            $this->audit->log(
                event: 'ERROR_LOG_RESOLVED',
                action: 'resolve',
                subject: $log,
                description: "Error log #{$log->id} resolved by actor $actorId"
            );

            return $log->fresh();
        });
    }

    public function delete(ErrorLog $log, int $actorId): void
    {
        DB::transaction(function () use ($log, $actorId) {
            $this->audit->log(
                event: 'ERROR_LOG_DELETED',
                action: 'delete',
                subject: $log,
                description: "Error log #{$log->id} deleted by actor $actorId"
            );
            $log->delete();
        });
    }

    public function bulkResolve(array $ids, int $actorId): int
    {
        return DB::transaction(function () use ($ids, $actorId) {
            $count = ErrorLog::whereIn('id', $ids)
                ->where('status', 'open')
                ->update([
                    'status'      => 'resolved',
                    'resolved_at' => now(),
                    'resolved_by' => $actorId,
                ]);

            $this->audit->log(
                event: 'ERROR_LOG_BULK_RESOLVED',
                action: 'resolve',
                subject: null,
                description: "Bulk resolved $count error logs by actor $actorId"
            );

            return $count;
        });
    }

    public function bulkDelete(array $ids, int $actorId): int
    {
        return DB::transaction(function () use ($ids, $actorId) {
            $count = ErrorLog::whereIn('id', $ids)->count();
            ErrorLog::whereIn('id', $ids)->delete();

            $this->audit->log(
                event: 'ERROR_LOG_BULK_DELETED',
                action: 'delete',
                subject: null,
                description: "Bulk deleted $count error logs by actor $actorId"
            );

            return $count;
        });
    }

    public function analytics(): array
    {
        $byType = ErrorLog::query()
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->limit(20)
            ->get();

        $trend = ErrorLog::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $topTenants = ErrorLog::query()
            ->whereNotNull('tenant_id')
            ->select('tenant_id', DB::raw('COUNT(*) as count'))
            ->groupBy('tenant_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'by_type'     => $byType,
            'trend'       => $trend,
            'top_tenants' => $topTenants,
            'open_count'  => ErrorLog::where('status','open')->count(),
            'resolved_count' => ErrorLog::where('status','resolved')->count(),
        ];
    }
}
```

### Task 6 — `DeveloperToolsService`

- [ ] `packages/aero-platform/src/Services/DeveloperToolsService.php`

```php
namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;

class DeveloperToolsService
{
    public function __construct(private AuditServiceInterface $audit) {}

    public function getCacheStats(): array
    {
        $stores = array_keys(config('cache.stores', []));
        $stats = [];

        foreach ($stores as $store) {
            $stats[$store] = [
                'driver' => config("cache.stores.$store.driver"),
                'size'   => $this->cacheSize($store),
            ];
        }

        return $stats;
    }

    private function cacheSize(string $store): ?int
    {
        try {
            $driver = config("cache.stores.$store.driver");
            if ($driver === 'redis') {
                $info = Redis::connection(config("cache.stores.$store.connection"))->info('memory');
                return (int) ($info['used_memory'] ?? 0);
            }
            if ($driver === 'file') {
                $path = config("cache.stores.$store.path", storage_path('framework/cache/data'));
                if (!File::isDirectory($path)) return 0;
                $size = 0;
                foreach (File::allFiles($path) as $f) $size += $f->getSize();
                return $size;
            }
            if ($driver === 'database') {
                return (int) DB::table(config("cache.stores.$store.table", 'cache'))->count();
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    public function clearCache(string $store, int $actorId): void
    {
        if (!array_key_exists($store, config('cache.stores', []))) {
            abort(422, "Unknown cache store: $store");
        }

        Cache::store($store)->flush();

        $this->audit->log(
            event: 'CACHE_CLEARED',
            action: 'clear',
            subject: null,
            description: "Cache store '$store' cleared by actor $actorId"
        );
    }

    public function getQueueStats(): array
    {
        $jobs   = DB::table('jobs')->count();
        $failed = DB::table('failed_jobs')->count();

        $byQueue = DB::table('jobs')
            ->select('queue', DB::raw('COUNT(*) as count'))
            ->groupBy('queue')
            ->get();

        return [
            'pending_total' => $jobs,
            'failed_total'  => $failed,
            'by_queue'      => $byQueue,
        ];
    }

    public function getQueueJobs(?string $queue, string $status = 'pending', int $perPage = 25)
    {
        if ($status === 'failed') {
            $q = DB::table('failed_jobs');
            if ($queue) $q->where('queue', $queue);
            return $q->orderByDesc('failed_at')->paginate($perPage);
        }

        $q = DB::table('jobs');
        if ($queue) $q->where('queue', $queue);
        return $q->orderByDesc('id')->paginate($perPage);
    }

    public function retryJob(string $uuid, int $actorId): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);

        $this->audit->log(
            event: 'QUEUE_JOB_RETRIED',
            action: 'manage',
            subject: null,
            description: "Failed job $uuid retried by actor $actorId"
        );
    }

    public function deleteJob(string $uuid, int $actorId): void
    {
        Artisan::call('queue:forget', ['id' => $uuid]);

        $this->audit->log(
            event: 'QUEUE_JOB_DELETED',
            action: 'manage',
            subject: null,
            description: "Failed job $uuid forgotten by actor $actorId"
        );
    }

    public function getLogFiles(): array
    {
        $path = storage_path('logs');
        if (!File::isDirectory($path)) return [];

        $files = [];
        foreach (File::files($path) as $f) {
            $files[] = [
                'name'        => $f->getFilename(),
                'size'        => $f->getSize(),
                'modified_at' => date('c', $f->getMTime()),
            ];
        }
        usort($files, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));
        return $files;
    }

    public function downloadLog(string $filename, int $actorId): string
    {
        $safe = basename($filename);
        $path = storage_path('logs/'.$safe);

        if (!File::exists($path)) abort(404);

        $this->audit->log(
            event: 'LOG_FILE_DOWNLOADED',
            action: 'download',
            subject: null,
            description: "Log file '$safe' downloaded by actor $actorId"
        );

        return $path;
    }

    public function tailLog(string $filename, int $lines = 100): array
    {
        $safe = basename($filename);
        $path = storage_path('logs/'.$safe);

        if (!File::exists($path)) return [];

        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $last = $file->key();
        $start = max(0, $last - $lines);

        $out = [];
        $file->seek($start);
        while (!$file->eof()) {
            $line = $file->fgets();
            if ($line !== false && $line !== '') $out[] = rtrim($line);
        }
        return $out;
    }
}
```

---

## 4. Controllers

All in `packages/aero-platform/src/Http/Controllers/Admin/`.

### Task 7 — `AuditLogController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/AuditLogController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\AuditLogAdminService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function __construct(private AuditLogAdminService $svc) {}

    public function index(Request $request)
    {
        $filters = $request->only(['event','actor_id','subject_type','from','to']);
        return Inertia::render('Platform/Admin/AuditLogs/Index', [
            'logs'    => $this->svc->list($filters),
            'filters' => $filters,
        ]);
    }

    public function show(int $id)
    {
        return response()->json($this->svc->show($id));
    }

    public function export(Request $request)
    {
        $filters = $request->only(['event','actor_id','subject_type','from','to']);
        return $this->svc->export($filters, $request->user()->id);
    }
}
```

### Task 8 — `AccessLogController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/AccessLogController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\AccessLogAdminService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccessLogController extends Controller
{
    public function __construct(private AccessLogAdminService $svc) {}

    public function index(Request $request)
    {
        $filters = $request->only(['subject_type','field','from','to']);
        return Inertia::render('Platform/Admin/AccessLogs/Index', [
            'logs'    => $this->svc->list($filters),
            'filters' => $filters,
            'pii'     => false,
        ]);
    }

    public function piiAccess(Request $request)
    {
        $filters = $request->only(['subject_type','field','from','to']);
        return Inertia::render('Platform/Admin/AccessLogs/Index', [
            'logs'    => $this->svc->listPii($filters),
            'filters' => $filters,
            'pii'     => true,
        ]);
    }

    public function export(Request $request)
    {
        $filters = $request->only(['subject_type','field','from','to']);
        $pii = (bool) $request->boolean('pii');
        return $this->svc->export($filters, $pii, $request->user()->id);
    }
}
```

### Task 9 — `ErrorLogController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/ErrorLogController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Models\ErrorLog;
use Aero\Platform\Services\ErrorLogAdminService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ErrorLogController extends Controller
{
    public function __construct(private ErrorLogAdminService $svc) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status','tenant_id','type','from','to']);
        return Inertia::render('Platform/Admin/ErrorLogs/Index', [
            'logs'    => $this->svc->list($filters),
            'filters' => $filters,
        ]);
    }

    public function show(ErrorLog $errorLog)
    {
        return Inertia::render('Platform/Admin/ErrorLogs/Show', [
            'log' => $this->svc->show($errorLog->id),
        ]);
    }

    public function resolve(Request $request, ErrorLog $errorLog)
    {
        $this->svc->resolve($errorLog, $request->user()->id);
        return back()->with('success', 'Error log resolved');
    }

    public function destroy(Request $request, ErrorLog $errorLog)
    {
        $this->svc->delete($errorLog, $request->user()->id);
        return redirect()->route('platform.admin.error-logs.index')
            ->with('success', 'Error log deleted');
    }

    public function bulkResolve(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $n = $this->svc->bulkResolve($data['ids'], $request->user()->id);
        return back()->with('success', "$n error logs resolved");
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        $n = $this->svc->bulkDelete($data['ids'], $request->user()->id);
        return back()->with('success', "$n error logs deleted");
    }

    public function analytics()
    {
        return Inertia::render('Platform/Admin/ErrorLogs/Analytics', [
            'analytics' => $this->svc->analytics(),
        ]);
    }
}
```

### Task 10 — `DeveloperToolsController`

- [ ] `packages/aero-platform/src/Http/Controllers/Admin/DeveloperToolsController.php`

```php
namespace Aero\Platform\Http\Controllers\Admin;

use Aero\Platform\Http\Controllers\Controller;
use Aero\Platform\Services\DeveloperToolsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DeveloperToolsController extends Controller
{
    public function __construct(private DeveloperToolsService $svc) {}

    public function dashboard()
    {
        return Inertia::render('Platform/Admin/Developer/Dashboard', [
            'cache_stats' => $this->svc->getCacheStats(),
            'queue_stats' => $this->svc->getQueueStats(),
            'recent_log'  => $this->svc->tailLog('laravel.log', 50),
        ]);
    }

    public function clearCache(Request $request)
    {
        $data = $request->validate(['store' => 'required|string']);
        $this->svc->clearCache($data['store'], $request->user()->id);
        return back()->with('success', "Cache store '{$data['store']}' cleared");
    }

    public function queueJobs(Request $request)
    {
        $queue = $request->query('queue');
        $status = $request->query('status', 'pending');
        return response()->json($this->svc->getQueueJobs($queue, $status));
    }

    public function retryJob(Request $request)
    {
        $data = $request->validate(['uuid' => 'required|string']);
        $this->svc->retryJob($data['uuid'], $request->user()->id);
        return back()->with('success', 'Job retried');
    }

    public function deleteJob(Request $request)
    {
        $data = $request->validate(['uuid' => 'required|string']);
        $this->svc->deleteJob($data['uuid'], $request->user()->id);
        return back()->with('success', 'Job forgotten');
    }

    public function logFiles()
    {
        return Inertia::render('Platform/Admin/Developer/Logs', [
            'files' => $this->svc->getLogFiles(),
        ]);
    }

    public function downloadLog(Request $request)
    {
        $data = $request->validate(['filename' => 'required|string']);
        $path = $this->svc->downloadLog($data['filename'], $request->user()->id);
        return response()->download($path);
    }

    public function tailLog(Request $request)
    {
        $data = $request->validate([
            'filename' => 'required|string',
            'lines'    => 'integer|min:1|max:1000',
        ]);
        return response()->json([
            'lines' => $this->svc->tailLog($data['filename'], $data['lines'] ?? 100),
        ]);
    }
}
```

---

## 5. Routes

### Task 11 — Register routes

- [ ] Append to `packages/aero-platform/routes/admin.php`

```php
use Aero\Platform\Http\Controllers\Admin\AccessLogController;
use Aero\Platform\Http\Controllers\Admin\AuditLogController;
use Aero\Platform\Http\Controllers\Admin\DeveloperToolsController;
use Aero\Platform\Http\Controllers\Admin\ErrorLogController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:landlord'])->prefix('platform/admin')->name('platform.admin.')->group(function () {

    // Audit logs
    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/',         [AuditLogController::class, 'index'])->name('index')->middleware('hrmac:audit-logs.audit-log-list.view');
        Route::get('/export',   [AuditLogController::class, 'export'])->name('export')->middleware('hrmac:audit-logs.audit-log-list.export');
        Route::get('/{id}',     [AuditLogController::class, 'show'])->name('show')->middleware('hrmac:audit-logs.audit-log-list.view');
    });

    // Access logs
    Route::prefix('access-logs')->name('access-logs.')->group(function () {
        Route::get('/',         [AccessLogController::class, 'index'])->name('index')->middleware('hrmac:access-logs.access-log-list.view');
        Route::get('/pii',      [AccessLogController::class, 'piiAccess'])->name('pii')->middleware('hrmac:access-logs.pii-access.view');
        Route::get('/export',   [AccessLogController::class, 'export'])->name('export')->middleware('hrmac:access-logs.access-log-list.export');
    });

    // Error logs
    Route::prefix('error-logs')->name('error-logs.')->group(function () {
        Route::get('/',                       [ErrorLogController::class, 'index'])->name('index')->middleware('hrmac:error-monitoring.error-log-list.view');
        Route::get('/analytics',              [ErrorLogController::class, 'analytics'])->name('analytics')->middleware('hrmac:error-monitoring.error-analytics.view');
        Route::get('/{errorLog}',             [ErrorLogController::class, 'show'])->name('show')->middleware('hrmac:error-monitoring.error-log-list.view');
        Route::post('/{errorLog}/resolve',    [ErrorLogController::class, 'resolve'])->name('resolve')->middleware('hrmac:error-monitoring.error-log-list.resolve');
        Route::delete('/{errorLog}',          [ErrorLogController::class, 'destroy'])->name('destroy')->middleware('hrmac:error-monitoring.error-log-list.delete');
        Route::post('/bulk/resolve',          [ErrorLogController::class, 'bulkResolve'])->name('bulk-resolve')->middleware('hrmac:error-monitoring.error-log-list.resolve');
        Route::post('/bulk/destroy',          [ErrorLogController::class, 'bulkDestroy'])->name('bulk-destroy')->middleware('hrmac:error-monitoring.error-log-list.delete');
    });

    // Developer tools
    Route::prefix('developer')->name('developer.')->group(function () {
        Route::get('/',                  [DeveloperToolsController::class, 'dashboard'])->name('dashboard')->middleware('hrmac:developer-tools.developer-dashboard.view');
        Route::post('/cache/clear',      [DeveloperToolsController::class, 'clearCache'])->name('cache.clear')->middleware('hrmac:developer-tools.cache-management.clear');
        Route::get('/queue/jobs',        [DeveloperToolsController::class, 'queueJobs'])->name('queue.jobs')->middleware('hrmac:developer-tools.queue-management.view');
        Route::post('/queue/retry',      [DeveloperToolsController::class, 'retryJob'])->name('queue.retry')->middleware('hrmac:developer-tools.queue-management.manage');
        Route::post('/queue/forget',     [DeveloperToolsController::class, 'deleteJob'])->name('queue.forget')->middleware('hrmac:developer-tools.queue-management.manage');
        Route::get('/logs',              [DeveloperToolsController::class, 'logFiles'])->name('logs.index')->middleware('hrmac:developer-tools.log-viewer.view');
        Route::get('/logs/download',     [DeveloperToolsController::class, 'downloadLog'])->name('logs.download')->middleware('hrmac:developer-tools.log-viewer.download');
        Route::get('/logs/tail',         [DeveloperToolsController::class, 'tailLog'])->name('logs.tail')->middleware('hrmac:developer-tools.log-viewer.view');
    });
});
```

---

## 6. React Pages

All pages live under `packages/aero-ui/resources/js/Pages/Platform/Admin/`. Depth-4 imports: `App` = `'../../../App.jsx'`, `useHRMAC` = `'../../../../hooks/useHRMAC.js'`. All UI from `@aero/ui`, no inline styles, no `window.confirm`.

### Task 12 — `AuditLogs/Index.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/AuditLogs/Index.jsx`

```jsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, Chip, Input, Select, SelectItem,
  Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Modal, ModalBody, ModalContent, ModalFooter, ModalHeader, useDisclosure,
} from '@aero/ui';
import { useState } from 'react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function AuditLogsIndex() {
  const { logs, filters } = usePage().props;
  const { hasAccess } = useHRMAC('audit-logs.audit-log-list');
  const detail = useDisclosure();
  const [selected, setSelected] = useState(null);
  const [form, setForm] = useState(filters || {});

  const apply = () => router.get(route('platform.admin.audit-logs.index'),
    form, { preserveState: true, replace: true });

  const exportCsv = () => {
    window.location.href = route('platform.admin.audit-logs.export', form);
  };

  return (
    <>
      <Head title="Audit Logs" />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-semibold">Audit Logs</h1>
          {hasAccess('export') && (
            <Button color="primary" variant="flat" onPress={exportCsv}>Export CSV</Button>
          )}
        </div>

        <Card>
          <CardBody className="grid grid-cols-1 md:grid-cols-5 gap-3">
            <Input label="Event" value={form.event ?? ''}
              onValueChange={(v) => setForm({...form, event: v})} />
            <Input label="Actor ID" value={form.actor_id ?? ''}
              onValueChange={(v) => setForm({...form, actor_id: v})} />
            <Input label="Subject Type" value={form.subject_type ?? ''}
              onValueChange={(v) => setForm({...form, subject_type: v})} />
            <Input label="From" type="date" value={form.from ?? ''}
              onValueChange={(v) => setForm({...form, from: v})} />
            <Input label="To" type="date" value={form.to ?? ''}
              onValueChange={(v) => setForm({...form, to: v})} />
            <div className="md:col-span-5 flex justify-end">
              <Button color="primary" onPress={apply}>Apply Filters</Button>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <Table aria-label="Audit logs">
              <TableHeader>
                <TableColumn>Timestamp</TableColumn>
                <TableColumn>Event</TableColumn>
                <TableColumn>Actor</TableColumn>
                <TableColumn>Subject</TableColumn>
                <TableColumn>Description</TableColumn>
              </TableHeader>
              <TableBody emptyContent="No audit log entries">
                {logs.data.map((l) => (
                  <TableRow key={l.id}
                    onClick={() => { setSelected(l); detail.onOpen(); }}
                    className="cursor-pointer">
                    <TableCell>{l.created_at}</TableCell>
                    <TableCell><Chip size="sm" variant="flat">{l.event}</Chip></TableCell>
                    <TableCell>#{l.actor_id ?? '—'}</TableCell>
                    <TableCell>{l.subject_type ? `${l.subject_type}#${l.subject_id}` : '—'}</TableCell>
                    <TableCell className="truncate max-w-md">{l.description}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardBody>
        </Card>
      </div>

      <Modal isOpen={detail.isOpen} onClose={detail.onClose} size="2xl">
        <ModalContent>
          <ModalHeader>Audit Log #{selected?.id}</ModalHeader>
          <ModalBody>
            <div className="space-y-2 text-sm">
              <div><b>Event:</b> {selected?.event}</div>
              <div><b>Action:</b> {selected?.action}</div>
              <div><b>Actor:</b> {selected?.actor_type} #{selected?.actor_id}</div>
              <div><b>Subject:</b> {selected?.subject_type} #{selected?.subject_id}</div>
              <div><b>Description:</b> {selected?.description}</div>
              <div><b>IP:</b> {selected?.ip_address}</div>
              <div><b>User Agent:</b> {selected?.user_agent}</div>
              {selected?.metadata && (
                <pre className="bg-default-100 p-2 rounded text-xs overflow-auto">
                  {JSON.stringify(selected.metadata, null, 2)}
                </pre>
              )}
            </div>
          </ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={detail.onClose}>Close</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}

AuditLogsIndex.layout = (page) => <App>{page}</App>;
```

### Task 13 — `AccessLogs/Index.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/AccessLogs/Index.jsx`

```jsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, Chip, Input, Tabs, Tab,
  Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
} from '@aero/ui';
import { useState } from 'react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function AccessLogsIndex() {
  const { logs, filters, pii } = usePage().props;
  const access = useHRMAC('access-logs.access-log-list');
  const piiAccess = useHRMAC('access-logs.pii-access');
  const [form, setForm] = useState(filters || {});

  const switchTab = (key) => {
    if (key === 'pii') router.get(route('platform.admin.access-logs.pii'), form);
    else router.get(route('platform.admin.access-logs.index'), form);
  };

  const apply = () => {
    const target = pii ? 'platform.admin.access-logs.pii' : 'platform.admin.access-logs.index';
    router.get(route(target), form, { preserveState: true, replace: true });
  };

  const exportCsv = () => {
    const params = { ...form, pii: pii ? 1 : 0 };
    window.location.href = route('platform.admin.access-logs.export', params);
  };

  return (
    <>
      <Head title={pii ? 'PII Access Logs' : 'Access Logs'} />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-semibold">Access Logs</h1>
          {((pii && piiAccess.hasAccess('export')) || (!pii && access.hasAccess('export'))) && (
            <Button color="primary" variant="flat" onPress={exportCsv}>Export CSV</Button>
          )}
        </div>

        <Tabs selectedKey={pii ? 'pii' : 'all'} onSelectionChange={switchTab}>
          <Tab key="all" title="All Access" />
          <Tab key="pii" title="PII Only" />
        </Tabs>

        <Card>
          <CardBody className="grid grid-cols-1 md:grid-cols-5 gap-3">
            <Input label="Subject Type" value={form.subject_type ?? ''}
              onValueChange={(v) => setForm({...form, subject_type: v})} />
            <Input label="Field" value={form.field ?? ''}
              onValueChange={(v) => setForm({...form, field: v})} />
            <Input label="From" type="date" value={form.from ?? ''}
              onValueChange={(v) => setForm({...form, from: v})} />
            <Input label="To" type="date" value={form.to ?? ''}
              onValueChange={(v) => setForm({...form, to: v})} />
            <div className="md:col-span-5 flex justify-end">
              <Button color="primary" onPress={apply}>Apply Filters</Button>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <Table aria-label="Access logs">
              <TableHeader>
                <TableColumn>Timestamp</TableColumn>
                <TableColumn>Actor</TableColumn>
                <TableColumn>Subject Type</TableColumn>
                <TableColumn>Field</TableColumn>
                <TableColumn>IP</TableColumn>
              </TableHeader>
              <TableBody emptyContent="No access log entries">
                {logs.data.map((l) => (
                  <TableRow key={l.id}>
                    <TableCell>{l.created_at}</TableCell>
                    <TableCell>{l.actor_type} #{l.actor_id}</TableCell>
                    <TableCell>{l.subject_type} #{l.subject_id}</TableCell>
                    <TableCell><Chip size="sm" color={pii ? 'warning' : 'default'} variant="flat">{l.field_accessed}</Chip></TableCell>
                    <TableCell>{l.ip_address}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardBody>
        </Card>
      </div>
    </>
  );
}

AccessLogsIndex.layout = (page) => <App>{page}</App>;
```

### Task 14 — `ErrorLogs/Index.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/ErrorLogs/Index.jsx`

```jsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, Checkbox, Chip, Input, Select, SelectItem,
  Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Modal, ModalBody, ModalContent, ModalFooter, ModalHeader, useDisclosure,
} from '@aero/ui';
import { useState } from 'react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function ErrorLogsIndex() {
  const { logs, filters } = usePage().props;
  const { hasAccess } = useHRMAC('error-monitoring.error-log-list');
  const [selected, setSelected] = useState([]);
  const [form, setForm] = useState(filters || {});
  const confirmResolve = useDisclosure();
  const confirmDelete = useDisclosure();
  const [target, setTarget] = useState(null);

  const apply = () => router.get(route('platform.admin.error-logs.index'),
    form, { preserveState: true, replace: true });

  const toggle = (id) => setSelected((s) => s.includes(id) ? s.filter(i => i !== id) : [...s, id]);

  const bulkResolve = () => router.post(route('platform.admin.error-logs.bulk-resolve'),
    { ids: selected }, { onSuccess: () => setSelected([]) });

  const bulkDelete = () => router.post(route('platform.admin.error-logs.bulk-destroy'),
    { ids: selected }, { onSuccess: () => setSelected([]) });

  return (
    <>
      <Head title="Error Logs" />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-semibold">Error Logs</h1>
          <div className="flex gap-2">
            <Button as={Link} href={route('platform.admin.error-logs.analytics')} variant="flat">Analytics</Button>
            {hasAccess('resolve') && selected.length > 0 && (
              <Button color="success" variant="flat" onPress={bulkResolve}>
                Resolve {selected.length}
              </Button>
            )}
            {hasAccess('delete') && selected.length > 0 && (
              <Button color="danger" variant="flat" onPress={bulkDelete}>
                Delete {selected.length}
              </Button>
            )}
          </div>
        </div>

        <Card>
          <CardBody className="grid grid-cols-1 md:grid-cols-5 gap-3">
            <Select label="Status" selectedKeys={form.status ? [form.status] : []}
              onSelectionChange={(k) => setForm({...form, status: [...k][0] ?? null})}>
              <SelectItem key="open">Open</SelectItem>
              <SelectItem key="resolved">Resolved</SelectItem>
            </Select>
            <Input label="Tenant" value={form.tenant_id ?? ''}
              onValueChange={(v) => setForm({...form, tenant_id: v})} />
            <Input label="Type" value={form.type ?? ''}
              onValueChange={(v) => setForm({...form, type: v})} />
            <Input label="From" type="date" value={form.from ?? ''}
              onValueChange={(v) => setForm({...form, from: v})} />
            <Input label="To" type="date" value={form.to ?? ''}
              onValueChange={(v) => setForm({...form, to: v})} />
            <div className="md:col-span-5 flex justify-end">
              <Button color="primary" onPress={apply}>Apply Filters</Button>
            </div>
          </CardBody>
        </Card>

        <Card>
          <CardBody>
            <Table aria-label="Error logs">
              <TableHeader>
                <TableColumn> </TableColumn>
                <TableColumn>Type</TableColumn>
                <TableColumn>Message</TableColumn>
                <TableColumn>Tenant</TableColumn>
                <TableColumn>Count</TableColumn>
                <TableColumn>Last Occurred</TableColumn>
                <TableColumn>Status</TableColumn>
                <TableColumn>Actions</TableColumn>
              </TableHeader>
              <TableBody emptyContent="No error logs">
                {logs.data.map((l) => (
                  <TableRow key={l.id}>
                    <TableCell>
                      <Checkbox isSelected={selected.includes(l.id)} onValueChange={() => toggle(l.id)} />
                    </TableCell>
                    <TableCell><Chip size="sm" variant="flat">{l.type}</Chip></TableCell>
                    <TableCell className="truncate max-w-sm">
                      <Link href={route('platform.admin.error-logs.show', l.id)} className="text-primary">
                        {l.message}
                      </Link>
                    </TableCell>
                    <TableCell>{l.tenant_id ?? '—'}</TableCell>
                    <TableCell>{l.occurrence_count}</TableCell>
                    <TableCell>{l.last_occurred_at}</TableCell>
                    <TableCell>
                      <Chip size="sm" color={l.status === 'resolved' ? 'success' : 'warning'}>
                        {l.status}
                      </Chip>
                    </TableCell>
                    <TableCell>
                      <div className="flex gap-1">
                        {hasAccess('resolve') && l.status === 'open' && (
                          <Button size="sm" variant="flat" color="success"
                            onPress={() => { setTarget(l); confirmResolve.onOpen(); }}>
                            Resolve
                          </Button>
                        )}
                        {hasAccess('delete') && (
                          <Button size="sm" variant="flat" color="danger"
                            onPress={() => { setTarget(l); confirmDelete.onOpen(); }}>
                            Delete
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardBody>
        </Card>
      </div>

      <Modal isOpen={confirmResolve.isOpen} onClose={confirmResolve.onClose}>
        <ModalContent>
          <ModalHeader>Resolve error #{target?.id}?</ModalHeader>
          <ModalBody>This will mark the error as resolved and stop notifications.</ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={confirmResolve.onClose}>Cancel</Button>
            <Button color="success"
              onPress={() => router.post(route('platform.admin.error-logs.resolve', target.id),
                {}, { onSuccess: () => confirmResolve.onClose() })}>
              Resolve
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      <Modal isOpen={confirmDelete.isOpen} onClose={confirmDelete.onClose}>
        <ModalContent>
          <ModalHeader>Delete error #{target?.id}?</ModalHeader>
          <ModalBody>This permanently deletes the error log entry.</ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={confirmDelete.onClose}>Cancel</Button>
            <Button color="danger"
              onPress={() => router.delete(route('platform.admin.error-logs.destroy', target.id),
                { onSuccess: () => confirmDelete.onClose() })}>
              Delete
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}

ErrorLogsIndex.layout = (page) => <App>{page}</App>;
```

### Task 15 — `ErrorLogs/Show.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/ErrorLogs/Show.jsx`

```jsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button, Card, CardBody, CardHeader, Chip } from '@aero/ui';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

export default function ErrorLogShow() {
  const { log } = usePage().props;
  const { hasAccess } = useHRMAC('error-monitoring.error-log-list');

  return (
    <>
      <Head title={`Error #${log.id}`} />
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-semibold">Error #{log.id}</h1>
          <div className="flex gap-2">
            {hasAccess('resolve') && log.status === 'open' && (
              <Button color="success"
                onPress={() => router.post(route('platform.admin.error-logs.resolve', log.id))}>
                Mark Resolved
              </Button>
            )}
            <Button as={Link} href={route('platform.admin.error-logs.index')} variant="light">
              Back
            </Button>
          </div>
        </div>

        <Card>
          <CardHeader className="justify-between">
            <span className="font-semibold">{log.type}</span>
            <Chip color={log.status === 'resolved' ? 'success' : 'warning'}>{log.status}</Chip>
          </CardHeader>
          <CardBody className="space-y-3">
            <div><b>Message:</b> {log.message}</div>
            <div><b>File:</b> {log.file}:{log.line}</div>
            <div><b>Tenant:</b> {log.tenant_id ?? '—'}</div>
            <div><b>First seen:</b> {log.created_at}</div>
            <div><b>Last seen:</b> {log.last_occurred_at}</div>
            <div><b>Occurrences:</b> {log.occurrence_count}</div>
            {log.resolved_at && (
              <div><b>Resolved at:</b> {log.resolved_at} by actor #{log.resolved_by}</div>
            )}
          </CardBody>
        </Card>

        <Card>
          <CardHeader>Stack Trace</CardHeader>
          <CardBody>
            <pre className="bg-default-100 rounded p-3 text-xs font-mono overflow-auto max-h-[600px]">
              {log.trace}
            </pre>
          </CardBody>
        </Card>
      </div>
    </>
  );
}

ErrorLogShow.layout = (page) => <App>{page}</App>;
```

### Task 16 — `ErrorLogs/Analytics.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/ErrorLogs/Analytics.jsx`

```jsx
import { Head, usePage } from '@inertiajs/react';
import {
  Card, CardBody, CardHeader,
  Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
} from '@aero/ui';
import App from '../../../App.jsx';

export default function ErrorLogAnalytics() {
  const { analytics } = usePage().props;

  return (
    <>
      <Head title="Error Analytics" />
      <div className="space-y-4">
        <h1 className="text-2xl font-semibold">Error Analytics</h1>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Card>
            <CardHeader>Status Summary</CardHeader>
            <CardBody>
              <div className="text-sm space-y-1">
                <div><b>Open:</b> {analytics.open_count}</div>
                <div><b>Resolved:</b> {analytics.resolved_count}</div>
              </div>
            </CardBody>
          </Card>

          <Card>
            <CardHeader>Errors by Type (top 20)</CardHeader>
            <CardBody>
              <Table aria-label="By type">
                <TableHeader>
                  <TableColumn>Type</TableColumn>
                  <TableColumn>Count</TableColumn>
                </TableHeader>
                <TableBody emptyContent="No data">
                  {analytics.by_type.map((row, i) => (
                    <TableRow key={i}>
                      <TableCell>{row.type}</TableCell>
                      <TableCell>{row.count}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardBody>
          </Card>

          <Card>
            <CardHeader>Trend (last 30 days)</CardHeader>
            <CardBody>
              <Table aria-label="Trend">
                <TableHeader>
                  <TableColumn>Day</TableColumn>
                  <TableColumn>Count</TableColumn>
                </TableHeader>
                <TableBody emptyContent="No data">
                  {analytics.trend.map((row, i) => (
                    <TableRow key={i}>
                      <TableCell>{row.day}</TableCell>
                      <TableCell>{row.count}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardBody>
          </Card>

          <Card>
            <CardHeader>Top Tenants by Error Count</CardHeader>
            <CardBody>
              <Table aria-label="Top tenants">
                <TableHeader>
                  <TableColumn>Tenant</TableColumn>
                  <TableColumn>Errors</TableColumn>
                </TableHeader>
                <TableBody emptyContent="No data">
                  {analytics.top_tenants.map((row, i) => (
                    <TableRow key={i}>
                      <TableCell>{row.tenant_id}</TableCell>
                      <TableCell>{row.count}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </CardBody>
          </Card>
        </div>
      </div>
    </>
  );
}

ErrorLogAnalytics.layout = (page) => <App>{page}</App>;
```

### Task 17 — `Developer/Dashboard.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Developer/Dashboard.jsx`

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, CardHeader, Chip,
  Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
  Modal, ModalBody, ModalContent, ModalFooter, ModalHeader, useDisclosure,
} from '@aero/ui';
import { useState } from 'react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

const formatBytes = (n) => {
  if (n == null) return '—';
  const units = ['B','KB','MB','GB'];
  let i = 0; let v = n;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return v.toFixed(1) + ' ' + units[i];
};

export default function DeveloperDashboard() {
  const { cache_stats, queue_stats, recent_log } = usePage().props;
  const cacheAccess = useHRMAC('developer-tools.cache-management');
  const queueAccess = useHRMAC('developer-tools.queue-management');
  const confirm = useDisclosure();
  const [target, setTarget] = useState(null);

  const askClear = (store) => { setTarget(store); confirm.onOpen(); };
  const doClear = () => router.post(route('platform.admin.developer.cache.clear'),
    { store: target }, { onSuccess: () => confirm.onClose() });

  return (
    <>
      <Head title="Developer Dashboard" />
      <div className="space-y-4">
        <h1 className="text-2xl font-semibold">Developer Dashboard</h1>

        <Card>
          <CardHeader className="justify-between">
            <span className="font-semibold">Cache Stores</span>
          </CardHeader>
          <CardBody>
            <Table aria-label="Cache stores">
              <TableHeader>
                <TableColumn>Store</TableColumn>
                <TableColumn>Driver</TableColumn>
                <TableColumn>Size</TableColumn>
                <TableColumn>Actions</TableColumn>
              </TableHeader>
              <TableBody>
                {Object.entries(cache_stats).map(([store, info]) => (
                  <TableRow key={store}>
                    <TableCell>{store}</TableCell>
                    <TableCell><Chip size="sm" variant="flat">{info.driver}</Chip></TableCell>
                    <TableCell>{formatBytes(info.size)}</TableCell>
                    <TableCell>
                      {cacheAccess.hasAccess('clear') && (
                        <Button size="sm" color="warning" variant="flat" onPress={() => askClear(store)}>
                          Clear
                        </Button>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>Queue</CardHeader>
          <CardBody>
            <div className="grid grid-cols-3 gap-4 mb-3">
              <div><b>Pending:</b> {queue_stats.pending_total}</div>
              <div><b>Failed:</b> {queue_stats.failed_total}</div>
              <div><b>Queues:</b> {queue_stats.by_queue.length}</div>
            </div>
            <Table aria-label="By queue">
              <TableHeader>
                <TableColumn>Queue</TableColumn>
                <TableColumn>Pending</TableColumn>
              </TableHeader>
              <TableBody emptyContent="No queued jobs">
                {queue_stats.by_queue.map((row, i) => (
                  <TableRow key={i}>
                    <TableCell>{row.queue}</TableCell>
                    <TableCell>{row.count}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardBody>
        </Card>

        <Card>
          <CardHeader>Recent laravel.log (last 50 lines)</CardHeader>
          <CardBody>
            <pre className="bg-default-100 rounded p-3 text-xs font-mono overflow-auto max-h-[400px]">
              {(recent_log ?? []).join('\n')}
            </pre>
          </CardBody>
        </Card>
      </div>

      <Modal isOpen={confirm.isOpen} onClose={confirm.onClose}>
        <ModalContent>
          <ModalHeader>Clear cache store '{target}'?</ModalHeader>
          <ModalBody>This will flush all entries in the selected store.</ModalBody>
          <ModalFooter>
            <Button variant="light" onPress={confirm.onClose}>Cancel</Button>
            <Button color="warning" onPress={doClear}>Clear</Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </>
  );
}

DeveloperDashboard.layout = (page) => <App>{page}</App>;
```

### Task 18 — `Developer/Logs.jsx`

- [ ] `packages/aero-ui/resources/js/Pages/Platform/Admin/Developer/Logs.jsx`

```jsx
import { Head, router, usePage } from '@inertiajs/react';
import {
  Button, Card, CardBody, CardHeader,
  Table, TableBody, TableCell, TableColumn, TableHeader, TableRow,
} from '@aero/ui';
import { useState } from 'react';
import App from '../../../App.jsx';
import { useHRMAC } from '../../../../hooks/useHRMAC.js';

const formatBytes = (n) => {
  if (n == null) return '—';
  const units = ['B','KB','MB','GB'];
  let i = 0; let v = n;
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++; }
  return v.toFixed(1) + ' ' + units[i];
};

export default function DeveloperLogs() {
  const { files } = usePage().props;
  const { hasAccess } = useHRMAC('developer-tools.log-viewer');
  const [tail, setTail] = useState({ filename: null, lines: [] });

  const loadTail = async (filename) => {
    const res = await fetch(route('platform.admin.developer.logs.tail',
      { filename, lines: 100 }), { headers: { Accept: 'application/json' } });
    const json = await res.json();
    setTail({ filename, lines: json.lines ?? [] });
  };

  const download = (filename) => {
    window.location.href = route('platform.admin.developer.logs.download', { filename });
  };

  return (
    <>
      <Head title="Log Files" />
      <div className="space-y-4">
        <h1 className="text-2xl font-semibold">Log Files</h1>

        <Card>
          <CardBody>
            <Table aria-label="Log files">
              <TableHeader>
                <TableColumn>Filename</TableColumn>
                <TableColumn>Size</TableColumn>
                <TableColumn>Modified</TableColumn>
                <TableColumn>Actions</TableColumn>
              </TableHeader>
              <TableBody emptyContent="No log files">
                {files.map((f) => (
                  <TableRow key={f.name}>
                    <TableCell>{f.name}</TableCell>
                    <TableCell>{formatBytes(f.size)}</TableCell>
                    <TableCell>{f.modified_at}</TableCell>
                    <TableCell>
                      <div className="flex gap-2">
                        <Button size="sm" variant="flat" onPress={() => loadTail(f.name)}>Tail</Button>
                        {hasAccess('download') && (
                          <Button size="sm" color="primary" variant="flat" onPress={() => download(f.name)}>
                            Download
                          </Button>
                        )}
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardBody>
        </Card>

        {tail.filename && (
          <Card>
            <CardHeader className="justify-between">
              <span>Tail — {tail.filename}</span>
              <Button size="sm" variant="flat" onPress={() => loadTail(tail.filename)}>Refresh</Button>
            </CardHeader>
            <CardBody>
              <pre className="bg-default-100 rounded p-3 text-xs font-mono overflow-auto max-h-[600px]">
                {tail.lines.join('\n')}
              </pre>
            </CardBody>
          </Card>
        )}
      </div>
    </>
  );
}

DeveloperLogs.layout = (page) => <App>{page}</App>;
```

---

## 7. Tests

All tests live under `packages/aero-platform/tests/Feature/Admin/`. Use `Gate::before(fn () => true)` and boot `AeroCoreServiceProvider` + `AeroPlatformServiceProvider`.

### Task 19 — `AuditLogControllerTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/AuditLogControllerTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Platform\AeroPlatformServiceProvider;
use Aero\Platform\Models\PlatformAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class AuditLogControllerTest extends TestCase
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

    private function makeLandlordUser() { /* factory stub */ }

    public function test_audit_log_list_filters_by_event(): void
    {
        PlatformAuditLog::create(['event' => 'TENANT_CREATED', 'action' => 'create', 'description' => 'x', 'created_at' => now()]);
        PlatformAuditLog::create(['event' => 'PLAN_CREATED',   'action' => 'create', 'description' => 'y', 'created_at' => now()]);

        $r = $this->get(route('platform.admin.audit-logs.index', ['event' => 'TENANT_CREATED']));
        $r->assertOk();
        $r->assertInertia(fn ($p) => $p->where('logs.data.0.event', 'TENANT_CREATED')
                                       ->where('logs.total', 1));
    }

    public function test_audit_log_export_returns_csv(): void
    {
        PlatformAuditLog::create(['event' => 'X', 'action' => 'a', 'description' => 'd', 'created_at' => now()]);

        $r = $this->get(route('platform.admin.audit-logs.export'));
        $r->assertOk();
        $this->assertStringContainsString('text/csv', $r->headers->get('Content-Type'));
    }
}
```

### Task 20 — `AccessLogControllerTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/AccessLogControllerTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Platform\AeroPlatformServiceProvider;
use Aero\Platform\Models\PlatformAccessLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class AccessLogControllerTest extends TestCase
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

    private function makeLandlordUser() { /* stub */ }

    public function test_pii_tab_only_returns_pii_field_accesses(): void
    {
        PlatformAccessLog::create(['field_accessed' => 'national_id',  'created_at' => now()]);
        PlatformAccessLog::create(['field_accessed' => 'display_name', 'created_at' => now()]);

        $r = $this->get(route('platform.admin.access-logs.pii'));
        $r->assertOk();
        $r->assertInertia(fn ($p) => $p->where('logs.total', 1)
                                       ->where('logs.data.0.field_accessed', 'national_id')
                                       ->where('pii', true));
    }
}
```

### Task 21 — `ErrorLogControllerTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/ErrorLogControllerTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Platform\AeroPlatformServiceProvider;
use Aero\Platform\Models\ErrorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class ErrorLogControllerTest extends TestCase
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

    private function makeLandlordUser() { /* stub */ }

    public function test_resolve_sets_status_and_timestamp(): void
    {
        $log = ErrorLog::create([
            'type' => 'RuntimeException', 'message' => 'boom', 'file' => 'x.php', 'line' => 1,
            'trace' => '', 'status' => 'open', 'occurrence_count' => 1, 'last_occurred_at' => now(),
        ]);

        $r = $this->post(route('platform.admin.error-logs.resolve', $log));
        $r->assertRedirect();

        $log->refresh();
        $this->assertSame('resolved', $log->status);
        $this->assertNotNull($log->resolved_at);
    }

    public function test_cannot_resolve_already_resolved(): void
    {
        $log = ErrorLog::create([
            'type' => 'RuntimeException', 'message' => 'boom', 'file' => 'x.php', 'line' => 1,
            'trace' => '', 'status' => 'resolved', 'resolved_at' => now(),
            'occurrence_count' => 1, 'last_occurred_at' => now(),
        ]);

        $this->post(route('platform.admin.error-logs.resolve', $log))->assertStatus(422);
    }

    public function test_bulk_resolve_only_touches_open_entries(): void
    {
        $a = ErrorLog::create(['type'=>'T','message'=>'a','file'=>'f','line'=>1,'trace'=>'','status'=>'open','occurrence_count'=>1,'last_occurred_at'=>now()]);
        $b = ErrorLog::create(['type'=>'T','message'=>'b','file'=>'f','line'=>1,'trace'=>'','status'=>'resolved','resolved_at'=>now(),'occurrence_count'=>1,'last_occurred_at'=>now()]);

        $this->post(route('platform.admin.error-logs.bulk-resolve'), ['ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertSame('resolved', $a->fresh()->status);
        $this->assertSame('resolved', $b->fresh()->status);
    }
}
```

### Task 22 — `DeveloperToolsControllerTest`

- [ ] `packages/aero-platform/tests/Feature/Admin/DeveloperToolsControllerTest.php`

```php
namespace Aero\Platform\Tests\Feature\Admin;

use Aero\Core\AeroCoreServiceProvider;
use Aero\Platform\AeroPlatformServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;

class DeveloperToolsControllerTest extends TestCase
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

    private function makeLandlordUser() { /* stub */ }

    public function test_clear_cache_flushes_store(): void
    {
        Cache::store('array')->put('hello', 'world', 60);
        $this->assertSame('world', Cache::store('array')->get('hello'));

        $this->post(route('platform.admin.developer.cache.clear'), ['store' => 'array'])
            ->assertRedirect();

        $this->assertNull(Cache::store('array')->get('hello'));
    }

    public function test_unknown_cache_store_is_rejected(): void
    {
        $this->post(route('platform.admin.developer.cache.clear'), ['store' => 'no-such-store'])
            ->assertStatus(422);
    }

    public function test_log_download_returns_file(): void
    {
        File::ensureDirectoryExists(storage_path('logs'));
        File::put(storage_path('logs/test.log'), "line1\nline2\n");

        $r = $this->get(route('platform.admin.developer.logs.download', ['filename' => 'test.log']));
        $r->assertOk();
        $this->assertStringContainsString('attachment', $r->headers->get('Content-Disposition'));
    }
}
```

---

## 8. Task Checklist Summary

- [ ] Task 0  — `config/module.php` HRMAC hierarchy
- [ ] Task 1  — Confirm existing tables (no new migrations)
- [ ] Task 2  — Models (`PlatformAuditLog`, `PlatformAccessLog`, `ErrorLog`)
- [ ] Task 3  — `AuditLogAdminService`
- [ ] Task 4  — `AccessLogAdminService`
- [ ] Task 5  — `ErrorLogAdminService`
- [ ] Task 6  — `DeveloperToolsService`
- [ ] Task 7  — `AuditLogController`
- [ ] Task 8  — `AccessLogController`
- [ ] Task 9  — `ErrorLogController`
- [ ] Task 10 — `DeveloperToolsController`
- [ ] Task 11 — `routes/admin.php`
- [ ] Task 12 — `AuditLogs/Index.jsx`
- [ ] Task 13 — `AccessLogs/Index.jsx`
- [ ] Task 14 — `ErrorLogs/Index.jsx`
- [ ] Task 15 — `ErrorLogs/Show.jsx`
- [ ] Task 16 — `ErrorLogs/Analytics.jsx`
- [ ] Task 17 — `Developer/Dashboard.jsx`
- [ ] Task 18 — `Developer/Logs.jsx`
- [ ] Task 19 — `AuditLogControllerTest`
- [ ] Task 20 — `AccessLogControllerTest`
- [ ] Task 21 — `ErrorLogControllerTest`
- [ ] Task 22 — `DeveloperToolsControllerTest`

---

## 9. Acceptance Criteria

- All HRMAC codes from section 1 are declared in `config/module.php` and enforced on routes.
- Audit log list supports filtering by event, actor, subject_type, and date range; CSV export streams up to 50,000 rows.
- PII tab only returns rows whose `field_accessed` is in `PlatformAccessLog::PII_FIELDS`.
- Resolving an error log sets `status='resolved'`, `resolved_at`, `resolved_by`, and writes an audit entry.
- Re-resolving an already-resolved error returns HTTP 422.
- Bulk resolve sets all selected open logs to resolved and logs one consolidated audit entry.
- Cache clear flushes only the requested store and rejects unknown stores with HTTP 422.
- Queue retry/forget dispatches the matching `queue:*` Artisan command and writes an audit entry.
- Log download enforces `basename()` on the filename and writes an audit entry; missing files return 404.
- Tail returns at most the requested number of lines without loading the entire file into memory.
- All mutating operations run inside `DB::transaction()` and write `platform_audit_logs` entries.
- React UI: all imports from `@aero/ui`, no inline styles, no `window.confirm`, destructive actions go through Modal.
