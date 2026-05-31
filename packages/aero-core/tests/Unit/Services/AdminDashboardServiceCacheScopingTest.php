<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Unit\Services;

use Aero\Core\Services\Dashboard\AdminDashboardService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 02 (aero-core) Task 1 — AdminDashboardService cache leak regression pin.
 *
 * Phase 1 audit found AdminDashboardService used `Cache::remember('admin_dashboard.*', ...)`
 * with flat (non-tenant-prefixed) keys. In SaaS mode this means every tenant
 * on the same node sees the SAME cached value — a critical cross-tenant
 * data leak.
 *
 * The fix migrates all 12 dashboard cache sites to `TenantCache::remember(...)`
 * which auto-prefixes keys with `tenant:{id}:` in tenant context.
 *
 * The remaining Cache:: facade calls in AdminDashboardService (lines 521-525)
 * are an INTENTIONAL self-test — they probe the cache driver itself and don't
 * store tenant-scoped data.
 *
 * Full HTTP-level isolation test (two tenants get distinct stats) lives in
 * the host repo's feature suite; this file structurally pins the fix so a
 * refactor cannot regress it.
 */
class AdminDashboardServiceCacheScopingTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(AdminDashboardService::class))->getFileName());
    }

    public function test_admin_dashboard_keys_use_tenant_cache_not_raw_facade(): void
    {
        $source = $this->source();

        // No raw `Cache::remember('admin_dashboard.*' ...)` may remain.
        // Negative lookbehind excludes the substring within TenantCache::remember.
        $this->assertDoesNotMatchRegularExpression(
            "/(?<!Tenant)Cache::remember\(\s*['\"]admin_dashboard\\./",
            $source,
            'AdminDashboardService::*() must NOT use raw Cache::remember with admin_dashboard.* keys. '.
            'Use TenantCache::remember to scope by tenant.'
        );
    }

    public function test_tenant_cache_is_imported(): void
    {
        $this->assertStringContainsString(
            'use Aero\\Core\\Support\\TenantCache;',
            $this->source(),
            'TenantCache must be imported (auto-tenant-prefix on every key).'
        );
    }

    public function test_minimum_tenant_cache_remember_call_count(): void
    {
        $source = $this->source();
        $matches = preg_match_all('/TenantCache::remember\(/', $source);

        $this->assertGreaterThanOrEqual(12, $matches,
            "AdminDashboardService must contain at least 12 TenantCache::remember calls ".
            "(one per dashboard widget). Found {$matches}.");
    }

    public function test_health_check_self_test_cache_calls_remain_raw(): void
    {
        // The health-check probe explicitly tests the cache driver — those
        // 3 calls (Cache::put / get / forget on key 'health_check_test') must
        // stay as raw facade calls. Pin them so a careless refactor doesn't
        // accidentally tenant-prefix the probe key.
        $source = $this->source();
        $this->assertStringContainsString("Cache::put('health_check_test'", $source);
        $this->assertStringContainsString("Cache::get('health_check_test'", $source);
        $this->assertStringContainsString("Cache::forget('health_check_test'", $source);
    }
}
