<?php

namespace Aero\Contracts\Tests\Models;

use Aero\Contracts\AeroMode;
use Aero\Contracts\Models\TenantModel;
use Illuminate\Database\Eloquent\Builder;
use Orchestra\Testbench\TestCase;

class TenantModelContractTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        AeroMode::reset();
    }

    public function test_no_exception_in_standalone_mode(): void
    {
        AeroMode::setModeResolver(fn () => false);

        $model = new class extends TenantModel {
            protected $table = 'test_table';
        };

        $builder = $model::query();
        $this->assertInstanceOf(Builder::class, $builder);
    }

    public function test_throws_when_checker_raises_logic_exception_in_saas_mode(): void
    {
        AeroMode::setModeResolver(fn () => true);
        AeroMode::setTenantContextChecker(function (string $modelClass) {
            throw new \LogicException("{$modelClass} queried outside of tenant context.");
        });

        $model = new class extends TenantModel {
            protected $table = 'test_table';
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/queried outside of tenant context/');

        // ->toSql() forces global-scope application (the guard closure runs in
        // applyScopes(), not at query() construction). Pre-existing test bug: it
        // called query() alone, which never triggered the scope, so the guard was
        // never actually exercised.
        $model::query()->toSql();
    }

    public function test_aero_core_shim_is_instance_of_contracts_tenant_model(): void
    {
        $coreShim = new class extends \Aero\Core\Models\TenantModel {
            protected $table = 'test_table';
        };

        $this->assertInstanceOf(\Aero\Contracts\Models\TenantModel::class, $coreShim);
    }
}
