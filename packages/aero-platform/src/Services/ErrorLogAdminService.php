<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\ErrorLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ErrorLogAdminService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = ErrorLog::query();

        // Map plan-schema 'status' filter to actual is_resolved column
        if (isset($filters['status'])) {
            $query->where('is_resolved', $filters['status'] === 'resolved');
        }

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('error_type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        return $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function show(int $id): ErrorLog
    {
        return ErrorLog::findOrFail($id);
    }

    public function resolve(ErrorLog $log, int $actorId): ErrorLog
    {
        if ($log->is_resolved) {
            abort(422, 'Error log is already resolved');
        }

        return DB::transaction(function () use ($log, $actorId): ErrorLog {
            $log->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolved_by' => $actorId,
            ]);

            $this->audit->log(
                event: 'ERROR_LOG_RESOLVED',
                action: 'resolve',
                subject: $log,
                description: "Error log #{$log->id} resolved by actor {$actorId}",
            );

            return $log->fresh();
        });
    }

    public function delete(ErrorLog $log, int $actorId): void
    {
        DB::transaction(function () use ($log, $actorId): void {
            $this->audit->log(
                event: 'ERROR_LOG_DELETED',
                action: 'delete',
                subject: $log,
                description: "Error log #{$log->id} deleted by actor {$actorId}",
            );
            $log->delete();
        });
    }

    public function bulkResolve(array $ids, int $actorId): int
    {
        return DB::transaction(function () use ($ids, $actorId): int {
            $count = ErrorLog::whereIn('id', $ids)
                ->where('is_resolved', false)
                ->update([
                    'is_resolved' => true,
                    'resolved_at' => now(),
                    'resolved_by' => $actorId,
                ]);

            $this->audit->log(
                event: 'ERROR_LOG_BULK_RESOLVED',
                action: 'resolve',
                subject: null,
                description: "Bulk resolved {$count} error logs by actor {$actorId}",
            );

            return $count;
        });
    }

    public function bulkDelete(array $ids, int $actorId): int
    {
        return DB::transaction(function () use ($ids, $actorId): int {
            $count = ErrorLog::whereIn('id', $ids)->count();
            ErrorLog::whereIn('id', $ids)->delete();

            $this->audit->log(
                event: 'ERROR_LOG_BULK_DELETED',
                action: 'delete',
                subject: null,
                description: "Bulk deleted {$count} error logs by actor {$actorId}",
            );

            return $count;
        });
    }

    public function analytics(): array
    {
        $byType = ErrorLog::query()
            ->select('error_type as type', DB::raw('COUNT(*) as count'))
            ->groupBy('error_type')
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
            'by_type' => $byType,
            'trend' => $trend,
            'top_tenants' => $topTenants,
            'open_count' => ErrorLog::where('is_resolved', false)->count(),
            'resolved_count' => ErrorLog::where('is_resolved', true)->count(),
        ];
    }
}
