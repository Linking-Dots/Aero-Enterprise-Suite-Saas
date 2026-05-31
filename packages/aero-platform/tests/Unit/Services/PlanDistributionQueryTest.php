<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Services;

use Aero\Platform\Services\PlatformAnalyticsService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Plan 03 (aero-platform) Task 1 — broken plan_id query regression pin.
 *
 * Phase 1 audit's #1 production bug: tenantAnalytics() did
 *   Tenant::query()->select('plan_id', DB::raw('count(*) as count'))
 *   ->groupBy('plan_id')
 * but the tenants table has no plan_id column anymore (it was removed when
 * subscriptions became polymorphic via billable_id/billable_type). In strict
 * MySQL this throws "Unknown column 'plan_id'"; in relaxed MySQL it returns
 * a single NULL row.
 *
 * The fix routes through a new planDistribution() method that joins
 *   subscriptions → tenants (polymorphic billable) → plans
 * and counts DISTINCT tenants per plan.
 *
 * Full integration test (with Tenant + Plan + Subscription factories) lives
 * in the host repo's feature suite — that requires real DB. This file pins
 * the structural shape so a refactor cannot regress to the broken query.
 */
class PlanDistributionQueryTest extends TestCase
{
    private function source(): string
    {
        return file_get_contents((new ReflectionClass(PlatformAnalyticsService::class))->getFileName());
    }

    public function test_plan_distribution_method_exists(): void
    {
        $r = new ReflectionClass(PlatformAnalyticsService::class);

        $this->assertTrue($r->hasMethod('planDistribution'),
            'PlatformAnalyticsService::planDistribution() must exist (Plan 03 T1).');
    }

    public function test_broken_plan_id_select_is_gone(): void
    {
        $source = $this->source();

        $this->assertDoesNotMatchRegularExpression(
            "/Tenant::query\(\)\s*->\s*select\(\s*['\"]plan_id['\"]/",
            $source,
            "The broken Tenant::query()->select('plan_id', ...) must NOT remain — ".
            'tenants table has no plan_id column.'
        );

        $this->assertDoesNotMatchRegularExpression(
            "/groupBy\(\s*['\"]plan_id['\"]\s*\)/",
            $source,
            "groupBy('plan_id') on tenants must NOT remain — use planDistribution() helper."
        );
    }

    public function test_plan_distribution_joins_polymorphic_subscription(): void
    {
        $source = $this->source();

        $this->assertMatchesRegularExpression(
            "/billable_id/",
            $source,
            'planDistribution() must join via subscriptions.billable_id (polymorphic) — '.
            'see Subscription model billable_id/billable_type columns.'
        );

        $this->assertMatchesRegularExpression(
            "/billable_type[\s\S]*?Tenant::class/",
            $source,
            'planDistribution() must filter subscriptions.billable_type = Tenant::class.'
        );
    }

    public function test_plan_distribution_returns_documented_shape(): void
    {
        $source = $this->source();

        // The new shape is ['plan_id' => int, 'plan_name' => string, 'count' => int].
        // Pin those three keys explicitly so a refactor that renames count → tenants
        // doesn't silently change the contract for the dashboard frontend.
        foreach (['plan_id', 'plan_name', 'count'] as $key) {
            $this->assertStringContainsString("'{$key}'", $source,
                "planDistribution() result shape must include '{$key}' key.");
        }
    }
}
