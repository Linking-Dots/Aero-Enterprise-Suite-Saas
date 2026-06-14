<?php

declare(strict_types=1);

namespace Aero\HRMAC\Models;

use Aero\Contracts\HrmacModelGuardInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Context-free base for all HRMAC models.
 *
 * HRMAC is an independent package shared by the SaaS platform, SaaS tenants, and
 * standalone. Its models therefore name NO connection and detect NO context — they
 * use the DEFAULT connection, which the host's runtime decides (stancl/tenancy swaps
 * it to the tenant DB for tenant requests; it is the central DB for platform requests;
 * the single DB in standalone).
 *
 * Isolation is the consumer's decision: if a consuming package binds
 * {@see HrmacModelGuardInterface}, every HRMAC query is checked by it (the tenant host
 * uses this to keep tenant-scoped tables inside a valid tenant/central context). With
 * nothing bound, queries run freely on the default connection.
 */
abstract class HrmacModel extends Model
{
    /**
     * Default audit label — the model key as a string. Subclasses may override.
     * Required by AuditService::log() which calls $subject->getAuditLabel().
     */
    public function getAuditLabel(): ?string
    {
        return (string) $this->getKey();
    }

    /**
     * First-class accessor for tenant_id (null when unset / not applicable).
     */
    public function getTenantId(): ?string
    {
        $value = $this->getAttribute('tenant_id');

        return $value === null ? null : (string) $value;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('hrmac_model_guard', function (Builder $builder) {
            // Delegate isolation to the consumer-bound guard, if any. HRMAC ships
            // none — context/isolation is the sharing package's responsibility.
            if (app()->bound(HrmacModelGuardInterface::class)) {
                app(HrmacModelGuardInterface::class)->assert(static::class);
            }
        });
    }
}
