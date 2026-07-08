<?php

declare(strict_types=1);

namespace Aero\Platform\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Append-only ledger row recording that a tenant was granted (and later possibly
 * revoked) access to a module by a given source. Audit record only — enforcement
 * still flows through {@see \Aero\Core\Services\Module\ModuleEntitlementService}.
 *
 * An "open" row (revoked_at IS NULL) means the source currently entitles the module.
 * source=override rows additionally grant access outside a purchase (comp/trial).
 */
class TenantEntitlement extends CentralModel
{
    protected $table = 'tenant_entitlements';

    public const SOURCE_SUBSCRIPTION = 'subscription';
    public const SOURCE_LICENSE = 'license';
    public const SOURCE_OVERRIDE = 'override';
    public const SOURCE_BASELINE = 'baseline';

    protected $fillable = [
        'tenant_id',
        'module_code',
        'source',
        'source_id',
        'granted_at',
        'revoked_at',
        'reason',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /** Rows still in effect (not revoked). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    /** Rows for a given tenant scope ('none' / null for standalone). */
    public function scopeForTenant(Builder $query, ?string $tenantId): Builder
    {
        return $tenantId === null
            ? $query->whereNull('tenant_id')
            : $query->where('tenant_id', $tenantId);
    }

    /** Active override grants (access outside a purchase). */
    public function scopeOverride(Builder $query): Builder
    {
        return $query->where('source', self::SOURCE_OVERRIDE)->whereNull('revoked_at');
    }
}
