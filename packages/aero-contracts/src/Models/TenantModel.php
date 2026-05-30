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
    /**
     * Default audit label — returns the model key as a string.
     * Subclasses may override to return a human-readable label.
     * Required by AuditService::log() which calls $subject->getAuditLabel().
     */
    public function getAuditLabel(): ?string
    {
        return (string) $this->getKey();
    }

    /**
     * First-class accessor for the tenant_id attribute (Plan 01 T3).
     *
     * Many consumers reach for $model->tenant_id directly which works but
     * gives no type signature and no central place to add fallback logic
     * (e.g., derive from currently-active tenancy if column absent).
     *
     * Returns null when the attribute is unset — useful in test fixtures
     * and standalone-mode rows where tenant_id is irrelevant.
     */
    public function getTenantId(): ?string
    {
        $value = $this->getAttribute('tenant_id');
        return $value === null ? null : (string) $value;
    }

    protected static function boot(): void
    {
        parent::boot();

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
