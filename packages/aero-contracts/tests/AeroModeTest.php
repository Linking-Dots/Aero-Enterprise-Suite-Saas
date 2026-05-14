<?php

namespace Aero\Contracts\Tests;

use Aero\Contracts\AeroMode;
use PHPUnit\Framework\TestCase;

class AeroModeTest extends TestCase
{
    protected function tearDown(): void
    {
        AeroMode::reset();
    }

    public function test_defaults_to_standalone_when_no_resolver_set(): void
    {
        $this->assertFalse(AeroMode::isSaas());
        $this->assertTrue(AeroMode::isStandalone());
    }

    public function test_returns_saas_when_resolver_returns_true(): void
    {
        AeroMode::setModeResolver(fn () => true);

        $this->assertTrue(AeroMode::isSaas());
        $this->assertFalse(AeroMode::isStandalone());
    }

    public function test_assert_tenant_context_is_noop_when_no_checker_set(): void
    {
        AeroMode::assertTenantContext('SomeModel');
        $this->addToAssertionCount(1);
    }

    public function test_assert_tenant_context_calls_checker(): void
    {
        $called = false;
        AeroMode::setTenantContextChecker(function (string $model) use (&$called) {
            $called = true;
            $this->assertEquals('App\Models\Foo', $model);
        });

        AeroMode::assertTenantContext('App\Models\Foo');

        $this->assertTrue($called);
    }

    public function test_reset_clears_both_resolvers(): void
    {
        AeroMode::setModeResolver(fn () => true);
        AeroMode::setTenantContextChecker(fn (string $m) => null);
        AeroMode::reset();

        $this->assertFalse(AeroMode::isSaas());
        AeroMode::assertTenantContext('SomeModel'); // should not throw
        $this->addToAssertionCount(1);
    }
}
