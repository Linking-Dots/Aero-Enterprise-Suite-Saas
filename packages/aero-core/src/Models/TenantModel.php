<?php

namespace Aero\Core\Models;

use Aero\Contracts\TenantScopeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all models that live in the TENANT database.
 *
 * In SaaS mode: connection is switched to the tenant's DB by stancl/tenancy middleware.
 * In standalone mode: single DB is used; no switching occurs.
 *
 * Extend this for any model whose table belongs to tenant data.
 * NEVER add relationships to Plan, LandlordUser, or other central models.
 */
abstract class TenantModel extends Model
{
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('tenant_context_guard', function (Builder $builder) {
            if (! is_saas_mode()) {
                return;
            }

            try {
                $scope = app(TenantScopeInterface::class);
                if (! $scope->inTenantContext()) {
                    throw new \LogicException(
                        static::class . ' queried outside of tenant context. ' .
                        'Ensure this runs after tenancy middleware. ' .
                        'For central-DB models extend CentralModel instead.'
                    );
                }
            } catch (\LogicException $e) {
                throw $e;
            } catch (\Throwable) {
                // TenantScopeInterface not bound during early boot — allow
            }
        });
    }
}
