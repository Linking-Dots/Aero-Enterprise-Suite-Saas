<?php

declare(strict_types=1);

namespace Aero\Contracts\Models;

use Aero\Contracts\AeroMode;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for all models that live in the CENTRAL (landlord) database.
 *
 * Central models are tenant-agnostic: Plans, LandlordUsers, Tenants, Products, etc.
 *
 * Connection resolution (Axis B B3):
 *   - SaaS: the 'central' connection (registered by aero-platform against the
 *     landlord DB).
 *   - Standalone: the DEFAULT connection — aero-platform is not installed, so
 *     'central' is never registered and there is exactly ONE database. Central
 *     models shipped in standalone packages (e.g. SocialAuthAccount in aero-auth)
 *     must transparently use it instead of throwing 'connection [central] not
 *     configured'.
 *
 * Resolved via AeroMode (the aero-contracts-native mode resolver) rather than the
 * aero-core central_connection() helper, to avoid a contracts -> core dependency.
 * The two stay in lock-step: aero-core wires AeroMode's resolver to is_saas_mode().
 *
 * NEVER add relationships to TenantModel subclasses — cross-DB joins fail.
 */
abstract class CentralModel extends Model
{
    protected $connection = 'central';

    /**
     * Resolve the central connection name for the active deployment mode.
     */
    public static function centralConnectionName(): string
    {
        if (AeroMode::isSaas() && config('database.connections.central') !== null) {
            return 'central';
        }

        return (string) config('database.default');
    }

    public function getConnectionName(): ?string
    {
        return static::centralConnectionName();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(fn (self $m) => $m->setConnection(static::centralConnectionName()));
        static::saving(fn (self $m) => $m->setConnection(static::centralConnectionName()));
    }

    /**
     * Default audit label — returns the model key as a string.
     * Subclasses may override to return a human-readable label (e.g. name, email).
     * Required by AuditService::log() which calls $subject->getAuditLabel().
     */
    public function getAuditLabel(): ?string
    {
        return (string) $this->getKey();
    }
}
