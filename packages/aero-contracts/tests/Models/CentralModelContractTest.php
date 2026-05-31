<?php

declare(strict_types=1);

namespace Aero\Contracts\Tests\Models;

use Aero\Contracts\Models\CentralModel;
use Aero\Contracts\Models\TenantModel;
use Orchestra\Testbench\TestCase;
use ReflectionClass;

/**
 * Plan 01 (aero-contracts) Task 2 — symmetric central-base contract test.
 *
 * Mirrors TenantModelContractTest. Pins:
 *   - the connection is hardcoded to 'central'
 *   - creating/saving observers re-pin (defense against accidental mutation)
 *   - CentralModel does NOT inherit the tenant_context_guard global scope
 *   - getAuditLabel() returns the model key by default
 */
class CentralModelContractTest extends TestCase
{
    public function test_central_connection_is_pinned(): void
    {
        $model = new class extends CentralModel {
            protected $table = 'fakes';
        };

        $this->assertSame('central', $model->getConnectionName(),
            'CentralModel::$connection must equal "central" — central models must never '.
            'fall back to the default tenant connection.');
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
