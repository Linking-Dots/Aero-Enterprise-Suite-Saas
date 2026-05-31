<?php

declare(strict_types=1);

namespace Aero\Core\Models\Concerns;

use Aero\Contracts\TenantScopeInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds the same tenant-context guard as TenantModel, for models that cannot
 * extend TenantModel directly (e.g. User, which must extend Authenticatable).
 *
 * In SaaS mode: throws LogicException if queried outside tenant context.
 * In standalone mode: no-op.
 */
trait EnforcesTenantContext
{
    protected static function bootEnforcesTenantContext(): void
    {
        static::addGlobalScope('tenant_context_guard', function (Builder $builder) {
            if (! is_saas_mode()) {
                return;
            }

            // Fail CLOSED (Axis A A10): only allow during genuine early boot,
            // before the scope binding exists. Otherwise resolve and let the
            // out-of-context LogicException propagate — never swallow it.
            if (! app()->bound(TenantScopeInterface::class)) {
                return;
            }

            $scope = app(TenantScopeInterface::class);
            if (! $scope->inTenantContext()) {
                throw new \LogicException(
                    static::class.' queried outside of tenant context. '.
                    'Ensure this runs after tenancy middleware. '.
                    'For central-DB models extend CentralModel instead.'
                );
            }
        });
    }
}
