<?php

declare(strict_types=1);

namespace Aero\Contracts\Models\Concerns;

use Aero\Contracts\AeroMode;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds the same tenant-context guard as {@see \Aero\Contracts\Models\TenantModel},
 * for models that cannot extend TenantModel directly (e.g. User, which must extend
 * Authenticatable).
 *
 * In SaaS mode: throws LogicException if queried outside tenant context.
 * In standalone mode: no-op (AeroMode::isSaas() returns false).
 *
 * Delegates to AeroMode (pure, in aero-contracts) — the identical mechanism
 * TenantModel::boot() uses — so this concern is itself pure-shareable.
 */
trait EnforcesTenantContext
{
    protected static function bootEnforcesTenantContext(): void
    {
        static::addGlobalScope('tenant_context_guard', function (Builder $builder) {
            if (! AeroMode::isSaas()) {
                return;
            }

            // Fail CLOSED (Axis A A10). assertTenantContext() is a no-op when no
            // checker is configured (early boot / tests). When configured, the
            // checker (set by aero-core) does its own narrow early-boot allowance
            // and throws LogicException out of context — let it propagate; never
            // swallow it, which previously let queries run with no context guard.
            AeroMode::assertTenantContext(static::class);
        });
    }
}
