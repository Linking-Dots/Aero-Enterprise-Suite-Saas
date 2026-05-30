<?php

declare(strict_types=1);

namespace Aero\Platform\Services\Tenant;

use Aero\Platform\Models\Tenant;
use Aero\Platform\Support\TenantDatabaseDropGuard;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tenant Purge Service
 *
 * Handles permanent deletion of tenants after retention period expires.
 * This is an IRREVERSIBLE operation.
 */
class TenantPurgeService
{
    public function __construct(
        protected TenantRetentionService $retentionService
    ) {}

    /**
     * Permanently purge a tenant.
     *
     * @throws \DomainException
     */
    public function purge(Tenant $tenant): void
    {
        // Ensure tenant is soft deleted
        if (! $tenant->trashed()) {
            throw new \DomainException('Tenant must be archived before purging');
        }

        // Check retention period
        if (! $this->retentionService->retentionExpired($tenant)) {
            $expiresAt = $this->retentionService->getRetentionExpiresAt($tenant);
            throw new \DomainException(
                "Retention period not expired. Can purge after {$expiresAt->toDateString()}"
            );
        }

        Log::info('Starting tenant purge', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'deleted_at' => $tenant->deleted_at,
        ]);

        DB::transaction(function () use ($tenant) {
            // Step 1: Drop tenant database
            $this->dropTenantDatabase($tenant);

            // Step 2: Delete tenant domains
            $tenant->domains()->forceDelete();

            // Step 3: Delete related records (subscriptions, etc.)
            $tenant->subscriptions()->forceDelete();

            // Step 4: Permanently delete tenant record
            $tenant->forceDelete();
        });

        Log::info('Tenant purged successfully', [
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * Drop the tenant's database.
     */
    protected function dropTenantDatabase(Tenant $tenant): void
    {
        if (! $tenant->database()->exists()) {
            Log::warning('Tenant database does not exist', [
                'tenant_id' => $tenant->id,
                'database_name' => $tenant->database()->getName(),
            ]);

            return;
        }

        // Shared safety guard (Axis A A9). The daily auto-purge path previously
        // had NO prefix/central guard — the most-automated drop was the least
        // protected. Refuse to proceed on an unsafe/central-matching name.
        TenantDatabaseDropGuard::assertSafe((string) $tenant->database()->getName());

        try {
            // Initialize tenancy to access tenant database
            tenancy()->initialize($tenant);

            // Use stancl/tenancy's built-in database deletion
            Artisan::call('tenants:delete', [
                '--tenant' => [$tenant->id],
                '--force' => true,
            ]);

            Log::info('Tenant database dropped', [
                'tenant_id' => $tenant->id,
                'database_name' => $tenant->database()->getName(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to drop tenant database', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            // Always end tenancy
            tenancy()->end();
        }

        // Verify database was actually dropped
        if ($tenant->database()->exists()) {
            throw new \RuntimeException('Failed to drop tenant database');
        }
    }

    /**
     * Batch purge multiple tenants.
     *
     * @return array ['success' => int, 'failed' => int, 'errors' => array]
     */
    public function batchPurge(iterable $tenants): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($tenants as $tenant) {
            try {
                $this->purge($tenant);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to purge tenant', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }
}
