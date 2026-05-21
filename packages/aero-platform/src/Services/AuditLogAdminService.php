<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\PlatformAuditLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogAdminService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = PlatformAuditLog::query();

        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['actor_id'])) {
            $query->where('actor_id', (int) $filters['actor_id']);
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
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

    public function show(int $id): PlatformAuditLog
    {
        return PlatformAuditLog::findOrFail($id);
    }

    public function export(array $filters, int $actorId): StreamedResponse
    {
        $query = PlatformAuditLog::query();

        if (! empty($filters['event_type'])) {
            $query->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['actor_id'])) {
            $query->where('actor_id', (int) $filters['actor_id']);
        }

        if (! empty($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        $rows = $query->orderByDesc('created_at')->limit(50000)->get();

        $this->audit->log(
            event: 'AUDIT_LOG_EXPORTED',
            action: 'export',
            subject: null,
            description: "Audit log exported by actor {$actorId} ({$rows->count()} rows)",
        );

        $filename = 'audit-logs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'created_at', 'event_type', 'action', 'actor_id', 'subject_type', 'subject_id', 'description', 'actor_ip']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    optional($r->created_at)->toIso8601String(),
                    $r->event_type,
                    $r->action,
                    $r->actor_id,
                    $r->subject_type,
                    $r->subject_id,
                    $r->description,
                    $r->actor_ip,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
