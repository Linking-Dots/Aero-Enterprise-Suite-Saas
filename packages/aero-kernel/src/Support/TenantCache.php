<?php

declare(strict_types=1);

namespace Aero\Kernel\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Tenant-Aware Cache Wrapper
 *
 * Automatically prefixes cache keys with tenant ID in SaaS mode.
 * Prevents cross-tenant cache leakage.
 */
class TenantCache
{
    public static function key(string $key): string
    {
        if (is_saas_mode()) {
            try {
                $scope = app(\Aero\Contracts\TenantScopeInterface::class);
                if ($scope->inTenantContext()) {
                    $tenantId = $scope->getCurrentTenantId();
                    return "tenant:{$tenantId}:{$key}";
                }
            } catch (\Throwable) {
                // TenantScopeInterface not bound during early boot
            }
        }

        return "global:{$key}";
    }

    public static function put(string $key, $value, $ttl = null): bool
    {
        return Cache::put(self::key($key), $value, $ttl);
    }

    public static function get(string $key, $default = null)
    {
        return Cache::get(self::key($key), $default);
    }

    public static function remember(string $key, $ttl, \Closure $callback)
    {
        return Cache::remember(self::key($key), $ttl, $callback);
    }

    public static function forget(string $key): bool
    {
        return Cache::forget(self::key($key));
    }

    public static function has(string $key): bool
    {
        return Cache::has(self::key($key));
    }
}
