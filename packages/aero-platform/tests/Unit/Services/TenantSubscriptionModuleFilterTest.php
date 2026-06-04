<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Unit\Services;

use Aero\Contracts\ModuleSyncFilterInterface;
use Aero\Platform\Services\TenantSubscriptionModuleFilter;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

/**
 * Audit D15 — tenant module syncs are gated to subscribed products. This logic moved
 * out of HRMAC's (context-free) sync command into this platform consumer filter.
 */
class TenantSubscriptionModuleFilterTest extends TestCase
{
    private function modules(): Collection
    {
        return collect([
            ['code' => 'core', 'name' => 'Core'],
            ['code' => 'hrm', 'name' => 'HRM'],
            ['code' => 'finance', 'name' => 'Finance'],
        ]);
    }

    public function test_it_implements_the_contract(): void
    {
        $this->assertInstanceOf(
            ModuleSyncFilterInterface::class,
            new TenantSubscriptionModuleFilter
        );
    }

    public function test_non_tenant_scope_passes_all_modules_through(): void
    {
        $filter = new TenantSubscriptionModuleFilter;

        // 'platform' and 'all' must never be subscription-gated.
        $this->assertCount(3, $filter->filter($this->modules(), 'platform'));
        $this->assertCount(3, $filter->filter($this->modules(), 'all'));
    }

    public function test_tenant_scope_without_active_tenancy_passes_all_through(): void
    {
        // In this package-level harness no stancl tenancy is initialized, so the filter
        // must degrade safely to returning every module rather than filtering to none.
        $filter = new TenantSubscriptionModuleFilter;

        $result = $filter->filter($this->modules(), 'tenant');

        $this->assertCount(3, $result);
    }
}
