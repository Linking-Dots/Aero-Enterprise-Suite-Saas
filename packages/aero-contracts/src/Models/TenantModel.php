<?php

declare(strict_types=1);

namespace Aero\Contracts\Models;

use Aero\Contracts\AeroMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all models that live in the TENANT database.
 *
 * In SaaS mode: stancl/tenancy middleware switches the connection to
 * the tenant's DB before this scope fires. In standalone mode the
 * scope is a no-op (AeroMode::isSaas() returns false).
 *
 * Extend this for any model whose table belongs to tenant data.
 * NEVER add relationships to CentralModel subclasses.
 */
abstract class TenantModel extends Model
{
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('tenant_context_guard', function (Builder $builder) {
            if (! AeroMode::isSaas()) {
                return;
            }

            try {
                AeroMode::assertTenantContext(static::class);
            } catch (\LogicException $e) {
                throw $e;
            } catch (\Throwable) {
                // AeroMode not yet configured (early boot, tests) — allow
            }
        });
    }
}
