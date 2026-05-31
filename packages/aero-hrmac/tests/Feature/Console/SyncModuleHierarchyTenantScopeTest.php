<?php

declare(strict_types=1);

namespace Aero\HRMAC\Tests\Feature\Console;

use Aero\HRMAC\Console\Commands\SyncModuleHierarchy;
use Aero\HRMAC\Services\ModuleDiscoveryService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Audit D15 — SyncModuleHierarchy --scope=tenant filter regression pin.
 *
 * Verifies that:
 * 1. The source code contains the tenant-scope guard block.
 * 2. The filter uses subscribed_product_modules accessor.
 * 3. The filter keys off $def['code'].
 * 4. The baseline_modules config key exists in hrmac.php.
 *
 * Full integration tests (real tenant DB + product_subscriptions rows +
 * hrmac:sync-modules run + modules table assertions) are deferred to the
 * host suite as they require Stancl tenancy bootstrapping which is out of
 * scope for the package-level test harness.
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
    // Source-code structural pins
    // -------------------------------------------------------------------------

    public function test_handle_contains_tenant_scope_guard(): void
    {
        $this->assertStringContainsString(
            "scope === 'tenant'",
            $this->source,
            "handle() must contain a scope === 'tenant' guard for the D15 filter."
        );
    }

    public function test_filter_checks_tenancy_initialized(): void
    {
        $this->assertStringContainsString(
            'tenancy()->initialized',
            $this->source,
            'The filter must check tenancy()->initialized before attempting to read the tenant.'
        );
    }

    public function test_filter_reads_subscribed_product_modules(): void
    {
        $this->assertStringContainsString(
            'subscribed_product_modules',
            $this->source,
            "The filter must read the tenant's subscribed_product_modules accessor (Audit D15)."
        );
    }

    public function test_filter_checks_module_definition_code_key(): void
    {
        // The filter expression must reference $def['code'] so it compares discovered
        // module codes against the allowed list.
        $this->assertMatchesRegularExpression(
            '/\$def\s*\[\s*[\'"]code[\'"]\s*\]/',
            $this->source,
            "The filter must compare discovered module \$def['code'] against the allowed list."
        );
    }

    public function test_filter_uses_in_array_strict(): void
    {
        // Strict in_array prevents '0' == 'core' type-juggling surprises.
        $this->assertStringContainsString(
            'in_array(',
            $this->source,
            'The filter must use in_array() for the allowed-code check.'
        );
        $this->assertStringContainsString(
            'true)',
            $this->source,
            'in_array() must be called with strict=true.'
        );
    }

    public function test_filter_calls_get_subscribed_product_modules_attribute_method_guard(): void
    {
        $this->assertStringContainsString(
            'getSubscribedProductModulesAttribute',
            $this->source,
            "The filter must guard with method_exists('getSubscribedProductModulesAttribute') ".
            'before reading the accessor so standalone mode does not break.'
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
