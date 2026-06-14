<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Feature\Console;

use Aero\Contracts\ModuleSyncFilterInterface;
use Aero\HRMAC\Console\Commands\SyncModuleHierarchy;
use Aero\HRMAC\Services\ModuleDiscoveryService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * HRMAC is a context-free shared package (2026-06-04 redesign).
 *
 * The tenant module-subscription filter (Audit D15) is NO LONGER in this command —
 * it moved to the SaaS consumer (Aero\Platform\Services\TenantSubscriptionModuleFilter)
 * and is applied via the ModuleSyncFilterInterface binding. These tests pin that the
 * command contains no context detection and delegates the data-decision to the contract.
 *
 * @see \Aero\Platform\Tests\... for the subscription-filter behavior tests.
 */
class SyncModuleHierarchyTenantScopeTest extends TestCase
{
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->source = file_get_contents(
            (new ReflectionClass(SyncModuleHierarchy::class))->getFileName()
        );
    }

    // -------------------------------------------------------------------------
    // Config contract
    // -------------------------------------------------------------------------

    public function test_hrmac_config_declares_baseline_modules(): void
    {
        $config = require dirname(__DIR__, 3).'/config/hrmac.php';

        $this->assertArrayHasKey('baseline_modules', $config,
            'hrmac.baseline_modules must be declared (Audit D15).');

        $baseline = $config['baseline_modules'];
        $this->assertIsArray($baseline);
        $this->assertNotEmpty($baseline);

        foreach (['core', 'auth', 'hrmac'] as $required) {
            $this->assertContains($required, $baseline,
                "baseline_modules must include '{$required}' as a foundation module.");
        }
    }

    // -------------------------------------------------------------------------
    // Context-free pins (HRMAC must NOT detect tenant/platform context)
    // -------------------------------------------------------------------------

    public function test_command_delegates_filtering_to_consumer_contract(): void
    {
        $this->assertStringContainsString(
            'ModuleSyncFilterInterface',
            $this->source,
            'The sync command must delegate module filtering to the consumer-bound '.
            'ModuleSyncFilterInterface (no SaaS logic inside HRMAC).'
        );
        $this->assertTrue(
            interface_exists(ModuleSyncFilterInterface::class),
            'ModuleSyncFilterInterface contract must exist.'
        );
    }

    public function test_command_contains_no_context_detection(): void
    {
        foreach (['tenancy()', 'detectScope', 'subscribed_product_modules', 'Stancl\\'] as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $this->source,
                "HRMAC sync command must be context-free — found '{$needle}'."
            );
        }
    }

    public function test_scope_defaults_to_all_without_detection(): void
    {
        $this->assertMatchesRegularExpression(
            "/option\('scope'\)\s*\?:\s*'all'/",
            $this->source,
            "Scope must default to 'all' (supplied by consumer), not auto-detected."
        );
    }

    // -------------------------------------------------------------------------
    // ModuleDiscoveryService contract
    // -------------------------------------------------------------------------

    public function test_module_discovery_service_is_injected_via_constructor(): void
    {
        $rc = new ReflectionClass(SyncModuleHierarchy::class);
        $constructor = $rc->getConstructor();

        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('moduleDiscovery', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(ModuleDiscoveryService::class, (string) $type);
    }
}
