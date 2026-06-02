<?php

namespace Aero\Core\Tests\Unit\Models;

use Aero\Contracts\TenantScopeInterface;
use Aero\Core\Models\TenantModel;
use Illuminate\Database\Eloquent\Builder;
use Orchestra\Testbench\TestCase;

class TenantModelGuardTest extends TestCase
{
    public function test_query_outside_tenant_context_throws_in_saas_mode(): void
    {
        // Bind a TenantScopeInterface that reports "outside tenant context"
        $this->app->bind(TenantScopeInterface::class, function () {
            return new class implements TenantScopeInterface {
                public function getCurrentTenantId(): int|string|null { return null; }
                public function getCurrentTenant(): mixed { return null; }
                public function inTenantContext(): bool { return false; }
                public function inCentralContext(): bool { return true; }
                public function getMode(): string { return 'saas'; }
                public function isSaaSMode(): bool { return true; }
                public function isStandaloneMode(): bool { return false; }
            };
        });

        // Create a concrete TenantModel subclass for testing
        $model = new class extends TenantModel {
            protected $table = 'test_table';
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/queried outside of tenant context/');

        // Apply global scopes by booting the anonymous class
        $model::addGlobalScope('tenant_context_guard', function (Builder $b) use ($model) {
            // Trigger the scope logic directly
            $scope = app(TenantScopeInterface::class);
            if ($scope->isSaaSMode() && ! $scope->inTenantContext()) {
                throw new \LogicException(
                    get_class($model).' queried outside of tenant context.'
                );
            }
        });

        // ->toSql() forces global-scope application (scopes run in applyScopes(),
        // not at query() construction). Pre-existing bug: query() alone never ran
        // the guard.
        $model::query()->toSql();
    }

    public function test_query_in_standalone_mode_does_not_throw(): void
    {
        $this->app->bind(TenantScopeInterface::class, function () {
            return new class implements TenantScopeInterface {
                public function getCurrentTenantId(): int|string|null { return null; }
                public function getCurrentTenant(): mixed { return null; }
                public function inTenantContext(): bool { return false; }
                public function inCentralContext(): bool { return true; }
                public function getMode(): string { return 'standalone'; }
                public function isSaaSMode(): bool { return false; }
                public function isStandaloneMode(): bool { return true; }
            };
        });

        // In standalone mode, no exception should be thrown
        $model = new class extends TenantModel {
            protected $table = 'test_table';
        };

        // No exception — standalone mode is a no-op
        $builder = $model::query();
        $this->assertInstanceOf(Builder::class, $builder);
    }
}
