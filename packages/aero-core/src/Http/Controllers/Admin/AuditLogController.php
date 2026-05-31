<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->get('tab', 'business');
        $perPage = (int) $request->get('per_page', 20);
        $search = (string) $request->get('search', '');
        $actorId = $request->get('actor_id');
        $eventType = $request->get('event_type');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        [$logs, $meta] = match ($tab) {
            'business' => $this->getBusinessLogs($perPage, $search, $actorId, $eventType, $dateFrom, $dateTo),
            'model' => $this->getModelActivityLogs($perPage, $search, $actorId, $dateFrom, $dateTo),
            'access' => $this->getAccessLogs($perPage, $search, $actorId, $dateFrom, $dateTo),
            default => $this->getBusinessLogs($perPage, $search, $actorId, $eventType, $dateFrom, $dateTo),
        };

        return Inertia::render('Core/AuditLogs/Index', [
            'title' => 'Audit Logs',
            'stats' => $this->getStats(),
            'tab' => $tab,
            'logs' => $logs,
            'meta' => $meta,
            'filters' => $request->only(['search', 'actor_id', 'event_type', 'date_from', 'date_to']),
        ]);
    }

    public function activityLogs(Request $request): JsonResponse
    {
        [$logs, $meta] = $this->getBusinessLogs(
            (int) $request->get('per_page', 20),
            (string) $request->get('search', ''),
            $request->get('actor_id'),
            $request->get('event_type'),
            $request->get('date_from'),
            $request->get('date_to'),
        );

        return response()->json(['data' => $logs, 'meta' => $meta]);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->getStats());
    }

    /**
     * CA-3: Security log Inertia view (auth.* / security.* events).
     */
    public function security(Request $request): Response
    {
        $perPage = (int) $request->get('per_page', 30);
        $search = (string) $request->get('search', '');

        $logs = [];
        $meta = $this->emptyMeta($perPage);

        if ($this->tableExists('audit_logs')) {
            $q = DB::table('audit_logs')
                ->where(function ($q) {
                    $q->where('event_type', 'like', 'auth.%')
                        ->orWhere('event_type', 'like', 'security.%');
                })
                ->when($search, fn ($q) => $q->where('actor_name', 'like', "%{$search}%"))
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();

            $logs = $q->items();
            $meta = [
                'current_page' => $q->currentPage(),
                'last_page' => $q->lastPage(),
                'per_page' => $q->perPage(),
                'total' => $q->total(),
            ];
        }

        return Inertia::render('Core/AuditLogs/Security', [
            'title' => 'Security Logs',
            'logs' => $logs,
            'meta' => $meta,
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * CA-3: Failed-jobs / queue monitor.
     */
    public function queues(Request $request): Response
    {
        $perPage = (int) $request->get('per_page', 30);
        $jobs = [];
        $meta = $this->emptyMeta($perPage);

        if ($this->tableExists('failed_jobs')) {
            $q = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->paginate($perPage)
                ->withQueryString();
            $jobs = $q->items();
            $meta = [
                'current_page' => $q->currentPage(),
                'last_page' => $q->lastPage(),
                'per_page' => $q->perPage(),
                'total' => $q->total(),
            ];
        }

        return Inertia::render('Core/AuditLogs/Queues', [
            'title' => 'Queue Monitor',
            'failed_jobs' => $jobs,
            'meta' => $meta,
        ]);
    }

    /**
     * CA-3: Retry an individual failed job.
     */
    public function retryJob(string $id, Request $request): RedirectResponse
    {
        try {
            Artisan::call('queue:retry', ['id' => [$id]]);
        } catch (\Throwable $e) {
            Log::warning('Queue retry failed', ['id' => $id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Failed to retry job: '.$e->getMessage());
        }

        return back()->with('success', "Job {$id} queued for retry.");
    }

    /**
     * CA-3: Flush all failed jobs.
     */
    public function flushQueue(Request $request): RedirectResponse
    {
        try {
            Artisan::call('queue:flush');
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to flush: '.$e->getMessage());
        }

        return back()->with('success', 'Failed queue flushed.');
    }

    /**
     * CA-3: Export audit logs as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $filename = 'audit-logs-'.date('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Event Type', 'Actor', 'Action', 'Subject', 'IP', 'Created At']);

            if ($this->tableExists('audit_logs')) {
                DB::table('audit_logs')
                    ->when($request->event_type, fn ($q, $t) => $q->where('event_type', $t))
                    ->when($request->date_from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                    ->when($request->date_to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
                    ->orderByDesc('created_at')
                    ->chunk(500, function ($rows) use ($out) {
                        foreach ($rows as $row) {
                            fputcsv($out, [
                                $row->id ?? '',
                                $row->event_type ?? '',
                                $row->actor_name ?? '',
                                $row->action ?? '',
                                $row->subject_label ?? '',
                                $row->actor_ip ?? '',
                                $row->created_at ?? '',
                            ]);
                        }
                    });
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Legacy alias kept for backwards compatibility with prior security log JSON endpoint.
     */
    public function securityLogs(Request $request): JsonResponse
    {
        if (! $this->tableExists('audit_logs')) {
            return response()->json(['data' => [], 'meta' => $this->emptyMeta((int) $request->get('per_page', 20))]);
        }

        $query = DB::table('audit_logs')
            ->where(function ($q) {
                $q->where('event_type', 'like', 'auth.%')
                    ->orWhere('event_type', 'like', 'security.%');
            })
            ->orderByDesc('created_at')
            ->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'data' => $query->items(),
            'meta' => [
                'current_page' => $query->currentPage(),
                'last_page' => $query->lastPage(),
                'per_page' => $query->perPage(),
                'total' => $query->total(),
            ],
        ]);
    }

    /**
     * Legacy stub: export activity logs (CSV) — proxies to {@see export()}.
     */
    public function exportActivityLogs(Request $request): StreamedResponse
    {
        return $this->export($request);
    }

    /**
     * Legacy stub: export security logs (CSV).
     */
    public function exportSecurityLogs(Request $request): StreamedResponse
    {
        $filename = 'security-logs-'.date('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Event Type', 'Actor', 'IP', 'User Agent', 'Created At']);

            if ($this->tableExists('audit_logs')) {
                DB::table('audit_logs')
                    ->where(function ($q) {
                        $q->where('event_type', 'like', 'auth.%')
                            ->orWhere('event_type', 'like', 'security.%');
                    })
                    ->orderByDesc('created_at')
                    ->chunk(500, function ($rows) use ($out) {
                        foreach ($rows as $row) {
                            fputcsv($out, [
                                $row->id ?? '',
                                $row->event_type ?? '',
                                $row->actor_name ?? '',
                                $row->actor_ip ?? '',
                                $row->actor_user_agent ?? '',
                                $row->created_at ?? '',
                            ]);
                        }
                    });
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

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
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            $query->items(),
            [
                'current_page' => $query->currentPage(),
                'last_page' => $query->lastPage(),
                'per_page' => $query->perPage(),
                'total' => $query->total(),
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
            ->when($dateTo, fn ($q) => $q->whereDate('activity_log.created_at', '<=', $dateTo))
            ->orderByDesc('activity_log.created_at')
            ->paginate($perPage);

        return [
            $query->items(),
            [
                'current_page' => $query->currentPage(),
                'last_page' => $query->lastPage(),
                'per_page' => $query->perPage(),
                'total' => $query->total(),
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
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return [
            $query->items(),
            [
                'current_page' => $query->currentPage(),
                'last_page' => $query->lastPage(),
                'per_page' => $query->perPage(),
                'total' => $query->total(),
            ],
        ];
    }

    private function getStats(): array
    {
        return [
            'business_events_today' => $this->tableExists('audit_logs')
                ? DB::table('audit_logs')->whereDate('created_at', today())->count() : 0,
            'business_events_total' => $this->tableExists('audit_logs')
                ? DB::table('audit_logs')->count() : 0,
            'model_changes_today' => $this->tableExists('activity_log')
                ? DB::table('activity_log')->whereDate('created_at', today())->count() : 0,
            'sensitive_accesses_today' => $this->tableExists('access_logs')
                ? DB::table('access_logs')->whereDate('created_at', today())->count() : 0,
            'active_users_today' => $this->tableExists('sessions')
                ? DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count('user_id') : 0,
        ];
    }

    private function emptyMeta(int $perPage): array
    {
        return ['current_page' => 1, 'last_page' => 1, 'per_page' => $perPage, 'total' => 0];
    }

    private function tableExists(string $table): bool
    {
        return DB::getSchemaBuilder()->hasTable($table);
    }
}
