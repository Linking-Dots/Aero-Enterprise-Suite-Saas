<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
use Aero\Platform\Support\TenantDatabaseDropGuard;
use Aero\Platform\Support\TenantTeardownSequencer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * GDPR right-to-be-forgotten executor (Audit D7).
 *
 * This is a DESTRUCTIVE, IRREVERSIBLE operation. Drops the tenant DB and
 * hard-deletes the tenants row. Bypasses the soft-delete retention path —
 * customers requesting GDPR forget cannot rely on the 30-day recovery
 * window because retention itself is a data-processing activity they
 * have asked us to stop.
 *
 * Audit is written BEFORE deletion so the audit row's foreign-key
 * reference (if any) doesn't dangle. The audit row itself uses
 * description + metadata; it does NOT carry the tenant_id FK that
 * gets dropped.
 *
 * Database deletion uses the same safety guards as ProvisionTenant::rollbackDatabase():
 *   1. Regex: only alphanumeric, underscore, dash characters.
 *   2. Prefix guard: name MUST start with the configured tenant prefix.
 *   3. Central-DB guard: name MUST NOT match the central database name.
 */
class TenantForgetService
{
    public function __construct(
        private readonly AuditServiceInterface $audit,
        private readonly TenantTeardownSequencer $sequencer,
    ) {}

    /**
     * Permanently purge a tenant: drop the tenant DB then hard-delete the row.
     *
     * @throws Throwable If the operation cannot complete. Caller should
     *                   surface the error — never retry a partial forget.
     */
    public function forget(Tenant $tenant, string $reason, ?int $requestedByUserId): void
    {
        // Capture identifying details BEFORE the row is destroyed.
        $subdomain = $tenant->subdomain ?? (string) $tenant->getTenantKey();
        $tenantId = (string) $tenant->getTenantKey();
        $email = $tenant->email ?? null;
        $databaseName = $this->resolveDatabaseName($tenant);

        // Atomic-as-possible teardown (Axis A A4): audit + row delete commit
        // FIRST, then drop the DB outside the transaction; on drop failure the
        // orphaned DB is reconciled rather than leaving a committed delete paired
        // with a live database (DDL can't be rolled back inside the transaction).
        $this->sequencer->teardown(
            databaseName: $databaseName,
            deleteCentralRows: function () use ($tenant, $tenantId, $subdomain, $email, $reason, $requestedByUserId): void {
                // Audit BEFORE deletion (no FK to dangle; AuditService stores raw values).
                $this->audit->log(
                    event: 'platform.tenant.forgotten',
                    action: 'forgotten',
                    subject: null,
                    description: "GDPR right-to-be-forgotten executed for tenant {$subdomain} ({$tenantId})",
                    before: null,
                    after: null,
                    metadata: [
                        'tenant_id' => $tenantId,
                        'subdomain' => $subdomain,
                        'email' => $email,
                        'reason' => $reason,
                        'requested_by_user_id' => $requestedByUserId,
                        'executed_at' => now()->toIso8601String(),
                    ],
                );

                // Hard-delete the tenants row. forceDelete() bypasses SoftDeletes.
                $tenant->forceDelete();
            },
            dropDatabase: fn () => $this->dropTenantDatabase($tenantId, $subdomain, (string) $databaseName),
            context: ['tenant_id' => $tenantId, 'subdomain' => $subdomain],
        );
    }

    /**
     * Resolve the physical database name for this tenant.
     * Returns null when the tenant has no associated database (e.g. BYOC / shared).
     */
    private function resolveDatabaseName(Tenant $tenant): ?string
    {
        try {
            $name = $tenant->database()->getName();

            return (is_string($name) && $name !== '') ? $name : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Drop the tenant database with the same safety guards used in ProvisionTenant::rollbackDatabase().
     *
     * @throws Throwable On hard failure (bad name format, central-DB collision, SQL error).
     */
    protected function dropTenantDatabase(string $tenantId, string $subdomain, string $databaseName): void
    {
        // Shared safety guard (Axis A A9): safe chars + tenant prefix + not central.
        try {
            TenantDatabaseDropGuard::assertSafe($databaseName);
        } catch (\RuntimeException $e) {
            Log::error('TenantForgetService: '.$e->getMessage(), [
                'tenant_id' => $tenantId,
                'subdomain' => $subdomain,
                'database_name' => $databaseName,
            ]);

            throw $e;
        }

        try {
            DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");

            Log::info('TenantForgetService: tenant database dropped', [
                'tenant_id' => $tenantId,
                'subdomain' => $subdomain,
                'database_name' => $databaseName,
            ]);
        } catch (Throwable $e) {
            Log::error('TenantForgetService: DROP DATABASE failed', [
                'tenant_id' => $tenantId,
                'subdomain' => $subdomain,
                'database_name' => $databaseName,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
