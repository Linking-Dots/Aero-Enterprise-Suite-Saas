<?php

namespace Aero\Platform\Jobs;

use Aero\Platform\Models\PlatformStatDaily;
use Aero\Platform\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AggregateTenantStats Job (Axis C C7 — fan-out dispatcher)
 *
 * Previously processed every active tenant SERIALLY in one job: one slow or
 * broken tenant delayed/failed the whole run, and per-tenant connection
 * switching ran sequentially. Now it dispatches one AggregateOneTenantStats
 * job per tenant onto the 'maintenance' queue as a Bus batch, so the work
 * parallelizes across workers and a single tenant's failure is isolated. The
 * platform-wide rollup runs once in the batch's finally() callback, after all
 * per-tenant jobs have completed.
 */
class AggregateTenantStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int,int> */
    public array $backoff = [60, 300, 600];

    public int $maxExceptions = 10;

    protected Carbon $date;

    /** Tenant id chunk size when building the batch. */
    protected int $chunkSize = 500;

    public function __construct(?Carbon $date = null)
    {
        $this->date = $date ?? Carbon::today();
        $this->onQueue('maintenance'); // Axis C C3
    }

    public function handle(): void
    {
        $date = $this->date->toDateString();

        Log::info('Dispatching tenant stats aggregation batch', ['date' => $date]);

        $jobs = [];
        Tenant::query()
            ->where('status', Tenant::STATUS_ACTIVE)
            ->select('id')
            ->chunk($this->chunkSize, function ($tenants) use (&$jobs, $date) {
                foreach ($tenants as $tenant) {
                    $jobs[] = new AggregateOneTenantStats((string) $tenant->id, $date);
                }
            });

        if ($jobs === []) {
            $this->aggregatePlatformStats($date);

            return;
        }

        Bus::batch($jobs)
            ->name('aggregate-tenant-stats:'.$date)
            ->onQueue('maintenance')
            ->allowFailures() // one tenant's failure must not cancel the batch
            ->finally(function () use ($date): void {
                // Roll up platform-wide stats once every per-tenant job has run.
                try {
                    PlatformStatDaily::aggregateFromTenantStats($date);
                    Log::info('Platform stats aggregated', ['date' => $date]);
                } catch (Throwable $e) {
                    Log::error('Failed to aggregate platform stats', [
                        'date' => $date,
                        'error' => $e->getMessage(),
                    ]);
                }
            })
            ->dispatch();

        Log::info('Tenant stats batch dispatched', [
            'date' => $date,
            'tenant_jobs' => count($jobs),
        ]);
    }

    /**
     * Roll up platform-wide stats (used directly when there are no tenants).
     */
    protected function aggregatePlatformStats(string $date): void
    {
        try {
            PlatformStatDaily::aggregateFromTenantStats($date);
        } catch (Throwable $e) {
            Log::error('Failed to aggregate platform stats', [
                'date' => $date,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::critical('Tenant stats aggregation dispatcher failed', [
            'date' => $this->date->toDateString(),
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * @return array<int,string>
     */
    public function tags(): array
    {
        return ['aggregate-stats', 'date:'.$this->date->toDateString()];
    }
}
