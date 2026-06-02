<?php

declare(strict_types=1);

namespace Aero\Contracts\Tests\Models;

use Aero\Contracts\AeroMode;
use Aero\Contracts\Models\CentralModel;
use Aero\Contracts\Models\TenantModel;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

/**
 * Plan 01 (aero-contracts) Task 2 — symmetric central-base contract test.
 *
 * Pins (updated for Axis B B3 — connection is now mode-aware):
 *   - SaaS (central connection configured) → 'central'
 *   - standalone (no central connection) → the default connection
 *   - CentralModel does NOT inherit the tenant_context_guard global scope
 *   - getAuditLabel() returns the model key by default
 */
class CentralModelContractTest extends TestCase
{
    protected function tearDown(): void
    {
        AeroMode::reset();
        parent::tearDown();
    }

    public function test_central_connection_resolves_to_central_in_saas(): void
    {
        AeroMode::setModeResolver(fn () => true);
        config(['database.connections.central' => ['driver' => 'sqlite', 'database' => ':memory:']]);

        $model = new class extends CentralModel {
            protected $table = 'fakes';
        };

        $this->assertSame('central', $model->getConnectionName(),
            'In SaaS with a central connection configured, CentralModel must use "central".');
    }

    public function test_central_connection_falls_back_to_default_in_standalone(): void
    {
        AeroMode::setModeResolver(fn () => false); // standalone

        $model = new class extends CentralModel {
            protected $table = 'fakes';
        };

        $this->assertSame(config('database.default'), $model->getConnectionName(),
            'In standalone (no "central" connection), CentralModel must use the default '.
            'connection so it works on the single DB (Axis B B3).');
    }

    public function test_central_model_does_not_register_tenant_context_scope(): void
    {
        $model = new class extends CentralModel {
            protected $table = 'fakes';
        };

        $globalScopes = $model->getGlobalScopes();
        $this->assertArrayNotHasKey('tenant_context_guard', $globalScopes,
            'CentralModel must NOT register the tenant_context_guard scope — '.
            'that is exclusive to TenantModel.');
    }

    public function test_central_and_tenant_models_are_disjoint_class_hierarchies(): void
    {
        $r = new ReflectionClass(CentralModel::class);
        $this->assertFalse($r->isSubclassOf(TenantModel::class),
            'CentralModel must not extend TenantModel — they are intentionally separate '.
            'to prevent accidental cross-DB joins.');
    }

    public function test_get_audit_label_returns_key_as_string_by_default(): void
    {
        $model = new class extends CentralModel {
            protected $table = 'fakes';
            protected $primaryKey = 'id';
            public $incrementing = true;
        };
        $model->setAttribute('id', 42);

        $this->assertSame('42', $model->getAuditLabel(),
            'Default getAuditLabel() must return the primary key cast to string.');
    }
}
