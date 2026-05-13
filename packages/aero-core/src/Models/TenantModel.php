<?php

namespace Aero\Core\Models;

use Aero\Contracts\TenantScopeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all models that live in the TENANT database.
 *
 * @todo Plan Q: Move this class (and CentralModel) to packages/aero-contracts/src/
 *   so that feature packages can depend on aero/contracts only, without aero/core.
 *   Blocked by: aero-contracts currently requires only illuminate/support and illuminate/database;
 *   TenantModel needs the full aero-core boot context (is_saas_mode(), TenantScopeInterface).
 *   Resolution: extract is_saas_mode() to aero-contracts as a static helper first.
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
