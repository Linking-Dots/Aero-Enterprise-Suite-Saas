<?php

namespace Aero\Core\Traits;

use Aero\Contracts\AeroMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * AeroTenantable Trait
 *
 * Provides tenant isolation that works in both SaaS (with stancl/tenancy)
 * and Standalone (without tenancy package) modes.
 *
 * This trait safely checks for the presence of tenancy dependencies and
 * applies appropriate scoping based on the runtime environment.
 */
trait AeroTenantable
{
    /**
     * Boot the AeroTenantable trait for a model.
     */
    public static function bootAeroTenantable(): void
    {
        // Only apply scoping if we're in a tenant context
        if (static::shouldApplyTenantScope()) {
            static::addGlobalScope(new AeroTenantScope);
        }
    }

    /**
     * Get the name of the tenant key column.
     */
    public function getTenantKeyName(): string
    {
        return $this->tenantKey ?? 'tenant_id';
    }

    /**
     * Get the current tenant identifier.
     */
    public static function getCurrentTenantId(): int|string|null
    {
        // Check if stancl/tenancy is available and active
        if (static::isTenancyPackageActive()) {
            return static::getTenantIdFromStancl();
        }

        // Fallback: Check for aero-platform's tenant context
        if (static::isPlatformActive()) {
            return static::getTenantIdFromPlatform();
        }

        // Standalone mode: Default to tenant_id = 1
        return static::getStandaloneTenantId();
    }

    /**
     * Determine if tenant scoping should be applied.
     */
    protected static function shouldApplyTenantScope(): bool
    {
        // Don't apply if model explicitly disables it
        if (property_exists(static::class, 'tenantable') && ! static::$tenantable) {
            return false;
        }

        // Don't apply if we're in central/landlord context
        if (static::isInCentralContext()) {
            return false;
        }

        // Apply if we have any tenant context (SaaS or Standalone)
        return static::getCurrentTenantId() !== null;
    }

    /**
     * Check if stancl/tenancy package is active.
     */
    protected static function isTenancyPackageActive(): bool
    {
        return interface_exists('Stancl\Tenancy\Contracts\Tenant') &&
               class_exists('Stancl\Tenancy\Database\Concerns\BelongsToTenant');
    }

    /**
     * Get tenant ID from stancl/tenancy.
     */
    protected static function getTenantIdFromStancl(): int|string|null
    {
        if (! function_exists('tenant')) {
            return null;
        }

        try {
            $tenant = tenant();

            if (! $tenant) {
                return null;
            }

            // Support both 'id' and 'tenant_id' as the key
            return $tenant->getTenantKey();
        } catch (\Throwable $e) {
            // Silently fail if tenant() throws (e.g., in queue context)
            return null;
        }
    }

    /**
     * Check if running in SaaS mode (formerly: "is aero-platform active").
     */
    protected static function isPlatformActive(): bool
    {
        return AeroMode::isSaas();
    }

    /**
     * Get tenant ID from aero-platform context.
     */
    protected static function getTenantIdFromPlatform(): int|string|null
    {
        // Check session for tenant context
        if (function_exists('session') && session()->has('tenant_id')) {
            return session('tenant_id');
        }

        // Check request for tenant context
        if (function_exists('request') && request()->has('tenant_id')) {
            return request('tenant_id');
        }

        return null;
    }

    /**
     * Get standalone mode tenant ID.
     */
    protected static function getStandaloneTenantId(): int
    {
        return (int) config('aero.standalone_tenant_id', 1);
    }

    /**
     * Determine if we're in central/landlord context.
     */
    protected static function isInCentralContext(): bool
    {
        // Check if running in central database context
        if (static::isTenancyPackageActive() && class_exists('Stancl\Tenancy\Database\Concerns\CentralConnection')) {
            // Check if model uses CentralConnection trait
            $traits = class_uses_recursive(static::class);
            if (isset($traits['Stancl\Tenancy\Database\Concerns\CentralConnection'])) {
                return true;
            }
        }

        // Check if explicitly running on central connection
        if (config('tenancy.central_domains') && in_array(request()->getHost(), config('tenancy.central_domains', []))) {
            return true;
        }

        return false;
    }

    /**
     * Create a new model instance without tenant scoping.
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope(AeroTenantScope::class);
    }

    /**
     * Create a new model instance for all tenants.
     */
    public static function allTenants(): Builder
    {
        return static::withoutTenantScope();
    }
}

/**
 * AeroTenantScope
 *
 * Global scope that automatically filters queries by tenant_id.
 */
class AeroTenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = $model::getCurrentTenantId();

        if ($tenantId !== null) {
            $builder->where($model->getQualifiedTenantKeyName(), $tenantId);
        }
    }

    /**
     * Extend the query builder with custom methods.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutTenantScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }

    /**
     * Get the qualified tenant key name.
     */
    protected function getQualifiedTenantKeyName(Model $model): string
    {
        return $model->getTable().'.'.$model->getTenantKeyName();
    }
}
