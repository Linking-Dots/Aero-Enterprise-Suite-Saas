<?php

declare(strict_types=1);

namespace Aero\Platform\Jobs;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantStat;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Collect + persist stats for ONE tenant (Axis C C7).
 *
 * AggregateTenantStats previously processed every tenant serially in a single
 * job — one slow/broken tenant delayed or failed the whole run, and per-tenant
 * connection switching ran sequentially. This fan-out job handles a single
 * tenant so the work parallelizes across the 'maintenance' queue workers and a
 * failure is isolated to that tenant (the batch continues).
 */
class AggregateOneTenantStats implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var array<int,int> */
    public array $backoff = [60, 300, 600];

    public function __construct(
        public readonly string $tenantId,
        public readonly string $date,
    ) {
        $this->onQueue('maintenance'); // Axis C C3
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $tenant = Tenant::find($this->tenantId);
        if (! $tenant) {
            return;
        }

        try {
            tenancy()->initialize($tenant);
            $stats = $this->collectTenantStats($tenant);
            tenancy()->end();

            $this->saveTenantStats($tenant, $stats);
        } catch (Throwable $e) {
            try {
                tenancy()->end();
            } catch (Throwable) {
                // ignore
            }

            Log::error('Failed to process tenant stats', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
            ]);

            throw $e; // let the queue retry this tenant; siblings are unaffected
        }
    }

    /**
     * @return array<string,mixed>
     */
    protected function collectTenantStats(Tenant $tenant): array
    {
        $totalUsers = DB::table('users')->count();
        $activeUsers = DB::table('users')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('last_login_at')
                    ->where('last_login_at', '>=', now()->subDays(30));
            })
            ->count();

        $activeProjects = DB::table('projects')
            ->whereIn('status', ['in_progress', 'active', 'not_started'])
            ->where('is_archived', false)
            ->count();

        $totalDocuments = DB::table('media')->count();
        $storageBytes = DB::table('media')->sum('size') ?? 0;
        $storageUsedMb = (int) ceil($storageBytes / (1024 * 1024));

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'active_projects' => $activeProjects,
            'total_documents' => $totalDocuments,
            'total_employees' => $totalUsers, // users are employees in this system
            'storage_used_mb' => $storageUsedMb,
            'api_requests' => 0,
            'total_revenue' => 0,
            'mrr' => 0,
            'module_usage' => $this->getModuleUsage(),
        ];
    }

    /**
     * @return array<string,array<string,int>>
     */
    protected function getModuleUsage(): array
    {
        return [
            'hr' => [
                'attendance_records' => $this->safeTableCount('attendances'),
                'leave_requests' => $this->safeTableCount('leaves'),
                'departments' => $this->safeTableCount('departments'),
            ],
            'project_management' => [
                'projects' => $this->safeTableCount('projects'),
                'tasks' => $this->safeTableCount('tasks'),
                'daily_works' => $this->safeTableCount('daily_works'),
            ],
            'dms' => [
                'documents' => $this->safeTableCount('media'),
                'folders' => $this->safeTableCount('folders'),
            ],
            'quality' => [
                'ncrs' => $this->safeTableCount('ncrs'),
                'cars' => $this->safeTableCount('cars'),
            ],
        ];
    }

    protected function safeTableCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @param  array<string,mixed>  $stats
     */
    protected function saveTenantStats(Tenant $tenant, array $stats): void
    {
        TenantStat::recordStats(
            tenantId: $tenant->id,
            stats: $stats,
            date: $this->date,
        );
    }
}
