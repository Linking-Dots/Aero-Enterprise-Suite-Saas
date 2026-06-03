<?php

declare(strict_types=1);

namespace Aero\Contracts;

/**
 * Static mode and tenant-context resolver for aero-contracts.
 *
 * Zero Laravel dependency. aero-core sets both resolvers during
 * ServiceProvider::register() via setModeResolver() and
 * setTenantContextChecker(). Until then (tests, queue workers,
 * standalone installs) defaults to standalone / no-guard.
 */
final class AeroMode
{
    private static ?\Closure $modeResolver = null;

    private static ?\Closure $tenantContextChecker = null;

    /** Called once by AeroCoreServiceProvider::register(). */
    public static function setModeResolver(\Closure $resolver): void
    {
        self::$modeResolver = $resolver;
    }

    /**
     * Called once by AeroCoreServiceProvider::register().
     * The checker MUST throw \LogicException if called outside tenant context.
     *
     * @param \Closure(string $modelClass): void $checker
     */
    public static function setTenantContextChecker(\Closure $checker): void
    {
        self::$tenantContextChecker = $checker;
    }

    public static function isSaas(): bool
    {
        return self::$modeResolver !== null && (self::$modeResolver)();
    }

    public static function isStandalone(): bool
    {
        return ! self::isSaas();
    }

    /**
     * Called from TenantModel's global scope.
     * No-op when no checker is set (tests, early boot).
     */
    public static function assertTenantContext(string $modelClass): void
    {
        if (self::$tenantContextChecker !== null) {
            (self::$tenantContextChecker)($modelClass);
        }
    }

    /**
     * Run a callback with the tenant-context guard temporarily disabled.
     *
     * For LEGITIMATE central/landlord operations that touch shared tenant-scoped
     * models on the central connection (e.g. seeding landlord HRMAC roles), where
     * there is no tenant to leak between. Always restores the previous checker.
     *
     * @template T
     * @param  \Closure(): T  $fn
     * @return T
     */
    public static function withoutTenantContextGuard(\Closure $fn)
    {
        $previous = self::$tenantContextChecker;
        self::$tenantContextChecker = null;
        try {
            return $fn();
        } finally {
            self::$tenantContextChecker = $previous;
        }
    }

    /** For testing only: reset all resolvers. */
    public static function reset(): void
    {
        self::$modeResolver         = null;
        self::$tenantContextChecker = null;
    }
}
