<?php

declare(strict_types=1);

namespace Aero\Platform\Tests\Feature;

use Aero\Contracts\TenantScopeInterface;
use Aero\Platform\Services\Module\ModuleAccessService;
use Aero\Platform\Services\PlatformWidgetRegistry;
use Aero\Platform\Services\SaaSTenantScope;
use Aero\Platform\Services\Tenant\TenantRetentionService;
use Tests\TestCase;

class PlatformBindingsUatTest extends TestCase
{
    public function test_platform_singletons_are_bound_and_resolvable(): void
    {
        $this->assertTrue(app()->bound(ModuleAccessService::class));
        $this->assertTrue(app()->bound(PlatformWidgetRegistry::class));
        $this->assertTrue(app()->bound(TenantRetentionService::class));
    }

    public function test_tenant_scope_binding_is_platform_saas_scope(): void
    {
        $resolved = app(TenantScopeInterface::class);

        $this->assertInstanceOf(TenantScopeInterface::class, $resolved);
        $this->assertInstanceOf(SaaSTenantScope::class, $resolved);
    }
}
