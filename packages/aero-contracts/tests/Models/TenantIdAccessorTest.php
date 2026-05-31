<?php

declare(strict_types=1);

namespace Aero\Contracts\Tests\Models;

use Aero\Contracts\AeroMode;
use Aero\Contracts\Models\TenantModel;
use Orchestra\Testbench\TestCase;

/**
 * Plan 01 (aero-contracts) Task 3 — getTenantId() accessor coverage.
 */
class TenantIdAccessorTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        AeroMode::reset();
    }

    private function model(): TenantModel
    {
        // standalone mode so the tenant_context_guard scope doesn't fire
        AeroMode::setModeResolver(fn () => false);

        return new class extends TenantModel {
            protected $table = 'test_table';
            protected $guarded = [];
        };
    }

    public function test_get_tenant_id_returns_attribute_value(): void
    {
        $m = $this->model();
        $m->setAttribute('tenant_id', 'abc-123-uuid');

        $this->assertSame('abc-123-uuid', $m->getTenantId());
    }

    public function test_get_tenant_id_returns_null_when_unset(): void
    {
        $m = $this->model();

        $this->assertNull($m->getTenantId());
    }

    public function test_get_tenant_id_casts_int_to_string(): void
    {
        $m = $this->model();
        $m->setAttribute('tenant_id', 42);

        // Tenant IDs are typically UUIDs but legacy installs use ints —
        // accessor must normalize to string so downstream serializers
        // don't see mixed types.
        $this->assertSame('42', $m->getTenantId());
    }
}
