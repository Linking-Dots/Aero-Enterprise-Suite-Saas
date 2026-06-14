<?php

namespace Aero\Core\Tests\Unit\Models;

use Aero\Contracts\AeroMode;
use Aero\Contracts\Models\Concerns\EnforcesTenantContext;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

/**
 * Proves the central-connection escape in {@see EnforcesTenantContext} (auth-identity
 * unification, provider-driven connection strategy):
 *  - a default/tenant-connection instance STILL trips the fail-closed tenant guard in SaaS;
 *  - an instance bound to the 'central' connection (e.g. a landlord User loaded by the
 *    landlord guard's provider) SKIPS the guard, because landlord rows are tenant-agnostic.
 */
class EnforcesTenantContextCentralEscapeTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $sqlite = ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', $sqlite);
        // The landlord/central connection the escape keys on.
        $app['config']->set('database.connections.central', $sqlite);
    }

    protected function tearDown(): void
    {
        AeroMode::reset();
        parent::tearDown();
    }

    private function bootSaasWithThrowingChecker(): void
    {
        AeroMode::setModeResolver(fn () => true); // SaaS
        AeroMode::setTenantContextChecker(function (string $modelClass) {
            throw new \LogicException($modelClass.' queried outside of tenant context.');
        });
    }

    public function test_default_connection_instance_trips_the_guard_in_saas(): void
    {
        $this->bootSaasWithThrowingChecker();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/outside of tenant context/');

        // toSql() forces global-scope application (applyScopes()).
        (new EtcEscapeDummy())->newQuery()->toSql();
    }

    public function test_central_connection_instance_skips_the_guard_in_saas(): void
    {
        $this->bootSaasWithThrowingChecker();

        // Bound to 'central' (as the landlord provider does at retrieval) -> guard must NOT fire.
        $sql = (new EtcEscapeDummy())->setConnection('central')->newQuery()->toSql();

        $this->assertIsString($sql);
        $this->assertStringContainsString('etc_escape_dummy', $sql);
    }

    public function test_standalone_mode_is_a_noop_regardless_of_connection(): void
    {
        AeroMode::setModeResolver(fn () => false); // standalone
        AeroMode::setTenantContextChecker(function () {
            throw new \LogicException('guard must not fire in standalone');
        });

        $this->assertIsString((new EtcEscapeDummy())->newQuery()->toSql());
    }
}

/**
 * Minimal model using the real trait. Anonymous classes can't be re-instantiated across
 * tests with a stable morph/boot, so use a named throwaway.
 */
class EtcEscapeDummy extends Model
{
    use EnforcesTenantContext;

    protected $table = 'etc_escape_dummy';

    public $timestamps = false;
}
