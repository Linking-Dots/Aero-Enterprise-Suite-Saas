<?php

namespace Aero\Core\Http\Controllers\Admin;

use Aero\Core\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

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
