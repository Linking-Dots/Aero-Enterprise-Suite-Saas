<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Marketing;

use Aero\Auth\Models\User;
use Aero\Platform\Models\ProspectLead;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lead Service
 *
 * Manages prospect leads for platform sales.
 */
class LeadService
{
    /**
     * Get paginated leads with filters.
     */
    public function getPaginatedLeads(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ProspectLead::query()->with('assignee');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (! empty($filters['unassigned'])) {
            $query->whereNull('assigned_to');
        }

        if (! empty($filters['min_score'])) {
            $query->where('score', '>=', $filters['min_score']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate($perPage);
    }

    /**
     * Create a new lead.
     */
    public function createLead(array $data): ProspectLead
    {
        $lead = ProspectLead::create($data);
        $lead->calculateScore();

        return $lead;
    }

    /**
     * Update a lead.
     */
    public function updateLead(ProspectLead $lead, array $data): ProspectLead
    {
        $lead->update($data);

        if (array_intersect(array_keys($data), ['source', 'interest_level', 'name', 'company_name', 'phone'])) {
            $lead->calculateScore();
        }

        return $lead->refresh();
    }

    /**
     * Assign lead to a user.
     */
    public function assignLead(ProspectLead $lead, int $userId): bool
    {
        return $lead->update([
            'assigned_to' => $userId,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Bulk assign leads.
     */
    public function bulkAssignLeads(array $leadIds, int $userId): int
    {
        return ProspectLead::whereIn('id', $leadIds)
            ->update([
                'assigned_to' => $userId,
                'last_activity_at' => now(),
            ]);
    }

    /**
     * Convert lead to tenant.
     */
    public function convertLead(ProspectLead $lead, int $tenantId): bool
    {
        return $lead->markAsConverted($tenantId);
    }

    /**
     * Get lead statistics.
     */
    public function getLeadStats(?string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $total = ProspectLead::where('created_at', '>=', $startDate)->count();
        $byStatus = ProspectLead::where('created_at', '>=', $startDate)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $bySource = ProspectLead::where('created_at', '>=', $startDate)
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        $avgScore = ProspectLead::where('created_at', '>=', $startDate)
            ->avg('score') ?? 0;

        $conversionRate = $total > 0
            ? round(($byStatus[ProspectLead::STATUS_CONVERTED] ?? 0) / $total * 100, 2)
            : 0;

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_source' => $bySource,
            'avg_score' => round($avgScore, 1),
            'conversion_rate' => $conversionRate,
            'new' => $byStatus[ProspectLead::STATUS_NEW] ?? 0,
            'qualified' => $byStatus[ProspectLead::STATUS_QUALIFIED] ?? 0,
            'converted' => $byStatus[ProspectLead::STATUS_CONVERTED] ?? 0,
        ];
    }

    /**
     * Get lead funnel data.
     */
    public function getLeadFunnel(?string $period = 'month'): array
    {
        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $stages = [
            ProspectLead::STATUS_NEW => 0,
            ProspectLead::STATUS_CONTACTED => 0,
            ProspectLead::STATUS_QUALIFIED => 0,
            ProspectLead::STATUS_CONVERTED => 0,
        ];

        $counts = ProspectLead::where('created_at', '>=', $startDate)
            ->whereIn('status', array_keys($stages))
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        foreach ($counts as $status => $count) {
            $stages[$status] = $count;
        }

        return array_map(fn ($status, $count) => [
            'status' => $status,
            'label' => ProspectLead::getStatusOptions()[$status] ?? $status,
            'count' => $count,
        ], array_keys($stages), array_values($stages));
    }

    /**
     * Get recent leads.
     */
    public function getRecentLeads(int $limit = 10): Collection
    {
        return ProspectLead::with('assignee')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get high-value leads.
     */
    public function getHighValueLeads(int $minScore = 70, int $limit = 10): Collection
    {
        return ProspectLead::with('assignee')
            ->where('score', '>=', $minScore)
            ->whereIn('status', [ProspectLead::STATUS_NEW, ProspectLead::STATUS_CONTACTED, ProspectLead::STATUS_QUALIFIED])
            ->orderByDesc('score')
            ->limit($limit)
            ->get();
    }

    /**
     * Full command-centre payload: every lead (mapped), pipeline stats,
     * KPI sparklines, source mix, score distribution, funnel, monthly trend,
     * plus the assignee + tenant pick-lists the console needs for its actions.
     */
    public function overview(string $period = '6m'): array
    {
        $leads = ProspectLead::with('assignee:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        $mapped = $leads->map(fn (ProspectLead $l) => $this->mapLead($l))->values()->all();

        $byStatus = $leads->groupBy('status')->map->count();
        $total = $leads->count();
        $converted = (int) ($byStatus[ProspectLead::STATUS_CONVERTED] ?? 0);
        $open = $leads->whereIn('status', [ProspectLead::STATUS_NEW, ProspectLead::STATUS_CONTACTED, ProspectLead::STATUS_QUALIFIED]);

        $stats = [
            'total' => $total,
            'new' => (int) ($byStatus[ProspectLead::STATUS_NEW] ?? 0),
            'contacted' => (int) ($byStatus[ProspectLead::STATUS_CONTACTED] ?? 0),
            'qualified' => (int) ($byStatus[ProspectLead::STATUS_QUALIFIED] ?? 0),
            'converted' => $converted,
            'lost' => (int) ($byStatus[ProspectLead::STATUS_LOST] ?? 0),
            'by_status' => $byStatus->toArray(),
            'avg_score' => round($leads->avg('score') ?? 0, 1),
            'conversion_rate' => $total > 0 ? round($converted / $total * 100, 1) : 0.0,
            'unassigned' => $leads->whereNull('assigned_to')->count(),
            'hot' => $open->where('score', '>=', 70)->count(),
            'open' => $open->count(),
        ];

        // KPI sparklines — weekly counts over the last 8 weeks.
        $sparks = [
            'total' => $this->weekly($leads, 'created_at'),
            'new' => $this->weekly($leads->where('status', ProspectLead::STATUS_NEW), 'created_at'),
            'qualified' => $this->weekly($leads->whereNotNull('qualified_at'), 'qualified_at'),
            'converted' => $this->weekly($leads->whereNotNull('converted_at'), 'converted_at'),
            'hot' => $this->weekly($open->where('score', '>=', 70), 'created_at'),
        ];

        // Source mix (donut) — every source that has leads.
        $sources = collect(ProspectLead::getSourceOptions())
            ->map(fn ($label, $key) => [
                'source' => $key,
                'label' => $label,
                'count' => (int) $leads->where('source', $key)->count(),
            ])
            ->filter(fn ($s) => $s['count'] > 0)
            ->sortByDesc('count')
            ->values()
            ->all();

        // Score distribution histogram.
        $buckets = [
            ['label' => '80–100', 'min' => 80, 'max' => 100],
            ['label' => '60–79', 'min' => 60, 'max' => 79],
            ['label' => '40–59', 'min' => 40, 'max' => 59],
            ['label' => '20–39', 'min' => 20, 'max' => 39],
            ['label' => '0–19', 'min' => 0, 'max' => 19],
        ];
        $scoreDist = array_map(fn ($b) => [
            'label' => $b['label'],
            'count' => $leads->filter(fn ($l) => $l->score >= $b['min'] && $l->score <= $b['max'])->count(),
        ], $buckets);

        // Conversion funnel (all-time) — new → contacted → qualified → converted.
        $funnel = [
            ['status' => 'new', 'label' => 'New', 'count' => $total],
            ['status' => 'contacted', 'label' => 'Contacted', 'count' => $leads->whereIn('status', [ProspectLead::STATUS_CONTACTED, ProspectLead::STATUS_QUALIFIED, ProspectLead::STATUS_CONVERTED])->count() + $leads->whereNotNull('contacted_at')->whereIn('status', [ProspectLead::STATUS_LOST])->count()],
            ['status' => 'qualified', 'label' => 'Qualified', 'count' => $leads->whereIn('status', [ProspectLead::STATUS_QUALIFIED, ProspectLead::STATUS_CONVERTED])->count()],
            ['status' => 'converted', 'label' => 'Converted', 'count' => $converted],
        ];

        // Monthly trend — created vs converted, last 6 months.
        $trend = $this->monthlyTrend($leads);

        $assignees = User::on('central')->select('id', 'name', 'email')->orderBy('name')->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->all();

        $tenants = Tenant::query()->orderByDesc('created_at')->limit(200)->get(['id', 'name'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->all();

        return [
            'leads' => $mapped,
            'stats' => $stats,
            'sparks' => $sparks,
            'sources' => $sources,
            'scoreDist' => $scoreDist,
            'funnel' => $funnel,
            'trend' => $trend,
            'assignees' => $assignees,
            'tenants' => $tenants,
            'statusOptions' => ProspectLead::getStatusOptions(),
            'sourceOptions' => ProspectLead::getSourceOptions(),
        ];
    }

    /**
     * Map a lead to the shape the console consumes.
     */
    private function mapLead(ProspectLead $l): array
    {
        return [
            'id' => $l->id,
            'name' => $l->name,
            'email' => $l->email,
            'company' => $l->company_name,
            'phone' => $l->phone,
            'country' => $l->country,
            'source' => $l->source,
            'source_detail' => $l->source_detail,
            'status' => $l->status,
            'score' => (int) $l->score,
            'interest' => $l->interest_level,
            'interests' => $l->interests ?? [],
            'utm' => $l->utm_data ?? [],
            'notes' => $l->notes,
            'assignee' => $l->assignee ? ['id' => $l->assignee->id, 'name' => $l->assignee->name] : null,
            'assigned_to' => $l->assigned_to,
            'converted_tenant_id' => $l->converted_tenant_id,
            'lost_reason' => $l->metadata['lost_reason'] ?? null,
            'created_at' => optional($l->created_at)->toIso8601String(),
            'contacted_at' => optional($l->contacted_at)->toIso8601String(),
            'qualified_at' => optional($l->qualified_at)->toIso8601String(),
            'converted_at' => optional($l->converted_at)->toIso8601String(),
            'last_activity_at' => optional($l->last_activity_at)->toIso8601String(),
        ];
    }

    /**
     * Weekly counts for a collection over the last 8 weeks (oldest → newest).
     */
    private function weekly(Collection $leads, string $dateField): array
    {
        $weeks = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->startOfWeek()->subWeeks($i);
            $end = (clone $start)->endOfWeek();
            $weeks[] = $leads->filter(function ($l) use ($dateField, $start, $end) {
                $d = $l->{$dateField};

                return $d instanceof Carbon && $d->betweenIncluded($start, $end);
            })->count();
        }

        return $weeks;
    }

    /**
     * Monthly created-vs-converted trend over the last 6 months.
     */
    private function monthlyTrend(Collection $leads): array
    {
        $labels = [];
        $created = [];
        $convertedSeries = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $labels[] = $month->format('M');
            $created[] = $leads->filter(fn ($l) => $l->created_at instanceof Carbon && $l->created_at->isSameMonth($month))->count();
            $convertedSeries[] = $leads->filter(fn ($l) => $l->converted_at instanceof Carbon && $l->converted_at->isSameMonth($month))->count();
        }

        return ['labels' => $labels, 'created' => $created, 'converted' => $convertedSeries];
    }

    /**
     * Apply a lifecycle transition using the model helpers so the matching
     * stage timestamp (contacted_at / qualified_at / converted_at) is set.
     */
    public function transition(ProspectLead $lead, string $status, ?string $reason = null): bool
    {
        return match ($status) {
            ProspectLead::STATUS_CONTACTED => $lead->markAsContacted(),
            ProspectLead::STATUS_QUALIFIED => $lead->markAsQualified(),
            ProspectLead::STATUS_LOST => $lead->markAsLost($reason),
            default => $lead->update(['status' => $status, 'last_activity_at' => now()]),
        };
    }

    /**
     * Bulk lifecycle action across many leads, in one transaction.
     */
    public function bulkAction(array $ids, string $action, ?int $userId = null, ?string $reason = null): int
    {
        return DB::transaction(function () use ($ids, $action, $userId, $reason) {
            $leads = ProspectLead::whereIn('id', $ids)->get();
            $n = 0;
            foreach ($leads as $lead) {
                $ok = match ($action) {
                    'assign' => $userId ? $this->assignLead($lead, $userId) : false,
                    'delete' => (bool) $lead->delete(),
                    'lost' => $lead->markAsLost($reason),
                    default => $this->transition($lead, $action, $reason),
                };
                if ($ok) {
                    $n++;
                }
            }

            return $n;
        });
    }

    /**
     * Create lead from form submission.
     */
    public function createFromFormSubmission(array $formData, array $utmData = []): ProspectLead
    {
        $data = [
            'email' => $formData['email'],
            'name' => $formData['name'] ?? null,
            'company_name' => $formData['company'] ?? $formData['company_name'] ?? null,
            'phone' => $formData['phone'] ?? null,
            'country' => $formData['country'] ?? null,
            'source' => $formData['source'] ?? ProspectLead::SOURCE_WEBSITE,
            'source_detail' => $formData['source_detail'] ?? request()->headers->get('referer'),
            'interest_level' => $formData['interest_level'] ?? null,
            'interests' => $formData['interests'] ?? [],
            'notes' => $formData['message'] ?? $formData['notes'] ?? null,
            'utm_data' => $utmData,
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'form_name' => $formData['form_name'] ?? 'contact',
            ],
        ];

        return $this->createLead($data);
    }
}
