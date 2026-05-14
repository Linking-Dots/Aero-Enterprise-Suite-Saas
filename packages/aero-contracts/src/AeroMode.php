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

    /** For testing only: reset all resolvers. */
    public static function reset(): void
    {
        self::$modeResolver         = null;
        self::$tenantContextChecker = null;
    }
}
