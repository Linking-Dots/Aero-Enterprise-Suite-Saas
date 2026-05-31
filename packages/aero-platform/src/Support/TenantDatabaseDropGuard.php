<?php

declare(strict_types=1);

namespace Aero\Platform\Support;

/**
 * Shared safety guard for DROP DATABASE on tenant databases (Axis A A9).
 *
 * Three code paths drop tenant databases — ProvisionTenant::rollbackDatabase,
 * TenantForgetService (GDPR), and TenantPurgeService (daily auto-purge). They
 * previously each re-implemented (or, in the purge case, OMITTED) the safety
 * checks, leaving the most-automated path the least protected.
 *
 * Every drop MUST route through assertSafe() first:
 *   1. Name contains only safe characters (alphanumeric, underscore, dash).
 *   2. Name starts with the configured tenant prefix.
 *   3. Name is not the central/platform database.
 */
final class TenantDatabaseDropGuard
{
    /**
     * @throws \RuntimeException when the database name is unsafe to drop.
     */
    public static function assertSafe(string $databaseName): void
    {
        if (! preg_match('/^[a-zA-Z0-9_\-]+$/', $databaseName)) {
            throw new \RuntimeException(
                "Refusing to drop tenant database: name '{$databaseName}' contains unsafe characters."
            );
        }

        $prefix = (string) config('tenancy.database.prefix', 'tenant');
        if ($prefix !== '' && ! str_starts_with($databaseName, $prefix)) {
            throw new \RuntimeException(
                "Refusing to drop database '{$databaseName}': it does not start with the tenant prefix '{$prefix}'."
            );
        }

        $central = config('database.connections.central.database');
        if ($central !== null && $databaseName === $central) {
            throw new \RuntimeException(
                "Refusing to drop database '{$databaseName}': it is the central database."
            );
        }
    }

    /**
     * Convenience predicate for callers that must not throw (e.g. error-path rollback).
     */
    public static function isSafe(string $databaseName): bool
    {
        try {
            self::assertSafe($databaseName);

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    }
}
