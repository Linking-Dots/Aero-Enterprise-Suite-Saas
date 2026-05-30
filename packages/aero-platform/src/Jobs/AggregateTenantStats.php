<?php

namespace Aero\Platform\Jobs;

use Aero\Platform\Models\PlatformStatDaily;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Models\TenantStat;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AggregateTenantStats Job
 *
 * Collects and aggregates statistics from all tenant databases into the
 * central landlord database. This job runs daily and creates a snapshot
 * of key metrics for platform-wide analytics and reporting.
 *
 * Memory-efficient chunking is used to handle large numbers of tenants.
 * Each tenant is processed in isolation with proper error handling to
 * ensure one tenant's failure doesn't affect others.
 */
class AggregateTenantStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Dedicated maintenance queue (Axis C C3) — keeps long batch work off 'default'. */
    public string $queue = 'maintenance';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [60, 300, 600];

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 10;

    /**
     * The date to aggregate stats for.
     */
    protected Carbon $date;

    /**
     * Chunk size for processing tenants.
     */
    protected int $chunkSize = 10;

    /**
     * Statistics counters.
     */
    protected int $processedCount = 0;

    protected int $errorCount = 0;

    protected array $failedTenants = [];

    /**
     * Create a new job instance.
     */
    public function __construct(?Carbon $date = null)
    {
        $this->date = $date ?? Carbon::today();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        Log::info('Starting tenant stats aggregation', [
            'date' => $this->date->toDateString(),
            'started_at' => now()->toIso8601String(),
        ]);

        try {
            $this->processTenants();
            $this->aggregatePlatformStats();
            $this->logSummary($startTime);
        } catch (Throwable $e) {
            Log::error('Fatal error in tenant stats aggregation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process all tenants in memory-efficient chunks.
     */
    protected function processTenants(): void
    {
        Tenant::query()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->chunk($this->chunkSize, function ($tenants) {
                foreach ($tenants as $tenant) {
                    $this->processOneTenant($tenant);
                }
            });
    }

    /**
     * Process a single tenant with error isolation.
     */
    protected function processOneTenant(Tenant $tenant): void
    {
        try {
            Log::debug('Processing tenant stats', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
            ]);

            // Initialize tenancy context to switch to tenant database
            tenancy()->initialize($tenant);

            // Collect stats from tenant database
            $stats = $this->collectTenantStats($tenant);

            // End tenancy context before writing to landlord database
            tenancy()->end();

            // Save stats to landlord database
            $this->saveTenantStats($tenant, $stats);

            $this->processedCount++;

            Log::debug('Tenant stats collected successfully', [
                'tenant_id' => $tenant->id,
                'stats' => $stats,
            ]);
        } catch (Throwable $e) {
            // Ensure we end tenancy context even on error
            try {
                tenancy()->end();
            } catch (Throwable) {
                // Ignore errors when ending tenancy
            }

            $this->errorCount++;
            $this->failedTenants[] = [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'error' => $e->getMessage(),
            ];

            Log::error('Failed to process tenant stats', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Continue to next tenant - don't stop the whole job
        }
    }

    /**
     * Collect statistics from the tenant database.
     */
    protected function collectTenantStats(Tenant $tenant): array
    {
        // User metrics
        $totalUsers = DB::table('users')->count();
        $activeUsers = DB::table('users')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('last_login_at')
                    ->where('last_login_at', '>=', now()->subDays(30));
            })
            ->count();

        // Project metrics
        $activeProjects = DB::table('projects')
            ->whereIn('status', ['in_progress', 'active', 'not_started'])
            ->where('is_archived', false)
            ->count();

        // Employee count (from HR module)
        $totalEmployees = $totalUsers; // In this system, users are employees

        // Document count from media library (DMS)
        $totalDocuments = DB::table('media')->count();

        // Storage usage (sum of media file sizes in bytes, convert to MB)
        $storageBytes = DB::table('media')->sum('size') ?? 0;
        $storageUsedMb = (int) ceil($storageBytes / (1024 * 1024));

        // Revenue metrics - check if payment/invoice tables exist
        $totalRevenue = 0;
        $mrr = 0;

        // Check if the tenant has a subscription and get revenue from Stripe
        // This would be from the landlord database, but we approximate based on plan
        if ($tenant->stripe_id) {
            // MRR is based on subscription price from landlord context
            // We'll calculate this in the aggregation step instead
        }

        // API request count - if we're tracking this (optional)
        $apiRequests = 0;

        // Module usage tracking
        $moduleUsage = $this->getModuleUsage();

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'active_projects' => $activeProjects,
            'total_documents' => $totalDocuments,
            'total_employees' => $totalEmployees,
            'storage_used_mb' => $storageUsedMb,
            'api_requests' => $apiRequests,
            'total_revenue' => $totalRevenue,
            'mrr' => $mrr,
            'module_usage' => $moduleUsage,
        ];
    }

    /**
     * Get module-specific usage statistics.
     */
    protected function getModuleUsage(): array
    {
        $usage = [];

        // HR Module usage
        $usage['hr'] = [
            'attendance_records' => $this->safeTableCount('attendances'),
            'leave_requests' => $this->safeTableCount('leaves'),
            'departments' => $this->safeTableCount('departments'),
        ];

        // Project Management usage
        $usage['project_management'] = [
            'projects' => $this->safeTableCount('projects'),
            'tasks' => $this->safeTableCount('tasks'),
            'daily_works' => $this->safeTableCount('daily_works'),
        ];

        // DMS usage
        $usage['dms'] = [
            'documents' => $this->safeTableCount('media'),
            'folders' => $this->safeTableCount('folders'),
        ];

        // Quality Management
        $usage['quality'] = [
            'ncrs' => $this->safeTableCount('ncrs'),
            'cars' => $this->safeTableCount('cars'),
        ];

        return $usage;
    }

    /**
     * Safely count records in a table (returns 0 if table doesn't exist).
     */
    protected function safeTableCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Save tenant stats to the landlord database.
     */
    protected function saveTenantStats(Tenant $tenant, array $stats): void
    {
        TenantStat::recordStats(
            tenantId: $tenant->id,
            stats: $stats,
            date: $this->date->toDateString()
        );
    }

    /**
     * Aggregate platform-wide stats after processing all tenants.
     */
    protected function aggregatePlatformStats(): void
    {
        try {
            PlatformStatDaily::aggregateFromTenantStats($this->date->toDateString());

            Log::info('Platform stats aggregated successfully', [
                'date' => $this->date->toDateString(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to aggregate platform stats', [
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log a summary of the aggregation job.
     */
    protected function logSummary(float $startTime): void
    {
        $duration = round(microtime(true) - $startTime, 2);

        $summary = [
            'date' => $this->date->toDateString(),
            'duration_seconds' => $duration,
            'tenants_processed' => $this->processedCount,
            'tenants_failed' => $this->errorCount,
            'failed_tenants' => $this->failedTenants,
            'completed_at' => now()->toIso8601String(),
        ];

        if ($this->errorCount > 0) {
            Log::warning('Tenant stats aggregation completed with errors', $summary);
        } else {
            Log::info('Tenant stats aggregation completed successfully', $summary);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::critical('Tenant stats aggregation job failed completely', [
            'date' => $this->date->toDateString(),
            'tenants_processed' => $this->processedCount,
            'tenants_failed' => $this->errorCount,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'aggregate-stats',
            'date:'.$this->date->toDateString(),
        ];
    }
}
