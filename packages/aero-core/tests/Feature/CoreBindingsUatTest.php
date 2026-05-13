<?php

declare(strict_types=1);

namespace Aero\Core\Tests\Feature;

use Aero\Contracts\TenantScopeInterface;
use Aero\Core\Services\NavigationRegistry;
use Tests\TestCase;

class CoreBindingsUatTest extends TestCase
{
    public function test_core_tenant_scope_and_navigation_registry_are_available(): void
    {
        $tenantScope = app(TenantScopeInterface::class);
        $registry = app(NavigationRegistry::class);

        $this->assertInstanceOf(TenantScopeInterface::class, $tenantScope);
        $this->assertInstanceOf(NavigationRegistry::class, $registry);
    }

    public function test_navigation_registry_accepts_and_returns_items(): void
    {
        $registry = app(NavigationRegistry::class);

        $registry->register('test_core_uat', [
            ['name' => 'Dashboard', 'route' => 'dashboard.index'],
            ['name' => 'Users', 'route' => 'users.index'],
        ], priority: 10, scope: 'platform');

        $registry->register('test_hrm_uat', [
            ['name' => 'Employees', 'route' => 'employees.index'],
        ], priority: 20);

        $all = $registry->all();

        $this->assertGreaterThanOrEqual(3, count($all));
        $this->assertTrue($registry->hasModule('test_core_uat'));
        $this->assertTrue($registry->hasModule('test_hrm_uat'));
        $this->assertCount(2, $registry->forModule('test_core_uat'));
        $this->assertCount(1, $registry->forModule('test_hrm_uat'));
    }
}
