<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\PlatformAuditLog;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogAdminService
{
    public function __construct(private readonly AuditServiceInterface $audit) {}

    /**
     * Command-centre payload for the /audit-logs console — KPIs, activity trend,
     * category + actor breakdowns, and the recent event stream (client-side
     * workbench). Read-only; audit records are immutable.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $now = Carbon::now();
        $since = $now->copy()->subDays(90)->startOfDay();

        // Bounded window for the stream/workbench (90 days, capped).
        $rows = PlatformAuditLog::where('created_at', '>=', $since)
            ->orderByDesc('created_at')->limit(500)
            ->get(['id', 'event_type', 'action', 'actor_id', 'actor_name', 'actor_ip', 'subject_type', 'subject_label', 'description', 'created_at']);

        $total = PlatformAuditLog::count();
        $last30 = PlatformAuditLog::where('created_at', '>=', $now->copy()->subDays(30))->count();
        $today = PlatformAuditLog::whereDate('created_at', $now->toDateString())->count();

        $stream = $rows->map(fn ($r) => [
            'id'          => $r->id,
            'event_type'  => $r->event_type,
            'action'      => $r->action,
            'actor'       => $r->actor_name ?: 'system',
            'actor_ip'    => $r->actor_ip,
            'subject'     => $r->subject_label ?: ($r->subject_type ? class_basename($r->subject_type) : '—'),
            'description' => $r->description,
            'category'    => $this->category($r->event_type),
            'severity'    => $this->severity($r->event_type),
            'at'          => optional($r->created_at)->toDateTimeString(),
        ])->all();

        return [
            'kpis' => [
                'events_30d'    => $last30,
                'total'         => $total,
                'today'         => $today,
                'actors'        => (int) PlatformAuditLog::distinct()->count('actor_name'),
                'event_types'   => (int) PlatformAuditLog::distinct()->count('event_type'),
                'security'      => collect($stream)->where('severity', '!=', 'info')->count(),
                'impersonations' => PlatformAuditLog::where('event_type', 'like', 'impersonation%')->count(),
            ],
            'trend'      => $this->dailyTrend($now),
            'categories' => $this->categoryBreakdown($stream),
            'top_actors' => $this->topActors(),
            'event_types' => PlatformAuditLog::select('event_type')->distinct()->orderBy('event_type')->pluck('event_type')->all(),
            'stream'     => $stream,
        ];
    }

    /**
     * Full record for the drawer — includes the state diff, changed fields,
     * request context and metadata.
     *
     * @return array<string, mixed>
     */
    public function detail(int $id): array
    {
        $r = PlatformAuditLog::findOrFail($id);

        return [
            'id'           => $r->id,
            'event_type'   => $r->event_type,
            'action'       => $r->action,
            'actor'        => $r->actor_name ?: 'system',
            'actor_id'     => $r->actor_id,
            'actor_ip'     => $r->actor_ip,
            'user_agent'   => $r->actor_user_agent,
            'subject'      => $r->subject_label ?: '—',
            'subject_type' => $r->subject_type ? class_basename($r->subject_type) : null,
            'subject_id'   => $r->subject_id,
            'description'  => $r->description,
            'category'     => $this->category($r->event_type),
            'severity'     => $this->severity($r->event_type),
            'url'          => $r->url,
            'http_method'  => $r->http_method,
            'session_id'   => $r->session_id,
            'before_state' => $this->decode($r->before_state),
            'after_state'  => $this->decode($r->after_state),
            'changed_fields' => $this->decode($r->changed_fields),
            'metadata'     => $this->decode($r->metadata),
            'at'           => optional($r->created_at)->toDateTimeString(),
        ];
    }

    /** @return array{labels: array, counts: array} */
    private function dailyTrend(Carbon $now): array
    {
        $rows = PlatformAuditLog::where('created_at', '>=', $now->copy()->subDays(13)->startOfDay())
            ->get(['created_at']);
        $buckets = [];
        for ($i = 13; $i >= 0; $i--) {
            $buckets[$now->copy()->subDays($i)->format('Y-m-d')] = 0;
        }
        foreach ($rows as $r) {
            $k = Carbon::parse($r->created_at)->format('Y-m-d');
            if (isset($buckets[$k])) {
                $buckets[$k]++;
            }
        }

        return [
            'labels' => array_map(fn ($d) => Carbon::parse($d)->format('M j'), array_keys($buckets)),
            'counts' => array_values($buckets),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $stream
     * @return array<int, array{key: string, label: string, count: int}>
     */
    private function categoryBreakdown(array $stream): array
    {
        $labels = ['auth' => 'Authentication', 'billing' => 'Billing', 'security' => 'Access/Security', 'tenants' => 'Tenants', 'other' => 'Other'];
        $counts = array_fill_keys(array_keys($labels), 0);
        foreach ($stream as $s) {
            $counts[$s['category']] = ($counts[$s['category']] ?? 0) + 1;
        }
        $out = [];
        foreach ($labels as $key => $label) {
            if ($counts[$key] > 0) {
                $out[] = ['key' => $key, 'label' => $label, 'count' => $counts[$key]];
            }
        }
        usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);

        return $out;
    }

    /** @return array<int, array{actor: string, count: int}> */
    private function topActors(): array
    {
        return PlatformAuditLog::selectRaw('COALESCE(actor_name, ?) actor, COUNT(*) c', ['system'])
            ->groupBy('actor')->orderByDesc('c')->limit(6)->get()
            ->map(fn ($r) => ['actor' => $r->actor, 'count' => (int) $r->c])->all();
    }

    /** Classify an event_type into a category bucket. */
    private function category(string $event): string
    {
        return match (true) {
            str_starts_with($event, 'auth.')                                     => 'auth',
            str_contains($event, 'impersonation') || str_contains($event, 'suspend') || str_contains($event, 'failed') || str_contains($event, 'security') => 'security',
            (bool) preg_match('/plan|refund|coupon|invoice|subscription|payment|billing|gateway/', $event) => 'billing',
            str_starts_with($event, 'tenant')                                    => 'tenants',
            default                                                              => 'other',
        };
    }

    /** Derived severity from the event_type (nothing is stored as "severity"). */
    private function severity(string $event): string
    {
        return match (true) {
            (bool) preg_match('/failed|suspend|deleted|breach/', $event)        => 'crit',
            (bool) preg_match('/impersonation|revoke|rotate|delete|reject|cancel/', $event) => 'warn',
            default                                                              => 'info',
        };
    }

    /** @return mixed */
    private function decode(mixed $value)
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $d = json_decode($value, true);

            return $d ?? $value;
        }

        return null;
    }

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
