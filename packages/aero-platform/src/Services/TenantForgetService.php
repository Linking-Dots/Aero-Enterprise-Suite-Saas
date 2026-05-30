<?php

declare(strict_types=1);

namespace Aero\Platform\Services;

use Aero\Contracts\AuditServiceInterface;
use Aero\Platform\Models\Tenant;
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

        DB::transaction(function () use ($tenant, $tenantId, $subdomain, $email, $databaseName, $reason, $requestedByUserId): void {
            // 1. Audit BEFORE deletion (no FK to dangle; AuditService stores raw values).
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

            // 2. Drop tenant DB (with safety guards).
            if ($databaseName !== null) {
                $this->dropTenantDatabase($tenantId, $subdomain, $databaseName);
            }

            // 3. Hard-delete the tenants row. forceDelete() bypasses SoftDeletes.
            $tenant->forceDelete();
        });
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
        // Guard 1: allow only safe characters (alphanumeric, underscore, dash).
        if (! preg_match('/^[a-zA-Z0-9_\-]+$/', $databaseName)) {
            Log::error('TenantForgetService: unsafe database name refused', [
                'tenant_id' => $tenantId,
                'subdomain' => $subdomain,
                'database_name' => $databaseName,
            ]);

            throw new \RuntimeException(
                "GDPR forget aborted: database name '{$databaseName}' contains unsafe characters."
            );
        }

        // Guard 2: name must start with the configured tenant prefix.
        $expectedPrefix = config('tenancy.database.prefix', 'tenant');

        if ($expectedPrefix !== '' && ! str_starts_with($databaseName, (string) $expectedPrefix)) {
            Log::error('TenantForgetService: database name does not carry tenant prefix — refused', [
                'tenant_id' => $tenantId,
                'subdomain' => $subdomain,
                'database_name' => $databaseName,
                'expected_prefix' => $expectedPrefix,
            ]);

            throw new \RuntimeException(
                "GDPR forget aborted: database '{$databaseName}' does not start with tenant prefix '{$expectedPrefix}'."
            );
        }

        // Guard 3: must not match the central database name.
        $centralDb = config('database.connections.central.database');

        if ($centralDb !== null && $databaseName === $centralDb) {
            Log::error('TenantForgetService: refusing to drop central database', [
                'tenant_id' => $tenantId,
                'subdomain' => $subdomain,
                'database_name' => $databaseName,
            ]);

            throw new \RuntimeException(
                "GDPR forget aborted: database '{$databaseName}' matches the central database — refusing."
            );
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
