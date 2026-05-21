<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\PlatformAccessLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccessLogAdminService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return PlatformAccessLog::query()
            ->byResourceType($filters['resource_type'] ?? null)
            ->dateRange($filters['from'] ?? null, $filters['to'] ?? null)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function listPii(array $filters): LengthAwarePaginator
    {
        return PlatformAccessLog::query()
            ->piiOnly()
            ->byResourceType($filters['resource_type'] ?? null)
            ->dateRange($filters['from'] ?? null, $filters['to'] ?? null)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
    }

    public function export(array $filters, bool $piiOnly, int $actorId): StreamedResponse
    {
        $query = PlatformAccessLog::query()
            ->byResourceType($filters['resource_type'] ?? null)
            ->dateRange($filters['from'] ?? null, $filters['to'] ?? null);

        if ($piiOnly) {
            $query->piiOnly();
        }

        $rows = $query->orderByDesc('created_at')->limit(50000)->get();

        $this->audit->log(
            event: $piiOnly ? 'PII_ACCESS_LOG_EXPORTED' : 'ACCESS_LOG_EXPORTED',
            action: 'export',
            subject: null,
            description: ($piiOnly ? 'PII ' : '')."Access log exported by actor {$actorId} ({$rows->count()} rows)",
        );

        $filename = ($piiOnly ? 'pii-access-' : 'access-').now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'created_at', 'actor_id', 'actor_name', 'resource_type', 'resource_id', 'subject_label', 'fields_accessed', 'actor_ip']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id,
                    optional($r->created_at)->toIso8601String(),
                    $r->actor_id,
                    $r->actor_name,
                    $r->resource_type,
                    $r->resource_id,
                    $r->subject_label,
                    is_array($r->fields_accessed) ? implode(',', $r->fields_accessed) : $r->fields_accessed,
                    $r->actor_ip,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
