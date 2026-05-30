<?php

declare(strict_types=1);

namespace Aero\Platform\Bootstrappers;

use Aero\Platform\Models\Tenant;
use RuntimeException;
use Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;

/**
 * Fail-closed queue tenancy bootstrapper (Audit D5c).
 *
 * Stock QueueTenancyBootstrapper happily initialises tenancy for any tenant
 * ID found in the job payload. If the tenant has since been suspended or
 * hard-deleted (GDPR purge, churn cleanup), the worker still tries to
 * connect — and depending on the connection cache may write to the wrong
 * DB or leak data.
 *
 * This wrapper checks the tenant's lifecycle status BEFORE bootstrapping
 * and refuses to run if the tenant is in a non-runnable state. The job
 * is moved to failed_jobs by the queue worker (no retry — the tenant
 * won't come back).
 */
class FailClosedQueueTenancyBootstrapper extends QueueTenancyBootstrapper
{
    /**
     * Statuses that disqualify a tenant from running queued work.
     *
     * - suspended: payment failure or operator-paused; jobs should not run
     *   even if the row still exists.
     * - cancelled / archived: customer churn; jobs would mutate stale data.
     * - failed: provisioning crashed mid-flight; DB may be inconsistent.
     */
    private const NON_RUNNABLE_STATUSES = [
        Tenant::STATUS_SUSPENDED,
        Tenant::STATUS_CANCELLED,
        Tenant::STATUS_ARCHIVED,
        Tenant::STATUS_FAILED,
    ];

    public function bootstrap(TenantContract $tenant): void
    {
        $status = $tenant->status ?? null;

        if (in_array($status, self::NON_RUNNABLE_STATUSES, true)) {
            throw new RuntimeException(sprintf(
                'Refusing to run queue job for tenant %s with status=%s. '.
                'The tenant is in a non-runnable lifecycle state — the job '.
                'will be moved to failed_jobs and not retried.',
                (string) $tenant->getTenantKey(),
                $status,
            ));
        }

        parent::bootstrap($tenant);
    }
}
